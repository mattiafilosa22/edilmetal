<?php
/**
 * Popola le pagine editoriali e la pagina delle impostazioni globali.
 *
 * Idempotente: le pagine sono individuate per slug/impronta e aggiornate al
 * posto di essere duplicate; le traduzioni EN sono collegate via Polylang.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Seed;

use Edilmetal\Core\Seed\Data\Catalog;
use Edilmetal\Core\Seed\Support\MediaLibrary;
use Edilmetal\Core\Seed\Support\MediaRef;
use Edilmetal\Core\Seed\Support\SeedMeta;

defined( 'ABSPATH' ) || exit;

/**
 * Crea/aggiorna pagine e impostazioni con i relativi blocchi tipizzati.
 */
final class PageSeeder {

	/**
	 * Servizio multilingua.
	 *
	 * @var LanguageSeeder
	 */
	private LanguageSeeder $language;

	/**
	 * Inietta il servizio multilingua.
	 *
	 * @param LanguageSeeder $language Servizio multilingua.
	 */
	public function __construct( LanguageSeeder $language ) {
		$this->language = $language;
	}

	/**
	 * Seeda impostazioni e pagine editoriali.
	 *
	 * @param MediaLibrary $media_library Libreria immagini seedata.
	 * @return int Numero di pagine (record base) processate.
	 */
	public function seed( MediaLibrary $media_library ): int {
		$count = 0;

		$settings = Catalog::settings();
		$this->seed_entry(
			(string) $settings['slug'],
			'settings:' . $settings['slug'],
			(string) $settings['title'],
			(string) $settings['title_en'],
			$settings['meta'],
			$settings['meta_en'],
			$media_library
		);
		++$count;

		foreach ( Catalog::pages() as $page ) {
			$this->seed_entry(
				(string) $page['key'],
				'page:' . $page['key'],
				(string) $page['title'],
				(string) $page['title_en'],
				$page['meta'],
				$page['meta_en'],
				$media_library
			);
			++$count;
		}

		return $count;
	}

	/**
	 * Seeda una pagina (IT + EN) e ne collega le traduzioni.
	 *
	 * @param string              $slug          Slug canonico della pagina.
	 * @param string              $ref_prefix    Prefisso dell'impronta seeder.
	 * @param string              $title_it      Titolo IT.
	 * @param string              $title_en      Titolo EN.
	 * @param array<string,mixed> $meta_it       Meta IT.
	 * @param array<string,mixed> $meta_en       Override meta EN.
	 * @param MediaLibrary        $media_library Libreria immagini.
	 */
	private function seed_entry( string $slug, string $ref_prefix, string $title_it, string $title_en, array $meta_it, array $meta_en, MediaLibrary $media_library ): void {
		$default = $this->language->default_language();

		$translations = array(
			$default => $this->upsert( $slug, $ref_prefix . ':' . $default, $title_it, $meta_it, $default, $media_library ),
		);

		if ( $this->language->is_active() ) {
			$english = array_merge( $meta_it, $meta_en );

			foreach ( $this->language->secondary_languages() as $lang ) {
				$translations[ $lang ] = $this->upsert( $slug, $ref_prefix . ':' . $lang, $title_en, $english, $lang, $media_library );
			}

			$this->language->link( $translations );
		}
	}

	/**
	 * Crea o aggiorna una variante linguistica di pagina.
	 *
	 * @param string              $slug          Slug canonico.
	 * @param string              $ref           Impronta seeder.
	 * @param string              $title         Titolo.
	 * @param array<string,mixed> $meta          Meta da scrivere.
	 * @param string              $lang          Slug lingua.
	 * @param MediaLibrary        $media_library Libreria immagini.
	 * @return int ID della pagina.
	 */
	private function upsert( string $slug, string $ref, string $title, array $meta, string $lang, MediaLibrary $media_library ): int {
		$post_id = $this->locate( $ref, $slug, $lang );

		$postarr = array(
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_title'  => $title,
			'post_name'   => $slug,
		);

		if ( null !== $post_id ) {
			$postarr['ID'] = $post_id;
			wp_update_post( $postarr );
		} else {
			$post_id = (int) wp_insert_post( $postarr, true );
		}

		if ( $post_id <= 0 ) {
			return 0;
		}

		$this->language->assign( $post_id, $lang );
		$this->normalize_slug( $post_id, $slug );
		$this->write_meta( $post_id, $meta, $media_library );
		SeedMeta::mark( $post_id, $ref );

		return $post_id;
	}

	/**
	 * Individua la pagina esistente: prima per impronta, poi (solo IT) per slug.
	 *
	 * @param string $ref  Impronta seeder.
	 * @param string $slug Slug canonico.
	 * @param string $lang Slug lingua.
	 */
	private function locate( string $ref, string $slug, string $lang ): ?int {
		$existing = SeedMeta::find( $ref );

		if ( null !== $existing ) {
			return $existing;
		}

		if ( $lang !== $this->language->default_language() ) {
			return null;
		}

		$page = get_page_by_path( $slug, OBJECT, 'page' );

		return $page instanceof \WP_Post ? (int) $page->ID : null;
	}

	/**
	 * Reimposta lo slug canonico (Polylang consente slug uguali per lingua).
	 *
	 * @param int    $post_id ID della pagina.
	 * @param string $slug    Slug desiderato.
	 */
	private function normalize_slug( int $post_id, string $slug ): void {
		$post = get_post( $post_id );

		if ( $post instanceof \WP_Post && $post->post_name !== $slug ) {
			wp_update_post(
				array(
					'ID'        => $post_id,
					'post_name' => $slug,
				)
			);
		}
	}

	/**
	 * Scrive le meta della pagina, risolvendo i riferimenti immagine.
	 *
	 * @param int                 $post_id       ID della pagina.
	 * @param array<string,mixed> $meta          Meta da scrivere.
	 * @param MediaLibrary        $media_library Libreria immagini.
	 */
	private function write_meta( int $post_id, array $meta, MediaLibrary $media_library ): void {
		foreach ( $meta as $key => $value ) {
			if ( $value instanceof MediaRef ) {
				$this->write_image( $post_id, (string) $key, $value, $media_library );

				continue;
			}

			update_post_meta( $post_id, (string) $key, $value );
		}
	}

	/**
	 * Scrive una meta immagine singola risolvendo il segnaposto.
	 *
	 * @param int          $post_id       ID della pagina.
	 * @param string       $key           Meta key.
	 * @param MediaRef     $ref           Riferimento immagine.
	 * @param MediaLibrary $media_library Libreria immagini.
	 */
	private function write_image( int $post_id, string $key, MediaRef $ref, MediaLibrary $media_library ): void {
		$attachment_id = $media_library->placeholder( $ref->key() );

		if ( null !== $attachment_id ) {
			update_post_meta( $post_id, $key, $attachment_id );

			return;
		}

		delete_post_meta( $post_id, $key );
	}
}
