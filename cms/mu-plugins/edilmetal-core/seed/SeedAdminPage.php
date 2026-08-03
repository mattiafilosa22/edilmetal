<?php
/**
 * Pagina di amministrazione per lanciare il seeding senza WP-CLI.
 *
 * Utile sugli hosting (es. Plesk senza accesso SSH/WP-CLI) dove il comando
 * "wp edilmetal seed" non e eseguibile: espone la stessa orchestrazione
 * tramite un form protetto da nonce sotto "Strumenti".
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Seed;

defined( 'ABSPATH' ) || exit;

/**
 * Registra la voce "Popola contenuti" sotto "Strumenti" e gestisce il submit.
 */
final class SeedAdminPage {

	/**
	 * Nome dell'azione admin-post.php e del nonce associato.
	 *
	 * @var string
	 */
	private const ACTION = 'edilmetal_run_seed';

	/**
	 * Nome dell'azione admin-post.php per la sola rimozione delle demo.
	 *
	 * @var string
	 */
	private const PURGE_ACTION = 'edilmetal_purge_demo_progetti';

	/**
	 * Chiave del transient usato per passare l'esito tra submit e render.
	 *
	 * @var string
	 */
	private const RESULT_TRANSIENT = 'edilmetal_seed_result';

	/**
	 * Slug della pagina in "Strumenti".
	 *
	 * @var string
	 */
	private const PAGE_SLUG = 'edilmetal-seed';

	/**
	 * Orchestratore del seeding.
	 *
	 * @var SeedCommand
	 */
	private SeedCommand $command;

	/**
	 * Inietta il comando di seeding.
	 *
	 * @param SeedCommand $command Orchestratore del seeding.
	 */
	public function __construct( SeedCommand $command ) {
		$this->command = $command;
	}

	/**
	 * Aggancia la voce di menu e l'handler di submit.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle_submit' ) );
		add_action( 'admin_post_' . self::PURGE_ACTION, array( $this, 'handle_purge_demo' ) );
	}

	/**
	 * Registra la voce "Popola contenuti" sotto "Strumenti".
	 */
	public function add_page(): void {
		add_management_page(
			__( 'Popola contenuti Edilmetal', 'edilmetal-core' ),
			__( 'Popola contenuti', 'edilmetal-core' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Mostra il form di lancio e, se presente, l'esito dell'ultima esecuzione.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Non hai i permessi necessari per accedere a questa pagina.', 'edilmetal-core' ) );
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Popola contenuti Edilmetal', 'edilmetal-core' ) . '</h1>';
		echo '<p>' . esc_html__( 'Popola tassonomie, realizzazioni di demo, impostazioni e pagine editoriali. Equivalente al comando "wp edilmetal seed", utile dove WP-CLI non e disponibile.', 'edilmetal-core' ) . '</p>';

		$this->render_last_result();

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( self::ACTION );
		echo '<input type="hidden" name="action" value="' . esc_attr( self::ACTION ) . '" />';
		echo '<p><label><input type="checkbox" name="fresh" value="1" /> ' . esc_html__( 'Elimina prima i contenuti di demo esistenti (--fresh)', 'edilmetal-core' ) . '</label></p>';
		submit_button( __( 'Esegui seed', 'edilmetal-core' ) );
		echo '</form>';

		echo '<hr />';
		echo '<h2>' . esc_html__( 'Rimuovi realizzazioni fittizie', 'edilmetal-core' ) . '</h2>';
		echo '<p>' . esc_html__( 'Elimina solo le realizzazioni con clienti inventati (es. Parmalat, Aiassa Costruzioni, Pinko) create dalle versioni precedenti del seeder, lasciando intatte le realizzazioni storiche reali, le pagine e le impostazioni.', 'edilmetal-core' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( self::PURGE_ACTION );
		echo '<input type="hidden" name="action" value="' . esc_attr( self::PURGE_ACTION ) . '" />';
		submit_button( __( 'Rimuovi realizzazioni fittizie', 'edilmetal-core' ), 'delete' );
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Mostra l'esito dell'ultima esecuzione, se presente, e lo consuma.
	 */
	private function render_last_result(): void {
		$lines = get_transient( self::RESULT_TRANSIENT );

		if ( ! is_array( $lines ) || array() === $lines ) {
			return;
		}

		delete_transient( self::RESULT_TRANSIENT );

		echo '<div class="edilmetal-seed-result">';
		echo '<h2>' . esc_html__( 'Esito dell\'ultima esecuzione', 'edilmetal-core' ) . '</h2>';
		echo '<ul style="list-style:none;padding:0;margin:0 0 1em;">';

		foreach ( $lines as $line ) {
			$level        = isset( $line['level'] ) ? (string) $line['level'] : 'log';
			$message      = isset( $line['message'] ) ? (string) $line['message'] : '';
			$notice_class = $this->notice_class( $level );

			if ( '' !== $notice_class ) {
				echo '<li class="notice ' . esc_attr( $notice_class ) . ' inline" style="margin:4px 0;padding:4px 12px;">' . esc_html( $message ) . '</li>';
			} else {
				echo '<li style="margin:2px 0;font-family:monospace;">' . esc_html( $message ) . '</li>';
			}
		}

		echo '</ul>';
		echo '</div>';
	}

	/**
	 * Mappa il livello di una riga di log alla classe "notice" di wp-admin.
	 *
	 * @param string $level Livello ("log"|"warning"|"success").
	 */
	private function notice_class( string $level ): string {
		if ( 'warning' === $level ) {
			return 'notice-warning';
		}

		if ( 'success' === $level ) {
			return 'notice-success';
		}

		return '';
	}

	/**
	 * Esegue il seeding a partire dal submit del form e reindirizza alla pagina.
	 */
	public function handle_submit(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Non hai i permessi necessari per eseguire questa azione.', 'edilmetal-core' ) );
		}

		check_admin_referer( self::ACTION );

		$fresh = isset( $_POST['fresh'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['fresh'] ) );

		// L'import media (generazione miniature via Imagick per ~180+ foto) supera
		// facilmente il max_execution_time di default su hosting condiviso quando
		// gira dentro un'unica richiesta HTTP sincrona (a differenza di WP-CLI,
		// che non ha questo limite). Rimuoviamo il limite solo per questa richiesta.
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
		if ( function_exists( 'ini_set' ) ) {
			@ini_set( 'memory_limit', '512M' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.IniSet.Risky, WordPress.PHP.IniSet.memory_limit_Disallowed
		}

		$lines = $this->command->run( $fresh );

		set_transient( self::RESULT_TRANSIENT, $lines, 60 );

		wp_safe_redirect( admin_url( 'tools.php?page=' . self::PAGE_SLUG . '&done=1' ) );
		exit;
	}

	/**
	 * Rimuove le sole realizzazioni fittizie a partire dal submit del form.
	 */
	public function handle_purge_demo(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Non hai i permessi necessari per eseguire questa azione.', 'edilmetal-core' ) );
		}

		check_admin_referer( self::PURGE_ACTION );

		$lines = $this->command->purge_demo_progetti();

		set_transient( self::RESULT_TRANSIENT, $lines, 60 );

		wp_safe_redirect( admin_url( 'tools.php?page=' . self::PAGE_SLUG . '&done=1' ) );
		exit;
	}
}
