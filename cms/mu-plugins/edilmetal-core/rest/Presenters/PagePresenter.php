<?php
/**
 * Presenter: trasforma una pagina editoriale nel DTO PageContent del front-end.
 *
 * Il contratto canonico e definito in docs/api-contract.md. Ogni pagina chiave
 * (home|servizi|azienda|contatti) espone blocchi tipizzati; le pagine legali
 * espongono il corpo sanificato. Tutte possono avere meta SEO editoriali.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Rest\Presenters;

use Edilmetal\Core\Rest\Support\ImageTransformer;
use Edilmetal\Core\Rest\Support\MetaReader;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Costruisce il contenuto editoriale tipizzato di ciascuna pagina chiave.
 */
final class PagePresenter {

	/**
	 * Trasformatore immagini, per l'immagine Open Graph editabile.
	 *
	 * @var ImageTransformer
	 */
	private ImageTransformer $images;

	/**
	 * Lettore meta della pagina corrente.
	 *
	 * @var MetaReader
	 */
	private MetaReader $meta;

	/**
	 * Inietta il trasformatore immagini.
	 *
	 * @param ImageTransformer $images Trasformatore immagini.
	 */
	public function __construct( ImageTransformer $images ) {
		$this->images = $images;
	}

	/**
	 * Trasforma la pagina nel DTO PageContent per la chiave indicata.
	 *
	 * @param WP_Post $post Pagina.
	 * @param string  $key  Chiave/slug pagina.
	 * @return array<string,mixed>
	 */
	public function to_dto( WP_Post $post, string $key ): array {
		$this->meta = new MetaReader( $post->ID );

		$dto = array(
			'key'   => $key,
			'title' => get_the_title( $post ),
		);

		switch ( $key ) {
			case 'home':
				$dto['hero']  = $this->home_hero();
				$dto['stats'] = $this->stats( 'edilmetal_home_stats' );
				$this->maybe_block( $dto, 'intro', $this->intro( 'edilmetal_home_intro' ) );
				break;

			case 'servizi':
				$dto['hero'] = $this->page_hero( 'edilmetal_servizi' );
				$this->maybe_block( $dto, 'intro', $this->intro( 'edilmetal_servizi_intro' ) );
				$dto['flow']      = $this->cards( 'edilmetal_servizi_flow' );
				$dto['tipologie'] = $this->cards( 'edilmetal_servizi_tipologie' );
				$this->maybe_block( $dto, 'callout', $this->callout( 'edilmetal_servizi_callout' ) );
				break;

			case 'azienda':
				$dto['hero']   = $this->page_hero( 'edilmetal_azienda' );
				$dto['storia'] = $this->html( 'edilmetal_azienda_storia' );
				$dto['valori'] = $this->cards( 'edilmetal_azienda_valori' );
				$dto['team']   = $this->pairs( 'edilmetal_azienda_team', 'nome', 'ruolo' );
				$dto['stats']  = $this->stats( 'edilmetal_azienda_stats' );
				break;

			case 'contatti':
				$dto['hero'] = $this->page_hero( 'edilmetal_contatti' );
				$this->maybe_block( $dto, 'intro', $this->intro( 'edilmetal_contatti_intro' ) );
				break;

			case 'privacy-policy':
				$dto['legal'] = $this->legal( 'edilmetal_privacy' );
				break;

			case 'cookie-policy':
				$dto['legal'] = $this->legal( 'edilmetal_cookie' );
				break;
		}

		$seo = $this->seo( $post, $this->seo_prefix( $key ) );
		if ( array() !== $seo ) {
			$dto['seo'] = $seo;
		}

		return $dto;
	}

	/**
	 * Blocco hero della home { eyebrow?, title, titleAccent?, subtitle?, cta? }.
	 *
	 * @return array<string,mixed>
	 */
	private function home_hero(): array {
		$hero = array(
			'title'    => $this->meta->string( 'edilmetal_home_hero_titolo' ),
			'subtitle' => $this->meta->string( 'edilmetal_home_hero_sottotitolo' ),
		);

		$this->maybe( $hero, 'eyebrow', $this->meta->string( 'edilmetal_home_hero_eyebrow' ) );
		$this->maybe( $hero, 'titleAccent', $this->meta->string( 'edilmetal_home_hero_titolo_accent' ) );

		$cta = $this->cta( 'edilmetal_home_hero_cta' );
		if ( array() !== $cta ) {
			$hero['cta'] = $cta;
		}

		return $hero;
	}

	/**
	 * Hero interno { eyebrow?, title, subtitle } dalla convenzione di prefisso.
	 *
	 * @param string $prefix Prefisso dei meta della pagina.
	 * @return array<string,string>
	 */
	private function page_hero( string $prefix ): array {
		$hero = array(
			'title'    => $this->meta->string( $prefix . '_titolo' ),
			'subtitle' => $this->meta->string( $prefix . '_sottotitolo' ),
		);

		$this->maybe( $hero, 'eyebrow', $this->meta->string( $prefix . '_hero_eyebrow' ) );

		return $hero;
	}

	/**
	 * Blocco intro { title?, text } dai meta "{prefix}_titolo" / "{prefix}_testo".
	 *
	 * @param string $prefix Prefisso dei meta.
	 * @return array<string,string>
	 */
	private function intro( string $prefix ): array {
		$intro = array();

		$this->maybe( $intro, 'title', $this->meta->string( $prefix . '_titolo' ) );
		$this->maybe( $intro, 'text', $this->meta->string( $prefix . '_testo' ) );

		return $intro;
	}

