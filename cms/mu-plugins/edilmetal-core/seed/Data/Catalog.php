<?php
/**
 * Sorgente dati del seeder: tassonomie, realizzazioni, impostazioni e pagine.
 *
 * I valori descrivono realizzazioni reali/plausibili di Edilmetal (carpenteria
 * metallica su commessa) con clienti reali del brief.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Seed\Data;

use Edilmetal\Core\Seed\Support\MediaRef;

defined( 'ABSPATH' ) || exit;

/**
 * Fornisce, in sola lettura, i dati statici da seedare.
 */
final class Catalog {

	/**
	 * Latitudine della sede (Noceto, PR).
	 *
	 * @var string
	 */
	private const LAT = '44.8106';

	/**
	 * Longitudine della sede.
	 *
	 * @var string
	 */
	private const LNG = '10.1730';

	/**
	 * Termini tassonomici da garantire (slug => nome [+ descrizione]).
	 *
	 * @return array<string,array<int,array{slug:string,name:string,description?:string}>>
	 */
	public static function taxonomies(): array {
		return array(
			'categoria_opera' => array(
				array(
					'slug'        => 'strutture-acciaio',
					'name'        => 'Strutture in acciaio',
					'description' => 'Capannoni · soppalchi · edifici industriali',
				),
				array(
					'slug'        => 'strutture-miste',
					'name'        => 'Strutture miste',
					'description' => 'Acciaio-calcestruzzo · ampliamenti',
				),
				array(
					'slug'        => 'scale',
					'name'        => 'Scale',
					'description' => 'Interne · esterne · di sicurezza',
				),
				array(
					'slug'        => 'pensiline',
					'name'        => 'Pensiline',
					'description' => 'Industriali · di ingresso · di carico',
				),
				array(
					'slug'        => 'pensiline-auto',
					'name'        => 'Pensiline auto / carport',
					'description' => 'Aree di sosta · fotovoltaico',
				),
				array(
					'slug'        => 'coperture-tamponamenti',
					'name'        => 'Coperture e tamponamenti',
					'description' => 'Pannelli · lamiere · isolamento',
				),
				array(
					'slug'        => 'rivestimenti-facciata',
					'name'        => 'Rivestimenti di facciata',
					'description' => 'Frangisole · lamiere forate · finiture',
				),
				array(
					'slug'        => 'opere-speciali',
					'name'        => 'Opere speciali',
					'description' => 'Su disegno · carpenteria di dettaglio',
				),
			),
			'settore'         => array(
				array(
					'slug' => 'industriale',
					'name' => 'Industriale',
				),
				array(
					'slug' => 'commerciale',
					'name' => 'Commerciale',
				),
				array(
					'slug' => 'terziario',
					'name' => 'Terziario',
				),
			),
		);
	}

