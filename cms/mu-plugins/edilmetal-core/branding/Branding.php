<?php
/**
 * Modulo branding: sostituisce logo/link WordPress generici con quelli
 * del sito Edilmetal (barra admin, login).
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Branding;

use Edilmetal\Core\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Compone i sotto-componenti responsabili del branding del back-office:
 * il rimando al frontend pubblico (barra admin, login) e lo stile della
 * schermata di login. Il back-office WP e solo API/back-office headless,
 * quindi i link "Vedi il sito" devono puntare al frontend Next.js reale.
 */
final class Branding implements Module {

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		( new FrontendLink() )->register();
		( new LoginScreen() )->register();
	}
}
