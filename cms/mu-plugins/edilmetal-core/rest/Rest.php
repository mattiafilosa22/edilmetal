<?php
/**
 * Modulo REST: compone repository/presenter/controller e registra le rotte.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Rest;

use Edilmetal\Core\Module;
use Edilmetal\Core\Rest\Controllers\Controller;
use Edilmetal\Core\Rest\Controllers\LeadController;
use Edilmetal\Core\Rest\Controllers\PagesController;
use Edilmetal\Core\Rest\Controllers\ProgettiController;
use Edilmetal\Core\Rest\Controllers\SettingsController;
use Edilmetal\Core\Rest\Presenters\PagePresenter;
use Edilmetal\Core\Rest\Presenters\ProgettoPresenter;
use Edilmetal\Core\Rest\Presenters\SettingsPresenter;
use Edilmetal\Core\Rest\Repositories\LeadRepository;
use Edilmetal\Core\Rest\Repositories\PageRepository;
use Edilmetal\Core\Rest\Repositories\ProgettoRepository;
use Edilmetal\Core\Rest\Repositories\SettingsRepository;
use Edilmetal\Core\Rest\Support\ImageTransformer;

defined( 'ABSPATH' ) || exit;

/**
 * Radice di composizione del layer API (namespace edilmetal/v1).
 */
final class Rest implements Module {

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Istanzia i controller con le loro dipendenze e ne registra le rotte.
	 */
	public function register_routes(): void {
		foreach ( $this->controllers() as $controller ) {
			$controller->register_routes();
		}
	}

	/**
	 * Costruisce i controller iniettando repository e presenter.
	 *
	 * @return Controller[]
	 */
	private function controllers(): array {
		$images = new ImageTransformer();

		return array(
			new ProgettiController(
				new ProgettoRepository(),
				new ProgettoPresenter( $images )
			),
			new PagesController(
				new PageRepository(),
				new PagePresenter( $images )
			),
			new SettingsController(
				new SettingsRepository(),
				new SettingsPresenter( $images )
			),
			new LeadController(
				new LeadRepository()
			),
		);
	}
}
