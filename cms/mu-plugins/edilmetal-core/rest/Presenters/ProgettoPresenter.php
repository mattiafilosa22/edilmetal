<?php
/**
 * Presenter: trasforma un post "progetto" nel DTO atteso dal front-end.
 *
 * Il contratto canonico e definito in docs/api-contract.md (ProgettoSummary /
 * Progetto): chiavi camelCase, categoria come { slug, nome }, dati tecnici come
 * lista { label, valore } e immagini nella forma Image (src/srcset/width/height/alt).
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Rest\Presenters;

use Edilmetal\Core\I18n\Language;
use Edilmetal\Core\Rest\Support\ImageTransformer;
use Edilmetal\Core\Rest\Support\MetaReader;
use Edilmetal\Core\Support\Schema;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Costruisce DTO progetto conformi al contratto del front-end.
 */
final class ProgettoPresenter {

	/**
	 * Trasformatore immagini condiviso.
	 *
	 * @var ImageTransformer
	 */
	private ImageTransformer $images;

	/**
	 * Inietta il trasformatore immagini.
	 *
	 * @param ImageTransformer $images Trasformatore immagini.
	 */
	public function __construct( ImageTransformer $images ) {
		$this->images = $images;
	}

	/**
	 * DTO ridotto (ProgettoSummary) per l'archivio realizzazioni e le card.
	 *
	 * @param WP_Post $post Post progetto.
	 * @return array<string,mixed>
	 */
	public function to_summary( WP_Post $post ): array {
		$meta = new MetaReader( $post->ID );
		$dto  = $this->base( $post, $meta );

		$dto['copertina'] = $this->cover( $post->ID, $meta );

		return $dto;
	}

	/**
	 * DTO completo (Progetto) per la scheda dettaglio.
	 *
	 * @param WP_Post $post Post progetto.
	 * @return array<string,mixed>
	 */
	public function to_detail( WP_Post $post ): array {
		$meta = new MetaReader( $post->ID );
		$dto  = $this->base( $post, $meta );

		$galleria = $this->images->to_front_many( $meta->attachment_ids( Schema::meta( 'galleria' ) ) );

		if ( array() === $galleria ) {
			$cover = $this->cover( $post->ID, $meta );

			if ( null !== $cover ) {
				$galleria = array( $cover );
			}
		}

		$dto['descrizione'] = $this->descrizione( $post, $meta );
		$dto['galleria']    = $galleria;
		$dto['datiTecnici'] = $this->dati_tecnici( $meta );
		$dto['lavorazioni'] = $meta->string_list( Schema::meta( 'lavorazioni' ) );
		$dto['materiali']   = $meta->string_list( Schema::meta( 'materiali' ) );

		$seo = $this->seo( $post, $meta, $galleria );
		if ( array() !== $seo ) {
			$dto['seo'] = $seo;
		}

		return $dto;
	}

	/**
	 * Campi comuni a summary e detail (ProgettoSummary senza copertina).
	 *
	 * @param WP_Post    $post Post progetto.
	 * @param MetaReader $meta Lettore meta.
	 * @return array<string,mixed>
	 */
	private function base( WP_Post $post, MetaReader $meta ): array {
		$dto = array(
			'id'         => (string) $post->ID,
			'slug'       => $post->post_name,
			'titolo'     => get_the_title( $post ),
			'traduzioni' => $this->translations( $post ),
			'cliente'    => $meta->string( Schema::meta( 'cliente' ) ),
			'luogo'      => $meta->string( Schema::meta( 'luogo' ) ),
			'anno'       => (int) ( $meta->int_or_null( Schema::meta( 'anno' ) ) ?? 0 ),
			'categoria'  => $this->categoria( $post->ID ),
			'inEvidenza' => $meta->bool( Schema::meta( 'in_evidenza' ) ),
		);

		$settore = $this->primary_term_name( $post->ID, Schema::TAX_SETTORE );
		if ( '' !== $settore ) {
			$dto['settore'] = $settore;
		}

		return $dto;
	}