	/**
	 * Elenco delle realizzazioni fittizie usate solo per popolare l'ambiente
	 * di sviluppo (clienti inventati: Parmalat, Aiassa, Pinko, ecc.). Non
	 * viene piu incluso in produzione da {@see progetti()}; resta pubblico
	 * cosi {@see ProgettoSeeder::purge_curated()} puo individuare ed
	 * eliminare i record gia seedati sui siti in cui erano stati creati.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function curated_progetti(): array {
		return array(
			self::progetto(
				'parmalat-pensilina-collecchio',
				'Pensilina industriale — Parmalat',
				'pensiline',
				'industriale',
				'Parmalat S.p.A.',
				'Collecchio (PR)',
				2023,
				true,
				'Pensilina di carico a sbalzo con struttura in acciaio saldato',
				620.0,
				24.0,
				8.0,
				42.0,
				array( 'Rilievo e progettazione dedicata', 'Relazioni di calcolo firmate', 'Produzione in officina', 'Montaggio in cantiere', 'Zincatura a caldo' ),
				array( 'Acciaio S275JR', 'Lamiera grecata zincata', 'Bulloneria classe 8.8' )
			),
			self::progetto(
				'italbox-capannone-parma',
				'Capannone industriale — Italbox',
				'strutture-acciaio',
				'industriale',
				'Italbox S.r.l.',
				'Parma (PR)',
				2022,
				true,
				'Struttura portante in acciaio per capannone produttivo',
				2400.0,
				20.0,
				9.5,
				180.0,
				array( 'Progettazione strutturale', 'Carpenteria in officina', 'Montaggio con autogru', 'Controlli di saldatura' ),
				array( 'Acciaio S355JR', 'Profili HEA/IPE', 'Tegoli di copertura' )
			),
			self::progetto(
				'iris-rivestimento-facciata-reggio',
				'Rivestimento di facciata — Iris',
				'rivestimenti-facciata',
				'industriale',
				'Iris Ceramica',
				'Reggio Emilia (RE)',
				2021,
				true,
				'Sottostruttura metallica e rivestimento di facciata ventilata',
				850.0,
				null,
				12.0,
				28.0,
				array( 'Progettazione della sottostruttura', 'Produzione staffe e montanti', 'Montaggio pannelli di facciata' ),
				array( 'Alluminio anodizzato', 'Acciaio zincato', 'Pannelli in gres porcellanato' )
			),
			self::progetto(
				'bervini-scala-sicurezza-noceto',
				'Scala di sicurezza — Bervini',
				'scale',
				'commerciale',
				'Bervini 1881',
				'Noceto (PR)',
				2020,
				false,
				'Scala di sicurezza esterna con struttura e parapetti in acciaio',
				null,
				null,
				11.0,
				9.5,
				array( 'Progettazione e calcoli', 'Produzione gradini e parapetti', 'Montaggio in cantiere' ),
				array( 'Acciaio S275JR', 'Grigliato elettrosaldato', 'Zincatura a caldo' )
			),
			self::progetto(
				'castellazzo-copertura-maneggio',
				'Copertura maneggio — Castellazzo',
				'coperture-tamponamenti',
				'terziario',
				'Maneggio Castellazzo',
				'Castell\'Arquato (PC)',
				2019,
				false,
				'Copertura a capriate reticolari per maneggio coperto',
				1800.0,
				30.0,
				7.5,
				95.0,
				array( 'Progettazione capriate reticolari', 'Produzione in officina', 'Montaggio e coperture' ),
				array( 'Acciaio S275JR', 'Lamiera grecata coibentata', 'Arcarecci zincati' )
			),
			self::progetto(
				'pinko-pensilina-fotovoltaica-fidenza',
				'Pensilina auto fotovoltaica — Pinko',
				'pensiline-auto',
				'commerciale',
				'Pinko',
				'Fidenza (PR)',
				2023,
				true,
				'Pensilina carport predisposta per impianto fotovoltaico',
				420.0,
				5.0,
				3.2,
				18.0,
				array( 'Progettazione carport', 'Predisposizione impianto FV', 'Produzione e montaggio' ),
				array( 'Acciaio zincato', 'Lamiera grecata', 'Struttura predisposta per moduli FV' )
			),
			self::progetto(
				'aiassa-struttura-mista-uffici',
				'Struttura mista uffici — Aiassa',
				'strutture-miste',
				'terziario',
				'Aiassa Costruzioni',
				'Piacenza (PC)',
				2021,
				false,
				'Struttura mista acciaio-calcestruzzo per palazzina uffici',
				960.0,
				8.0,
				13.5,
				64.0,
				array( 'Progettazione struttura mista', 'Produzione carpenteria', 'Getto solai collaboranti', 'Montaggio' ),
				array( 'Acciaio S355JR', 'Lamiera grecata collaborante', 'Calcestruzzo Rck 30' )
			),
			self::progetto(
				'meta-scala-elicoidale',
				'Scala elicoidale — Meta',
				'scale',
				'terziario',
				'Meta S.r.l.',
				'Parma (PR)',
				2022,
				false,
				'Scala elicoidale autoportante in acciaio e gradini in lamiera',
				null,
				null,
				4.2,
				3.8,
				array( 'Progettazione elica e calcoli', 'Produzione gradini calandrati', 'Montaggio e finitura' ),
				array( 'Acciaio S275JR', 'Lamiera piegata', 'Verniciatura a polvere' )
			),
			self::progetto(
				'passerella-pedonale-parco',
				'Passerella pedonale — Comune',
				'opere-speciali',
				'terziario',
				'Amministrazione comunale',
				'Noceto (PR)',
				2020,
				false,
				'Passerella pedonale a travi reticolari su corso d\'acqua',
				null,
				18.0,
				3.5,
				22.0,
				array( 'Progettazione travata reticolare', 'Produzione in officina', 'Varo con autogru' ),
				array( 'Acciaio Corten', 'Grigliato pedonale', 'Bulloneria ad alta resistenza' )
			),
			self::progetto(
				'coperture-capannone-logistico',
				'Copertura capannone logistico',
				'coperture-tamponamenti',
				'industriale',
				'Ricci Logistica',
				'Fontevivo (PR)',
				2022,
				false,
				'Copertura e tamponamenti per capannone logistico',
				3200.0,
				25.0,
				10.5,
				140.0,
				array( 'Progettazione coperture', 'Produzione arcarecci e travi', 'Montaggio pannelli e lattoneria' ),
				array( 'Acciaio S355JR', 'Pannelli sandwich coibentati', 'Lamiera grecata' )
			),
			self::progetto(
				'soppalco-industriale-magazzino',
				'Soppalco industriale di magazzino',
				'strutture-miste',
				'industriale',
				'Officine Emiliane',
				'Sorbolo (PR)',
				2019,
				false,
				'Soppalco industriale con piano di calpestio collaborante',
				540.0,
				6.0,
				4.5,
				38.0,
				array( 'Progettazione soppalco', 'Produzione pilastri e travi', 'Montaggio e piano di calpestio' ),
				array( 'Acciaio S275JR', 'Lamiera grecata', 'Pannelli multistrato' )
			),
			self::progetto(
				'rivestimento-showroom-parma',
				'Rivestimento showroom',
				'rivestimenti-facciata',
				'commerciale',
				'Gruppo commerciale',
				'Parma (PR)',
				2023,
				false,
				'Rivestimento di facciata in lamiera microforata per showroom',
				380.0,
				null,
				7.0,
				12.0,
				array( 'Progettazione sottostruttura', 'Produzione pannelli microforati', 'Montaggio di facciata' ),
				array( 'Alluminio microforato', 'Acciaio zincato', 'Fissaggi a scomparsa' )
			),
		);
	}

	/**
	 * Realizzazioni storiche reali (foto 1997–2018), caricate dal manifest
	 * generato da `scripts/seed/build-realizzazioni-media.mjs`.
	 *
	 * Dati tecnici (superficie/luce/altezza/peso) e descrizione restano
	 * segnaposto onesti: sono da compilare in WP admin, progetto per progetto.
	 *
	 * Un piccolo sottoinsieme, una voce per categoria diversa e con gallerie
	 * sostanziose, è marcato `in_evidenza` così che la banda "Realizzazioni"
	 * della home mostri anche foto reali del sito storico, non solo i 4 case
	 * study curati con galleria segnaposto.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private static function historic_progetti(): array {
		$path = __DIR__ . '/realizzazioni-storiche.json';

		if ( ! file_exists( $path ) ) {
			return array();
		}

		$raw     = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- file locale del repository, non remoto.
		$entries = null !== $raw ? json_decode( $raw, true ) : null;

		if ( ! is_array( $entries ) ) {
			return array();
		}

		$featured_refs = array(
			'strutture-acciaio-bervini',
			'strutture-miste-castellazzo-maneggio',
			'pensiline-torri-modena',
			'rivestimenti-facciata-massenza',
		);

		$records = array();

		foreach ( $entries as $entry ) {
			$titolo = (string) ( $entry['titolo'] ?? '' );
			$ref    = (string) ( $entry['ref'] ?? '' );

			if ( '' === $titolo || '' === $ref ) {
				continue;
			}

			$records[] = self::progetto(
				$ref,
				$titolo,
				(string) ( $entry['categoria'] ?? '' ),
				'',
				$titolo,
				'Provincia di Parma',
				2018,
				in_array( $ref, $featured_refs, true ),
				sprintf( 'Realizzazione in carpenteria metallica per %s', $titolo ),
				null,
				null,
				null,
				null,
				array(),
				array(),
				is_array( $entry['media'] ?? null ) ? $entry['media'] : array()
			);
		}

		return $records;
	}

	/**
	 * Tutte le realizzazioni da seedare in produzione: solo le storiche reali
	 * importate da realizzazioni-storiche.json (niente clienti fittizi).
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function progetti(): array {
		return self::historic_progetti();
	}

	/**
	 * Impostazioni globali del sito (dati reali sede Edilmetal, Noceto PR).
	 *
	 * @return array<string,mixed>
	 */
	public static function settings(): array {
		return array(
			'slug'     => 'impostazioni',
			'title'    => 'Impostazioni sito',
			'title_en' => 'Site settings',
			'meta'     => array(
				'edilmetal_set_nome_azienda'    => 'Edilmetal',
				'edilmetal_set_ragione_sociale' => 'Edilmetal S.r.l.',
				'edilmetal_set_indirizzo'       => 'Piazza Alpini d\'Italia, 10/A — 43015 Noceto (PR)',
				'edilmetal_set_telefono'        => '0521 615023',
				'edilmetal_set_fax'             => '0521 615207',
				'edilmetal_set_email'           => 'info@edilmetal.it',
				'edilmetal_set_piva'            => '01234567890',
				'edilmetal_set_rea'             => 'PR-000000',
				'edilmetal_set_maps_url'        => 'https://www.google.com/maps/search/?api=1&query=Edilmetal+Noceto',
				'edilmetal_set_map_lat'         => self::LAT,
				'edilmetal_set_map_lng'         => self::LNG,
				'edilmetal_set_orari'           => "Lunedì–Venerdì: 08:00–12:00 / 14:00–18:00\nSabato–Domenica: chiuso",
				'edilmetal_set_facebook'        => 'https://www.facebook.com/edilmetal',
				'edilmetal_set_instagram'       => 'https://www.instagram.com/edilmetal',
				'edilmetal_set_linkedin'        => 'https://www.linkedin.com/company/edilmetal',
				'edilmetal_set_slogan'          => 'Carpenteria metallica su commessa dal 1997',
				'edilmetal_set_copyright'       => '© Edilmetal S.r.l. — Tutti i diritti riservati',
				'edilmetal_set_privacy_url'     => '/privacy-policy',
				'edilmetal_set_cookie_url'      => '/cookie-policy',
				'edilmetal_set_hero_image'      => new MediaRef( 'real:home/hero-parmalat' ),
				'edilmetal_set_foto_credit'     => 'Immagini dimostrative delle realizzazioni Edilmetal.',
			),
			'meta_en'  => array(
				'edilmetal_set_orari'       => "Monday–Friday: 8:00–12:00 am / 2:00–6:00 pm\nSaturday–Sunday: closed",
				'edilmetal_set_slogan'      => 'Made-to-order structural steelwork since 1997',
				'edilmetal_set_copyright'   => '© Edilmetal S.r.l. — All rights reserved',
				'edilmetal_set_foto_credit' => 'Demonstrative images of Edilmetal projects.',
			),
		);
	}

