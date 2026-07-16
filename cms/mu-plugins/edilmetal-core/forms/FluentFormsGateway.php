<?php
/**
 * Gateway verso Fluent Forms (free): unico punto di contatto con l'API del
 * plugin, cosi il resto del modulo resta disaccoppiato dai suoi interni.
 *
 * Tutte le chiamate degradano in modo sicuro se Fluent Forms non e attivo:
 * i metodi restituiscono null / WP_Error senza mai emettere fatal.
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Forms;

use Throwable;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Wrapper sottile sulle classi di Fluent Forms usate per provisioning e invio.
 */
final class FluentFormsGateway {

	/**
	 * Modello del form di Fluent Forms.
	 *
	 * @var string
	 */
	private const FORM_MODEL = '\FluentForm\App\Models\Form';

	/**
	 * Modello delle meta del form.
	 *
	 * @var string
	 */
	private const FORM_META_MODEL = '\FluentForm\App\Models\FormMeta';

	/**
	 * Service che gestisce l'intera pipeline di submission (insert + notifiche).
	 *
	 * @var string
	 */
	private const SUBMISSION_SERVICE = '\FluentForm\App\Services\Form\SubmissionHandlerService';

	/**
	 * Indica se Fluent Forms e attivo e le API attese sono disponibili.
	 */
	public function is_available(): bool {
		return function_exists( 'wpFluent' )
			&& class_exists( self::FORM_MODEL )
			&& class_exists( self::FORM_META_MODEL )
			&& class_exists( self::SUBMISSION_SERVICE );
	}

	/**
	 * Verifica l'esistenza di un form dato il suo ID.
	 *
	 * @param int $form_id ID del form.
	 */
	public function form_exists( int $form_id ): bool {
		if ( $form_id <= 0 || ! $this->is_available() ) {
			return false;
		}

		try {
			$model = self::FORM_MODEL;

			return null !== $model::find( $form_id );
		} catch ( Throwable $e ) {
			return false;
		}
	}

	/**
	 * Crea un form con i relativi meta (settings + notifiche).
	 *
	 * @param array<string,mixed>               $attributes Attributi del form (title, form_fields, ...).
	 * @param array<string,array<mixed>|string> $metas      Meta da persistere (formSettings, notifications, ...).
	 * @return int|WP_Error ID del form creato, oppure errore.
	 */
	public function create_form( array $attributes, array $metas ) {
		if ( ! $this->is_available() ) {
			return new WP_Error( 'edilmetal_ff_unavailable', 'Fluent Forms non e attivo.' );
		}

		try {
			$model = self::FORM_MODEL;
			$meta  = self::FORM_META_MODEL;

			$form = ( new $model() )->create( $attributes );

			if ( ! isset( $form->id ) ) {
				return new WP_Error( 'edilmetal_ff_create_failed', 'Creazione del form non riuscita.' );
			}

			$form_id = (int) $form->id;

			foreach ( $metas as $key => $value ) {
				$meta::persist( $form_id, $key, $value );
			}

			return $form_id;
		} catch ( Throwable $e ) {
			return new WP_Error( 'edilmetal_ff_create_exception', $e->getMessage() );
		}
	}

	/**
	 * Inserisce una submission riusando l'intera pipeline di Fluent Forms
	 * (validazione, entry, notifiche): l'entry compare in "Entries" e scattano
	 * le notifiche configurate sul form.
	 *
	 * @param int                 $form_id   ID del form di destinazione.
	 * @param array<string,mixed> $form_data Dati gia mappati sui name dei campi del form.
	 * @return int|WP_Error ID della submission creata, oppure errore.
	 */
	public function insert_submission( int $form_id, array $form_data ) {
		if ( ! $this->is_available() || ! $this->form_exists( $form_id ) ) {
			return new WP_Error( 'edilmetal_ff_unavailable', 'Fluent Forms non e disponibile.' );
		}

		try {
			$service = self::SUBMISSION_SERVICE;
			$result  = ( new $service() )->handleSubmission( $form_data, $form_id );

			$insert_id = is_array( $result ) && isset( $result['insert_id'] ) ? (int) $result['insert_id'] : 0;

			if ( $insert_id <= 0 ) {
				return new WP_Error( 'edilmetal_ff_submission_failed', 'Submission non registrata.' );
			}

			return $insert_id;
		} catch ( Throwable $e ) {
			return new WP_Error( 'edilmetal_ff_submission_exception', $e->getMessage() );
		}
	}
}
