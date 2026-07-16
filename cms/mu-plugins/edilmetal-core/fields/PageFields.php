<?php
/**
 * Schema dei campi Meta Box per le pagine editoriali (home, servizi, azienda,
 * contatti, legali).
 *
 * Ogni pagina "chiave" (slug) ha un proprio meta box. I box restano registrati
 * su tutte le pagine per garantire il salvataggio; il front-end legge solo il
 * box pertinente alla pagina richiesta via /pages/{key}.
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
	 * Restituisce i meta box delle pagine editoriali.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function meta_boxes(): array {
		return array(
			$this->home_box(),
			$this->servizi_box(),
			$this->azienda_box(),
			$this->contatti_box(),
			$this->privacy_box(),
			$this->cookie_box(),
		);
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
	 * Homepage: hero, statistiche, intro e SEO.
	 *
	 * @return array<string,mixed>
	 */
	private function home_box(): array {
		$fields = array(
			$this->text( 'edilmetal_home_hero_eyebrow', __( 'Hero — Eyebrow', 'edilmetal-core' ) ),
			$this->text( 'edilmetal_home_hero_titolo', __( 'Hero — Titolo', 'edilmetal-core' ) ),
			$this->text( 'edilmetal_home_hero_titolo_accent', __( 'Hero — Titolo (accento)', 'edilmetal-core' ) ),
			$this->textarea( 'edilmetal_home_hero_sottotitolo', __( 'Hero — Sottotitolo', 'edilmetal-core' ) ),
			$this->text( 'edilmetal_home_hero_cta_label', __( 'Hero — CTA (testo)', 'edilmetal-core' ) ),
			$this->url( 'edilmetal_home_hero_cta_url', __( 'Hero — CTA (URL)', 'edilmetal-core' ) ),
			$this->image( 'edilmetal_home_hero_img', __( 'Hero — Immagine', 'edilmetal-core' ) ),

			$this->repeater_text( 'edilmetal_home_stats', __( 'Statistiche (formato "valore|etichetta")', 'edilmetal-core' ) ),

			$this->text( 'edilmetal_home_intro_titolo', __( 'Intro — Titolo', 'edilmetal-core' ) ),
			$this->textarea( 'edilmetal_home_intro_testo', __( 'Intro — Testo', 'edilmetal-core' ) ),
		);

		$fields = array_merge( $fields, $this->seo_fields( 'edilmetal_home' ) );

		return $this->box( 'edilmetal_page_home', __( 'Contenuti: Homepage', 'edilmetal-core' ), $fields );
	}

	/**
	 * Pagina Servizi / Cosa facciamo: hero, intro, processo, tipologie di opere,
	 * callout e SEO.
	 *
	 * @return array<string,mixed>
	 */
	private function servizi_box(): array {
		$fields = array(
			$this->text( 'edilmetal_servizi_hero_eyebrow', __( 'Hero — Eyebrow', 'edilmetal-core' ) ),
			$this->text( 'edilmetal_servizi_titolo', __( 'Hero — Titolo', 'edilmetal-core' ) ),
			$this->textarea( 'edilmetal_servizi_sottotitolo', __( 'Hero — Sottotitolo', 'edilmetal-core' ) ),
			$this->text( 'edilmetal_servizi_intro_titolo', __( 'Intro — Titolo', 'edilmetal-core' ) ),
			$this->textarea( 'edilmetal_servizi_intro_testo', __( 'Intro — Testo', 'edilmetal-core' ) ),
			$this->repeater_text( 'edilmetal_servizi_flow', __( 'Processo (formato "titolo|testo")', 'edilmetal-core' ) ),
			$this->repeater_text( 'edilmetal_servizi_tipologie', __( 'Tipologie di opere (formato "titolo|testo")', 'edilmetal-core' ) ),
			$this->text( 'edilmetal_servizi_callout_titolo', __( 'Callout — Titolo', 'edilmetal-core' ) ),
			$this->textarea( 'edilmetal_servizi_callout_testo', __( 'Callout — Testo', 'edilmetal-core' ) ),
			$this->text( 'edilmetal_servizi_callout_cta_label', __( 'Callout — CTA (testo)', 'edilmetal-core' ) ),
			$this->url( 'edilmetal_servizi_callout_cta_url', __( 'Callout — CTA (URL)', 'edilmetal-core' ) ),
		);

		$fields = array_merge( $fields, $this->seo_fields( 'edilmetal_servizi' ) );

		return $this->box( 'edilmetal_page_servizi', __( 'Contenuti: Servizi', 'edilmetal-core' ), $fields );
	}

	/**
	 * Pagina Azienda (Chi siamo / Dove siamo): hero, storia, valori, team,
	 * statistiche e SEO. Sede, mappa e orari arrivano dalle impostazioni globali.
	 *
	 * @return array<string,mixed>
	 */
	private function azienda_box(): array {
		$fields = array(
			$this->text( 'edilmetal_azienda_hero_eyebrow', __( 'Hero — Eyebrow', 'edilmetal-core' ) ),
			$this->text( 'edilmetal_azienda_titolo', __( 'Hero — Titolo', 'edilmetal-core' ) ),
			$this->textarea( 'edilmetal_azienda_sottotitolo', __( 'Hero — Sottotitolo', 'edilmetal-core' ) ),
			$this->wysiwyg( 'edilmetal_azienda_storia', __( 'Storia aziendale', 'edilmetal-core' ) ),
			$this->image( 'edilmetal_azienda_img', __( 'Immagine sede / team', 'edilmetal-core' ) ),
			$this->repeater_text( 'edilmetal_azienda_valori', __( 'Valori (formato "titolo|testo")', 'edilmetal-core' ) ),
			$this->repeater_text( 'edilmetal_azienda_team', __( 'Team (formato "nome|ruolo")', 'edilmetal-core' ) ),
			$this->repeater_text( 'edilmetal_azienda_stats', __( 'Statistiche (formato "valore|etichetta")', 'edilmetal-core' ) ),
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
