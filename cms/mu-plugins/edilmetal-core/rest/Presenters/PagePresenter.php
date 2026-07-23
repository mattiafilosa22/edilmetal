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
use Edilmetal\Core\Support\Schema;
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
				$dto['home'] = array(
					'hero'       => $this->home_hero(),
					'inEvidenza' => $this->home_in_evidenza(),
				);
				break;

			case 'servizi':
				$this->maybe( $dto, 'subtitle', $this->meta->string( 'edilmetal_servizi_sottotitolo' ) );
				$dto['servizi'] = array( 'tipologie' => $this->categoria_terms() );
				break;

			case 'azienda':
				$this->maybe( $dto, 'subtitle', $this->meta->string( 'edilmetal_azienda_sottotitolo' ) );
				$dto['azienda'] = array(
					'storiaTitolo' => $this->fallback( $this->meta->string( 'edilmetal_azienda_storia_titolo' ), 'La società' ),
					'storia'       => $this->storia_paragraphs( 'edilmetal_azienda_storia' ),
				);
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
	 * Blocco hero della home { eyebrow?, title, titleAccent?, subtitle, ctaPrimary, ctaSecondary?, index }.
	 *
	 * @return array<string,mixed>
	 */
	private function home_hero(): array {
		$hero = array(
			'title'      => $this->meta->string( 'edilmetal_home_hero_titolo' ),
			'subtitle'   => $this->meta->string( 'edilmetal_home_hero_sottotitolo' ),
			'ctaPrimary' => $this->cta_href( 'edilmetal_home_hero_cta' )
				?? array(
					'label' => 'Le realizzazioni',
					'href'  => '/realizzazioni',
				),
			'index'      => $this->pairs( 'edilmetal_home_hero_index', 'valore', 'etichetta' ),
		);

		$this->maybe( $hero, 'eyebrow', $this->meta->string( 'edilmetal_home_hero_eyebrow' ) );
		$this->maybe( $hero, 'titleAccent', $this->meta->string( 'edilmetal_home_hero_titolo_accent' ) );

		$secondary = $this->cta_href( 'edilmetal_home_hero_cta2' );
		if ( null !== $secondary ) {
			$hero['ctaSecondary'] = $secondary;
		}

		return $hero;
	}

	/**
	 * Variante di `cta()` con chiave `href` (contratto frontend) al posto di `url`.
	 *
	 * @param string $prefix Prefisso dei meta ("{prefix}_label" / "{prefix}_url").
	 * @return array{label:string,href:string}|null
	 */
	private function cta_href( string $prefix ): ?array {
		$cta = $this->cta( $prefix );

		if ( array() === $cta ) {
			return null;
		}

		return array(
			'label' => $cta['label'],
			'href'  => $cta['url'],
		);
	}

	/**
	 * Blocco "in evidenza": fino a 2 categorie con foto reale.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function home_in_evidenza(): array {
		$items = array();

		foreach ( array( 1, 2 ) as $n ) {
			$item = $this->evidenza_item( $n );

			if ( null !== $item ) {
				$items[] = $item;
			}
		}

		return $items;
	}

	/**
	 * Singola voce "in evidenza": risolve slug categoria + immagine.
	 *
	 * @param int $n Indice dello slot (1 o 2).
	 * @return array<string,mixed>|null
	 */
	private function evidenza_item( int $n ): ?array {
		$slug          = $this->meta->string( "edilmetal_home_evidenza{$n}_categoria" );
		$attachment_id = $this->meta->int_or_null( "edilmetal_home_evidenza{$n}_img" );

		if ( '' === $slug || null === $attachment_id ) {
			return null;
		}

		$term = get_term_by( 'slug', $slug, Schema::TAX_CATEGORIA );

		if ( ! $term instanceof \WP_Term ) {
			return null;
		}

		$image = $this->images->to_front( $attachment_id );

		if ( null === $image ) {
			return null;
		}

		return array(
			'categoria' => array(
				'slug' => $term->slug,
				'nome' => $term->name,
			),
			'immagine'  => $image,
		);
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

	/**
	 * Termini della tassonomia `categoria_opera`, in ordine canonico, nella
	 * forma { slug, nome, dettaglio } (dettaglio = descrizione del termine,
	 * o il nome stesso se non valorizzata).
	 *
	 * @return array<int,array<string,string>>
	 */
	private function categoria_terms(): array {
		$order = array(
			'strutture-acciaio',
			'strutture-miste',
			'scale',
			'pensiline',
			'pensiline-auto',
			'coperture-tamponamenti',
			'rivestimenti-facciata',
			'opere-speciali',
		);

		$terms = get_terms(
			array(
				'taxonomy'   => Schema::TAX_CATEGORIA,
				'hide_empty' => false,
			)
		);

		if ( ! is_array( $terms ) ) {
			return array();
		}

		$by_slug = array();
		foreach ( $terms as $term ) {
			if ( $term instanceof \WP_Term ) {
				$by_slug[ $term->slug ] = $term;
			}
		}

		$out = array();
		foreach ( $order as $slug ) {
			if ( ! isset( $by_slug[ $slug ] ) ) {
				continue;
			}

			$term = $by_slug[ $slug ];

			$out[] = array(
				'slug'      => $term->slug,
				'nome'      => $term->name,
				'dettaglio' => '' !== $term->description ? $term->description : $term->name,
			);
		}

		return $out;
	}

	/**
	 * Spezza un campo WYSIWYG in un elenco di paragrafi di solo testo.
	 * Ogni `<p>` diventa una voce; se il contenuto non contiene paragrafi
	 * HTML, l'intero testo (ripulito) diventa un unico paragrafo.
	 *
	 * @param string $key Meta key completa del campo WYSIWYG.
	 * @return string[]
	 */
	private function storia_paragraphs( string $key ): array {
		$html = $this->meta->string( $key );

		if ( '' === $html ) {
			return array();
		}

		if ( false === strpos( $html, '<p' ) ) {
			$text = trim( wp_strip_all_tags( $html ) );

			return '' !== $text ? array( $text ) : array();
		}

		preg_match_all( '/<p[^>]*>(.*?)<\/p>/is', $html, $matches );

		$paragraphs = array();
		foreach ( $matches[1] as $inner ) {
			$text = trim( wp_strip_all_tags( $inner ) );

			if ( '' !== $text ) {
				$paragraphs[] = $text;
			}
		}

		return $paragraphs;
	}

	/**
	 * Restituisce il valore se non vuoto, altrimenti il fallback indicato.
	 *
	 * @param string $value    Valore letto.
	 * @param string $fallback Valore di riserva.
	 */
	private function fallback( string $value, string $fallback ): string {
		return '' !== trim( $value ) ? $value : $fallback;
	}
}
