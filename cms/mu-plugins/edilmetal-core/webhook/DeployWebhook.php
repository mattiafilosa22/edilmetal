<?php
/**
 * Webhook di deploy: notifica GitHub (repository_dispatch) sui cambi contenuto.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Webhook;

use Edilmetal\Core\Module;
use Edilmetal\Core\Support\Schema;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Invia un repository_dispatch a GitHub quando realizzazioni o pagine cambiano.
 *
 * Include un debounce (transient) per evitare rebuild a raffica ed e un no-op
 * silenzioso se le costanti di configurazione non sono definite.
 */
final class DeployWebhook implements Module {

	/**
	 * Chiave del transient di debounce.
	 *
	 * @var string
	 */
	private const DEBOUNCE_KEY = 'edilmetal_deploy_debounce';

	/**
	 * Finestra di debounce in secondi.
	 *
	 * @var int
	 */
	private const DEBOUNCE_SECONDS = 120;

	/**
	 * Evento inviato a GitHub Actions.
	 *
	 * @var string
	 */
	private const EVENT_TYPE = 'wp-content-updated';

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'save_post_' . Schema::CPT_PROGETTO, array( $this, 'on_save' ), 10, 2 );
		add_action( 'save_post_page', array( $this, 'on_save' ), 10, 2 );
		add_action( 'transition_post_status', array( $this, 'on_transition' ), 10, 3 );
	}

	/**
	 * Trigger al salvataggio di realizzazioni/pagine.
	 *
	 * @param int     $post_id ID del post.
	 * @param WP_Post $post    Oggetto post.
	 */
	public function on_save( int $post_id, WP_Post $post ): void {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( 'publish' !== $post->post_status ) {
			return;
		}

		$this->dispatch();
	}

	/**
	 * Trigger alla pubblicazione (transizione verso publish).
	 *
	 * @param string  $new_status Nuovo stato.
	 * @param string  $old_status Stato precedente.
	 * @param WP_Post $post       Oggetto post.
	 */
	public function on_transition( string $new_status, string $old_status, WP_Post $post ): void {
		if ( 'publish' !== $new_status || $new_status === $old_status ) {
			return;
		}

		if ( ! in_array( $post->post_type, array( Schema::CPT_PROGETTO, 'page' ), true ) ) {
			return;
		}

		$this->dispatch();
	}

	/**
	 * Invia il dispatch a GitHub rispettando il debounce.
	 */
	private function dispatch(): void {
		if ( ! $this->is_configured() ) {
			return;
		}

		if ( false !== get_transient( self::DEBOUNCE_KEY ) ) {
			return;
		}

		set_transient( self::DEBOUNCE_KEY, time(), self::DEBOUNCE_SECONDS );

		$repo  = (string) constant( 'EDILMETAL_GH_REPO' );
		$token = (string) constant( 'EDILMETAL_GH_TOKEN' );

		wp_remote_post(
			'https://api.github.com/repos/' . $repo . '/dispatches',
			array(
				'timeout'  => 15,
				'blocking' => false,
				'headers'  => array(
					'Accept'        => 'application/vnd.github+json',
					'Authorization' => 'Bearer ' . $token,
					'Content-Type'  => 'application/json',
					'User-Agent'    => 'edilmetal-core/' . EDILMETAL_CORE_VERSION,
				),
				'body'     => wp_json_encode(
					array(
						'event_type'     => self::EVENT_TYPE,
						'client_payload' => array(
							'source' => 'wordpress',
							'time'   => time(),
						),
					)
				),
			)
		);
	}

	/**
	 * Verifica che le costanti di configurazione siano presenti e valorizzate.
	 */
	private function is_configured(): bool {
		return defined( 'EDILMETAL_GH_REPO' )
			&& defined( 'EDILMETAL_GH_TOKEN' )
			&& '' !== (string) constant( 'EDILMETAL_GH_REPO' )
			&& '' !== (string) constant( 'EDILMETAL_GH_TOKEN' );
	}
}
