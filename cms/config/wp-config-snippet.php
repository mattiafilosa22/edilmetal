<?php
/**
 * Snippet di configurazione consigliato per Edilmetal Core.
 *
 * Copiare queste costanti dentro wp-config.php (prima della riga
 * "That's all, stop editing!"). NON committare i valori reali dei token.
 *
 * @package Edilmetal\Core
 */

defined( 'ABSPATH' ) || exit;

/*
 * -------------------------------------------------------------------------
 * Aggiornamenti automatici — disattivati (gestione manuale controllata).
 * -------------------------------------------------------------------------
 */
if ( ! defined( 'WP_AUTO_UPDATE_CORE' ) ) {
	define( 'WP_AUTO_UPDATE_CORE', false );
}
if ( ! defined( 'AUTOMATIC_UPDATER_DISABLED' ) ) {
	define( 'AUTOMATIC_UPDATER_DISABLED', true );
}

/*
 * -------------------------------------------------------------------------
 * Hardening: blocco editor file e installazioni dalla dashboard.
 * -------------------------------------------------------------------------
 */
if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}

/*
 * Decommentare per bloccare anche installazioni/aggiornamenti dalla dashboard:
 * define( 'DISALLOW_FILE_MODS', true );
 */

/*
 * -------------------------------------------------------------------------
 * Webhook di deploy (GitHub repository_dispatch).
 * Impostare con i valori reali SOLO su questo file del server, mai in repo.
 * -------------------------------------------------------------------------
 */
if ( ! defined( 'EDILMETAL_GH_REPO' ) ) {
	// Formato: "owner/repository".
	define( 'EDILMETAL_GH_REPO', 'owner/repository' );
}
if ( ! defined( 'EDILMETAL_GH_TOKEN' ) ) {
	// Personal Access Token con scope "repo" (o fine-grained: Contents + Metadata).
	define( 'EDILMETAL_GH_TOKEN', '' );
}

/*
 * -------------------------------------------------------------------------
 * SMTP (placeholder) — usati da un plugin SMTP o dalla configurazione mail.
 * -------------------------------------------------------------------------
 */
if ( ! defined( 'EDILMETAL_SMTP_HOST' ) ) {
	define( 'EDILMETAL_SMTP_HOST', '' );
}
if ( ! defined( 'EDILMETAL_SMTP_PORT' ) ) {
	define( 'EDILMETAL_SMTP_PORT', 587 );
}
if ( ! defined( 'EDILMETAL_SMTP_USER' ) ) {
	define( 'EDILMETAL_SMTP_USER', '' );
}
if ( ! defined( 'EDILMETAL_SMTP_PASS' ) ) {
	define( 'EDILMETAL_SMTP_PASS', '' );
}
if ( ! defined( 'EDILMETAL_SMTP_FROM' ) ) {
	define( 'EDILMETAL_SMTP_FROM', 'no-reply@example.com' );
}
