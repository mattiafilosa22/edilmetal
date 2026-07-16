<?php
/**
 * Repository: persistenza dei lead come CPT privato.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Rest\Repositories;

use Edilmetal\Core\Support\Schema;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Crea i lead come post del CPT "lead" salvandone i dati come meta.
 */
final class LeadRepository {

	/**
	 * Crea un lead dai dati gia sanificati.
	 *
	 * @param array<string,mixed> $data Dati sanificati del contatto.
	 * @return int|WP_Error ID del lead creato, oppure errore.
	 */
	public function create( array $data ) {
		$title = sprintf(
			/* translators: 1: nome del contatto, 2: oggetto della richiesta. */
			__( 'Lead: %1$s — %2$s', 'edilmetal-core' ),
			(string) ( $data['nome'] ?? '' ),
			(string) ( $data['oggetto'] ?? __( 'richiesta', 'edilmetal-core' ) )
		);

		$lead_id = wp_insert_post(
			array(
				'post_type'   => Schema::CPT_LEAD,
				'post_status' => 'private',
				'post_title'  => $title,
			),
			true
		);

		if ( is_wp_error( $lead_id ) ) {
			return $lead_id;
		}

		foreach ( $data as $key => $value ) {
			update_post_meta( $lead_id, Schema::meta( 'lead_' . $key ), $value );
		}

		return (int) $lead_id;
	}
}