	/**
	 * Pagine editoriali con i rispettivi blocchi (IT + EN).
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function pages(): array {
		return array(
			self::page_home(),
			self::page_servizi(),
			self::page_azienda(),
			self::page_contatti(),
			self::page_privacy(),
			self::page_cookie(),
		);
	}

	/**
	 * Costruisce il record di una realizzazione.
	 *
	 * @param string     $ref            Slug canonico.
	 * @param string     $title          Titolo della realizzazione.
	 * @param string     $categoria      Slug della categoria opera.
	 * @param string     $settore        Slug del settore.
	 * @param string     $cliente        Cliente committente.
	 * @param string     $luogo          Luogo dell'intervento.
	 * @param int        $anno           Anno di realizzazione.
	 * @param bool       $in_evidenza    Se mostrarla nel rail della home.
	 * @param string     $tipologia      Tipologia strutturale.
	 * @param float|null $superficie_mq  Superficie coperta (m²) o null.
	 * @param float|null $luce_m         Luce/campata (m) o null.
	 * @param float|null $altezza_m      Altezza (m) o null.
	 * @param float|null $peso_t         Peso acciaio (t) o null.
	 * @param string[]   $lavorazioni    Lavorazioni eseguite.
	 * @param string[]   $materiali      Materiali impiegati.
	 * @param string[]   $media          Chiavi MediaLibrary per la galleria, in ordine (facoltativo).
	 * @return array<string,mixed>
	 */
	private static function progetto(
		string $ref,
		string $title,
		string $categoria,
		string $settore,
		string $cliente,
		string $luogo,
		int $anno,
		bool $in_evidenza,
		string $tipologia,
		?float $superficie_mq,
		?float $luce_m,
		?float $altezza_m,
		?float $peso_t,
		array $lavorazioni,
		array $materiali,
		array $media = array()
	): array {
		return array(
			'ref'             => $ref,
			'title'           => $title,
			'content'         => sprintf(
				'<p>Realizzazione Edilmetal per %s a %s (%d): %s. L\'intervento è stato gestito come unico interlocutore, dal sopralluogo alla progettazione dedicata, con relazioni di calcolo firmate, produzione in officina e montaggio in cantiere.</p>',
				esc_html( $cliente ),
				esc_html( $luogo ),
				$anno,
				esc_html( lcfirst( $tipologia ) )
			),
			'categoria'       => $categoria,
			'settore'         => $settore,
			'cliente'         => $cliente,
			'luogo'           => $luogo,
			'anno'            => $anno,
			'in_evidenza'     => $in_evidenza,
			'tipologia'       => $tipologia,
			'superficie_mq'   => $superficie_mq,
			'luce_campata_m'  => $luce_m,
			'altezza_m'       => $altezza_m,
			'peso_acciaio_t'  => $peso_t,
			'lavorazioni'     => $lavorazioni,
			'materiali'       => $materiali,
			'media'           => $media,
			'seo_title'       => $title . ' — Edilmetal',
			'seo_description' => sprintf( 'Case study Edilmetal: %s per %s a %s (%d).', lcfirst( $tipologia ), $cliente, $luogo, $anno ),
			'en'              => array(
				'content' => sprintf(
					'<p>Edilmetal project for %s in %s (%d): %s. Managed as the single point of contact, from survey to dedicated design, with signed structural calculations, in-house fabrication and on-site assembly.</p>',
					esc_html( $cliente ),
					esc_html( $luogo ),
					$anno,
					esc_html( lcfirst( $tipologia ) )
				),
			),
		);
	}

