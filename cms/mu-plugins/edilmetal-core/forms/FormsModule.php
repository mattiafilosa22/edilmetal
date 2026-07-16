<?php
/**
 * Modulo Forms: integra i lead con Fluent Forms (free).
 *
 * Radice di composizione della feature: costruisce gateway/provisioner/bridge
 * e, quando Fluent Forms e attivo, aggancia il bridge all'evento
 * edilmetal_lead_created ed elegge Fluent Forms a UNICA sorgente di notifica
 * (disattivando la mail legacy di LeadNotifier). Se Fluent Forms non e attivo
 * il comportamento resta invariato: lead CPT + notifica wp_mail.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Forms;

use Edilmetal\Core\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Compone e registra l'integrazione Fluent Forms dei lead.
 */
final class FormsModule implements Module {

	/**
	 * Gateway verso Fluent Forms.
	 *
	 * @var FluentFormsGateway
	 */
	private FluentFormsGateway $gateway;

	/**
	 * Bridge lead -> Fluent Forms.
	 *
	 * @var LeadFormBridge
	 */
	private LeadFormBridge $bridge;

	/**
	 * Compone le dipendenze del modulo.
	 */
	public function __construct() {
		$this->gateway = new FluentFormsGateway();
		$provisioner   = new LeadFormProvisioner( $this->gateway );
		$this->bridge  = new LeadFormBridge( $this->gateway, $provisioner );
	}

	/**
	 * {@inheritDoc}
	 *
	 * Il wiring e differito a plugins_loaded: i mu-plugin vengono caricati prima
	 * dei plugin normali, quindi Fluent Forms non e ancora disponibile al boot.
	 */
	public function register(): void {
		add_action( 'plugins_loaded', array( $this, 'boot' ), 20 );
	}

	/**
	 * Aggancia il bridge quando Fluent Forms risulta attivo.
	 *
	 * Degrado: senza Fluent Forms non si aggancia nulla e resta la mail legacy.
	 */
	public function boot(): void {
		if ( ! $this->gateway->is_available() ) {
			return;
		}

		add_action( 'edilmetal_lead_created', array( $this->bridge, 'forward' ), 20, 2 );

		// Fluent Forms diventa l'unica sorgente di notifica: spegne la mail legacy.
		add_filter( 'edilmetal_lead_send_wp_mail', '__return_false' );
	}
}
