<?php
/**
 * Controller REST per le impostazioni globali.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Rest\Controllers;

use Edilmetal\Core\Rest\Presenters\SettingsPresenter;
use Edilmetal\Core\Rest\Repositories\SettingsRepository;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Espone GET /settings.
 */
final class SettingsController extends Controller {

	/**
	 * Repository impostazioni.
	 *
	 * @var SettingsRepository
	 */
	private SettingsRepository $repository;

	/**
	 * Presenter impostazioni.
	 *
	 * @var SettingsPresenter
	 */
	private SettingsPresenter $presenter;

	/**
	 * Inietta repository e presenter delle impostazioni.
	 *
	 * @param SettingsRepository $repository Repository impostazioni.
	 * @param SettingsPresenter  $presenter  Presenter impostazioni.
	 */
	public function __construct( SettingsRepository $repository, SettingsPresenter $presenter ) {
		$this->repository = $repository;
		$this->presenter  = $presenter;
	}

	/**
	 * {@inheritDoc}
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace(),
			'/settings',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'public_access' ),
				'args'                => array(
					'lang' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);
	}

	/**
	 * GET /settings — dati globali del sito.
	 *
	 * @param WP_REST_Request $request Richiesta.
	 */
	public function get_item( WP_REST_Request $request ): WP_REST_Response {
		$page = $this->repository->find( $this->lang( $request ) );

		return new WP_REST_Response( $this->presenter->to_dto( $page ), 200 );
	}
}
