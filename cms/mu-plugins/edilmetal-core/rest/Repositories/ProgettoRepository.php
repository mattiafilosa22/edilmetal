<?php
/**
 * Repository: accesso in lettura al CPT "progetto".
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Rest\Repositories;

use Edilmetal\Core\I18n\Language;
use Edilmetal\Core\Support\Schema;
use WP_Post;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Incapsula le query WP per le realizzazioni, isolando lo storage dall'API.
 */
final class ProgettoRepository {

	/**
	 * Restituisce le realizzazioni pubblicate, filtrate per i criteri indicati.
	 *
	 * @param string               $lang    Codice lingua normalizzato.
	 * @param array<string,mixed>  $filters Filtri (categoria|settore|anno|inEvidenza).
	 * @return WP_Post[]
	 */
	public function find_all( string $lang, array $filters = array() ): array {
		$args = array(
			'post_type'      => Schema::CPT_PROGETTO,
			'post_status'    => 'publish',
			// Vetrina editoriale volutamente limitata: l'export statico consuma
			// l'intera lista in un'unica chiamata.
			'posts_per_page' => 200, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- catalogo limitato per il front-end statico.
			'orderby'        => array(
				'meta_value_num' => 'DESC',
				'date'           => 'DESC',
			),
			'meta_key'       => Schema::meta( 'anno' ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- ordinamento per anno su dataset ridotto.
			'no_found_rows'  => true,
		);

		$tax_query = $this->tax_query( $filters );
		if ( array() !== $tax_query ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- filtro necessario su dataset ridotto.
		}

		$meta_query = $this->meta_query( $filters );
		if ( array() !== $meta_query ) {
			$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- filtro necessario su dataset ridotto.
		}

		$args  = Language::constrain_query( $args, $lang );
		$query = new WP_Query( $args );

		return $query->posts;
	}

	/**
	 * Trova una realizzazione pubblicata dal suo slug.
	 *
	 * @param string $slug Slug del post.
	 * @param string $lang Codice lingua normalizzato.
	 */
	public function find_by_slug( string $slug, string $lang ): ?WP_Post {
		$args = array(
			'post_type'      => Schema::CPT_PROGETTO,
			'post_status'    => 'publish',
			'name'           => $slug,
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		);

		$args  = Language::constrain_query( $args, $lang );
		$query = new WP_Query( $args );

		return array() === $query->posts ? null : $query->posts[0];
	}

	/**
	 * Costruisce il tax_query per i filtri categoria/settore.
	 *
	 * @param array<string,mixed> $filters Filtri richiesti.
	 * @return array<int,array<string,mixed>>
	 */
	private function tax_query( array $filters ): array {
		$clauses = array();

		if ( isset( $filters['categoria'] ) && '' !== (string) $filters['categoria'] ) {
			$clauses[] = array(
				'taxonomy' => Schema::TAX_CATEGORIA,
				'field'    => 'slug',
				'terms'    => array( (string) $filters['categoria'] ),
			);
		}

		if ( isset( $filters['settore'] ) && '' !== (string) $filters['settore'] ) {
			$clauses[] = array(
				'taxonomy' => Schema::TAX_SETTORE,
				'field'    => 'slug',
				'terms'    => array( (string) $filters['settore'] ),
			);
		}

		return $clauses;
	}

	/**
	 * Costruisce il meta_query per i filtri anno/inEvidenza.
	 *
	 * @param array<string,mixed> $filters Filtri richiesti.
	 * @return array<int,array<string,mixed>>
	 */
	private function meta_query( array $filters ): array {
		$clauses = array();

		if ( isset( $filters['anno'] ) && (int) $filters['anno'] > 0 ) {
			$clauses[] = array(
				'key'     => Schema::meta( 'anno' ),
				'value'   => (int) $filters['anno'],
				'compare' => '=',
				'type'    => 'NUMERIC',
			);
		}

		if ( ! empty( $filters['inEvidenza'] ) ) {
			$clauses[] = array(
				'key'     => Schema::meta( 'in_evidenza' ),
				'value'   => '1',
				'compare' => '=',
			);
		}

		return $clauses;
	}
}
