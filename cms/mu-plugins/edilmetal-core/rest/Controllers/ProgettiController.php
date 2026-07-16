<?php
/**
 * Controller REST per le realizzazioni: lista e dettaglio.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Rest\Controllers;

use Edilmetal\Core\Rest\Presenters\ProgettoPresenter;
use Edilmetal\Core\Rest\Repositories\ProgettoRepository;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Espone GET /progetti e GET /progetti/{slug}.
 */
final class ProgettiController extends Controller {

	/**
	 * Repository progetti.
	 *
	 * @var ProgettoRepository
	 */
	private ProgettoRepository $repository;

	/**
	 * Presenter progetti.
	 *
	 * @var ProgettoPresenter
	 */
	private ProgettoPresenter $presenter;

	/**
	 * Inietta repository e presenter delle realizzazioni.
	 *
	 * @param ProgettoRepository $repository Repository progetti.
	 * @param ProgettoPresenter  $presenter  Presenter progetti.
	 */
	public function __construct( ProgettoRepository $repository, ProgettoPresenter $presenter ) {
		$this->repository = $repository;
		$this->presenter  = $presenter;
	}

	/**
	 * {@inheritDoc}
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace(),
			'/progetti',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'public_access' ),
				'args'                => array(
					'lang'       => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
					'categoria'  => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_title',
					),
					'settore'    => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_title',
					),
					'anno'       => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'inEvidenza' => array(
						'type'              => 'boolean',
						'sanitize_callback' => 'rest_sanitize_boolean',
					),
				),
			)
		);

		register_rest_route(
			$this->namespace(),
			'/progetti/(?P<slug>[a-z0-9-]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'public_access' ),
				'args'                => array(
					'slug' => array(
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
	 * GET /progetti — lista DTO ridotti (ProgettoSummary).
	 *
	 * @param WP_REST_Request $request Richiesta.
	 */
	public function get_items( WP_REST_Request $request ): WP_REST_Response {
		$progetti = $this->repository->find_all( $this->lang( $request ), $this->filters( $request ) );

		$data = array_map(
			fn ( $post ): array => $this->presenter->to_summary( $post ),
			$progetti
		);

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * GET /progetti/{slug} — DTO completo (Progetto).
	 *
	 * @param WP_REST_Request $request Richiesta.
	 */
	public function get_item( WP_REST_Request $request ): WP_REST_Response {
		$slug     = (string) $request->get_param( 'slug' );
		$progetto = $this->repository->find_by_slug( $slug, $this->lang( $request ) );

		if ( null === $progetto ) {
			return new WP_REST_Response(
				array( 'message' => __( 'Realizzazione non trovata.', 'edilmetal-core' ) ),
				404
			);
		}

		return new WP_REST_Response( $this->presenter->to_detail( $progetto ), 200 );
	}

	/**
	 * Estrae i filtri opzionali dalla richiesta.
	 *
	 * @param WP_REST_Request $request Richiesta.
	 * @return array<string,mixed>
	 */
	private function filters( WP_REST_Request $request ): array {
		$filters = array();

		$categoria = (string) $request->get_param( 'categoria' );
		if ( '' !== $categoria ) {
			$filters['categoria'] = $categoria;
		}

		$settore = (string) $request->get_param( 'settore' );
		if ( '' !== $settore ) {
			$filters['settore'] = $settore;
		}

		$anno = (int) $request->get_param( 'anno' );
		if ( $anno > 0 ) {
			$filters['anno'] = $anno;
		}

		if ( null !== $request->get_param( 'inEvidenza' ) ) {
			$filters['inEvidenza'] = rest_sanitize_boolean( $request->get_param( 'inEvidenza' ) );
		}

		return $filters;
	}
}