	/**
	 * Pagina Homepage (contenuti allineati al contratto PageContent).
	 *
	 * @return array<string,mixed>
	 */
	private static function page_home(): array {
		return array(
			'key'      => 'home',
			'title'    => 'Edilmetal · Carpenteria metallica su commessa',
			'title_en' => 'Edilmetal · Made-to-order structural steelwork',
			'meta'     => array(
				'edilmetal_home_hero_eyebrow'        => 'Carpenteria metallica su commessa · dal 1997',
				'edilmetal_home_hero_titolo'         => 'Strutture in acciaio',
				'edilmetal_home_hero_titolo_accent'  => 'progettate, prodotte e montate.',
				'edilmetal_home_hero_sottotitolo'    => 'Progettazione dedicata, relazioni di calcolo firmate, produzione in officina e montaggio in cantiere per l\'edilizia industriale, commerciale e terziaria.',
				'edilmetal_home_hero_cta_label'      => 'Le realizzazioni',
				'edilmetal_home_hero_cta_url'        => '/realizzazioni',
				'edilmetal_home_hero_cta2_label'     => 'Richiedi un preventivo',
				'edilmetal_home_hero_cta2_url'       => '/contatti',
				'edilmetal_home_hero_index'          => array(
					'Dal 1997|Esperienza in cantiere',
					'Su commessa|Calcoli firmati da tecnici abilitati',
					'Noceto (PR)|Progettazione · produzione · montaggio',
				),
				'edilmetal_home_evidenza1_categoria' => 'strutture-acciaio',
				'edilmetal_home_evidenza1_img'       => new MediaRef( 'real:strutture-acciaio/strutture-acciaio-acetum/01' ),
				'edilmetal_home_evidenza2_categoria' => 'pensiline',
				'edilmetal_home_evidenza2_img'       => new MediaRef( 'real:pensiline/pensiline-ferrari/01' ),
			),
			'meta_en'  => array(
				'edilmetal_home_hero_eyebrow'       => 'Made-to-order structural steelwork · since 1997',
				'edilmetal_home_hero_titolo'        => 'Steel structures',
				'edilmetal_home_hero_titolo_accent' => 'designed, fabricated and assembled.',
				'edilmetal_home_hero_sottotitolo'   => 'Dedicated design, signed structural calculations, in-house fabrication and on-site assembly for industrial, commercial and tertiary construction.',
				'edilmetal_home_hero_cta_label'     => 'Our projects',
				'edilmetal_home_hero_cta2_label'    => 'Request a quote',
				'edilmetal_home_hero_index'         => array(
					'Since 1997|Experience on site',
					'Made to order|Calculations signed by qualified engineers',
					'Noceto (PR)|Design · fabrication · assembly',
				),
			),
		);
	}

