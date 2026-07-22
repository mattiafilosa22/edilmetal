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
	 * Termini tassonomici da garantire (slug => nome).
	 *
	 * @return array<string,array<int,array{slug:string,name:string}>>
	 */
	public static function taxonomies(): array {
		return array(
			'categoria_opera' => array(
				array(
					'slug' => 'strutture-acciaio',
					'name' => 'Strutture in acciaio',
				),
				array(
					'slug' => 'strutture-miste',
					'name' => 'Strutture miste',
				),
				array(
					'slug' => 'scale',
					'name' => 'Scale',
				),
				array(
					'slug' => 'pensiline',
					'name' => 'Pensiline',
				),
				array(
					'slug' => 'pensiline-auto',
					'name' => 'Pensiline auto / carport',
				),
				array(
					'slug' => 'coperture-tamponamenti',
					'name' => 'Coperture e tamponamenti',
				),
				array(
					'slug' => 'rivestimenti-facciata',
					'name' => 'Rivestimenti di facciata',
				),
				array(
					'slug' => 'opere-speciali',
					'name' => 'Opere speciali',
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
	 * Elenco delle realizzazioni curate a mano (12 case study Edilmetal).
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private static function curated_progetti(): array {
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
				false,
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
	 * Tutte le realizzazioni: curate a mano + storiche reali importate.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function progetti(): array {
		return array_merge( self::curated_progetti(), self::historic_progetti() );
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
	 * Pagina Servizi / Cosa facciamo.
	 *
	 * @return array<string,mixed>
	 */
	private static function page_servizi(): array {
		return array(
			'key'      => 'servizi',
			'title'    => 'Servizi',
			'title_en' => 'Services',
			'meta'     => array(
				'edilmetal_servizi_hero_eyebrow'      => 'Cosa facciamo',
				'edilmetal_servizi_titolo'            => 'Dalla progettazione al montaggio',
				'edilmetal_servizi_sottotitolo'       => 'Un processo integrato per strutture in acciaio su commessa, con relazioni di calcolo firmate da tecnici abilitati.',
				'edilmetal_servizi_intro_titolo'      => 'Carpenteria metallica completa',
				'edilmetal_servizi_intro_testo'       => 'Progettiamo, produciamo e montiamo strutture in acciaio per nuove costruzioni e ristrutturazioni.',
				'edilmetal_servizi_flow'              => array(
					'Sopralluogo e consulenza|Analisi delle esigenze in cantiere e preventivazione rapida.',
					'Progettazione e calcoli|Progettazione dedicata con relazioni di calcolo firmate da tecnici abilitati.',
					'Produzione in officina|Taglio, saldatura e assemblaggio della carpenteria nella nostra officina.',
					'Montaggio in cantiere|Montaggio con mezzi propri e assistenza post-vendita.',
				),
				'edilmetal_servizi_tipologie'         => array(
					'Strutture in acciaio|Ossature portanti per capannoni e edifici industriali.',
					'Strutture miste|Soluzioni acciaio-calcestruzzo per solai e edifici.',
					'Scale e pensiline|Scale di sicurezza, scale d\'arredo, pensiline e carport.',
					'Coperture e rivestimenti|Coperture, tamponamenti e facciate ventilate.',
				),
				'edilmetal_servizi_callout_titolo'    => 'Hai una commessa in mente?',
				'edilmetal_servizi_callout_testo'     => 'Raccontaci il tuo progetto: ti rispondiamo con un preventivo dedicato.',
				'edilmetal_servizi_callout_cta_label' => 'Richiedi un preventivo',
				'edilmetal_servizi_callout_cta_url'   => '/contatti',
			),
			'meta_en'  => array(
				'edilmetal_servizi_hero_eyebrow'      => 'What we do',
				'edilmetal_servizi_titolo'            => 'From design to assembly',
				'edilmetal_servizi_sottotitolo'       => 'An integrated process for made-to-order steel structures, with calculations signed by qualified engineers.',
				'edilmetal_servizi_intro_titolo'      => 'Complete structural steelwork',
				'edilmetal_servizi_intro_testo'       => 'We design, fabricate and assemble steel structures for new builds and renovations.',
				'edilmetal_servizi_callout_titolo'    => 'Have a project in mind?',
				'edilmetal_servizi_callout_testo'     => 'Tell us about your project: we reply with a dedicated quote.',
				'edilmetal_servizi_callout_cta_label' => 'Request a quote',
			),
		);
	}

	/**
	 * Pagina Azienda (Chi siamo / Dove siamo).
	 *
	 * @return array<string,mixed>
	 */
	private static function page_azienda(): array {
		return array(
			'key'      => 'azienda',
			'title'    => 'Azienda',
			'title_en' => 'Company',
			'meta'     => array(
				'edilmetal_azienda_hero_eyebrow' => 'Chi siamo',
				'edilmetal_azienda_titolo'       => 'Carpenteria metallica dal 1997',
				'edilmetal_azienda_sottotitolo'  => 'Fondata a Noceto (PR) da Alessio Ricci e Aldo Medioli, Edilmetal realizza strutture in acciaio su commessa per clienti industriali e prestigiosi.',
				'edilmetal_azienda_storia'       => '<p>Edilmetal S.r.l. nasce nel 1997 dall\'iniziativa di Alessio Ricci e Aldo Medioli. Da allora l\'azienda progetta, produce e monta strutture in carpenteria metallica per l\'edilizia industriale, commerciale e terziaria, sia in nuova costruzione sia in ristrutturazione.</p><p>Lavoriamo su commessa, con progettazione dedicata e relazioni di calcolo firmate da tecnici abilitati iscritti agli albi. Dal sopralluogo al montaggio siamo l\'unico interlocutore del cliente, con assistenza post-vendita.</p>',
				'edilmetal_azienda_img'          => new MediaRef( 'insieme' ),
				'edilmetal_azienda_valori'       => array(
					'Progettazione dedicata|Ogni struttura è calcolata e disegnata sulla specifica commessa.',
					'Qualità certificata|Relazioni di calcolo firmate e materiali tracciati.',
					'Un solo interlocutore|Dal preventivo al post-vendita, seguiamo tutto internamente.',
				),
				'edilmetal_azienda_team'         => array(
					'Alessio Ricci|Fondatore',
					'Aldo Medioli|Fondatore',
				),
				'edilmetal_azienda_stats'        => array( '1997|anno di fondazione', 'Noceto (PR)|sede e officina', '250+|realizzazioni' ),
			),
			'meta_en'  => array(
				'edilmetal_azienda_hero_eyebrow' => 'About us',
				'edilmetal_azienda_titolo'       => 'Structural steelwork since 1997',
				'edilmetal_azienda_sottotitolo'  => 'Founded in Noceto (PR) by Alessio Ricci and Aldo Medioli, Edilmetal builds made-to-order steel structures for industrial and prestigious clients.',
				'edilmetal_azienda_storia'       => '<p>Edilmetal S.r.l. was founded in 1997 by Alessio Ricci and Aldo Medioli. Since then the company has designed, fabricated and assembled structural steelwork for industrial, commercial and tertiary construction, both new builds and renovations.</p><p>We work to order, with dedicated design and structural calculations signed by qualified engineers. From survey to assembly we are the client\'s single point of contact, with after-sales support.</p>',
				'edilmetal_azienda_stats'        => array( '1997|year founded', 'Noceto (PR)|headquarters and workshop', '250+|projects' ),
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
				'edilmetal_privacy_body'    => "<p>La presente informativa descrive le modalità di trattamento dei dati personali degli utenti che consultano il sito e utilizzano i moduli di contatto, ai sensi del Regolamento UE 2016/679 (GDPR).</p><h2>Titolare del trattamento</h2><p>Titolare del trattamento è Edilmetal S.r.l., Piazza Alpini d'Italia 10/A, 43015 Noceto (PR).</p><h2>Finalità e base giuridica</h2><p>I dati forniti tramite i moduli sono trattati per rispondere alle richieste di informazioni e di preventivo. Il conferimento è facoltativo ma necessario per dare seguito alla richiesta.</p><h2>Conservazione</h2><p>I dati sono conservati per il tempo necessario a gestire la richiesta e ad adempiere agli obblighi di legge.</p><h2>Diritti dell'interessato</h2><p>In ogni momento è possibile esercitare i diritti di accesso, rettifica, cancellazione e opposizione scrivendo a info@edilmetal.it.</p>",
				'edilmetal_privacy_updated' => '2026-01-01',
			),
			'meta_en'  => array(
				'edilmetal_privacy_body' => '<p>This notice describes how the personal data of users who browse the site and use the contact forms is processed, pursuant to EU Regulation 2016/679 (GDPR).</p><h2>Data controller</h2><p>The data controller is Edilmetal S.r.l., Piazza Alpini d\'Italia 10/A, 43015 Noceto (PR), Italy.</p><h2>Purposes and legal basis</h2><p>Data provided through the forms is processed to respond to requests for information and quotes. Providing it is optional but required to follow up on the request.</p><h2>Retention</h2><p>Data is kept for as long as necessary to handle the request and to comply with legal obligations.</p><h2>Rights of the data subject</h2><p>You may exercise your rights of access, rectification, erasure and objection at any time by writing to info@edilmetal.it.</p>',
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
				'edilmetal_cookie_body'    => '<p>Questo sito utilizza cookie tecnici necessari al funzionamento e, previo consenso, cookie di terze parti per finalità statistiche.</p><h2>Cookie tecnici</h2><p>Sono indispensabili per la corretta navigazione del sito e non richiedono consenso.</p><h2>Mappa OpenStreetMap</h2><p>Le mappe delle pagine Contatti e Azienda sono servite tramite tile OpenStreetMap, senza cookie di profilazione.</p><h2>Gestione delle preferenze</h2><p>È possibile gestire o revocare il consenso in qualsiasi momento dalle impostazioni del browser.</p>',
				'edilmetal_cookie_updated' => '2026-01-01',
			),
			'meta_en'  => array(
				'edilmetal_cookie_body' => '<p>This site uses technical cookies necessary for its operation and, subject to consent, third-party cookies for statistical purposes.</p><h2>Technical cookies</h2><p>They are essential for correct browsing and do not require consent.</p><h2>OpenStreetMap map</h2><p>The maps on the Contact and Company pages are served through OpenStreetMap tiles, without profiling cookies.</p><h2>Managing preferences</h2><p>You can manage or withdraw consent at any time from your browser settings.</p>',
			),
		);
	}
}
