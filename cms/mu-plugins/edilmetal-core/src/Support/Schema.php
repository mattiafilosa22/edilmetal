<?php
/**
 * Costanti condivise: nomi di CPT, tassonomie, prefisso meta e chiavi pagina.
 *
 * Centralizzare queste stringhe evita duplicazione e "magic string" sparse.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Nomi canonici delle entita del plugin.
 */
final class Schema {

	public const CPT_PROGETTO = 'progetto';
	public const CPT_LEAD     = 'lead';

	public const TAX_CATEGORIA = 'categoria_opera';
	public const TAX_SETTORE   = 'settore';

	/**
	 * Prefisso applicato a tutte le meta key.
	 *
	 * @var string
	 */
	public const META_PREFIX = 'edilmetal_';

	/**
	 * Slug della pagina che ospita le impostazioni globali del sito.
	 *
	 * @var string
	 */
	public const SETTINGS_PAGE_SLUG = 'impostazioni';

	/**
	 * Slug/chiavi delle pagine editoriali gestite dal front-end.
	 *
	 * @var string[]
	 */
	public const PAGE_KEYS = array(
		'home',
		'servizi',
		'azienda',
		'contatti',
		'privacy-policy',
		'cookie-policy',
	);

	/**
	 * Restituisce una meta key completa di prefisso.
	 *
	 * @param string $name Nome del campo senza prefisso.
	 */
	public static function meta( string $name ): string {
		return self::META_PREFIX . $name;
	}
}
