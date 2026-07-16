<?php
/**
 * Repository: accesso in lettura alle pagine editoriali.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Rest\Repositories;

use Edilmetal\Core\I18n\Language;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Recupera le pagine editoriali per chiave (slug canonico) e lingua.
 */
final class PageRepository {

	/**
	 * Trova una pagina pubblicata dalla sua chiave, nella lingua richiesta.
	 *
	 * @param string $key  Slug canonico della pagina.
	 * @param string $lang Codice lingua normalizzato.
	 */
	public function find_by_key( string $key, string $lang ): ?WP_Post {
		$page = get_page_by_path( $key, OBJECT, 'page' );

		if ( ! $page instanceof WP_Post ) {
			return null;
		}

		$translated_id = Language::translated_post_id( $page->ID, $lang );
		$resolved      = get_post( $translated_id );

		if ( ! $resolved instanceof WP_Post || 'publish' !== $resolved->post_status ) {
			return null;
		}

		return $resolved;
	}
}
