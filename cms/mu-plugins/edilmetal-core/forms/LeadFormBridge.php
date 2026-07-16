<?php
/**
 * Bridge lead -> Fluent Forms: ad ogni lead creato inserisce una submission
 * nel form "Richieste Edilmetal" cosi da alimentare Entries e notifiche.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Forms;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Traduce i dati del lead nei name dei campi del form e li invia al gateway.
 */
final class LeadFormBridge {

	/**
	 * Gateway verso Fluent Forms.
	 *
	 * @var FluentFormsGateway
	 */
	private FluentFormsGateway $gateway;

	/**
	 * Provisioner del form dei lead.
	 *
	 * @var LeadFormProvisioner
	 */
	private LeadFormProvisioner $provisioner;

	/**
	 * Inietta gateway e provisioner.
	 *
	 * @param FluentFormsGateway  $gateway     Gateway Fluent Forms.
	 * @param LeadFormProvisioner $provisioner Provisioner del form.
	 */
	public function __construct( FluentFormsGateway $gateway, LeadFormProvisioner $provisioner ) {
		$this->gateway     = $gateway;
		$this->provisioner = $provisioner;
	}

	/**
	 * Inoltra un lead a Fluent Forms. Non blocca mai il flusso: in caso di
	 * problemi il lead resta salvato come CPT e l'errore viene solo loggato.
	 *
	 * @param int                 $lead_id ID del lead (CPT).
	 * @param array<string,mixed> $data    Dati sanificati del lead.
	 * @return int|WP_Error ID della submission, oppure errore (degrado silenzioso).
	 */
	public function forward( int $lead_id, array $data ) {
		if ( ! $this->gateway->is_available() ) {
			return new WP_Error( 'edilmetal_ff_unavailable', 'Fluent Forms non attivo.' );
		}

		$form_id = $this->provisioner->ensure_form();

		if ( $form_id <= 0 ) {
			return new WP_Error( 'edilmetal_ff_no_form', 'Form dei lead non disponibile.' );
		}

		$result = $this->gateway->insert_submission( $form_id, $this->map( $data ) );

		if ( $result instanceof WP_Error && function_exists( 'error_log' ) ) {
			error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- log di degrado non bloccante.
				sprintf(
					'[edilmetal-core] Lead %d non inoltrato a Fluent Forms: %s',
					$lead_id,
					$result->get_error_message()
				)
			);
		}

		return $result;
	}

	/**
	 * Mappa i dati del lead sui name dei campi del form Fluent Forms.
	 *
	 * @param array<string,mixed> $data Dati sanificati del lead.
	 * @return array<string,string>
	 */
	private function map( array $data ): array {
		return array(
			'nome'      => $this->value( $data, 'nome' ),
			'cognome'   => $this->value( $data, 'cognome' ),
			'email'     => $this->value( $data, 'email' ),
			'telefono'  => $this->value( $data, 'telefono' ),
			'tipo'      => $this->first( $data, array( 'tipo', 'oggetto' ) ),
			'fonte'     => $this->first( $data, array( 'fonte', 'progetto_slug' ) ),
			'messaggio' => $this->value( $data, 'messaggio' ),
			'consenso'  => ! empty( $data['consenso'] )
				? __( 'Prestato', 'edilmetal-core' )
				: __( 'Non prestato', 'edilmetal-core' ),
		);
	}

	/**
	 * Estrae un valore stringa da una chiave.
	 *
	 * @param array<string,mixed> $data Dati.
	 * @param string              $key  Chiave.
	 */
	private function value( array $data, string $key ): string {
		return isset( $data[ $key ] ) ? (string) $data[ $key ] : '';
	}

	/**
	 * Restituisce il primo valore non vuoto tra piu chiavi candidate.
	 *
	 * @param array<string,mixed> $data Dati.
	 * @param string[]            $keys Chiavi in ordine di priorita.
	 */
	private function first( array $data, array $keys ): string {
		foreach ( $keys as $key ) {
			$value = $this->value( $data, $key );

			if ( '' !== $value ) {
				return $value;
			}
		}

		return '';
	}
}
