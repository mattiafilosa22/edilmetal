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
				$description = (string) ( $term['description'] ?? '' );

				if ( $this->ensure_term( $taxonomy, (string) $term['slug'], (string) $term['name'], $description ) ) {
					++$created;
				}
			}
		}

		return $created;
	}

	/**
	 * Crea il termine se assente, altrimenti ne allinea il nome e la descrizione.
	 *
	 * @param string $taxonomy    Tassonomia di destinazione.
	 * @param string $slug        Slug univoco del termine.
	 * @param string $name        Nome visualizzato.
	 * @param string $description Descrizione breve del termine (usata come "dettaglio" nel frontend).
	 * @return bool True se il termine e stato creato ora.
	 */
	private function ensure_term( string $taxonomy, string $slug, string $name, string $description = '' ): bool {
		$existing = get_term_by( 'slug', $slug, $taxonomy );

		if ( $existing instanceof \WP_Term ) {
			$changes = array();

			if ( $existing->name !== $name ) {
				$changes['name'] = $name;
			}

			if ( '' !== $description && $existing->description !== $description ) {
				$changes['description'] = $description;
			}

			if ( array() !== $changes ) {
				wp_update_term( $existing->term_id, $taxonomy, $changes );
			}

			return false;
		}

		$args = array( 'slug' => $slug );

		if ( '' !== $description ) {
			$args['description'] = $description;
		}

		wp_insert_term( $name, $taxonomy, $args );

		return true;
	}
}
