<?php
/**
 * Schema dei campi Meta Box per le pagine editoriali (home, servizi, azienda,
 * contatti, legali).
 *
 * Ogni pagina "chiave" (slug) ha un proprio meta box, mostrato SOLO quando si
 * modifica quella pagina: aprendo "Azienda" si vede solo il box "Azienda",
 * non anche quelli di Home/Servizi/Contatti/legali vuoti e non pertinenti.
 * Fuori da uno schermo di modifica di una singola pagina (nuova pagina,
 * azioni di massa, ...) restano tutti registrati per non rompere il salvataggio.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Definisce i blocchi editoriali tipizzati delle pagine.
 */
final class PageFields {

	/**
	 * Restituisce solo il meta box della pagina in modifica (per slug), o
	 * tutti quando lo slug non e determinabile (nuova pagina, bulk, REST).
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function meta_boxes(): array {
		$builders = array(
			'home'           => fn() => $this->home_box(),
			'servizi'        => fn() => $this->servizi_box(),
			'azienda'        => fn() => $this->azienda_box(),
			'contatti'       => fn() => $this->contatti_box(),
			'privacy-policy' => fn() => $this->privacy_box(),
			'cookie-policy'  => fn() => $this->cookie_box(),
		);

		$slug = $this->editing_page_slug();

		if ( null === $slug ) {
			return array_map( static fn( callable $build ) => $build(), array_values( $builders ) );
		}

		return isset( $builders[ $slug ] ) ? array( $builders[ $slug ]() ) : array();
	}

	/**
	 * Slug della pagina attualmente in modifica in wp-admin, o null se non
	 * si e in uno schermo di modifica di una singola pagina esistente.
	 */
	private function editing_page_slug(): ?string {
		$post_id = 0;

		if ( isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sola lettura, determina solo quali campi mostrare.
			$post_id = (int) $_GET['post']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( isset( $_POST['post_ID'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- sola lettura, il salvataggio vero e gestito da Meta Box/WP core con la propria nonce.
			$post_id = (int) $_POST['post_ID']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		if ( $post_id <= 0 ) {
			return null;
		}

		$post = get_post( $post_id );

		return $post instanceof \WP_Post && 'page' === $post->post_type ? $post->post_name : null;
	}

	/**
	 * Campo WYSIWYG (contenuto ricco).
	 *
	 * @param string $id   Meta key completa.
	 * @param string $name Etichetta.
	 * @return array<string,string>
	 */
	private function wysiwyg( string $id, string $name ): array {
		return array(
			'id'   => $id,
			'name' => $name,
			'type' => 'wysiwyg',
		);
	}

	/**
	 * Wrapper per un meta box legato al post type "page".
	 *
	 * @param string                         $id     Identificativo.
	 * @param string                         $title  Titolo in admin.
	 * @param array<int,array<string,mixed>> $fields Campi.
	 * @return array<string,mixed>
	 */
	private function box( string $id, string $title, array $fields ): array {
		return array(
			'id'         => $id,
			'title'      => $title,
			'post_types' => array( 'page' ),
			'context'    => 'normal',
			'priority'   => 'default',
			'fields'     => $fields,
		);
	}

	/**
	 * Campo testo semplice.
	 *
	 * @param string $id   Meta key completa.
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
	 * @param string $id   Meta key completa.
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
	 * Campo immagine singola.
	 *
	 * @param string $id   Meta key completa.
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
	 * @param string $id   Meta key completa.
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

	/**
	 * Lista clonabile di voci testuali.
	 *
	 * @param string $id   Meta key completa.
	 * @param string $name Etichetta.
	 * @return array<string,mixed>
	 */
	private function repeater_text( string $id, string $name ): array {
		return array(
			'id'         => $id,
			'name'       => $name,
			'type'       => 'text',
			'clone'      => true,
			'sort_clone' => true,
		);
	}

	/**
	 * Campi SEO editoriali comuni a una pagina (title, description, OG image).
	 *
	 * @param string $prefix Prefisso dei meta della pagina (es. "edilmetal_home").
	 * @return array<int,array<string,mixed>>
	 */
	private function seo_fields( string $prefix ): array {
		return array(
			$this->text( $prefix . '_seo_title', __( 'SEO — Title', 'edilmetal-core' ) ),
			$this->textarea( $prefix . '_seo_description', __( 'SEO — Description', 'edilmetal-core' ) ),
			$this->image( $prefix . '_seo_og', __( 'SEO — Immagine Open Graph', 'edilmetal-core' ) ),
		);
	}

	/**
	 * Homepage: hero (con CTA secondaria e barra indice), 2 slot "in evidenza"
	 * e SEO. Immagine hero e statistiche generali arrivano dalle impostazioni
	 * globali.
	 *
	 * @return array<string,mixed>
	 */
	private function home_box(): array {
		$fields = array(
			$this->text( 'edilmetal_home_hero_eyebrow', __( 'Hero — Eyebrow', 'edilmetal-core' ) ),
			$this->text( 'edilmetal_home_hero_titolo', __( 'Hero — Titolo', 'edilmetal-core' ) ),
			$this->text( 'edilmetal_home_hero_titolo_accent', __( 'Hero — Titolo (accento)', 'edilmetal-core' ) ),
			$this->textarea( 'edilmetal_home_hero_sottotitolo', __( 'Hero — Sottotitolo', 'edilmetal-core' ) ),
			$this->text( 'edilmetal_home_hero_cta_label', __( 'Hero — CTA primaria (testo)', 'edilmetal-core' ) ),
			$this->url( 'edilmetal_home_hero_cta_url', __( 'Hero — CTA primaria (URL)', 'edilmetal-core' ) ),
			$this->text( 'edilmetal_home_hero_cta2_label', __( 'Hero — CTA secondaria (testo, opzionale)', 'edilmetal-core' ) ),
			$this->url( 'edilmetal_home_hero_cta2_url', __( 'Hero — CTA secondaria (URL, opzionale)', 'edilmetal-core' ) ),
			$this->repeater_text( 'edilmetal_home_hero_index', __( 'Hero — Barra indice, max 3 voci (formato "valore|etichetta")', 'edilmetal-core' ) ),

			array(
				'id'   => 'edilmetal_home_evidenza1_categoria',
				'name' => __( 'In evidenza 1 — Slug categoria', 'edilmetal-core' ),
				'type' => 'text',
				'desc' => __( 'Uno tra: strutture-acciaio, strutture-miste, scale, pensiline, pensiline-auto, coperture-tamponamenti, rivestimenti-facciata, opere-speciali', 'edilmetal-core' ),
			),
			$this->image( 'edilmetal_home_evidenza1_img', __( 'In evidenza 1 — Immagine', 'edilmetal-core' ) ),
			array(
				'id'   => 'edilmetal_home_evidenza2_categoria',
				'name' => __( 'In evidenza 2 — Slug categoria', 'edilmetal-core' ),
				'type' => 'text',
				'desc' => __( 'Uno tra: strutture-acciaio, strutture-miste, scale, pensiline, pensiline-auto, coperture-tamponamenti, rivestimenti-facciata, opere-speciali', 'edilmetal-core' ),
			),
			$this->image( 'edilmetal_home_evidenza2_img', __( 'In evidenza 2 — Immagine', 'edilmetal-core' ) ),
		);

		$fields = array_merge( $fields, $this->seo_fields( 'edilmetal_home' ) );

		return $this->box( 'edilmetal_page_home', __( 'Contenuti: Homepage', 'edilmetal-core' ), $fields );
	}

	/**
	 * Pagina Servizi / "Prodotti": sottotitolo di testata + SEO.
	 * L'elenco delle categorie (tipologie) arriva dalla tassonomia
	 * `categoria_opera`, non da un campo separato — niente da tenere in sync.
	 *
	 * @return array<string,mixed>
	 */
	private function servizi_box(): array {
		$fields = array(
			$this->textarea( 'edilmetal_servizi_sottotitolo', __( 'Sottotitolo di testata', 'edilmetal-core' ) ),
		);

		$fields = array_merge( $fields, $this->seo_fields( 'edilmetal_servizi' ) );

		return $this->box( 'edilmetal_page_servizi', __( 'Contenuti: Servizi', 'edilmetal-core' ), $fields );
	}

	/**
	 * Pagina Azienda / "Chi siamo": sottotitolo di testata + storia + SEO.
	 * Sede, mappa e orari restano nelle impostazioni globali (non usati qui).
	 *
	 * @return array<string,mixed>
	 */
	private function azienda_box(): array {
		$fields = array(
			$this->textarea( 'edilmetal_azienda_sottotitolo', __( 'Sottotitolo di testata', 'edilmetal-core' ) ),
			$this->text( 'edilmetal_azienda_storia_titolo', __( 'Storia — Titolo (es. "La società")', 'edilmetal-core' ) ),
			$this->wysiwyg( 'edilmetal_azienda_storia', __( 'Storia aziendale (un paragrafo per blocco)', 'edilmetal-core' ) ),
		);

		$fields = array_merge( $fields, $this->seo_fields( 'edilmetal_azienda' ) );

		return $this->box( 'edilmetal_page_azienda', __( 'Contenuti: Azienda', 'edilmetal-core' ), $fields );
	}

	/**
	 * Pagina Contatti. Hero + intro; contatti, orari e mappa provengono dalle
	 * impostazioni globali.
	 *
	 * @return array<string,mixed>
	 */
	private function contatti_box(): array {
		$fields = array(
			$this->text( 'edilmetal_contatti_hero_eyebrow', __( 'Hero — Eyebrow', 'edilmetal-core' ) ),
			$this->text( 'edilmetal_contatti_titolo', __( 'Hero — Titolo', 'edilmetal-core' ) ),
			$this->textarea( 'edilmetal_contatti_sottotitolo', __( 'Hero — Sottotitolo', 'edilmetal-core' ) ),
			$this->text( 'edilmetal_contatti_intro_titolo', __( 'Intro — Titolo', 'edilmetal-core' ) ),
			$this->textarea( 'edilmetal_contatti_intro_testo', __( 'Intro — Testo', 'edilmetal-core' ) ),
		);

		$fields = array_merge( $fields, $this->seo_fields( 'edilmetal_contatti' ) );

		return $this->box( 'edilmetal_page_contatti', __( 'Contenuti: Contatti', 'edilmetal-core' ), $fields );
	}

	/**
	 * Pagina Privacy Policy (testo legale editabile).
	 *
	 * @return array<string,mixed>
	 */
	private function privacy_box(): array {
		return $this->box(
			'edilmetal_page_privacy',
			__( 'Contenuti: Privacy Policy', 'edilmetal-core' ),
			array(
				$this->wysiwyg( 'edilmetal_privacy_body', __( 'Testo privacy', 'edilmetal-core' ) ),
				$this->text( 'edilmetal_privacy_updated', __( 'Ultimo aggiornamento (AAAA-MM-GG)', 'edilmetal-core' ) ),
			)
		);
	}

	/**
	 * Pagina Cookie Policy (testo legale editabile).
	 *
	 * @return array<string,mixed>
	 */
	private function cookie_box(): array {
		return $this->box(
			'edilmetal_page_cookie',
			__( 'Contenuti: Cookie Policy', 'edilmetal-core' ),
			array(
				$this->wysiwyg( 'edilmetal_cookie_body', __( 'Testo cookie', 'edilmetal-core' ) ),
				$this->text( 'edilmetal_cookie_updated', __( 'Ultimo aggiornamento (AAAA-MM-GG)', 'edilmetal-core' ) ),
			)
		);
	}
}