	/**
	 * Categoria opera primaria come { slug, nome }.
	 *
	 * @param int $post_id ID progetto.
	 * @return array<string,string>
	 */
	private function categoria( int $post_id ): array {
		$term = $this->primary_term( $post_id, Schema::TAX_CATEGORIA );

		if ( null === $term ) {
			return array(
				'slug' => '',
				'nome' => '',
			);
		}

		return array(
			'slug' => $term->slug,
			'nome' => $term->name,
		);
	}

	/**
	 * Descrizione HTML sanificata: meta "descrizione" se presente, altrimenti
	 * il contenuto del post.
	 *
	 * @param WP_Post    $post Post progetto.
	 * @param MetaReader $meta Lettore meta.
	 */
	private function descrizione( WP_Post $post, MetaReader $meta ): string {
		$custom = $meta->string( Schema::meta( 'descrizione' ) );

		if ( '' !== $custom ) {
			return wp_kses_post( $custom );
		}

		return wp_kses_post( apply_filters( 'the_content', $post->post_content ) );
	}

	/**
	 * Scheda tecnica come lista { label, valore } (solo voci valorizzate).
	 *
	 * @param MetaReader $meta Lettore meta.
	 * @return array<int,array<string,string>>
	 */
	private function dati_tecnici( MetaReader $meta ): array {
		$dati = array();

		$this->add_dato( $dati, __( 'Tipologia', 'edilmetal-core' ), $meta->string( Schema::meta( 'tipologia' ) ) );

		$superficie = $meta->float_or_null( Schema::meta( 'superficie_mq' ) );
		if ( null !== $superficie && $superficie > 0.0 ) {
			$this->add_dato( $dati, __( 'Superficie coperta', 'edilmetal-core' ), $this->number( $superficie ) . ' m²' );
		}

		$luce = $meta->float_or_null( Schema::meta( 'luce_campata_m' ) );
		if ( null !== $luce && $luce > 0.0 ) {
			$this->add_dato( $dati, __( 'Luce / campata', 'edilmetal-core' ), $this->number( $luce ) . ' m' );
		}

		$altezza = $meta->float_or_null( Schema::meta( 'altezza_m' ) );
		if ( null !== $altezza && $altezza > 0.0 ) {
			$this->add_dato( $dati, __( 'Altezza', 'edilmetal-core' ), $this->number( $altezza ) . ' m' );
		}

		$peso = $meta->float_or_null( Schema::meta( 'peso_acciaio_t' ) );
		if ( null !== $peso && $peso > 0.0 ) {
			$this->add_dato( $dati, __( 'Peso acciaio', 'edilmetal-core' ), $this->number( $peso ) . ' t' );
		}

		return $dati;
	}

	/**
	 * Meta SEO editoriali con fallback da titolo/descrizione/copertina.
	 *
	 * @param WP_Post                        $post     Post progetto.
	 * @param MetaReader                     $meta     Lettore meta.
	 * @param array<int,array<string,mixed>> $galleria Galleria gia risolta.
	 * @return array<string,string>
	 */
	private function seo( WP_Post $post, MetaReader $meta, array $galleria ): array {
		$seo = array();

		$title = $meta->string( Schema::meta( 'seo_title' ) );
		if ( '' === $title ) {
			$title = get_the_title( $post );
		}
		if ( '' !== $title ) {
			$seo['title'] = $title;
		}

		$description = $meta->string( Schema::meta( 'seo_description' ) );
		if ( '' === $description ) {
			$description = wp_trim_words( wp_strip_all_tags( $this->descrizione( $post, $meta ) ), 30 );
		}
		if ( '' !== $description ) {
			$seo['description'] = $description;
		}

		$og = $meta->int_or_null( Schema::meta( 'seo_og' ) );
		if ( null !== $og && $og > 0 ) {
			$image = $this->images->to_front( $og );

			if ( null !== $image ) {
				$seo['ogImage'] = (string) $image['src'];
			}
		}

		if ( ! isset( $seo['ogImage'] ) && array() !== $galleria && isset( $galleria[0]['src'] ) ) {
			$seo['ogImage'] = (string) $galleria[0]['src'];
		}

		return $seo;
	}