	/**
	 * Pagina Servizi / Cosa facciamo (sottotitolo di testata; l'elenco
	 * categorie arriva dalla tassonomia, vedi Catalog::taxonomies()).
	 *
	 * @return array<string,mixed>
	 */
	private static function page_servizi(): array {
		return array(
			'key'      => 'servizi',
			'title'    => 'Servizi',
			'title_en' => 'Services',
			'meta'     => array(
				'edilmetal_servizi_sottotitolo' => 'Progettazione, costruzione e montaggio di strutture in carpenteria metallica per l\'edilizia industriale, commerciale e terziaria. Un unico interlocutore, dal sopralluogo al post-vendita.',
			),
			'meta_en'  => array(
				'edilmetal_servizi_sottotitolo' => 'Design, fabrication and assembly of structural steelwork for industrial, commercial and tertiary construction. A single point of contact, from survey to after-sales.',
			),
		);
	}

	/**
	 * Pagina Azienda (Chi siamo). Sede, mappa e orari restano nelle
	 * impostazioni globali.
	 *
	 * @return array<string,mixed>
	 */
	private static function page_azienda(): array {
		return array(
			'key'      => 'azienda',
			'title'    => 'Azienda',
			'title_en' => 'Company',
			'meta'     => array(
				'edilmetal_azienda_sottotitolo'   => 'Fondata a Noceto (PR) da Alessio Ricci e Aldo Medioli, Edilmetal realizza strutture in acciaio su commessa per clienti industriali e prestigiosi.',
				'edilmetal_azienda_storia_titolo' => 'La società',
				'edilmetal_azienda_storia'        => '<p>Edilmetal S.r.l. nasce nel 1997 dall\'iniziativa di Alessio Ricci e Aldo Medioli. Da allora l\'azienda progetta, produce e monta strutture in carpenteria metallica per l\'edilizia industriale, commerciale e terziaria, sia in nuova costruzione sia in ristrutturazione.</p><p>Lavoriamo su commessa, con progettazione dedicata e relazioni di calcolo firmate da tecnici abilitati iscritti agli albi. Dal sopralluogo al montaggio siamo l\'unico interlocutore del cliente, con assistenza post-vendita.</p>',
			),
			'meta_en'  => array(
				'edilmetal_azienda_sottotitolo'   => 'Founded in Noceto (PR) by Alessio Ricci and Aldo Medioli, Edilmetal builds made-to-order steel structures for industrial and prestigious clients.',
				'edilmetal_azienda_storia_titolo' => 'The company',
				'edilmetal_azienda_storia'        => '<p>Edilmetal S.r.l. was founded in 1997 by Alessio Ricci and Aldo Medioli. Since then the company has designed, fabricated and assembled structural steelwork for industrial, commercial and tertiary construction, both new builds and renovations.</p><p>We work to order, with dedicated design and structural calculations signed by qualified engineers. From survey to assembly we are the client\'s single point of contact, with after-sales support.</p>',
			),
		);
	}

