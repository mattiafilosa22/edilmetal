<?php
/**
 * Trasforma gli allegati in DTO immagine con tutte le image-size per srcset.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Rest\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Produce strutture immagine stabili (id, alt, sizes) per il front-end.
 */
final class ImageTransformer {

	/**
	 * Image size esposte nel DTO.
	 *
	 * @var string[]
	 */
	private const SIZES = array( 'thumbnail', 'medium', 'large', 'full' );

	/**
	 * Trasforma un singolo allegato in DTO immagine.
	 *
	 * @param int $attachment_id ID dell'allegato.
	 * @return array<string,mixed>|null Null se l'allegato non e un'immagine valida.
	 */
	public function transform( int $attachment_id ): ?array {
		if ( $attachment_id <= 0 || 'attachment' !== get_post_type( $attachment_id ) ) {
			return null;
		}

		$sizes = array();
		foreach ( self::SIZES as $size ) {
			$src = wp_get_attachment_image_src( $attachment_id, $size );

			if ( false === $src ) {
				continue;
			}

			$sizes[ $size ] = array(
				'url'    => (string) $src[0],
				'width'  => (int) $src[1],
				'height' => (int) $src[2],
			);
		}

		if ( array() === $sizes ) {
			return null;
		}

		$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

		return array(
			'id'    => $attachment_id,
			'alt'   => is_string( $alt ) ? $alt : '',
			'sizes' => $sizes,
		);
	}

	/**
	 * Trasforma una lista di allegati, scartando quelli non validi.
	 *
	 * @param int[] $attachment_ids ID degli allegati.
	 * @return array<int,array<string,mixed>>
	 */
	public function transform_many( array $attachment_ids ): array {
		$images = array();

		foreach ( $attachment_ids as $id ) {
			$dto = $this->transform( (int) $id );

			if ( null !== $dto ) {
				$images[] = $dto;
			}
		}

		return $images;
	}

	/**
	 * DTO immagine nella forma attesa dal front-end (Image):
	 * { src, srcset, width, height, alt }.
	 *
	 * @param int $attachment_id ID dell'allegato.
	 * @return array<string,mixed>|null Null se l'allegato non e valido.
	 */
	public function to_front( int $attachment_id ): ?array {
		$dto = $this->transform( $attachment_id );

		return null === $dto ? null : $this->front_shape( $dto );
	}

	/**
	 * Lista di immagini front-end (Image[]), scartando quelle non valide.
	 *
	 * @param int[] $attachment_ids ID degli allegati.
	 * @return array<int,array<string,mixed>>
	 */
	public function to_front_many( array $attachment_ids ): array {
		$images = array();

		foreach ( $attachment_ids as $id ) {
			$dto = $this->to_front( (int) $id );

			if ( null !== $dto ) {
				$images[] = $dto;
			}
		}

		return $images;
	}

	/**
	 * Converte il DTO interno { id, alt, sizes } nella forma Image.
	 *
	 * Sceglie come sorgente principale la size piu adatta (full > large >
	 * medium > thumbnail) e costruisce lo srcset da tutte le size disponibili.
	 *
	 * "full" e la base preferita (non il file grezzo: WordPress la ridimensiona
	 * gia a `big_image_size_threshold`, 2560px di default) perche width/height
	 * diventano le dimensioni intrinseche passate a next/image: usare "large"
	 * (1024px) le limitava anche per l'hero a piena larghezza, che su schermi
	 * grandi veniva quindi ingrandito via CSS e appariva sgranato.
	 *
	 * @param array<string,mixed> $image DTO interno prodotto da transform().
	 * @return array<string,mixed>
	 */
	private function front_shape( array $image ): array {
		$sizes   = is_array( $image['sizes'] ) ? $image['sizes'] : array();
		$primary = $sizes['full'] ?? $sizes['large'] ?? $sizes['medium'] ?? $sizes['thumbnail'] ?? null;

		if ( null === $primary ) {
			$primary = array(
				'url'    => '',
				'width'  => 1,
				'height' => 1,
			);
		}

		return array(
			'src'    => (string) $primary['url'],
			'srcset' => $this->build_srcset( $sizes ),
			'width'  => (int) $primary['width'],
			'height' => (int) $primary['height'],
			'alt'    => (string) $image['alt'],
		);
	}

	/**
	 * Costruisce l'attributo srcset da tutte le size (dedotto per larghezza).
	 *
	 * @param array<string,array<string,mixed>> $sizes Mappa size => { url, width, height }.
	 */
	private function build_srcset( array $sizes ): string {
		$entries = array();

		foreach ( $sizes as $size ) {
			$width = (int) ( $size['width'] ?? 0 );
			$url   = (string) ( $size['url'] ?? '' );

			if ( $width <= 0 || '' === $url ) {
				continue;
			}

			$entries[ $width ] = $url . ' ' . $width . 'w';
		}

		ksort( $entries );

		return implode( ', ', $entries );
	}
}