	/**
	 * Immagine di copertina (Image) dalla galleria o dalla thumbnail.
	 *
	 * @param int        $post_id ID progetto.
	 * @param MetaReader $meta    Lettore meta.
	 * @return array<string,mixed>|null
	 */
	private function cover( int $post_id, MetaReader $meta ): ?array {
		$ids = $meta->attachment_ids( Schema::meta( 'galleria' ) );

		if ( array() !== $ids ) {
			$front = $this->images->to_front( (int) $ids[0] );

			if ( null !== $front ) {
				return $front;
			}
		}

		$thumb_id = get_post_thumbnail_id( $post_id );

		return $thumb_id ? $this->images->to_front( (int) $thumb_id ) : null;
	}

	/**
	 * Mappa delle traduzioni Polylang: codice lingua => slug reale del post.
	 *
	 * @param WP_Post $post Post progetto.
	 * @return array<string,string>
	 */
	private function translations( WP_Post $post ): array {
		$map = array();

		if ( function_exists( 'pll_get_post_translations' ) ) {
			$translations = pll_get_post_translations( $post->ID );

			if ( is_array( $translations ) ) {
				foreach ( $translations as $lang => $translated_id ) {
					$slug = get_post_field( 'post_name', (int) $translated_id );

					if ( is_string( $slug ) && '' !== $slug ) {
						$map[ (string) $lang ] = $slug;
					}
				}
			}
		}

		if ( array() === $map ) {
			$lang = function_exists( 'pll_get_post_language' )
				? (string) pll_get_post_language( $post->ID )
				: Language::DEFAULT;

			$map[ '' !== $lang ? $lang : Language::DEFAULT ] = $post->post_name;
		}

		return $map;
	}

	/**
	 * Aggiunge una voce di scheda tecnica solo se il valore non e vuoto.
	 *
	 * @param array<int,array<string,string>> $dati  Lista dati (per riferimento).
	 * @param string                          $label Etichetta.
	 * @param string                          $value Valore.
	 */
	private function add_dato( array &$dati, string $label, string $value ): void {
		if ( '' !== trim( $value ) ) {
			$dati[] = array(
				'label'  => $label,
				'valore' => trim( $value ),
			);
		}
	}

	/**
	 * Formatta un numero rimuovendo i decimali superflui (es. 24.0 -> "24").
	 *
	 * @param float $value Valore numerico.
	 */
	private function number( float $value ): string {
		if ( floor( $value ) === $value ) {
			return (string) (int) $value;
		}

		return rtrim( rtrim( number_format( $value, 2, ',', '' ), '0' ), ',' );
	}

	/**
	 * Nome del termine primario (o vuoto) di una tassonomia.
	 *
	 * @param int    $post_id  ID progetto.
	 * @param string $taxonomy Tassonomia.
	 */
	private function primary_term_name( int $post_id, string $taxonomy ): string {
		$term = $this->primary_term( $post_id, $taxonomy );

		return null === $term ? '' : $term->name;
	}

	/**
	 * Primo termine assegnato per la tassonomia.
	 *
	 * @param int    $post_id  ID progetto.
	 * @param string $taxonomy Tassonomia.
	 * @return \WP_Term|null
	 */
	private function primary_term( int $post_id, string $taxonomy ): ?\WP_Term {
		$terms = get_the_terms( $post_id, $taxonomy );

		if ( ! is_array( $terms ) || array() === $terms ) {
			return null;
		}

		$first = $terms[0];

		return $first instanceof \WP_Term ? $first : null;
	}
}
