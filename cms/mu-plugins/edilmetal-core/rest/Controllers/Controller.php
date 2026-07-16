<?php
/**
 * Contratto e helper comuni ai controller REST.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Rest\Controllers;

use Edilmetal\Core\I18n\Language;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Base dei controller: registrazione rotte e utilita di richiesta.
 */
abstract class Controller {

	/**
	 * Registra le rotte del controller sotto il namespace del plugin.
	 */
	abstract public function register_routes(): void;

	/**
	 * Namespace REST del plugin.
	 */
	final protected function namespace(): string {
		return EDILMETAL_CORE_REST_NAMESPACE;
	}

	/**
	 * Estrae e normalizza il parametro lingua dalla richiesta.
	 *
	 * @param WP_REST_Request $request Richiesta corrente.
	 */
	final protected function lang( WP_REST_Request $request ): string {
		return Language::resolve( $request->get_param( 'lang' ) );
	}

	/**
	 * Permission callback pubblico per le GET su dati non sensibili.
	 */
	final public function public_access(): bool {
		return true;
	}
}
