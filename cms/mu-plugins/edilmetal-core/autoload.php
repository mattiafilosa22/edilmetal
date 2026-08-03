<?php
/**
 * Autoloader PSR-4 personalizzato per il namespace Edilmetal\Core.
 *
 * I moduli vivono in cartelle "kebab-case" (post-types, security, ...), mentre
 * le classi usano sotto-namespace PascalCase. La mappa sotto associa ogni
 * prefisso di namespace alla propria cartella, mantenendo i segmenti annidati
 * (Presenters, Repositories, ...) invariati.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix_to_dir = array(
			'Edilmetal\\Core\\PostTypes\\' => 'post-types/',
			'Edilmetal\\Core\\Fields\\'    => 'fields/',
			'Edilmetal\\Core\\Rest\\'      => 'rest/',
			'Edilmetal\\Core\\I18n\\'      => 'i18n/',
			'Edilmetal\\Core\\Security\\'  => 'security/',
			'Edilmetal\\Core\\Branding\\'  => 'branding/',
			'Edilmetal\\Core\\Webhook\\'   => 'webhook/',
			'Edilmetal\\Core\\Mail\\'      => 'mail/',
			'Edilmetal\\Core\\Forms\\'     => 'forms/',
			'Edilmetal\\Core\\Seed\\'      => 'seed/',
			'Edilmetal\\Core\\'            => 'src/',
		);

		foreach ( $prefix_to_dir as $prefix => $dir ) {
			if ( 0 !== strpos( $class_name, $prefix ) ) {
				continue;
			}

			$relative = substr( $class_name, strlen( $prefix ) );
			$path     = EDILMETAL_CORE_DIR . '/' . $dir . str_replace( '\\', '/', $relative ) . '.php';

			if ( is_readable( $path ) ) {
				require_once $path;
			}

			return;
		}
	}
);
