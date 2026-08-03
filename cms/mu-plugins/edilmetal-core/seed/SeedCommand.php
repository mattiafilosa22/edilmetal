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
		$lines = $this->run( $fresh );

		foreach ( $lines as $line ) {
			switch ( $line['level'] ) {
				case 'warning':
					\WP_CLI::warning( $line['message'] );
					break;
				case 'success':
					\WP_CLI::success( $line['message'] );
					break;
				default:
					\WP_CLI::log( $line['message'] );
			}
		}
	}

	/**
	 * Esegue il seeding e restituisce l'esito come elenco di righe di log
	 * (disaccoppiato da WP-CLI, cosi riusabile anche da un trigger admin).
	 *
	 * @param bool $fresh Se true, elimina prima i contenuti di demo generati.
	 * @return array<int,array{level:string,message:string}> Righe con level "log"|"warning"|"success".
	 */
	public function run( bool $fresh ): array {
		$lines = array();

		$lines[] = $this->line( 'log', '▸ Configurazione lingue (Polylang) ...' );
		$lines   = array_merge( $lines, $this->configure_languages() );

		if ( $fresh ) {
			$lines = array_merge( $lines, $this->purge() );
		}

		$lines[] = $this->line( 'log', '▸ Tassonomie (categorie opera, settori) ...' );
		$terms   = $this->taxonomies->seed();
		$lines[] = $this->line( 'log', sprintf( '  %d termini creati.', $terms ) );

		$lines[] = $this->line( 'log', '▸ Immagini (segnaposto realizzazioni) ...' );
		$library = $this->media->seed();
		$lines[] = $this->line( 'log', sprintf( '  %d immagini disponibili.', $library->total() ) );

		$lines[]  = $this->line( 'log', '▸ Realizzazioni di demo ...' );
		$progetti = $this->progetti->seed( $library );
		$lines[]  = $this->line( 'log', sprintf( '  %d realizzazioni processate.', $progetti ) );

		$lines[] = $this->line( 'log', '▸ Impostazioni e pagine editoriali ...' );
		$pages   = $this->pages->seed( $library );
		$lines[] = $this->line( 'log', sprintf( '  %d pagine processate.', $pages ) );

		$this->finish();

		$lines[] = $this->line( 'success', 'Seeding completato.' );

		return $lines;
	}

	/**
	 * Elimina le sole realizzazioni fittizie di sviluppo gia seedate in
	 * precedenza, lasciando intatte le storiche reali e le pagine/impostazioni.
	 *
	 * @return array<int,array{level:string,message:string}> Righe di log.
	 */
	public function purge_demo_progetti(): array {
		$lines     = array( $this->line( 'log', '▸ Rimozione realizzazioni fittizie di demo ...' ) );
		$eliminate = $this->progetti->purge_curated();
		$lines[]   = $this->line( 'log', sprintf( '  %d realizzazioni fittizie eliminate.', $eliminate ) );

		$this->finish();

		$lines[] = $this->line( 'success', 'Pulizia completata.' );

		return $lines;
	}

	/**
	 * Costruisce una riga di log strutturata.
	 *
	 * @param string $level   Livello ("log"|"warning"|"success").
	 * @param string $message Messaggio testuale.
	 * @return array{level:string,message:string} Riga di log.
	 */
	private function line( string $level, string $message ): array {
		return array(
			'level'   => $level,
			'message' => $message,
		);
	}

	/**
	 * Configura le lingue e segnala lo stato di Polylang.
	 *
	 * @return array<int,array{level:string,message:string}> Righe di log.
	 */
	private function configure_languages(): array {
		if ( ! $this->language->is_active() ) {
			return array( $this->line( 'warning', 'Polylang non attivo: seeding solo in italiano.' ) );
		}

		$created = $this->language->ensure_languages();

		if ( array() !== $created ) {
			return array( $this->line( 'log', sprintf( '  Lingue create: %s.', implode( ', ', $created ) ) ) );
		}

		return array( $this->line( 'log', '  Lingue gia configurate.' ) );
	}

	/**
	 * Elimina i contenuti di demo generati in precedenza (--fresh).
	 *
	 * @return array<int,array{level:string,message:string}> Righe di log.
	 */
	private function purge(): array {
		$lines       = array( $this->line( 'log', '▸ Pulizia contenuti di demo (--fresh) ...' ) );
		$progetti    = $this->progetti->purge();
		$leads       = $this->purge_leads();
		$attachments = $this->media->purge();
		$lines[]     = $this->line( 'log', sprintf( '  Eliminati: %d realizzazioni, %d lead, %d immagini.', $progetti, $leads, $attachments ) );

		return $lines;
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
