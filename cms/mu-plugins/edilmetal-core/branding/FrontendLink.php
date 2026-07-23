<?php
/**
 * Rimanda al frontend pubblico Next.js dalla barra admin e dal login.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Branding;

defined( 'ABSPATH' ) || exit;

/**
 * Sostituisce i link "Vedi il sito" (barra admin e login) con l'URL del
 * frontend pubblico, invece del sottodominio cms.* che ospita solo il
 * back-office headless (nessun contenuto pubblico da mostrare li).
 */
final class FrontendLink {

	/**
	 * Aggancia i filtri/azioni che rimandano al frontend pubblico.
	 */
	public function register(): void {
		add_action( 'admin_bar_menu', array( $this, 'point_site_name_to_frontend' ), 999 );
		add_filter( 'login_headerurl', array( $this, 'frontend_url' ) );
		add_filter( 'login_headertext', array( $this, 'site_name' ) );
	}

	/**
	 * Rimappa l'href dei nodi "site-name" e "view-site" della barra admin
	 * verso il frontend pubblico, preservando le altre proprieta del nodo.
	 *
	 * @param \WP_Admin_Bar $admin_bar Istanza della barra admin corrente.
	 */
	public function point_site_name_to_frontend( \WP_Admin_Bar $admin_bar ): void {
		foreach ( array( 'site-name', 'view-site' ) as $node_id ) {
			$node = $admin_bar->get_node( $node_id );

			if ( null === $node ) {
				continue;
			}

			$node_data         = (array) $node;
			$node_data['href'] = $this->frontend_url();

			$admin_bar->add_node( $node_data );
		}
	}

	/**
	 * Restituisce l'URL pubblico del frontend, con fallback sicuro.
	 *
	 * Non ritorna mai una stringa vuota ne genera un fatal error se la
	 * costante non e definita in un ambiente non ancora configurato.
	 */
	public function frontend_url(): string {
		if ( defined( 'EDILMETAL_FRONTEND_URL' ) && '' !== EDILMETAL_FRONTEND_URL ) {
			return EDILMETAL_FRONTEND_URL;
		}

		return home_url( '/' );
	}

	/**
	 * Restituisce il nome del sito, usato come testo del link di login.
	 */
	public function site_name(): string {
		return get_bloginfo( 'name' );
	}
}
