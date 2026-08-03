<?php
/**
 * Rimappa i link "Visualizza"/"Anteprima" di pagine e realizzazioni verso
 * l'URL reale sul frontend Next.js, invece del sottodominio cms.* headless.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Branding;

use Edilmetal\Core\Support\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Sostituisce l'URL di pagine e realizzazioni con il percorso corrispondente
 * sul dominio pubblico, cosi "Visualizza pagina"/"Anteprima" in wp-admin
 * portano l'editore sulla pagina giusta di edilmetal.it invece che su un
 * back-office che non ha alcun tema pubblico da mostrare.
 */
final class FrontendPostLink {

	/**
	 * Slug pagina del CPT realizzazioni, coerente con il rewrite in PostTypes.
	 *
	 * @var string
	 */
	private const PROGETTO_BASE = 'realizzazioni';

	/**
	 * Aggancia i filtri sui link di pagine, realizzazioni e anteprime.
	 */
	public function register(): void {
		add_filter( 'page_link', array( $this, 'page_link' ), 10, 2 );
		add_filter( 'post_type_link', array( $this, 'progetto_link' ), 10, 2 );
		add_filter( 'preview_post_link', array( $this, 'preview_link' ), 10, 2 );
	}

	/**
	 * Rimappa il link di una pagina editoriale (home, servizi, azienda, ...).
	 *
	 * @param string $link    Link originale generato da WordPress.
	 * @param int    $post_id ID della pagina.
	 */
	public function page_link( string $link, int $post_id ): string {
		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post || 'page' !== $post->post_type ) {
			return $link;
		}

		$path = 'home' === $post->post_name ? '' : $post->post_name;

		return $this->frontend_link( $post_id, $path );
	}

	/**
	 * Rimappa il link di una realizzazione (CPT progetto).
	 *
	 * @param string   $link Link originale generato da WordPress.
	 * @param \WP_Post $post Post corrente.
	 */
	public function progetto_link( string $link, \WP_Post $post ): string {
		if ( Schema::CPT_PROGETTO !== $post->post_type ) {
			return $link;
		}

		return $this->frontend_link( $post->ID, self::PROGETTO_BASE . '/' . $post->post_name );
	}

	/**
	 * Rimappa il link di anteprima (bozze non ancora pubblicate).
	 *
	 * @param string   $link Link di anteprima originale.
	 * @param \WP_Post $post Post corrente.
	 */
	public function preview_link( string $link, \WP_Post $post ): string {
		if ( 'page' === $post->post_type ) {
			return $this->page_link( $link, $post->ID );
		}

		if ( Schema::CPT_PROGETTO === $post->post_type ) {
			return $this->progetto_link( $link, $post );
		}

		return $link;
	}

	/**
	 * Compone l'URL sul frontend pubblico per lingua e percorso indicati.
	 *
	 * @param int    $post_id ID del post, usato per risolvere la lingua Polylang.
	 * @param string $path    Percorso relativo (senza lingua), senza slash iniziale.
	 */
	private function frontend_link( int $post_id, string $path ): string {
		$lang = $this->language_of( $post_id );
		$url  = FrontendUrl::base() . '/' . $lang;

		if ( '' !== $path ) {
			$url .= '/' . $path;
		}

		return trailingslashit( $url );
	}

	/**
	 * Determina la lingua del post (Polylang), con fallback "it".
	 *
	 * @param int $post_id ID del post.
	 */
	private function language_of( int $post_id ): string {
		if ( function_exists( 'pll_get_post_language' ) ) {
			$lang = pll_get_post_language( $post_id );

			if ( is_string( $lang ) && '' !== $lang ) {
				return $lang;
			}
		}

		return 'it';
	}
}
