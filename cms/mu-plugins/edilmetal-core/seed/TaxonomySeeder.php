<?php
/**
 * Popola marche, modelli, carrozzerie e alimentazioni in modo idempotente.
 *
 * La chiave univoca e lo slug del termine: al secondo lancio i termini vengono
 * ritrovati e (se serve) aggiornati nel nome, mai duplicati.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Seed;

use Edilmetal\Core\Seed\Data\Catalog;

defined( 'ABSPATH' ) || exit;

/**
 * Garantisce l'esistenza dei termini tassonomici richiesti dalle realizzazioni.
 */
final class TaxonomySeeder {

	/**
	 * Crea o aggiorna tutti i termini definiti nel catalogo.
	 *
	 * @return int Numero di termini creati (esclusi quelli gia presenti).
	 */
	public function seed(): int {
		$created = 0;

		foreach ( Catalog::taxonomies() as $taxonomy => $terms ) {
			foreach ( $terms as $term ) {
				if ( $this->ensure_term( $taxonomy, (string) $term['slug'], (string) $term['name'] ) ) {
					++$created;
				}
			}
		}

		return $created;
	}

	/**
	 * Crea il termine se assente, altrimenti ne allinea il nome.
	 *
	 * @param string $taxonomy Tassonomia di destinazione.
	 * @param string $slug     Slug univoco del termine.
	 * @param string $name     Nome visualizzato.
	 * @return bool True se il termine e stato creato ora.
	 */
	private function ensure_term( string $taxonomy, string $slug, string $name ): bool {
		$existing = get_term_by( 'slug', $slug, $taxonomy );

		if ( $existing instanceof \WP_Term ) {
			if ( $existing->name !== $name ) {
				wp_update_term( $existing->term_id, $taxonomy, array( 'name' => $name ) );
			}

			return false;
		}

		wp_insert_term(
			$name,
			$taxonomy,
			array( 'slug' => $slug )
		);

		return true;
	}
}