	/**
	 * Blocco callout { title, text, cta? }.
	 *
	 * @param string $prefix Prefisso dei meta.
	 * @return array<string,mixed>
	 */
	private function callout( string $prefix ): array {
		$callout = array();

		$this->maybe( $callout, 'title', $this->meta->string( $prefix . '_titolo' ) );
		$this->maybe( $callout, 'text', $this->meta->string( $prefix . '_testo' ) );

		$cta = $this->cta( $prefix . '_cta' );
		if ( array() !== $cta ) {
			$callout['cta'] = $cta;
		}

		return $callout;
	}

	/**
	 * Blocco CTA { label, url } se entrambi valorizzati.
	 *
	 * @param string $prefix Prefisso dei meta ("{prefix}_label" / "{prefix}_url").
	 * @return array<string,string>
	 */
	private function cta( string $prefix ): array {
		$label = $this->meta->string( $prefix . '_label' );
		$url   = $this->meta->string( $prefix . '_url' );

		if ( '' === $label || '' === $url ) {
			return array();
		}

		return array(
			'label' => $label,
			'url'   => $url,
		);
	}

	/**
	 * Blocco legale { body, updatedAt? }.
	 *
	 * @param string $prefix Prefisso dei meta (edilmetal_privacy / edilmetal_cookie).
	 * @return array<string,string>
	 */
	private function legal( string $prefix ): array {
		$legal = array(
			'body' => $this->html( $prefix . '_body' ),
		);

		$this->maybe( $legal, 'updatedAt', $this->meta->string( $prefix . '_updated' ) );

		return $legal;
	}

	/**
	 * Meta SEO editoriali { title?, description?, ogImage? }.
	 *
	 * @param WP_Post $post   Pagina.
	 * @param string  $prefix Prefisso dei meta SEO ("" se la pagina non ne ha).
	 * @return array<string,string>
	 */
	private function seo( WP_Post $post, string $prefix ): array {
		if ( '' === $prefix ) {
			return array();
		}

		$seo = array();

		$this->maybe( $seo, 'title', $this->meta->string( $prefix . '_seo_title' ) );
		$this->maybe( $seo, 'description', $this->meta->string( $prefix . '_seo_description' ) );

		$og = $this->meta->int_or_null( $prefix . '_seo_og' );
		if ( null !== $og && $og > 0 ) {
			$image = $this->images->to_front( $og );

			if ( null !== $image ) {
				$seo['ogImage'] = (string) $image['src'];
			}
		}

		return $seo;
	}

	/**
	 * Prefisso dei meta SEO della pagina, o "" per le pagine legali.
	 *
	 * @param string $key Chiave pagina.
	 */
	private function seo_prefix( string $key ): string {
		$map = array(
			'home'     => 'edilmetal_home',
			'servizi'  => 'edilmetal_servizi',
			'azienda'  => 'edilmetal_azienda',
			'contatti' => 'edilmetal_contatti',
		);

		return $map[ $key ] ?? '';
	}

	/**
	 * Legge un campo WYSIWYG e lo sanifica (wp_kses_post) per l'output REST.
	 *
	 * @param string $key Meta key completa.
	 */
	private function html( string $key ): string {
		return wp_kses_post( $this->meta->string( $key ) );
	}

	/**
	 * Lista di card { title, text } da un campo clonabile "titolo|testo".
	 *
	 * @param string $key Meta key.
	 * @return array<int,array<string,string>>
	 */
	private function cards( string $key ): array {
		return $this->pairs( $key, 'title', 'text' );
	}

	/**
	 * Statistiche { value, label } da un campo clonabile "valore|etichetta".
	 *
	 * @param string $key Meta key.
	 * @return array<int,array<string,string>>
	 */
	private function stats( string $key ): array {
		return $this->pairs( $key, 'value', 'label' );
	}

	/**
	 * Lista di coppie da un campo clonabile "primo|secondo".
	 *
	 * @param string $key    Meta key.
	 * @param string $first  Nome chiave del primo valore.
	 * @param string $second Nome chiave del secondo valore.
	 * @return array<int,array<string,string>>
	 */
	private function pairs( string $key, string $first, string $second ): array {
		$items = array();

		foreach ( $this->meta->string_list( $key ) as $line ) {
			$parts = explode( '|', $line, 2 );
			$a     = trim( $parts[0] );
			$b     = isset( $parts[1] ) ? trim( $parts[1] ) : '';

			if ( '' === $a || '' === $b ) {
				continue;
			}

			$items[] = array(
				$first  => $a,
				$second => $b,
			);
		}

		return $items;
	}

	/**
	 * Aggiunge un blocco al DTO solo se non vuoto.
	 *
	 * @param array<string,mixed> $dto   DTO da arricchire (per riferimento).
	 * @param string              $key   Chiave del blocco.
	 * @param array<string,mixed> $block Blocco candidato.
	 */
	private function maybe_block( array &$dto, string $key, array $block ): void {
		if ( array() !== $block ) {
			$dto[ $key ] = $block;
		}
	}

	/**
	 * Aggiunge una chiave opzionale solo se il valore non e vuoto.
	 *
	 * @param array<string,mixed> $target Struttura da arricchire (per riferimento).
	 * @param string              $key    Chiave da impostare.
	 * @param string              $value  Valore candidato.
	 */
	private function maybe( array &$target, string $key, string $value ): void {
		if ( '' !== trim( $value ) ) {
			$target[ $key ] = $value;
		}
	}
}
