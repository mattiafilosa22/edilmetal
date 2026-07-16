<?php
/**
 * Fornisce le immagini JPEG segnaposto per le gallerie delle realizzazioni.
 *
 * Gli asset (segnaposto committati in cms/seed/media) vengono riusati; se
 * assenti sono generati via GD una sola volta. La generazione e idempotente e
 * non sovrascrive file esistenti. Le foto reali li sostituiranno in seguito.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Seed\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Produce (se assenti) i JPEG segnaposto e ne restituisce i percorsi.
 */
final class Placeholders {

	/**
	 * Larghezza dei segnaposto in pixel (sufficiente per tutte le image-size).
	 *
	 * @var int
	 */
	private const WIDTH = 1200;

	/**
	 * Altezza dei segnaposto in pixel.
	 *
	 * @var int
	 */
	private const HEIGHT = 800;

	/**
	 * Definizione dei segnaposto: chiave => [etichetta, colore RGB di sfondo].
	 *
	 * @var array<string,array{0:string,1:array{0:int,1:int,2:int}}>
	 */
	private const ASSETS = array(
		'insieme'   => array( "Vista d'insieme", array( 15, 39, 64 ) ),
		'dettaglio' => array( 'Dettaglio nodo', array( 107, 119, 133 ) ),
		'cantiere'  => array( 'Montaggio in cantiere', array( 31, 35, 40 ) ),
	);

	/**
	 * Directory di destinazione dei segnaposto.
	 *
	 * @var string
	 */
	private string $directory;

	/**
	 * Risolve la directory dei segnaposto (montaggio cms/seed/media o fallback).
	 */
	public function __construct() {
		$this->directory = $this->resolve_directory();
	}

	/**
	 * Garantisce l'esistenza dei file e ne restituisce i percorsi assoluti.
	 *
	 * @return array<string,string> Mappa chiave => percorso file JPEG.
	 */
	public function ensure(): array {
		$paths = array();

		foreach ( self::ASSETS as $key => $asset ) {
			$path = trailingslashit( $this->directory ) . 'placeholder-' . $key . '.jpg';

			if ( ! file_exists( $path ) ) {
				$this->render( $path, (string) $asset[0], $asset[1] );
			}

			if ( file_exists( $path ) ) {
				$paths[ $key ] = $path;
			}
		}

		return $paths;
	}

	/**
	 * Determina e crea (se necessario) la directory dei segnaposto.
	 */
	private function resolve_directory(): string {
		$preferred = defined( 'EDILMETAL_SEED_MEDIA_DIR' ) ? (string) EDILMETAL_SEED_MEDIA_DIR : '/cms/seed/media';

		if ( ! is_dir( $preferred ) ) {
			wp_mkdir_p( $preferred );
		}

		if ( is_dir( $preferred ) && wp_is_writable( $preferred ) ) {
			return $preferred;
		}

		$fallback = trailingslashit( get_temp_dir() ) . 'edilmetal-seed-media';
		wp_mkdir_p( $fallback );

		return $fallback;
	}

	/**
	 * Disegna un JPEG segnaposto con etichetta centrata.
	 *
	 * @param string                   $path  Percorso di destinazione.
	 * @param string                   $label Testo mostrato al centro.
	 * @param array{0:int,1:int,2:int} $rgb Colore di sfondo.
	 */
	private function render( string $path, string $label, array $rgb ): void {
		if ( ! function_exists( 'imagecreatetruecolor' ) ) {
			return;
		}

		$image = imagecreatetruecolor( self::WIDTH, self::HEIGHT );

		if ( false === $image ) {
			return;
		}

		$background = imagecolorallocate( $image, $rgb[0], $rgb[1], $rgb[2] );
		$foreground = imagecolorallocate( $image, 226, 232, 240 );
		imagefilledrectangle( $image, 0, 0, self::WIDTH, self::HEIGHT, $background );

		$brand = 'EDILMETAL';
		imagestring( $image, 5, $this->center_x( strlen( $brand ), 5 ), 320, $brand, $foreground );
		imagestring( $image, 5, $this->center_x( strlen( $label ), 5 ), 360, $label, $foreground );
		imagestring( $image, 3, $this->center_x( 22, 3 ), 400, 'immagine dimostrativa', $foreground );

		imagejpeg( $image, $path, 82 );
		imagedestroy( $image );
	}

	/**
	 * Calcola la X iniziale per centrare un testo a larghezza fissa GD.
	 *
	 * @param int $length    Numero di caratteri.
	 * @param int $font_size Indice del font GD integrato.
	 */
	private function center_x( int $length, int $font_size ): int {
		$char_width = imagefontwidth( $font_size );

		return (int) max( 0, ( self::WIDTH - ( $length * $char_width ) ) / 2 );
	}
}