	/**
	 * Pagina Contatti.
	 *
	 * @return array<string,mixed>
	 */
	private static function page_contatti(): array {
		return array(
			'key'      => 'contatti',
			'title'    => 'Contatti',
			'title_en' => 'Contact',
			'meta'     => array(
				'edilmetal_contatti_hero_eyebrow' => 'Parla con Edilmetal',
				'edilmetal_contatti_titolo'       => 'Richiedi un preventivo',
				'edilmetal_contatti_sottotitolo'  => 'Siamo a Noceto (PR), in Piazza Alpini d\'Italia 10/A. Chiamaci o scrivici: ti rispondiamo con un preventivo dedicato.',
				'edilmetal_contatti_intro_titolo' => 'Come possiamo aiutarti',
				'edilmetal_contatti_intro_testo'  => 'Raccontaci la tua commessa: strutture, scale, pensiline, coperture o rivestimenti. Analizziamo le esigenze e prepariamo un\'offerta.',
			),
			'meta_en'  => array(
				'edilmetal_contatti_hero_eyebrow' => 'Talk to Edilmetal',
				'edilmetal_contatti_titolo'       => 'Request a quote',
				'edilmetal_contatti_sottotitolo'  => 'We are in Noceto (PR), at Piazza Alpini d\'Italia 10/A. Call or write to us: we reply with a dedicated quote.',
				'edilmetal_contatti_intro_titolo' => 'How we can help',
				'edilmetal_contatti_intro_testo'  => 'Tell us about your project: structures, stairs, canopies, roofing or cladding. We analyse the requirements and prepare an offer.',
			),
		);
	}

