<?php
/**
 * Impedisce che il CMS headless sia navigabile come sito pubblico.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Security;

use Edilmetal\Core\Branding\FrontendUrl;

defined( 'ABSPATH' ) || exit;

/**
 * Il sottodominio cms.* ospita solo API REST e back-office: nessun tema pubblico deve essere
 * raggiungibile navigando le sue pagine. Ogni richiesta front-end (pagine,
 * realizzazioni, archivi, ricerche, 404, ...) viene rimandata con un 301 alla
 * pagina corrispondente sul frontend pubblico Next.js, o alla sua home se non
 * esiste un equivalente diretto.
 */
final class FrontendOnlyBackoffice {

	/**
	 * Aggancia il redirect su ogni richiesta front-end.
	 */
	public function register(): void {
		add_action( 'template_redirect', array( $this, 'redirect_to_frontend' ) );
	}

	/**
	 * Rimanda al frontend pubblico invece di renderizzare un tema sul CMS.
	 */
	public function redirect_to_frontend(): void {
		if ( is_singular() ) {
			// I permalink di pagine e realizzazioni sono gia rimappati dai
			// filtri in Branding\FrontendPostLink: get_permalink() restituisce
			// direttamente l'URL corretto sul frontend pubblico.
			$target = get_permalink( get_queried_object_id() );

			if ( is_string( $target ) && '' !== $target ) {
				$this->redirect( $target );
			}
		}

		$this->redirect( FrontendUrl::base() . '/it/' );
	}

	/**
	 * Esegue il redirect permanente e termina la richiesta.
	 *
	 * @param string $target URL di destinazione (dominio pubblico, non locale).
	 */
	private function redirect( string $target ): void {
		wp_redirect( $target, 301 ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- destinazione fissa (dominio frontend pubblico), non input utente.
		exit;
	}
}
