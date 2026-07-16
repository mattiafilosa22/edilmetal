<?php
/**
 * Schema dei campi Meta Box per il CPT "progetto" (realizzazione).
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Fields;

use Edilmetal\Core\Support\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Costruisce i meta box del CPT progetto (commessa, dati tecnici, lavorazioni,
 * galleria e SEO editoriale).
 *
 * Categoria opera e settore sono gestiti come tassonomie e non vengono
 * duplicati qui come meta.
 */
final class ProgettoFields {

	/**
	 * Restituisce l'elenco dei meta box per il CPT progetto.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function meta_boxes(): array {
		return array(
			$this->commessa_box(),
			$this->technical_box(),
			$this->work_box(),
			$this->media_box(),
			$this->seo_box(),
		);
	}

	/**
	 * Costruisce la struttura base di un meta box legato al CPT progetto.
	 *
	 * @param string                         $id     Identificativo del box.
	 * @param string                         $title  Titolo mostrato in admin.
	 * @param array<int,array<string,mixed>> $fields Campi contenuti.
	 * @return array<string,mixed>
	 */
	private function box( string $id, string $title, array $fields ): array {
		return array(
			'id'         => $id,
			'title'      => $title,
			'post_types' => array( Schema::CPT_PROGETTO ),
			'context'    => 'normal',
			'priority'   => 'high',
			'fields'     => $fields,
		);
	}

	/**
	 * Dati della commessa: cliente, luogo, anno, evidenza e descrizione.
	 *
	 * @return array<string,mixed>
	 */
	private function commessa_box(): array {
		return $this->box(
			'edilmetal_progetto_commessa',
			__( 'Dati commessa', 'edilmetal-core' ),
			array(
				array(
					'id'   => Schema::meta( 'cliente' ),
					'name' => __( 'Cliente', 'edilmetal-core' ),
					'type' => 'text',
					'desc' => __( 'Es. Parmalat S.p.A.', 'edilmetal-core' ),
				),
				array(
					'id'   => Schema::meta( 'luogo' ),
					'name' => __( 'Luogo', 'edilmetal-core' ),
					'type' => 'text',
					'desc' => __( 'Es. Collecchio (PR)', 'edilmetal-core' ),
				),
				array(
					'id'   => Schema::meta( 'anno' ),
					'name' => __( 'Anno', 'edilmetal-core' ),
					'type' => 'number',
					'min'  => 1990,
					'max'  => 2100,
					'step' => 1,
				),
				array(
					'id'   => Schema::meta( 'in_evidenza' ),
					'name' => __( 'In evidenza (rail homepage)', 'edilmetal-core' ),
					'type' => 'switch',
					'std'  => 0,
					'desc' => __( 'Mostra la realizzazione tra quelle in evidenza in homepage.', 'edilmetal-core' ),
				),
				array(
					'id'   => Schema::meta( 'descrizione' ),
					'name' => __( 'Descrizione (opzionale, sovrascrive il contenuto)', 'edilmetal-core' ),
					'type' => 'wysiwyg',
					'desc' => __( 'Se vuoto viene usato il contenuto principale del progetto.', 'edilmetal-core' ),
				),
			)
		);
	}

	/**
	 * Scheda tecnica della struttura in acciaio.
	 *
	 * @return array<string,mixed>
	 */
	private function technical_box(): array {
		return $this->box(
			'edilmetal_progetto_tecnica',
			__( 'Dati tecnici', 'edilmetal-core' ),
			array(
				array(
					'id'   => Schema::meta( 'tipologia' ),
					'name' => __( 'Tipologia strutturale', 'edilmetal-core' ),
					'type' => 'text',
					'desc' => __( 'Es. Struttura in acciaio con copertura in lamiera grecata.', 'edilmetal-core' ),
				),
				array(
					'id'   => Schema::meta( 'superficie_mq' ),
					'name' => __( 'Superficie coperta (m²)', 'edilmetal-core' ),
					'type' => 'number',
					'min'  => 0,
					'step' => 1,
				),
				array(
					'id'   => Schema::meta( 'luce_campata_m' ),
					'name' => __( 'Luce / campata (m)', 'edilmetal-core' ),
					'type' => 'number',
					'min'  => 0,
					'step' => 0.1,
				),
				array(
					'id'   => Schema::meta( 'altezza_m' ),
					'name' => __( 'Altezza (m)', 'edilmetal-core' ),
					'type' => 'number',
					'min'  => 0,
					'step' => 0.1,
				),
				array(
					'id'   => Schema::meta( 'peso_acciaio_t' ),
					'name' => __( 'Peso acciaio (t)', 'edilmetal-core' ),
					'type' => 'number',
					'min'  => 0,
					'step' => 0.1,
				),
			)
		);
	}

	/**
	 * Lavorazioni eseguite e materiali impiegati.
	 *
	 * @return array<string,mixed>
	 */
	private function work_box(): array {
		return $this->box(
			'edilmetal_progetto_lavorazioni',
			__( 'Lavorazioni e materiali', 'edilmetal-core' ),
			array(
				array(
					'id'          => Schema::meta( 'lavorazioni' ),
					'name'        => __( 'Lavorazioni', 'edilmetal-core' ),
					'type'        => 'textarea',
					'desc'        => __( 'Una voce per riga.', 'edilmetal-core' ),
					'placeholder' => __( "Progettazione e calcoli strutturali\nProduzione in officina\nMontaggio in cantiere", 'edilmetal-core' ),
				),
				array(
					'id'          => Schema::meta( 'materiali' ),
					'name'        => __( 'Materiali', 'edilmetal-core' ),
					'type'        => 'textarea',
					'desc'        => __( 'Una voce per riga.', 'edilmetal-core' ),
					'placeholder' => __( "Acciaio S275JR\nLamiera grecata zincata\nBulloneria ad alta resistenza", 'edilmetal-core' ),
				),
			)
		);
	}

	/**
	 * Galleria fotografica della realizzazione.
	 *
	 * @return array<string,mixed>
	 */
	private function media_box(): array {
		return $this->box(
			'edilmetal_progetto_media',
			__( 'Galleria', 'edilmetal-core' ),
			array(
				array(
					'id'               => Schema::meta( 'galleria' ),
					'name'             => __( 'Galleria foto', 'edilmetal-core' ),
					'type'             => 'image_advanced',
					'max_file_uploads' => 20,
					'desc'             => __( 'Da 1 a 20 immagini. La prima è la copertina.', 'edilmetal-core' ),
				),
			)
		);
	}

	/**
	 * Meta SEO editoriali della realizzazione.
	 *
	 * @return array<string,mixed>
	 */
	private function seo_box(): array {
		return $this->box(
			'edilmetal_progetto_seo',
			__( 'SEO', 'edilmetal-core' ),
			array(
				array(
					'id'   => Schema::meta( 'seo_title' ),
					'name' => __( 'SEO — Title', 'edilmetal-core' ),
					'type' => 'text',
				),
				array(
					'id'   => Schema::meta( 'seo_description' ),
					'name' => __( 'SEO — Description', 'edilmetal-core' ),
					'type' => 'textarea',
				),
				array(
					'id'               => Schema::meta( 'seo_og' ),
					'name'             => __( 'SEO — Immagine Open Graph', 'edilmetal-core' ),
					'type'             => 'single_image',
					'max_file_uploads' => 1,
				),
			)
		);
	}
}