	/**
	 * Pagina Privacy Policy (testo legale).
	 *
	 * @return array<string,mixed>
	 */
	private static function page_privacy(): array {
		return array(
			'key'      => 'privacy-policy',
			'title'    => 'Privacy Policy',
			'title_en' => 'Privacy Policy',
			'meta'     => array(
				'edilmetal_privacy_body'    => "<p>La presente informativa descrive le modalità di trattamento dei dati personali degli utenti che consultano il sito e utilizzano i moduli di contatto, ai sensi del Regolamento UE 2016/679 (GDPR).</p><h2>Titolare del trattamento</h2><p>Titolare del trattamento è Edilmetal S.r.l., con sede in Piazza Alpini d'Italia 10/A, 43015 Noceto (PR), P.IVA 01234567890 — email: info@edilmetal.it.</p><h2>Dati trattati</h2><p>Tramite il modulo di contatto raccogliamo nome, email, telefono (facoltativo) e il testo della richiesta: nessun altro dato personale viene raccolto al di fuori di quanto necessario a rispondere.</p><h2>Finalità e base giuridica</h2><p>I dati sono trattati per rispondere alle richieste di informazioni e di preventivo, sulla base dell'esecuzione di misure precontrattuali richieste dall'interessato (art. 6.1.b GDPR). Il conferimento è facoltativo ma necessario per dare seguito alla richiesta: in assenza non sarà possibile fornire una risposta. Non effettuiamo profilazione né processi decisionali automatizzati.</p><h2>Destinatari dei dati</h2><p>I dati sono trattati da personale Edilmetal autorizzato e possono essere conosciuti da fornitori che agiscono come responsabili del trattamento (art. 28 GDPR), quali il fornitore del servizio di hosting/CMS e della posta elettronica, solo per le finalità indicate. I dati non sono ceduti a terzi per finalità di marketing.</p><h2>Trasferimento dei dati</h2><p>I dati sono conservati su server ubicati in Unione Europea e non vengono trasferiti verso paesi extra-UE.</p><h2>Conservazione</h2><p>I dati sono conservati per il tempo necessario a gestire la richiesta e comunque non oltre 24 mesi dall'ultimo contatto, salvi gli obblighi di legge (es. contabili/fiscali in caso di rapporto contrattuale).</p><h2>Diritti dell'interessato</h2><p>Scrivendo a info@edilmetal.it è possibile esercitare in ogni momento i diritti previsti dagli artt. 15-22 GDPR: accesso, rettifica, cancellazione, limitazione, portabilità e opposizione al trattamento. È inoltre possibile proporre reclamo al Garante per la protezione dei dati personali (www.garanteprivacy.it).</p>",
				'edilmetal_privacy_updated' => '2026-08-03',
			),
			'meta_en'  => array(
				'edilmetal_privacy_body' => '<p>This notice describes how the personal data of users who browse the site and use the contact forms is processed, pursuant to EU Regulation 2016/679 (GDPR).</p><h2>Data controller</h2><p>The data controller is Edilmetal S.r.l., Piazza Alpini d\'Italia 10/A, 43015 Noceto (PR), Italy, VAT 01234567890 — email: info@edilmetal.it.</p><h2>Data collected</h2><p>Through the contact form we collect name, email, phone (optional) and the text of the request: no other personal data is collected beyond what is needed to respond.</p><h2>Purposes and legal basis</h2><p>Data is processed to respond to requests for information and quotes, on the basis of pre-contractual measures requested by the data subject (Art. 6.1.b GDPR). Providing it is optional but required to follow up on the request: without it we cannot respond. We do not carry out profiling or automated decision-making.</p><h2>Recipients of the data</h2><p>Data is processed by authorised Edilmetal staff and may be accessed by suppliers acting as data processors (Art. 28 GDPR), such as our hosting/CMS and email providers, solely for the purposes stated. Data is not shared with third parties for marketing purposes.</p><h2>Data transfers</h2><p>Data is stored on servers located in the European Union and is not transferred to non-EU countries.</p><h2>Retention</h2><p>Data is kept for as long as necessary to handle the request and in any case no longer than 24 months from the last contact, except where a longer retention is required by law (e.g. accounting/tax obligations under a contract).</p><h2>Rights of the data subject</h2><p>By writing to info@edilmetal.it you may at any time exercise the rights set out in Articles 15-22 GDPR: access, rectification, erasure, restriction, portability and objection. You may also lodge a complaint with the Italian Data Protection Authority (www.garanteprivacy.it).</p>',
			),
		);
	}

