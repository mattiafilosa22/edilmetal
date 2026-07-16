<?php
/**
 * Configura Polylang e collega le traduzioni IT/EN dei contenuti seedati.
 *
 * Se Polylang non e attivo, tutte le operazioni degradano silenziosamente a
 * solo italiano senza generare errori: i contenuti restano validi e pubblici.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Seed;

use Edilmetal\Core\Support\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Facciata sottile sopra le API di Polylang usate dal seeder.
 */
final class LanguageSeeder {

	/**
	 * Lingue gestite dal sito (slug => locale WordPress).
	 *
	 * @var array<string,string>
	 */
	private const LANGUAGES = array(
		'it' => 'it_IT',
		'en' => 'en_US',
	);

	/**
	 * Indica se Polylang e attivo e utilizzabile.
	 */
	public function is_active(): bool {
		return function_exists( 'PLL' ) && function_exists( 'pll_set_post_language' ) && is_object( \PLL() );
	}

	/**
	 * Lingua di default gestita dal seeder.
	 */
	public function default_language(): string {
		return 'it';
	}

	/**
	 * Elenco degli slug lingua non di default (per creare le traduzioni).
	 *
	 * @return string[]
	 */
	public function secondary_languages(): array {
		return array( 'en' );
	}

	/**
	 * Crea le lingue IT/EN se assenti e rende "progetto" traducibile.
	 *
	 * @return string[] Slug delle lingue create in questa esecuzione.
	 */
	public function ensure_languages(): array {
		if ( ! $this->is_active() ) {
			return array();
		}

		$existing = $this->existing_slugs();
		$created  = array();

		foreach ( self::LANGUAGES as $slug => $locale ) {
			if ( in_array( $slug, $existing, true ) ) {
				continue;
			}

			if ( $this->add_language( $slug, $locale ) ) {
				$created[] = $slug;
			}
		}

		if ( array() !== $created ) {
			\PLL()->model->clean_languages_cache();
		}

		$this->make_progetto_translatable();

		return $created;
	}

	/**
	 * Imposta la lingua di un post.
	 *
	 * @param int    $post_id ID del post.
	 * @param string $slug    Slug lingua.
	 */
	public function assign( int $post_id, string $slug ): void {
		if ( $this->is_active() ) {
			pll_set_post_language( $post_id, $slug );
		}
	}

	/**
	 * Collega tra loro le traduzioni di uno stesso contenuto.
	 *
	 * @param array<string,int> $translations Mappa slug lingua => ID post.
	 */
	public function link( array $translations ): void {
		if ( $this->is_active() && count( $translations ) > 1 ) {
			pll_save_post_translations( $translations );
		}
	}

	/**
	 * Slug delle lingue gia registrate in Polylang.
	 *
	 * @return string[]
	 */
	private function existing_slugs(): array {
		if ( ! function_exists( 'pll_languages_list' ) ) {
			return array();
		}

		$list = pll_languages_list();

		return is_array( $list ) ? array_map( 'strval', $list ) : array();
	}

	/**
	 * Aggiunge una lingua tramite il modello di Polylang.
	 *
	 * @param string $slug   Slug lingua (es. "it").
	 * @param string $locale Locale WordPress (es. "it_IT").
	 */
	private function add_language( string $slug, string $locale ): bool {
		$model = \PLL()->model;

		if ( ! isset( $model->languages ) || ! method_exists( $model->languages, 'add' ) ) {
			return false;
		}

		$result = $model->languages->add(
			array(
				'slug'       => $slug,
				'locale'     => $locale,
				'rtl'        => false,
				'term_group' => 'it' === $slug ? 0 : 1,
			)
		);

		return ! is_wp_error( $result );
	}

	/**
	 * Aggiunge il CPT "progetto" all'elenco dei tipi tradotti da Polylang.
	 *
	 * La mutazione dell'oggetto opzioni ha effetto immediato nel processo
	 * corrente (necessario per collegare subito le traduzioni) e viene
	 * persistita da Polylang, cosi anche le richieste REST filtrano per lingua.
	 */
	private function make_progetto_translatable(): void {
		$options = \PLL()->options;

		$post_types = isset( $options['post_types'] ) && is_array( $options['post_types'] )
			? $options['post_types']
			: array();

		if ( in_array( Schema::CPT_PROGETTO, $post_types, true ) ) {
			return;
		}

		$post_types[]          = Schema::CPT_PROGETTO;
		$options['post_types'] = array_values( $post_types );
	}
}
