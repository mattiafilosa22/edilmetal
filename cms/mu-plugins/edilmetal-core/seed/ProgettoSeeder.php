<?php
/**
 * Popola il CPT "progetto" con le realizzazioni di demo, in italiano e inglese.
 *
 * Idempotente: ogni variante linguistica e ritrovata tramite la sua impronta
 * seeder e aggiornata al posto di essere duplicata.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Seed;

use Edilmetal\Core\Seed\Data\Catalog;
use Edilmetal\Core\Seed\Support\MediaLibrary;
use Edilmetal\Core\Seed\Support\SeedMeta;
use Edilmetal\Core\Support\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Crea/aggiorna le realizzazioni e ne collega le traduzioni via Polylang.
 */
final class ProgettoSeeder {

	/**
	 * Chiavi dei segnaposto usati come galleria di demo, in ordine.
	 *
	 * @var string[]
	 */
	private const GALLERY_KEYS = array( 'insieme', 'dettaglio', 'cantiere' );

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
	 * Seeda tutte le realizzazioni collegando le traduzioni.
	 *
	 * @param MediaLibrary $media_library Libreria immagini seedata.
	 * @return int Numero di realizzazioni (record base) processate.
	 */
	public function seed( MediaLibrary $media_library ): int {
		$count = 0;

		foreach ( Catalog::progetti() as $record ) {
			$this->seed_record( $record, $media_library );
			++$count;
		}

		return $count;
	}

