<?php
/**
 * Notifica email alla creazione di un lead.
 *
 * Il wiring definitivo (template, SMTP, Fluent Forms) e un task successivo:
 * qui viene fornita una notifica base agganciata all'evento edilmetal_lead_created.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Mail;

use Edilmetal\Core\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Invia una notifica al destinatario configurato quando arriva un lead.
 */
final class LeadNotifier implements Module {

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'edilmetal_lead_created', array( $this, 'notify' ), 10, 2 );
	}

	/**
	 * Invia l'email di notifica.
	 *
	 * @param int                 $lead_id ID del lead (CPT).
	 * @param array<string,mixed> $data    Dati sanificati del lead.
	 */
	public function notify( int $lead_id, array $data ): void {
		/**
		 * Consente di disattivare la mail legacy quando un'altra sorgente
		 * (es. Fluent Forms) diventa l'unico canale di notifica dei lead.
		 *
		 * @param bool                $enabled Se inviare la mail legacy.
		 * @param int                 $lead_id ID del lead.
		 * @param array<string,mixed> $data    Dati del lead.
		 */
		if ( ! apply_filters( 'edilmetal_lead_send_wp_mail', true, $lead_id, $data ) ) {
			return;
		}

		$recipient = $this->recipient();

		if ( '' === $recipient ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: oggetto/tipo del lead. */
			__( 'Nuovo contatto dal sito: %s', 'edilmetal-core' ),
			isset( $data['oggetto'] ) ? (string) $data['oggetto'] : __( 'richiesta', 'edilmetal-core' )
		);

		$lines = array(
			sprintf( '%s: %s', __( 'Nome', 'edilmetal-core' ), (string) ( $data['nome'] ?? '' ) ),
			sprintf( '%s: %s', __( 'Email', 'edilmetal-core' ), (string) ( $data['email'] ?? '' ) ),
			sprintf( '%s: %s', __( 'Telefono', 'edilmetal-core' ), (string) ( $data['telefono'] ?? '' ) ),
			sprintf( '%s: %s', __( 'Messaggio', 'edilmetal-core' ), (string) ( $data['messaggio'] ?? '' ) ),
			sprintf( '%s: %d', __( 'ID lead', 'edilmetal-core' ), $lead_id ),
		);

		wp_mail(
			$recipient,
			$subject,
			implode( "\n", $lines )
		);
	}

	/**
	 * Determina il destinatario della notifica.
	 */
	private function recipient(): string {
		/**
		 * Filtra l'indirizzo email che riceve le notifiche dei lead.
		 *
		 * @param string $recipient Email destinataria (default: admin del sito).
		 */
		$recipient = apply_filters( 'edilmetal_lead_recipient', get_option( 'admin_email' ) );

		return is_string( $recipient ) ? sanitize_email( $recipient ) : '';
	}
}
