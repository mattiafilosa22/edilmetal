<?php
/**
 * Schema dei campi Meta Box per le impostazioni globali del sito.
 *
 * Sono ospitati sulla pagina con slug definito da Schema::SETTINGS_PAGE_SLUG e
 * letti dal front-end tramite l'endpoint /settings.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Definisce contatti, orari, social e testi legali globali.
 */
final class SettingsFields {

	/**
	 * Restituisce il meta box delle impostazioni globali.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function meta_boxes(): array {
		return array(
			array(
				'id'         => 'edilmetal_settings',
				'title'      => __( 'Impostazioni globali sito', 'edilmetal-core' ),
				'post_types' => array( 'page' ),
				'context'    => 'normal',
				'priority'   => 'default',
				'fields'     => array(
					$this->heading( __( 'Anagrafica', 'edilmetal-core' ) ),
					$this->text( 'edilmetal_set_nome_azienda', __( 'Nome azienda (brand)', 'edilmetal-core' ) ),
					$this->text( 'edilmetal_set_ragione_sociale', __( 'Ragione sociale', 'edilmetal-core' ) ),

					$this->heading( __( 'Contatti', 'edilmetal-core' ) ),
					$this->textarea( 'edilmetal_set_indirizzo', __( 'Indirizzo', 'edilmetal-core' ) ),
					$this->text( 'edilmetal_set_telefono', __( 'Telefono', 'edilmetal-core' ) ),
					array(
						'id'   => 'edilmetal_set_email',
						'name' => __( 'Email', 'edilmetal-core' ),
						'type' => 'email',
					),
					$this->text( 'edilmetal_set_piva', __( 'Partita IVA', 'edilmetal-core' ) ),
					$this->text( 'edilmetal_set_rea', __( 'REA', 'edilmetal-core' ) ),
					$this->url( 'edilmetal_set_maps_url', __( 'URL Google Maps', 'edilmetal-core' ) ),
					$this->text( 'edilmetal_set_map_lat', __( 'Mappa — Latitudine (Leaflet)', 'edilmetal-core' ) ),
					$this->text( 'edilmetal_set_map_lng', __( 'Mappa — Longitudine (Leaflet)', 'edilmetal-core' ) ),

					$this->heading( __( 'Orari', 'edilmetal-core' ) ),
					$this->textarea( 'edilmetal_set_orari', __( 'Orari (una fascia per riga, formato "Giorni: Apertura")', 'edilmetal-core' ) ),

					$this->heading( __( 'Social', 'edilmetal-core' ) ),
					$this->url( 'edilmetal_set_facebook', __( 'Facebook (URL)', 'edilmetal-core' ) ),
					$this->url( 'edilmetal_set_instagram', __( 'Instagram (URL)', 'edilmetal-core' ) ),
					$this->url( 'edilmetal_set_linkedin', __( 'LinkedIn (URL)', 'edilmetal-core' ) ),

					$this->heading( __( 'Hero e credito foto', 'edilmetal-core' ) ),
					$this->image( 'edilmetal_set_hero_image', __( 'Immagine hero (home)', 'edilmetal-core' ) ),
					$this->textarea( 'edilmetal_set_foto_credit', __( 'Credito foto (hero e footer)', 'edilmetal-core' ) ),

					$this->heading( __( 'Footer e testi legali', 'edilmetal-core' ) ),
					$this->text( 'edilmetal_set_slogan', __( 'Slogan', 'edilmetal-core' ) ),
					$this->text( 'edilmetal_set_copyright', __( 'Testo copyright', 'edilmetal-core' ) ),
					$this->url( 'edilmetal_set_privacy_url', __( 'Privacy policy (URL)', 'edilmetal-core' ) ),
					$this->url( 'edilmetal_set_cookie_url', __( 'Cookie policy (URL)', 'edilmetal-core' ) ),
				),
			),
		);
	}

	/**
	 * Intestazione visiva (divider) tra gruppi di campi.
	 *
	 * @param string $label Testo dell'intestazione.
	 * @return array<string,string>
	 */
	private function heading( string $label ): array {
		return array(
			'type' => 'heading',
			'name' => $label,
		);
	}

	/**
	 * Campo testo.
	 *
	 * @param string $id   Meta key.
	 * @param string $name Etichetta.
	 * @return array<string,string>
	 */
	private function text( string $id, string $name ): array {
		return array(
			'id'   => $id,
			'name' => $name,
			'type' => 'text',
		);
	}

	/**
	 * Campo textarea.
	 *
	 * @param string $id   Meta key.
	 * @param string $name Etichetta.
	 * @return array<string,string>
	 */
	private function textarea( string $id, string $name ): array {
		return array(
			'id'   => $id,
			'name' => $name,
			'type' => 'textarea',
		);
	}

	/**
	 * Campo immagine singola (attachment ID).
	 *
	 * @param string $id   Meta key.
	 * @param string $name Etichetta.
	 * @return array<string,mixed>
	 */
	private function image( string $id, string $name ): array {
		return array(
			'id'               => $id,
			'name'             => $name,
			'type'             => 'single_image',
			'max_file_uploads' => 1,
		);
	}

	/**
	 * Campo URL.
	 *
	 * @param string $id   Meta key.
	 * @param string $name Etichetta.
	 * @return array<string,string>
	 */
	private function url( string $id, string $name ): array {
		return array(
			'id'   => $id,
			'name' => $name,
			'type' => 'url',
		);
	}
}
