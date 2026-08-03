<?php
/**
 * Modulo sicurezza: lockdown auto-update e restrizioni UI.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Security;

use Edilmetal\Core\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Disattiva gli aggiornamenti automatici e applica le restrizioni di menu.
 */
final class Security implements Module {

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_filter( 'automatic_updater_disabled', '__return_true' );
		add_filter( 'auto_update_plugin', '__return_false' );
		add_filter( 'auto_update_theme', '__return_false' );
		add_filter( 'auto_update_core', '__return_false' );
		add_filter( 'allow_dev_auto_core_updates', '__return_false' );
		add_filter( 'allow_minor_auto_core_updates', '__return_false' );
		add_filter( 'allow_major_auto_core_updates', '__return_false' );

		( new AdminMenu() )->register();
		( new FrontendOnlyBackoffice() )->register();
	}
}
