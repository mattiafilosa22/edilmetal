<?php
/**
 * Contratto comune a tutti i moduli del plugin.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Un modulo incapsula una responsabilita del plugin e registra i propri hook.
 */
interface Module {

	/**
	 * Registra hook, filtri e rotte del modulo.
	 */
	public function register(): void;
}
