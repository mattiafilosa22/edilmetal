<?php
/**
 * URL base del frontend pubblico Next.js, condiviso dai componenti di branding.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Branding;

defined( 'ABSPATH' ) || exit;

/**
 * Risolve l'URL base del frontend pubblico, con fallback sicuro.
 */
final class FrontendUrl {

	/**
	 * Restituisce l'URL base del frontend pubblico (senza slash finale).
	 *
	 * Non ritorna mai una stringa vuota ne genera un fatal error se la
	 * costante non e definita in un ambiente non ancora configurato.
	 */
	public static function base(): string {
		if ( defined( 'EDILMETAL_FRONTEND_URL' ) && '' !== EDILMETAL_FRONTEND_URL ) {
			return untrailingslashit( EDILMETAL_FRONTEND_URL );
		}

		return untrailingslashit( home_url( '/' ) );
	}
}