	/**
	 * Elimina tutte le realizzazioni generate dal seeder (usato con --fresh).
	 *
	 * @return int Numero di realizzazioni eliminate.
	 */
	public function purge(): int {
		$deleted = 0;

		foreach ( SeedMeta::all_of_type( Schema::CPT_PROGETTO ) as $post_id ) {
			if ( wp_delete_post( $post_id, true ) ) {
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * Elimina solo le realizzazioni fittizie di sviluppo gia seedate in
	 * precedenza (quando {@see Catalog::progetti()} includeva ancora
	 * {@see Catalog::curated_progetti()}), senza toccare le storiche reali.
	 *
	 * @return int Numero di realizzazioni eliminate.
	 */
	public function purge_curated(): int {
		$deleted = 0;
		$langs   = array( $this->language->default_language() );

		if ( $this->language->is_active() ) {
			$langs = array_merge( $langs, $this->language->secondary_languages() );
		}

		foreach ( Catalog::curated_progetti() as $record ) {
			foreach ( $langs as $lang ) {
				$post_id = SeedMeta::find( 'progetto:' . $record['ref'] . ':' . $lang );

				if ( null !== $post_id && wp_delete_post( $post_id, true ) ) {
					++$deleted;
				}
			}
		}

		return $deleted;
	}

	/**
	 * Seeda un singolo record (IT + EN) e collega le traduzioni.
	 *
	 * @param array<string,mixed> $record        Dati della realizzazione.
	 * @param MediaLibrary        $media_library Libreria immagini seedata.
	 */
	private function seed_record( array $record, MediaLibrary $media_library ): void {
		$default = $this->language->default_language();

		$translations = array(
			$default => $this->upsert( $record, $default, $media_library ),
		);

		if ( $this->language->is_active() ) {
			foreach ( $this->language->secondary_languages() as $lang ) {
				$translations[ $lang ] = $this->upsert( $record, $lang, $media_library );
			}

			$this->language->link( $translations );
		}
	}

	/**
	 * Crea o aggiorna la variante linguistica di una realizzazione.
	 *
	 * @param array<string,mixed> $record        Dati della realizzazione.
	 * @param string              $lang          Slug lingua.
	 * @param MediaLibrary        $media_library Libreria immagini seedata.
	 * @return int ID del post.
	 */
	private function upsert( array $record, string $lang, MediaLibrary $media_library ): int {
		$ref      = 'progetto:' . $record['ref'] . ':' . $lang;
		$existing = SeedMeta::find( $ref );
		$content  = $this->localized( $record, $lang, 'content', (string) $record['content'] );

		$postarr = array(
			'post_type'    => Schema::CPT_PROGETTO,
			'post_status'  => 'publish',
			'post_title'   => (string) $record['title'],
			'post_content' => $content,
			'post_name'    => (string) $record['ref'],
		);

		if ( null !== $existing ) {
			$postarr['ID'] = $existing;
			wp_update_post( $postarr );
			$post_id = $existing;
		} else {
			$post_id = (int) wp_insert_post( $postarr, true );
		}

		if ( $post_id <= 0 ) {
			return 0;
		}

		$this->language->assign( $post_id, $lang );
		$this->normalize_slug( $post_id, (string) $record['ref'] );
		$this->assign_terms( $post_id, $record );
		$this->write_meta( $post_id, $record );
		$this->write_gallery( $post_id, $record, $media_library );
		SeedMeta::mark( $post_id, $ref );

		return $post_id;
	}

	/**
	 * Reimposta lo slug canonico (Polylang consente slug uguali per lingua).
	 *
	 * @param int    $post_id ID del post.
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
	 * Assegna categoria opera e settore.
	 *
	 * @param int                 $post_id ID del post.
	 * @param array<string,mixed> $record  Dati della realizzazione.
	 */
	private function assign_terms( int $post_id, array $record ): void {
		$map = array(
			Schema::TAX_CATEGORIA => (string) $record['categoria'],
			Schema::TAX_SETTORE   => (string) $record['settore'],
		);

		foreach ( $map as $taxonomy => $slug ) {
			$term = get_term_by( 'slug', $slug, $taxonomy );

			if ( $term instanceof \WP_Term ) {
				wp_set_object_terms( $post_id, array( $term->term_id ), $taxonomy, false );
			}
		}
	}

	/**
	 * Scrive tutte le meta della realizzazione.
	 *
	 * @param int                 $post_id ID del post.
	 * @param array<string,mixed> $record  Dati della realizzazione.
	 */
	private function write_meta( int $post_id, array $record ): void {
		$values = array(
			'cliente'         => (string) $record['cliente'],
			'luogo'           => (string) $record['luogo'],
			'anno'            => (int) $record['anno'],
			'in_evidenza'     => $this->flag( (bool) $record['in_evidenza'] ),
			'tipologia'       => (string) $record['tipologia'],
			'superficie_mq'   => $record['superficie_mq'],
			'luce_campata_m'  => $record['luce_campata_m'],
			'altezza_m'       => $record['altezza_m'],
			'peso_acciaio_t'  => $record['peso_acciaio_t'],
			'lavorazioni'     => $this->lines( $this->string_array( $record['lavorazioni'] ) ),
			'materiali'       => $this->lines( $this->string_array( $record['materiali'] ) ),
			'seo_title'       => (string) $record['seo_title'],
			'seo_description' => (string) $record['seo_description'],
		);

		foreach ( $values as $key => $value ) {
			$this->set_meta( $post_id, Schema::meta( $key ), $value );
		}
	}

	/**
	 * Sostituisce la galleria con le foto del record, o i segnaposto di demo.
	 *
	 * @param int                 $post_id       ID del post.
	 * @param array<string,mixed> $record        Dati della realizzazione.
	 * @param MediaLibrary        $media_library Libreria immagini seedata.
	 */
	private function write_gallery( int $post_id, array $record, MediaLibrary $media_library ): void {
		$key = Schema::meta( 'galleria' );
		delete_post_meta( $post_id, $key );

		$media_keys  = ! empty( $record['media'] ) ? $record['media'] : self::GALLERY_KEYS;
		$attachments = $media_library->gallery( $media_keys );
		$first       = null;

		foreach ( $attachments as $attachment_id ) {
			$attachment_id = (int) $attachment_id;
			add_post_meta( $post_id, $key, $attachment_id );
			$first ??= $attachment_id;
		}

		if ( null !== $first ) {
			set_post_thumbnail( $post_id, $first );
		}
	}

	/**
	 * Aggiorna una meta, oppure la elimina se il valore e nullo o stringa vuota.
	 *
	 * @param int    $post_id ID del post.
	 * @param string $key     Meta key completa.
	 * @param mixed  $value   Valore da scrivere.
	 */
	private function set_meta( int $post_id, string $key, $value ): void {
		if ( null === $value || '' === $value ) {
			delete_post_meta( $post_id, $key );

			return;
		}

		update_post_meta( $post_id, $key, $value );
	}

	/**
	 * Converte una lista di stringhe nel formato multi-riga atteso dalle textarea.
	 *
	 * @param string[] $items Voci.
	 */
	private function lines( array $items ): string {
		return implode( "\n", $items );
	}

	/**
	 * Normalizza un valore in array di stringhe.
	 *
	 * @param mixed $value Valore grezzo.
	 * @return string[]
	 */
	private function string_array( $value ): array {
		return is_array( $value ) ? array_map( 'strval', $value ) : array();
	}

	/**
	 * Rappresentazione di un flag switch/checkbox Meta Box.
	 *
	 * @param bool $on Stato del flag.
	 */
	private function flag( bool $on ): string {
		return $on ? '1' : '0';
	}

	/**
	 * Restituisce il valore localizzato (override EN) o il default IT.
	 *
	 * @param array<string,mixed> $record   Dati della realizzazione.
	 * @param string              $lang     Slug lingua.
	 * @param string              $key      Chiave override.
	 * @param string              $fallback Valore di default (IT).
	 */
	private function localized( array $record, string $lang, string $key, string $fallback ): string {
		if ( 'en' === $lang && isset( $record['en'][ $key ] ) && is_string( $record['en'][ $key ] ) ) {
			return (string) $record['en'][ $key ];
		}

		return $fallback;
	}
}
