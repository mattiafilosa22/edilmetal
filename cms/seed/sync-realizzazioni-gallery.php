<?php
/**
 * Synchronise historic project galleries from the committed manifest.
 *
 * Usage: wp eval-file cms/seed/sync-realizzazioni-gallery.php [apply]
 * Without `apply`, the command only reports intended changes.
 */

declare( strict_types=1 );

$apply         = isset( $args[0] ) && 'apply' === $args[0];
$manifest_path = dirname( __DIR__ ) . '/mu-plugins/edilmetal-core/seed/Data/realizzazioni-storiche.json';
$records       = json_decode( (string) file_get_contents( $manifest_path ), true );

if ( ! is_array( $records ) ) {
	WP_CLI::error( 'Manifest realizzazioni non valido.' );
}

foreach ( $records as $record ) {
	$ref = isset( $record['ref'] ) && is_string( $record['ref'] ) ? $record['ref'] : '';
	if ( '' === $ref || ! isset( $record['media'] ) || ! is_array( $record['media'] ) ) {
		WP_CLI::warning( 'Record manifest incompleto: ' . $ref );
		continue;
	}

	// The manifest is the canonical gallery order. Do not reverse it here: doing
	// so would make a second run undo the order seeded into new installations.
	$media_keys = $record['media'];
	$expected = array();
	foreach ( $media_keys as $media_key ) {
		$attachments = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'meta_key'       => '_edilmetal_seed_ref',
				'meta_value'     => 'media:' . $media_key,
			)
		);
		if ( array() === $attachments ) {
			WP_CLI::warning( $ref . ': allegato mancante (' . $media_key . ')' );
			$expected = array();
			break;
		}
		$expected[] = (int) $attachments[0];
	}
	if ( array() === $expected ) {
		continue;
	}

	$projects = get_posts(
		array(
			'post_type'      => 'progetto',
			'post_status'    => 'any',
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'meta_key'       => '_edilmetal_seed_ref',
			'meta_value'     => 'progetto:' . $ref . ':it',
		)
	);
	if ( array() === $projects ) {
		WP_CLI::warning( $ref . ': progetto IT mancante.' );
		continue;
	}

	foreach ( $projects as $project_id ) {
		$current = array_map( 'intval', get_post_meta( (int) $project_id, 'edilmetal_galleria', false ) );
		if ( $current === $expected ) {
			WP_CLI::log( $ref . ': gia allineato.' );
			continue;
		}
		if ( count( $current ) !== count( $expected ) || array_diff( $current, $expected ) !== array() ) {
			WP_CLI::warning( $ref . ': galleria modificata editorialmente, saltata.' );
			continue;
		}
		WP_CLI::log( $ref . ': ' . ( $apply ? 'aggiornamento.' : 'da aggiornare.' ) );
		if ( ! $apply ) {
			continue;
		}
		delete_post_meta( (int) $project_id, 'edilmetal_galleria' );
		foreach ( $expected as $attachment_id ) {
			add_post_meta( (int) $project_id, 'edilmetal_galleria', $attachment_id );
		}
		set_post_thumbnail( (int) $project_id, $expected[0] );
		update_post_meta( (int) $project_id, 'edilmetal_seo_og', $expected[0] );
	}
}
