<?php
/**
 * Nasconde i menu di manutenzione al ruolo "Redattore Edilmetal".
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Rimuove Aggiornamenti, Plugin e Temi dalla dashboard per il redattore.
 */
final class AdminMenu {

	/**
	 * Aggancia la rimozione dei menu (priorita alta per agire dopo il core).
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'hide_maintenance_menus' ), 999 );
	}

	/**
	 * Nasconde i menu solo per gli utenti con esclusivamente il ruolo redattore.
	 */
	public function hide_maintenance_menus(): void {
		$user = wp_get_current_user();

		if ( ! $user instanceof \WP_User ) {
			return;
		}

		// Gli amministratori mantengono l'accesso completo.
		if ( in_array( 'administrator', (array) $user->roles, true ) ) {
			return;
		}

		if ( ! in_array( Roles::ROLE, (array) $user->roles, true ) ) {
			return;
		}

		remove_menu_page( 'plugins.php' );
		remove_menu_page( 'themes.php' );

		// La voce Aggiornamenti vive come sottomenu di "Bacheca".
		remove_submenu_page( 'index.php', 'update-core.php' );
		remove_submenu_page( 'themes.php', 'themes.php' );
	}
}
