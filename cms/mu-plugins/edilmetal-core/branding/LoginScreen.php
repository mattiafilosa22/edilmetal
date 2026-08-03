<?php
/**
 * Applica lo stile Edilmetal alla schermata di login WordPress.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Branding;

defined( 'ABSPATH' ) || exit;

/**
 * Sostituisce logo e stile di default della pagina wp-login.php con la
 * palette del sito, cosi da non mostrare il branding WordPress generico
 * ai redattori del cliente.
 */
final class LoginScreen {

	/**
	 * Aggancia l'enqueue del foglio di stile dedicato al login.
	 */
	public function register(): void {
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Carica il CSS statico del login (nessuna dipendenza, versionato con
	 * la versione del plugin per invalidare correttamente la cache).
	 */
	public function enqueue_styles(): void {
		wp_enqueue_style(
			'edilmetal-login',
			plugins_url( 'assets/login.css', EDILMETAL_CORE_FILE ),
			array(),
			EDILMETAL_CORE_VERSION
		);
	}
}
