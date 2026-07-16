<?php
/**
 * Loader must-use per il plugin Edilmetal Core.
 *
 * WordPress carica automaticamente solo i file PHP nella radice di mu-plugins,
 * non nelle sottocartelle. Questo loader richiede il bootstrap reale del plugin.
 *
 * @package Edilmetal\Core
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/edilmetal-core/edilmetal-core.php';
