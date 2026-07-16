<?php
/**
 * Lettura tipizzata delle meta, disaccoppiata dal plugin Meta Box.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Rest\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Espone accessi coerenti alle post meta (stringa, numero, booleano, liste).
 */
final class MetaReader {

	/**
	 * ID del post da cui leggere.
	 *
	 * @var int
	 */
	private int $post_id;

	/**
	 * Vincola il lettore a un singolo post.
	 *
	 * @param int $post_id ID del post.
	 */
	public function __construct( int $post_id ) {
		$this->post_id = $post_id;
	}

	/**
	 * Valore stringa (trim).
	 *
	 * @param string $key Meta key completa.
	 */
	public function string( string $key ): string {
		$value = get_post_meta( $this->post_id, $key, true );

		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}

	/**
	 * Valore intero, oppure null se il campo e vuoto.
	 *
	 * @param string $key Meta key completa.
	 */
	public function int_or_null( string $key ): ?int {
		$value = get_post_meta( $this->post_id, $key, true );

		if ( '' === $value || null === $value || false === $value ) {
			return null;
		}

		return (int) $value;
	}

	/**
	 * Valore float, oppure null se il campo e vuoto.
	 *
	 * @param string $key Meta key completa.
	 */
	public function float_or_null( string $key ): ?float {
		$value = get_post_meta( $this->post_id, $key, true );

		if ( '' === $value || null === $value || false === $value ) {
			return null;
		}

		return (float) $value;
	}

	/**
	 * Valore booleano (checkbox Meta Box: 1/0).
	 *
	 * @param string $key Meta key completa.
	 */
	public function bool( string $key ): bool {
		return '1' === (string) get_post_meta( $this->post_id, $key, true );
	}

	/**
	 * Lista di stringhe da un campo clonabile o da testo multi-riga.
	 *
	 * @param string $key Meta key completa.
	 * @return string[]
	 */
	public function string_list( string $key ): array {
		$value = get_post_meta( $this->post_id, $key, true );

		if ( is_array( $value ) ) {
			$items = $value;
		} elseif ( is_string( $value ) && '' !== $value ) {
			$split = preg_split( '/\r\n|\r|\n/', $value );
			$items = is_array( $split ) ? $split : array();
		} else {
			$items = array();
		}

		$items = array_map(
			static fn ( $item ): string => trim( (string) $item ),
			$items
		);

		return array_values( array_filter( $items, static fn ( string $item ): bool => '' !== $item ) );
	}

	/**
	 * Lista di ID allegato (campo galleria / image_advanced).
	 *
	 * @param string $key Meta key completa.
	 * @return int[]
	 */
	public function attachment_ids( string $key ): array {
		$values = get_post_meta( $this->post_id, $key, false );

		if ( ! is_array( $values ) ) {
			return array();
		}

		// image_advanced puo salvare piu righe oppure una singola riga-array.
		$flat = array();
		foreach ( $values as $value ) {
			if ( is_array( $value ) ) {
				$flat = array_merge( $flat, $value );
			} else {
				$flat[] = $value;
			}
		}

		$ids = array_map( 'absint', $flat );

		return array_values( array_filter( $ids, static fn ( int $id ): bool => $id > 0 ) );
	}
}