	/**
	 * Pagina Cookie Policy (testo legale).
	 *
	 * @return array<string,mixed>
	 */
	private static function page_cookie(): array {
		return array(
			'key'      => 'cookie-policy',
			'title'    => 'Cookie Policy',
			'title_en' => 'Cookie Policy',
			'meta'     => array(
				'edilmetal_cookie_body'    => "<p>Questo sito utilizza esclusivamente cookie tecnici necessari al funzionamento, per i quali non è richiesto il consenso dell'utente (art. 122 Codice Privacy e Linee guida cookie del Garante Privacy). Il sito non utilizza cookie di profilazione né cookie di terze parti per finalità statistiche o pubblicitarie.</p><h2>Cookie tecnici utilizzati</h2><ul><li><strong>Preferenza di tema (chiaro/scuro)</strong>: memorizzata nel browser, nessun dato inviato a terzi.</li><li><strong>Preferenza di lingua</strong>: memorizzata nel browser, stessa finalità.</li></ul><h2>Mappa OpenStreetMap</h2><p>Le mappe delle pagine Contatti e Azienda sono servite tramite tile OpenStreetMap in modalità cookieless, senza cookie di profilazione né raccolta di dati personali.</p><h2>Servizi futuri</h2><p>Se in futuro venissero attivati strumenti di analisi statistica o servizi di terze parti con cookie non tecnici, questa informativa sarà aggiornata e verrà richiesto il consenso dell'utente tramite apposito banner prima della loro attivazione.</p><h2>Gestione dei cookie</h2><p>È possibile eliminare i cookie già presenti e disabilitarne la memorizzazione futura dalle impostazioni del proprio browser.</p>",
				'edilmetal_cookie_updated' => '2026-08-03',
			),
			'meta_en'  => array(
				'edilmetal_cookie_body' => '<p>This site uses only technical cookies necessary for its operation, for which no user consent is required (Art. 122 of the Italian Privacy Code and the Garante\'s cookie guidelines). The site does not use profiling cookies or third-party cookies for statistical or advertising purposes.</p><h2>Technical cookies in use</h2><ul><li><strong>Theme preference (light/dark)</strong>: stored in the browser, no data sent to third parties.</li><li><strong>Language preference</strong>: stored in the browser, same purpose.</li></ul><h2>OpenStreetMap map</h2><p>The maps on the Contact and Company pages are served through OpenStreetMap tiles in cookieless mode, without profiling cookies or collection of personal data.</p><h2>Future services</h2><p>Should statistical analysis tools or third-party services using non-technical cookies be introduced in the future, this notice will be updated and the user\'s consent will be requested via a dedicated banner before they are activated.</p><h2>Managing cookies</h2><p>You can delete cookies already stored and disable future storage from your browser settings.</p>',
			),
		);
	}
}
