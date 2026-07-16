<?php
/**
 * Chiave e utilita per marcare e ritrovare i contenuti generati dal seeder.
 *
 * Ogni post/allegato seedato porta una meta "impronta" univoca: cosi il
 * comando puo aggiornare (invece di duplicare) al secondo lancio e ripulire
 * in modo mirato con --fresh, senza toccare i contenuti reali dell'editore.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Seed\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Traccia i contenuti di demo tramite una meta chiave riservata.
 */
final class SeedMeta {

	/**
	 * Meta key che identifica un contenuto generato dal seeder.
	 *
	 * @var string
	 */
	public const META_KEY = '_edilmetal_seed_ref';

	/**
	 * Restituisce l'ID del contenuto seedato con l'impronta indicata, se esiste.
	 *
	 * @param string $ref Impronta univoca (es. "progetto:parmalat-pensilina-collecchio:it").
	 * @return int|null ID del post/allegato oppure null.
	 */
	public static function find( string $ref ): ?int {
		$found = get_posts(
			array(
				'post_type'        => 'any',
				'post_status'      => 'any',
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => true,
				'meta_key'         => self::META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- lookup su dataset di demo ridotto, solo in CLI.
				'meta_value'       => $ref, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- lookup su dataset di demo ridotto, solo in CLI.
			)
		);

		if ( array() === $found ) {
			return null;
		}

		return (int) $found[0];
	}

	/**
	 * Marca un contenuto come generato dal seeder con l'impronta indicata.
	 *
	 * @param int    $post_id ID del post/allegato.
	 * @param string $ref     Impronta univoca.
	 */
	public static function mark( int $post_id, string $ref ): void {
		update_post_meta( $post_id, self::META_KEY, $ref );
	}

	/**
	 * Restituisce tutti gli ID dei contenuti seedati di un dato post type.
	 *
	 * @param string $post_type Tipo di post da filtrare.
	 * @return int[]
	 */
	public static function all_of_type( string $post_type ): array {
		$found = get_posts(
			array(
				'post_type'        => $post_type,
				'post_status'      => 'any',
				'posts_per_page'   => -1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- pulizia dei soli contenuti di demo, solo in CLI.
				'meta_key'         => self::META_KEY,
			)
		);

		return array_map( 'intval', $found );
	}
}
