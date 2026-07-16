<?php
/**
 * Presenter: trasforma la pagina impostazioni nel DTO SiteSettings del front-end.
 *
 * Il contratto canonico e definito in docs/api-contract.md: anagrafica,
 * contatti, coordinate Leaflet, orari e social (facebook/instagram/linkedin).
 * Dominio B2B su commessa: nessun whatsapp/prezzi/noleggio.
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
 * Costruisce anagrafica, contatti, orari e social globali del sito.
 */
final class SettingsPresenter {

	/**
	 * Trasformatore immagini, per l'immagine hero editabile.
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
	 * Trasforma la pagina impostazioni (o null) in un DTO SiteSettings valido.
	 *
	 * @param WP_Post|null $post Pagina impostazioni oppure null.
	 * @return array<string,mixed>
	 */
	public function to_dto( ?WP_Post $post ): array {
		if ( null === $post ) {
			return $this->empty_dto();
		}

		$meta = new MetaReader( $post->ID );

		$dto = array(
			'nomeAzienda'    => $this->fallback( $meta->string( 'edilmetal_set_nome_azienda' ), 'Edilmetal' ),
			'ragioneSociale' => $this->fallback( $meta->string( 'edilmetal_set_ragione_sociale' ), 'Edilmetal S.r.l.' ),
			'partitaIva'     => $this->fallback( $meta->string( 'edilmetal_set_piva' ), '-' ),
			'indirizzo'      => $this->fallback( $meta->string( 'edilmetal_set_indirizzo' ), '-' ),
			'telefono'       => $this->fallback( $meta->string( 'edilmetal_set_telefono' ), '-' ),
			'email'          => $this->fallback( $meta->string( 'edilmetal_set_email' ), 'info@example.com' ),
			'orari'          => $this->orari( $meta->string( 'edilmetal_set_orari' ) ),
			'social'         => $this->social( $meta ),
		);

		$maps_url = $meta->string( 'edilmetal_set_maps_url' );
		if ( '' !== $maps_url ) {
			$dto['mapsUrl'] = $maps_url;
		}

		$coordinate = $this->coordinate( $meta );
		if ( null !== $coordinate ) {
			$dto['coordinate'] = $coordinate;
		}

		$hero_image = $this->hero_image( $meta );
		if ( null !== $hero_image ) {
			$dto['heroImage'] = $hero_image;
		}

		$foto_credit = $meta->string( 'edilmetal_set_foto_credit' );
		if ( '' !== $foto_credit ) {
			$dto['fotoCredit'] = $foto_credit;
		}

		return $dto;
	}

	/**
	 * Immagine hero editabile nel formato Image, oppure null se non impostata.
	 *
	 * @param MetaReader $meta Lettore meta.
	 * @return array<string,mixed>|null
	 */
	private function hero_image( MetaReader $meta ): ?array {
		$attachment_id = $meta->int_or_null( 'edilmetal_set_hero_image' );

		if ( null === $attachment_id || $attachment_id <= 0 ) {
			return null;
		}

		return $this->images->to_front( $attachment_id );
	}

	/**
	 * Coordinate mappa { lat, lng } se entrambe valorizzate e numeriche.
	 *
	 * @param MetaReader $meta Lettore meta.
	 * @return array<string,float>|null
	 */
	private function coordinate( MetaReader $meta ): ?array {
		$lat = $meta->string( 'edilmetal_set_map_lat' );
		$lng = $meta->string( 'edilmetal_set_map_lng' );

		if ( ! is_numeric( $lat ) || ! is_numeric( $lng ) ) {
			return null;
		}

		return array(
			'lat' => (float) $lat,
			'lng' => (float) $lng,
		);
	}

	/**
	 * Converte l'orario multi-riga in una lista { giorni, apertura }.
	 *
	 * Ogni riga ha forma "Giorni: Apertura"; lo split avviene sul primo ":".
	 *
	 * @param string $raw Testo orari (una fascia per riga).
	 * @return array<int,array<string,string>>
	 */
	private function orari( string $raw ): array {
		if ( '' === trim( $raw ) ) {
			return array();
		}

		$lines = preg_split( '/\r\n|\r|\n/', $raw );
		$lines = is_array( $lines ) ? $lines : array();
		$orari = array();

		foreach ( $lines as $line ) {
			$line = trim( (string) $line );

			if ( '' === $line ) {
				continue;
			}

			$parts    = explode( ':', $line, 2 );
			$giorni   = trim( $parts[0] );
			$apertura = isset( $parts[1] ) ? trim( $parts[1] ) : '';

			if ( '' === $giorni ) {
				continue;
			}

			$orari[] = array(
				'giorni'   => $giorni,
				'apertura' => $apertura,
			);
		}

		return $orari;
	}

	/**
	 * Blocco social: include solo i canali valorizzati.
	 *
	 * @param MetaReader $meta Lettore meta.
	 * @return array<string,string>
	 */
	private function social( MetaReader $meta ): array {
		$social  = array();
		$channels = array(
			'facebook'  => 'edilmetal_set_facebook',
			'instagram' => 'edilmetal_set_instagram',
			'linkedin'  => 'edilmetal_set_linkedin',
		);

		foreach ( $channels as $key => $meta_key ) {
			$value = $meta->string( $meta_key );

			if ( '' !== $value ) {
				$social[ $key ] = $value;
			}
		}

		return $social;
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

	/**
	 * DTO minimo ma valido quando la pagina impostazioni non esiste ancora.
	 *
	 * @return array<string,mixed>
	 */
	private function empty_dto(): array {
		return array(
			'nomeAzienda'    => 'Edilmetal',
			'ragioneSociale' => 'Edilmetal S.r.l.',
			'partitaIva'     => '-',
			'indirizzo'      => '-',
			'telefono'       => '-',
			'email'          => 'info@example.com',
			'orari'          => array(),
			'social'         => array(),
		);
	}
}
