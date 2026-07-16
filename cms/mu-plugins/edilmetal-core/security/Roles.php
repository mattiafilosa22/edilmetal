<?php
/**
 * Ruolo custom "Redattore Edilmetal" (clone di Editor senza capacita pericolose).
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Registra e rimuove in modo idempotente il ruolo redazionale del cliente.
 */
final class Roles {

	/**
	 * Slug del ruolo.
	 *
	 * @var string
	 */
	public const ROLE = 'redattore_edilmetal';

	/**
	 * Capacita rimosse rispetto al ruolo Editor per impedire manutenzioni
	 * rischiose (aggiornamenti, installazioni, editing file).
	 *
	 * @var string[]
	 */
	private const DENIED_CAPS = array(
		'update_core',
		'update_plugins',
		'update_themes',
		'install_plugins',
		'install_themes',
		'activate_plugins',
		'delete_plugins',
		'edit_plugins',
		'edit_themes',
		'edit_files',
	);

	/**
	 * Crea il ruolo clonando le capacita di Editor meno quelle vietate.
	 */
	public static function register(): void {
		$editor = get_role( 'editor' );

		if ( null === $editor ) {
			return;
		}

		$caps = $editor->capabilities;

		foreach ( self::DENIED_CAPS as $cap ) {
			unset( $caps[ $cap ] );
		}

		// Idempotente: rimuove un'eventuale versione precedente prima di ricreare.
		remove_role( self::ROLE );
		add_role(
			self::ROLE,
			__( 'Redattore Edilmetal', 'edilmetal-core' ),
			$caps
		);
	}

	/**
	 * Rimuove il ruolo custom.
	 */
	public static function unregister(): void {
		if ( null !== get_role( self::ROLE ) ) {
			remove_role( self::ROLE );
		}
	}
}
