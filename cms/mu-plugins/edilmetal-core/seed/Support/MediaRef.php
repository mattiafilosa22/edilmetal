<?php
/**
 * Riferimento simbolico a un'immagine segnaposto della libreria seedata.
 *
 * Il catalogo non conosce gli ID degli allegati (assegnati a runtime): usa
 * questo segnaposto, risolto dai seeder nella meta immagine corrispondente.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Seed\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Chiave logica di un'immagine segnaposto (es. "esterno-fronte").
 */
final class MediaRef {

	/**
	 * Chiave del segnaposto nella libreria media.
	 *
	 * @var string
	 */
	private string $key;

	/**
	 * Costruisce il riferimento a partire dalla sua chiave logica.
	 *
	 * @param string $key Chiave logica del segnaposto.
	 */
	public function __construct( string $key ) {
		$this->key = $key;
	}

	/**
	 * Restituisce la chiave logica del segnaposto.
	 */
	public function key(): string {
		return $this->key;
	}
}
