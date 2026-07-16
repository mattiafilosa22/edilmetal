<?php
/**
 * Libreria media seedata: immagini segnaposto usate come gallerie di demo.
 *
 * Struttura immutabile passata a ProgettoSeeder e PageSeeder: mappa chiave
 * logica (es. "insieme") => ID allegato.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Seed\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Contiene gli ID allegato dei segnaposto risolti dal MediaSeeder.
 */
final class MediaLibrary {

	/**
	 * Segnaposto: chiave logica (es. "insieme") => ID allegato.
	 *
	 * @var array<string,int>
	 */
	private array $placeholders;

	/**
	 * Costruisce la libreria dai segnaposto risolti.
	 *
	 * @param array<string,int> $placeholders Segnaposto (chiave => ID allegato).
	 */
	public function __construct( array $placeholders ) {
		$this->placeholders = $placeholders;
	}

	/**
	 * ID dell'immagine segnaposto per la chiave logica, oppure null.
	 *
	 * @param string $key Chiave logica (es. "insieme").
	 */
	public function placeholder( string $key ): ?int {
		return $this->placeholders[ $key ] ?? null;
	}

	/**
	 * Galleria di demo (ID allegato ordinati) a partire da un insieme di chiavi.
	 *
	 * @param string[] $keys Chiavi logiche dei segnaposto, in ordine.
	 * @return array<int,int>
	 */
	public function gallery( array $keys ): array {
		$ids = array();

		foreach ( $keys as $key ) {
			$id = $this->placeholder( $key );

			if ( null !== $id ) {
				$ids[] = $id;
			}
		}

		return $ids;
	}

	/**
	 * Numero totale di allegati disponibili.
	 */
	public function total(): int {
		return count( $this->placeholders );
	}
}
