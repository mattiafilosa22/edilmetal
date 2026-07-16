<?php
/**
 * Registrazione dei Custom Post Type del plugin.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\PostTypes;

use Edilmetal\Core\Module;
use Edilmetal\Core\Support\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Registra i CPT "progetto" (realizzazioni) e "lead" (contatti privati).
 */
final class PostTypes implements Module {

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_post_types' ) );
		( new Taxonomies() )->register();
	}

	/**
	 * Registra tutti i CPT.
	 */
	public function register_post_types(): void {
		$this->register_progetto();
		$this->register_lead();
	}

	/**
	 * CPT "progetto": vetrina editoriale delle realizzazioni (case study di
	 * strutture in acciaio). Pubblico con archivio /realizzazioni, ma senza REST
	 * core: i dati passano dalle rotte custom edilmetal/v1.
	 */
	private function register_progetto(): void {
		$labels = array(
			'name'               => _x( 'Realizzazioni', 'post type general name', 'edilmetal-core' ),
			'singular_name'      => _x( 'Realizzazione', 'post type singular name', 'edilmetal-core' ),
			'menu_name'          => _x( 'Realizzazioni', 'admin menu', 'edilmetal-core' ),
			'add_new'            => __( 'Aggiungi realizzazione', 'edilmetal-core' ),
			'add_new_item'       => __( 'Aggiungi nuova realizzazione', 'edilmetal-core' ),
			'edit_item'          => __( 'Modifica realizzazione', 'edilmetal-core' ),
			'new_item'           => __( 'Nuova realizzazione', 'edilmetal-core' ),
			'view_item'          => __( 'Vedi realizzazione', 'edilmetal-core' ),
			'search_items'       => __( 'Cerca realizzazioni', 'edilmetal-core' ),
			'not_found'          => __( 'Nessuna realizzazione trovata', 'edilmetal-core' ),
			'not_found_in_trash' => __( 'Nessuna realizzazione nel cestino', 'edilmetal-core' ),
			'all_items'          => __( 'Tutte le realizzazioni', 'edilmetal-core' ),
		);

		register_post_type(
			Schema::CPT_PROGETTO,
			array(
				'labels'              => $labels,
				'public'              => true,
				'publicly_queryable'  => true,
				'exclude_from_search' => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => false,
				// Endpoint core disattivati: i dati passano dalle rotte custom edilmetal/v1.
				'show_in_rest'        => false,
				'menu_icon'           => 'dashicons-building',
				'menu_position'       => 20,
				'hierarchical'        => false,
				'has_archive'         => true,
				'supports'            => array( 'title', 'editor', 'thumbnail' ),
				'rewrite'             => array(
					'slug'       => 'realizzazioni',
					'with_front' => false,
				),
			)
		);
	}

	/**
	 * CPT "lead": privato, raccoglie i contatti dei form. Mai pubblico.
	 */
	private function register_lead(): void {
		$labels = array(
			'name'          => _x( 'Lead', 'post type general name', 'edilmetal-core' ),
			'singular_name' => _x( 'Lead', 'post type singular name', 'edilmetal-core' ),
			'menu_name'     => _x( 'Lead', 'admin menu', 'edilmetal-core' ),
			'edit_item'     => __( 'Dettaglio lead', 'edilmetal-core' ),
			'view_item'     => __( 'Vedi lead', 'edilmetal-core' ),
			'search_items'  => __( 'Cerca lead', 'edilmetal-core' ),
			'not_found'     => __( 'Nessun lead', 'edilmetal-core' ),
			'all_items'     => __( 'Tutti i lead', 'edilmetal-core' ),
		);

		register_post_type(
			Schema::CPT_LEAD,
			array(
				'labels'              => $labels,
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => false,
				'show_in_rest'        => false,
				'menu_icon'           => 'dashicons-email',
				'menu_position'       => 21,
				'hierarchical'        => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'capability_type'     => 'post',
				'supports'            => array( 'title', 'custom-fields' ),
			)
		);
	}
}
