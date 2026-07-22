<?php
/**
 * Elenca le foto storiche reali già presenti in `cms/seed/media/realizzazioni/`
 * (organizzate per categoria/progetto), pronte per l'import in libreria media.
 *
 * A differenza di `Placeholders`, questa classe non genera nulla: i file sono
 * commitati nel repository come asset reali (foto di cantiere 1997–2018).
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Seed\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Scansiona `cms/seed/media/realizzazioni/**\/*.jpg` e ne mappa la chiave logica.
 */
final class RealPhotos {

	/**
	 * Directory radice delle foto storiche reali.
	 *
	 * @var string
	 */
	private string $directory;

	/**
	 * Directory radice della seed media library (contiene anche `home/`).
	 *
	 * @var string
	 */
	private string $base;

	/**
	 * Risolve la directory radice (montaggio cms/seed/media o fallback locale).
	 */
	public function __construct() {
		$this->base      = defined( 'EDILMETAL_SEED_MEDIA_DIR' ) ? (string) EDILMETAL_SEED_MEDIA_DIR : '/cms/seed/media';
		$this->directory = trailingslashit( $this->base ) . 'realizzazioni';
	}

	/**
	 * Restituisce la mappa chiave logica => percorso assoluto file.
	 *
	 * Chiave: "real:<categoria-slug>/<progetto-slug>/<NN>" per le realizzazioni,
	 * "real:home/<stem>" per gli asset editoriali di home (es. foto hero).
	 *
	 * @return array<string,string>
	 */
	public function ensure(): array {
		$paths = array();

		if ( is_dir( $this->directory ) ) {
			foreach ( $this->glob_dirs( trailingslashit( $this->directory ) . '*' ) as $categoria_dir ) {
				$categoria = basename( $categoria_dir );

				foreach ( $this->glob_dirs( trailingslashit( $categoria_dir ) . '*' ) as $progetto_dir ) {
					$progetto = basename( $progetto_dir );

					foreach ( $this->glob_files( trailingslashit( $progetto_dir ) . '*.jpg' ) as $file ) {
						$stem          = pathinfo( $file, PATHINFO_FILENAME );
						$key           = "real:{$categoria}/{$progetto}/{$stem}";
						$paths[ $key ] = $file;
					}
				}
			}
		}

		foreach ( $this->glob_files( trailingslashit( $this->base ) . 'home/*.jpg' ) as $file ) {
			$paths[ 'real:home/' . pathinfo( $file, PATHINFO_FILENAME ) ] = $file;
		}

		return $paths;
	}

	/**
	 * Elenca le sottodirectory corrispondenti al pattern, senza mai fallire.
	 *
	 * @param string $pattern Pattern glob per directory.
	 *
	 * @return array<int,string>
	 */
	private function glob_dirs( string $pattern ): array {
		$matches = glob( $pattern, GLOB_ONLYDIR );

		return false === $matches ? array() : $matches;
	}

	/**
	 * Elenca i file corrispondenti al pattern, senza mai fallire.
	 *
	 * @param string $pattern Pattern glob per file.
	 *
	 * @return array<int,string>
	 */
	private function glob_files( string $pattern ): array {
		$matches = glob( $pattern );

		return false === $matches ? array() : $matches;
	}
}
