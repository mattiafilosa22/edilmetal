<?php
/**
 * Controller REST per le pagine editoriali.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Rest\Controllers;

use Edilmetal\Core\Rest\Presenters\PagePresenter;
use Edilmetal\Core\Rest\Repositories\PageRepository;
use Edilmetal\Core\Support\Schema;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Espone GET /pages/{key}.
 */
final class PagesController extends Controller {

	/**
	 * Repository pagine.
	 *
	 * @var PageRepository
	 */
	private PageRepository $repository;

	/**
	 * Presenter pagine.
	 *
	 * @var PagePresenter
	 */
	private PagePresenter $presenter;

	/**
	 * Inietta repository e presenter delle pagine.
	 *
	 * @param PageRepository $repository Repository pagine.
	 * @param PagePresenter  $presenter  Presenter pagine.
	 */
	public function __construct( PageRepository $repository, PagePresenter $presenter ) {
		$this->repository = $repository;
		$this->presenter  = $presenter;
	}

	/**
	 * {@inheritDoc}
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace(),
			'/pages/(?P<key>[a-z0-9-]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'public_access' ),
				'args'                => array(
					'key'  => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_title',
						'required'          => true,
					),
					'lang' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);
	}

	/**
	 * GET /pages/{key} — blocchi editoriali tipizzati.
	 *
	 * @param WP_REST_Request $request Richiesta.
	 */
	public function get_item( WP_REST_Request $request ): WP_REST_Response {
		$key = (string) $request->get_param( 'key' );

		if ( ! in_array( $key, Schema::PAGE_KEYS, true ) ) {
			return new WP_REST_Response(
				array( 'message' => __( 'Pagina non riconosciuta.', 'edilmetal-core' ) ),
				404
			);
		}

		$page = $this->repository->find_by_key( $key, $this->lang( $request ) );

		if ( null === $page ) {
			return new WP_REST_Response(
				array( 'message' => __( 'Pagina non trovata.', 'edilmetal-core' ) ),
				404
			);
		}

		return new WP_REST_Response( $this->presenter->to_dto( $page, $key ), 200 );
	}
}
