<?php
/**
 * Modulo seeder: registra il comando WP-CLI "wp edilmetal seed".
 *
 * Il comando popola tassonomie, realizzazioni di demo, impostazioni globali e
 * pagine editoriali in modo idempotente.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Seed;

use Edilmetal\Core\Module;
use Edilmetal\Core\Rest\Support\ImageTransformer;
use Edilmetal\Core\Seed\Support\Placeholders;
use Edilmetal\Core\Seed\Support\RealPhotos;

defined( 'ABSPATH' ) || exit;

/**
 * Espone il seeder come comando WP-CLI oppure, quando WP-CLI non e
 * disponibile (es. hosting Plesk senza SSH), come pagina di amministrazione
 * sotto "Strumenti".
 */
final class SeedModule implements Module {

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		if ( defined( 'WP_CLI' ) && \WP_CLI ) {
			\WP_CLI::add_command( 'edilmetal seed', $this->command() );

			return;
		}

		if ( is_admin() ) {
			( new SeedAdminPage( $this->command() ) )->register();
		}
	}

	/**
	 * Compone il comando iniettando i seeder collaboratori.
	 */
	private function command(): SeedCommand {
		$language = new LanguageSeeder();

		return new SeedCommand(
			$language,
			new TaxonomySeeder(),
			new MediaSeeder( new Placeholders(), new RealPhotos(), new ImageTransformer() ),
			new ProgettoSeeder( $language ),
			new PageSeeder( $language )
		);
	}
}
