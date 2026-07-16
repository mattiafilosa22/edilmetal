<?php
/**
 * Importa nella libreria media i segnaposto usati come gallerie di demo.
 *
 * I segnaposto editoriali (vista d'insieme, dettaglio, cantiere) vivono in
 * cms/seed/media e vengono importati in modo idempotente; il ProgettoSeeder li
 * riusa come galleria fino all'arrivo delle foto reali.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Seed;

use Edilmetal\Core\Rest\Support\ImageTransformer;
use Edilmetal\Core\Seed\Support\MediaLibrary;
use Edilmetal\Core\Seed\Support\Placeholders;
use Edilmetal\Core\Seed\Support\SeedMeta;

defined( 'ABSPATH' ) || exit;

/**
 * Crea gli allegati segnaposto e ne restituisce la libreria.
 */
final class MediaSeeder {

	/**
	 * Generatore/fornitore dei file segnaposto.
	 *
	 * @var Placeholders
	 */
	private Placeholders $placeholders;

	/**
	 * Trasformatore immagini, usato per validare il DTO risultante.
	 *
	 * @var ImageTransformer
	 */
	private ImageTransformer $images;

	/**
	 * Inietta le dipendenze del seeder media.
	 *
	 * @param Placeholders     $placeholders Fornitore file segnaposto.
	 * @param ImageTransformer $images       Trasformatore immagini.
	 */
	public function __construct( Placeholders $placeholders, ImageTransformer $images ) {
		$this->placeholders = $placeholders;
		$this->images       = $images;
	}

	/**
	 * Garantisce i segnaposto e restituisce la libreria media.
	 */
	public function seed(): MediaLibrary {
		$this->load_dependencies();

		return new MediaLibrary( $this->seed_placeholders() );
	}

	/**
	 * Rimuove gli allegati generati dal seeder (usato con --fresh).
	 *
	 * @return int Numero di allegati eliminati.
	 */
	public function purge(): int {
		$deleted = 0;

		foreach ( SeedMeta::all_of_type( 'attachment' ) as $attachment_id ) {
			if ( wp_delete_attachment( $attachment_id, true ) ) {
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * Garantisce i segnaposto editoriali e ne mappa gli ID.
	 *
	 * @return array<string,int>
	 */
	private function seed_placeholders(): array {
		$library = array();

		foreach ( $this->placeholders->ensure() as $key => $path ) {
			$title = $this->placeholder_title( $key );
			$id    = $this->ensure_attachment( 'media:' . $key, $title, $title, $path );

			if ( null !== $id ) {
				$library[ $key ] = $id;
			}
		}

		return $library;
	}

	/**
	 * Restituisce l'ID dell'allegato per l'impronta, creandolo se assente.
	 *
	 * @param string $ref   Impronta seeder univoca.
	 * @param string $title Titolo dell'allegato.
	 * @param string $alt   Testo alternativo.
	 * @param string $path  Percorso del file sorgente.
	 */
	private function ensure_attachment( string $ref, string $title, string $alt, string $path ): ?int {
		$existing = SeedMeta::find( $ref );

		if ( null !== $existing && 'attachment' === get_post_type( $existing ) ) {
			return $existing;
		}

		return $this->create_attachment( $ref, $title, $alt, $path );
	}

	/**
	 * Copia il file negli upload e crea l'allegato con i metadati immagine.
	 *
	 * @param string $ref   Impronta seeder.
	 * @param string $title Titolo dell'allegato.
	 * @param string $alt   Testo alternativo.
	 * @param string $path  Percorso del file sorgente.
	 */
	private function create_attachment( string $ref, string $title, string $alt, string $path ): ?int {
		$bytes = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- file locale del repository, non remoto.

		if ( false === $bytes ) {
			return null;
		}

		$upload = wp_upload_bits( basename( $path ), null, $bytes );

		if ( ! empty( $upload['error'] ) || empty( $upload['file'] ) ) {
			return null;
		}

		$file = (string) $upload['file'];

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => 'image/jpeg',
				'post_title'     => $title,
				'post_status'    => 'inherit',
				'guid'           => (string) $upload['url'],
			),
			$file,
			0,
			true
		);

		if ( is_wp_error( $attachment_id ) ) {
			return null;
		}

		$attachment_id = (int) $attachment_id;

		$metadata = wp_generate_attachment_metadata( $attachment_id, $file );
		wp_update_attachment_metadata( $attachment_id, $metadata );

		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
		SeedMeta::mark( $attachment_id, $ref );

		// Validazione difensiva: l'allegato deve produrre un DTO immagine valido.
		if ( null === $this->images->transform( $attachment_id ) ) {
			return null;
		}

		return $attachment_id;
	}

	/**
	 * Etichetta descrittiva (e alt) del segnaposto editoriale.
	 *
	 * @param string $key Chiave logica.
	 */
	private function placeholder_title( string $key ): string {
		$labels = array(
			'insieme'   => __( "Edilmetal — vista d'insieme (immagine dimostrativa)", 'edilmetal-core' ),
			'dettaglio' => __( 'Edilmetal — dettaglio costruttivo (immagine dimostrativa)', 'edilmetal-core' ),
			'cantiere'  => __( 'Edilmetal — montaggio in cantiere (immagine dimostrativa)', 'edilmetal-core' ),
		);

		return $labels[ $key ] ?? __( 'Edilmetal — immagine dimostrativa', 'edilmetal-core' );
	}

	/**
	 * Carica le funzioni di wp-admin necessarie a generare i metadati immagine.
	 */
	private function load_dependencies(): void {
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		if ( ! function_exists( 'wp_read_image_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}
	}
}
