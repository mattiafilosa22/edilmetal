<?php
/**
 * Routine di installazione idempotente.
 *
 * I plugin must-use non ricevono gli hook di attivazione/disattivazione: la
 * sincronizzazione (ruoli, rewrite) avviene quindi al variare della versione.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core;

use Edilmetal\Core\Security\Roles;

defined( 'ABSPATH' ) || exit;

/**
 * Applica le modifiche persistenti richieste dal plugin (ruoli custom, ecc.).
 */
final class Installer {

	/**
	 * Nome dell'opzione che traccia la versione installata.
	 *
	 * @var string
	 */
	private const VERSION_OPTION = 'edilmetal_core_version';

	/**
	 * Esegue l'installazione solo quando la versione salvata differisce.
	 */
	public static function maybe_install(): void {
		$installed = get_option( self::VERSION_OPTION );

		if ( EDILMETAL_CORE_VERSION === $installed ) {
			return;
		}

		self::install();

		update_option( self::VERSION_OPTION, EDILMETAL_CORE_VERSION );
	}

	/**
	 * Applica le modifiche persistenti.
	 */
	public static function install(): void {
		Roles::register();
		flush_rewrite_rules();
	}

	/**
	 * Rimuove le modifiche persistenti (per uso manuale/CLI).
	 */
	public static function uninstall(): void {
		Roles::unregister();
		delete_option( self::VERSION_OPTION );
		flush_rewrite_rules();
	}
}
