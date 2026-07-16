<?php
/**
 * Servizio multilingua: integrazione con Polylang e degrado a IT.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\I18n;

defined( 'ABSPATH' ) || exit;

/**
 * Risolve e applica la lingua richiesta dagli endpoint REST.
 *
 * Se Polylang non e attivo tutte le operazioni degradano a italiano senza
 * generare errori.
 */
final class Language {

	/**
	 * Lingua di default.
	 *
	 * @var string
	 */
	public const DEFAULT = 'it';

	/**
	 * Lingue supportate quando Polylang non e disponibile.
	 *
	 * @var string[]
	 */
	private const FALLBACK_LANGUAGES = array( 'it', 'en' );

	/**
	 * Normalizza un valore di lingua grezzo in un codice valido.
	 *
	 * @param mixed $raw Valore ricevuto (es. query param).
	 * @return string Codice lingua a due lettere.
	 */
	public static function resolve( $raw ): string {
		$code = is_string( $raw ) ? strtolower( sanitize_key( $raw ) ) : '';

		if ( '' === $code ) {
			return self::DEFAULT;
		}

		if ( in_array( $code, self::available(), true ) ) {
			return $code;
		}

		return self::DEFAULT;
	}

	/**
	 * Indica se Polylang e attivo.
	 */
	public static function is_active(): bool {
		return function_exists( 'pll_languages_list' );
	}

	/**
	 * Restituisce l'elenco delle lingue disponibili.
	 *
	 * @return string[]
	 */
	public static function available(): array {
		if ( self::is_active() ) {
			$languages = pll_languages_list();

			if ( is_array( $languages ) && array() !== $languages ) {
				return array_map( 'strval', $languages );
			}
		}

		return self::FALLBACK_LANGUAGES;
	}

	/**
	 * Aggiunge il vincolo di lingua a un array di argomenti WP_Query.
	 *
	 * @param array<string,mixed> $args Argomenti della query.
	 * @param string              $lang Codice lingua gia normalizzato.
	 * @return array<string,mixed>
	 */
	public static function constrain_query( array $args, string $lang ): array {
		if ( self::is_active() ) {
			$args['lang'] = $lang;
		}

		return $args;
	}

	/**
	 * Restituisce l'ID della traduzione di un post nella lingua indicata.
	 *
	 * @param int    $post_id ID del post originale.
	 * @param string $lang    Codice lingua.
	 * @return int ID tradotto oppure l'ID originale se non disponibile.
	 */
	public static function translated_post_id( int $post_id, string $lang ): int {
		if ( self::is_active() ) {
			$translated = pll_get_post( $post_id, $lang );

			if ( is_int( $translated ) && $translated > 0 ) {
				return $translated;
			}
		}

		return $post_id;
	}
}
