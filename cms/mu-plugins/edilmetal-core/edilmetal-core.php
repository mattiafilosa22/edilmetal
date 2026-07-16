<?php
/**
 * Plugin Name:  Edilmetal Core
 * Description:  Back-office headless per Edilmetal (carpenteria metallica su commessa): CPT progetto/lead, campi Meta Box, REST API edilmetal/v1, multilingua, sicurezza e webhook di deploy.
 * Version:      1.0.0
 * Author:       Romiltec Srl
 * Text Domain:  edilmetal-core
 * Requires PHP: 8.1
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

if ( defined( 'EDILMETAL_CORE_VERSION' ) ) {
	return;
}

define( 'EDILMETAL_CORE_VERSION', '1.0.0' );
define( 'EDILMETAL_CORE_DIR', __DIR__ );
define( 'EDILMETAL_CORE_FILE', __FILE__ );
define( 'EDILMETAL_CORE_TEXTDOMAIN', 'edilmetal-core' );
define( 'EDILMETAL_CORE_REST_NAMESPACE', 'edilmetal/v1' );

require_once __DIR__ . '/autoload.php';

/*
 * Avvio del plugin. L'autoloader PSR-4 personalizzato risolve i namespace
 * Edilmetal\Core\* verso i moduli su disco.
 */
\Edilmetal\Core\Plugin::instance()->boot();
