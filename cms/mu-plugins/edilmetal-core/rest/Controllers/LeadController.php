<?php
/**
 * Controller REST per l'invio dei lead (form contatti).
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Rest\Controllers;

use Edilmetal\Core\Rest\Repositories\LeadRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Espone POST /lead con honeypot, consenso GDPR e rate-limit via transient.
 */
final class LeadController extends Controller {

	/**
	 * Campo honeypot: se valorizzato, la richiesta e considerata spam.
	 *
	 * @var string
	 */
	private const HONEYPOT_FIELD = 'website';

	/**
	 * Finestra del rate-limit in secondi.
	 *
	 * @var int
	 */
	private const RATE_WINDOW = 600;

	/**
	 * Numero massimo di invii per IP nella finestra.
	 *
	 * @var int
	 */
	private const RATE_MAX = 5;

	/**
	 * Repository lead.
	 *
	 * @var LeadRepository
	 */
	private LeadRepository $repository;

	/**
	 * Inietta il repository dei lead.
	 *
	 * @param LeadRepository $repository Repository lead.
	 */
	public function __construct( LeadRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * {@inheritDoc}
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace(),
			'/lead',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'public_access' ),
			)
		);
	}

	/**
	 * POST /lead — valida, applica rate-limit e crea il lead.
	 *
	 * @param WP_REST_Request $request Richiesta.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( WP_REST_Request $request ) {
		// Honeypot: i bot compilano il campo nascosto.
		if ( '' !== trim( (string) $request->get_param( self::HONEYPOT_FIELD ) ) ) {
			return new WP_Error(
				'edilmetal_lead_spam',
				__( 'Richiesta non valida.', 'edilmetal-core' ),
				array( 'status' => 400 )
			);
		}

		$rate_error = $this->check_rate_limit();
		if ( $rate_error instanceof WP_Error ) {
			return $rate_error;
		}

		$data           = $this->sanitize( $request );
		$validation_err = $this->validate( $data );
		if ( $validation_err instanceof WP_Error ) {
			return $validation_err;
		}

		$lead_id = $this->repository->create( $data );
		if ( is_wp_error( $lead_id ) ) {
			return new WP_Error(
				'edilmetal_lead_failed',
				__( 'Impossibile registrare la richiesta.', 'edilmetal-core' ),
				array( 'status' => 500 )
			);
		}

		$this->register_hit();

		/**
		 * Evento emesso alla creazione di un lead (usato per le notifiche).
		 *
		 * @param int                 $lead_id ID del lead creato.
		 * @param array<string,mixed> $data    Dati sanificati.
		 */
		do_action( 'edilmetal_lead_created', $lead_id, $data );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Richiesta inviata correttamente.', 'edilmetal-core' ),
			),
			201
		);
	}

	/**
	 * Sanifica i campi in ingresso.
	 *
	 * @param WP_REST_Request $request Richiesta.
	 * @return array<string,mixed>
	 */
	private function sanitize( WP_REST_Request $request ): array {
		return array(
			'nome'          => sanitize_text_field( (string) $request->get_param( 'nome' ) ),
			'cognome'       => sanitize_text_field( (string) $request->get_param( 'cognome' ) ),
			'email'         => sanitize_email( (string) $request->get_param( 'email' ) ),
			'telefono'      => sanitize_text_field( (string) $request->get_param( 'telefono' ) ),
			'oggetto'       => sanitize_text_field( (string) $request->get_param( 'oggetto' ) ),
			'messaggio'     => sanitize_textarea_field( (string) $request->get_param( 'messaggio' ) ),
			'progetto_slug' => sanitize_title( (string) $request->get_param( 'progetto_slug' ) ),
			// Tipo richiesta e fonte/progetto: metadati usati anche dal bridge Fluent Forms.
			'tipo'          => $this->first_param( $request, array( 'tipo', 'tipoRichiesta' ) ),
			'fonte'         => $this->first_param( $request, array( 'fonte', 'progettoSlug' ) ),
			'consenso'      => $this->to_bool( $request->get_param( 'consenso' ) ),
			'marketing'     => $this->to_bool( $request->get_param( 'marketing' ) ),
		);
	}

	/**
	 * Valida i campi obbligatori e il consenso GDPR.
	 *
	 * @param array<string,mixed> $data Dati sanificati.
	 * @return WP_Error|null Errore se non valido, altrimenti null.
	 */
	private function validate( array $data ): ?WP_Error {
		if ( '' === $data['nome'] || '' === $data['messaggio'] ) {
			return new WP_Error(
				'edilmetal_lead_invalid',
				__( 'Nome e messaggio sono obbligatori.', 'edilmetal-core' ),
				array( 'status' => 422 )
			);
		}

		if ( '' === $data['email'] || ! is_email( $data['email'] ) ) {
			return new WP_Error(
				'edilmetal_lead_invalid_email',
				__( 'Email non valida.', 'edilmetal-core' ),
				array( 'status' => 422 )
			);
		}

		if ( true !== $data['consenso'] ) {
			return new WP_Error(
				'edilmetal_lead_consent',
				__( 'Il consenso al trattamento dei dati e obbligatorio.', 'edilmetal-core' ),
				array( 'status' => 422 )
			);
		}

		return null;
	}

	/**
	 * Verifica il rate-limit per IP.
	 *
	 * @return WP_Error|null Errore 429 se superato, altrimenti null.
	 */
	private function check_rate_limit(): ?WP_Error {
		$count = (int) get_transient( $this->rate_key() );

		if ( $count >= self::RATE_MAX ) {
			return new WP_Error(
				'edilmetal_lead_rate_limited',
				__( 'Troppe richieste. Riprova piu tardi.', 'edilmetal-core' ),
				array( 'status' => 429 )
			);
		}

		return null;
	}

	/**
	 * Registra un invio andato a buon fine ai fini del rate-limit.
	 */
	private function register_hit(): void {
		$key   = $this->rate_key();
		$count = (int) get_transient( $key );
		set_transient( $key, $count + 1, self::RATE_WINDOW );
	}

	/**
	 * Chiave transient del rate-limit basata sull'IP del client.
	 */
	private function rate_key(): string {
		return 'edilmetal_lead_rl_' . md5( $this->client_ip() );
	}

	/**
	 * IP del client, normalizzato.
	 */
	private function client_ip(): string {
		$raw = isset( $_SERVER['REMOTE_ADDR'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
			: '';

		$ip = filter_var( $raw, FILTER_VALIDATE_IP );

		return false === $ip ? 'unknown' : $ip;
	}

	/**
	 * Converte un valore grezzo in booleano.
	 *
	 * @param mixed $value Valore ricevuto.
	 */
	private function to_bool( $value ): bool {
		return in_array( $value, array( true, 'true', '1', 1, 'on', 'yes' ), true );
	}

	/**
	 * Primo parametro non vuoto tra piu alias (supporta snake_case e camelCase).
	 *
	 * @param WP_REST_Request $request Richiesta.
	 * @param string[]        $names   Nomi candidati in ordine di priorita.
	 */
	private function first_param( WP_REST_Request $request, array $names ): string {
		foreach ( $names as $name ) {
			$value = sanitize_text_field( (string) $request->get_param( $name ) );

			if ( '' !== $value ) {
				return $value;
			}
		}

		return '';
	}
}
