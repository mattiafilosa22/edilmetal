<?php
/**
 * Comando WP-CLI "wp edilmetal seed": popola i contenuti di demo.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Seed;

use Edilmetal\Core\Seed\Support\SeedMeta;
use Edilmetal\Core\Support\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Orchestratore del seeding: lingue, tassonomie, media, realizzazioni e pagine.
 */
final class SeedCommand {

	/**
	 * Servizio multilingua.
	 *
	 * @var LanguageSeeder
	 */
	private LanguageSeeder $language;

	/**
	 * Seeder delle tassonomie.
	 *
	 * @var TaxonomySeeder
	 */
	private TaxonomySeeder $taxonomies;

	/**
	 * Seeder della libreria media.
	 *
	 * @var MediaSeeder
	 */
	private MediaSeeder $media;

	/**
	 * Seeder delle realizzazioni.
	 *
	 * @var ProgettoSeeder
	 */
	private ProgettoSeeder $progetti;

	/**
	 * Seeder delle pagine.
	 *
	 * @var PageSeeder
	 */
	private PageSeeder $pages;

	/**
	 * Inietta i seeder collaboratori.
	 *
	 * @param LanguageSeeder $language   Servizio multilingua.
	 * @param TaxonomySeeder $taxonomies Seeder tassonomie.
	 * @param MediaSeeder    $media      Seeder media.
	 * @param ProgettoSeeder $progetti   Seeder realizzazioni.
	 * @param PageSeeder     $pages      Seeder pagine.
	 */
	public function __construct(
		LanguageSeeder $language,
		TaxonomySeeder $taxonomies,
		MediaSeeder $media,
		ProgettoSeeder $progetti,
		PageSeeder $pages
	) {
		$this->language   = $language;
		$this->taxonomies = $taxonomies;
		$this->media      = $media;
		$this->progetti   = $progetti;
		$this->pages      = $pages;
	}

	/**
	 * Popola i contenuti di demo del sito Edilmetal.
	 *
	 * ## OPTIONS
	 *
	 * [--fresh]
	 * : Elimina prima le realizzazioni e i lead di demo generati dal seeder.
	 *
	 * ## EXAMPLES
	 *
	 *     wp edilmetal seed
	 *     wp edilmetal seed --fresh
	 *
	 * @param array<int,string>    $args       Argomenti posizionali (non usati).
	 * @param array<string,string> $assoc_args Opzioni ("fresh").
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$fresh = (bool) \WP_CLI\Utils\get_flag_value( $assoc_args, 'fresh', false );

		\WP_CLI::log( '▸ Configurazione lingue (Polylang) ...' );
		$this->configure_languages();

		if ( $fresh ) {
			$this->purge();
		}

		\WP_CLI::log( '▸ Tassonomie (categorie opera, settori) ...' );
		$terms = $this->taxonomies->seed();
		\WP_CLI::log( sprintf( '  %d termini creati.', $terms ) );

		\WP_CLI::log( '▸ Immagini (segnaposto realizzazioni) ...' );
		$library = $this->media->seed();
		\WP_CLI::log( sprintf( '  %d immagini disponibili.', $library->total() ) );

		\WP_CLI::log( '▸ Realizzazioni di demo ...' );
		$progetti = $this->progetti->seed( $library );
		\WP_CLI::log( sprintf( '  %d realizzazioni processate.', $progetti ) );

		\WP_CLI::log( '▸ Impostazioni e pagine editoriali ...' );
		$pages = $this->pages->seed( $library );
		\WP_CLI::log( sprintf( '  %d pagine processate.', $pages ) );

		$this->finish();

		\WP_CLI::success( 'Seeding completato.' );
	}

	/**
	 * Configura le lingue e segnala lo stato di Polylang.
	 */
	private function configure_languages(): void {
		if ( ! $this->language->is_active() ) {
			\WP_CLI::warning( 'Polylang non attivo: seeding solo in italiano.' );

			return;
		}

		$created = $this->language->ensure_languages();

		if ( array() !== $created ) {
			\WP_CLI::log( sprintf( '  Lingue create: %s.', implode( ', ', $created ) ) );
		} else {
			\WP_CLI::log( '  Lingue gia configurate.' );
		}
	}

	/**
	 * Elimina i contenuti di demo generati in precedenza (--fresh).
	 */
	private function purge(): void {
		\WP_CLI::log( '▸ Pulizia contenuti di demo (--fresh) ...' );
		$progetti    = $this->progetti->purge();
		$leads       = $this->purge_leads();
		$attachments = $this->media->purge();
		\WP_CLI::log( sprintf( '  Eliminati: %d realizzazioni, %d lead, %d immagini.', $progetti, $leads, $attachments ) );
	}

	/**
	 * Elimina i lead eventualmente marcati dal seeder.
	 */
	private function purge_leads(): int {
		$deleted = 0;

		foreach ( SeedMeta::all_of_type( Schema::CPT_LEAD ) as $lead_id ) {
			if ( wp_delete_post( $lead_id, true ) ) {
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * Rigenera i permalink cosi che le rotte REST e i CPT siano coerenti.
	 */
	private function finish(): void {
		flush_rewrite_rules( false );
	}
}
