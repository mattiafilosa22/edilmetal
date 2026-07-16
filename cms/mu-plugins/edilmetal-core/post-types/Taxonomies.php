<?php
/**
 * Registrazione delle tassonomie associate al CPT "progetto".
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\PostTypes;

use Edilmetal\Core\Support\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Registra categoria opera (8 famiglie) e settore (industriale/commerciale/terziario).
 */
final class Taxonomies {

	/**
	 * Aggancia la registrazione all'hook init.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_taxonomies' ) );
	}

	/**
	 * Registra tutte le tassonomie.
	 */
	public function register_taxonomies(): void {
		$this->register_taxonomy( Schema::TAX_CATEGORIA, __( 'Categoria opera', 'edilmetal-core' ), __( 'Categorie opera', 'edilmetal-core' ), false );
		$this->register_taxonomy( Schema::TAX_SETTORE, __( 'Settore', 'edilmetal-core' ), __( 'Settori', 'edilmetal-core' ), false );
	}

	/**
	 * Registra una singola tassonomia sul CPT progetto.
	 *
	 * @param string $taxonomy     Slug della tassonomia.
	 * @param string $singular     Etichetta singolare.
	 * @param string $plural       Etichetta plurale.
	 * @param bool   $hierarchical Se la tassonomia e gerarchica.
	 */
	private function register_taxonomy( string $taxonomy, string $singular, string $plural, bool $hierarchical ): void {
		$labels = array(
			'name'          => $plural,
			'singular_name' => $singular,
			'search_items'  => sprintf(
				/* translators: %s: nome plurale della tassonomia. */
				__( 'Cerca %s', 'edilmetal-core' ),
				$plural
			),
			'all_items'     => sprintf(
				/* translators: %s: nome plurale della tassonomia. */
				__( 'Tutte le %s', 'edilmetal-core' ),
				$plural
			),
			'edit_item'     => sprintf(
				/* translators: %s: nome singolare della tassonomia. */
				__( 'Modifica %s', 'edilmetal-core' ),
				$singular
			),
			'add_new_item'  => sprintf(
				/* translators: %s: nome singolare della tassonomia. */
				__( 'Aggiungi %s', 'edilmetal-core' ),
				$singular
			),
		);

		register_taxonomy(
			$taxonomy,
			array( Schema::CPT_PROGETTO ),
			array(
				'labels'             => $labels,
				'hierarchical'       => $hierarchical,
				'public'             => true,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_admin_column'  => true,
				'show_in_nav_menus'  => false,
				// Esposta solo tramite le rotte custom edilmetal/v1.
				'show_in_rest'       => false,
				'rewrite'            => false,
			)
		);
	}
}
