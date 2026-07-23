<?php
/**
 * Bootstrap principale del plugin: compone e avvia i moduli.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core;

use Edilmetal\Core\Branding\Branding;
use Edilmetal\Core\Fields\Fields;
use Edilmetal\Core\Forms\FormsModule;
use Edilmetal\Core\Mail\LeadNotifier;
use Edilmetal\Core\PostTypes\PostTypes;
use Edilmetal\Core\Rest\Rest;
use Edilmetal\Core\Seed\SeedModule;
use Edilmetal\Core\Security\Security;
use Edilmetal\Core\Webhook\DeployWebhook;

defined( 'ABSPATH' ) || exit;

/**
 * Contenitore di composizione (composition root) del plugin.
 *
 * Istanzia i moduli una sola volta e ne invoca la registrazione degli hook.
 */
final class Plugin {

	/**
	 * Istanza singleton.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Moduli registrati.
	 *
	 * @var Module[]
	 */
	private array $modules = array();

	/**
	 * Indica se il plugin e stato avviato.
	 *
	 * @var bool
	 */
	private bool $booted = false;

	/**
	 * Costruttore privato: usare instance().
	 */
	private function __construct() {}

	/**
	 * Restituisce l'istanza singleton.
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Avvia il plugin registrando tutti i moduli (idempotente).
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		$this->modules = array(
			new PostTypes(),
			new Fields(),
			new Rest(),
			new Security(),
			new Branding(),
			new DeployWebhook(),
			new LeadNotifier(),
			new FormsModule(),
			new SeedModule(),
		);

		foreach ( $this->modules as $module ) {
			$module->register();
		}

		add_action( 'plugins_loaded', array( Installer::class, 'maybe_install' ) );

		$this->booted = true;
	}
}
