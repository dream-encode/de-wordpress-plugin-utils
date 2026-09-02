<?php
/**
 * Version History checkpoints.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils\VersionHistory
 * @since   [NEXT_VERSION]
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\VersionHistory;

use stdClass;

defined( 'ABSPATH' ) || exit;

/**
 * Version History checkpoints.
 *
 * A checkpoint is a full inventory at a moment in time. Historical queries
 * replay events forward from the nearest earlier checkpoint instead of from the
 * baseline, which keeps reconstruction cost flat as the ledger grows.
 *
 * The events table remains the authoritative record. A checkpoint is only ever
 * an optimisation, and is always derivable from the events that precede it.
 *
 * @since [NEXT_VERSION]
 */
class VH_Checkpoints {

	/**
	 * Create a checkpoint from the current state table.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  string       $reason  Optional. Why the checkpoint was taken. Default 'scheduled'.
	 * @param  string|null  $gmt     Optional. GMT timestamp to stamp it with. Default now.
	 * @return int Checkpoint ID, or 0 on failure.
	 */
	public static function create( string $reason = 'scheduled', ?string $gmt = null ): int {
		global $wpdb;

		$gmt = $gmt ?? current_time( 'mysql', true );

		$state = self::build_state();

		$inserted = $wpdb->insert(
			VH_Installer::checkpoints_table(),
			array(
				'blog_id'             => get_current_blog_id(),
				'checkpoint_time'     => get_date_from_gmt( $gmt ),
				'checkpoint_time_gmt' => $gmt,
				'reason'              => $reason,
				'component_count'     => count( $state ),
				'state'               => (string) wp_json_encode( $state ),
			)
		);

		if ( ! $inserted ) {
			return 0;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Get the most recent checkpoint at or before a GMT timestamp.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  string  $gmt  GMT timestamp.
	 * @return stdClass|null
	 */
	public static function latest_before( string $gmt ): ?stdClass {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i
				  WHERE blog_id = %d
				    AND checkpoint_time_gmt <= %s
				  ORDER BY checkpoint_time_gmt DESC, id DESC
				  LIMIT 1',
				VH_Installer::checkpoints_table(),
				get_current_blog_id(),
				$gmt
			)
		);

		if ( null === $row ) {
			return null;
		}

		return $row;
	}

	/**
	 * Count the checkpoints recorded for this site.
	 *
	 * @since  [NEXT_VERSION]
	 * @return int
	 */
	public static function count(): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE blog_id = %d',
				VH_Installer::checkpoints_table(),
				get_current_blog_id()
			)
		);
	}

	/**
	 * Decode a checkpoint's stored state.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  stdClass  $checkpoint  Checkpoint row.
	 * @return array<string, array<string, mixed>>
	 */
	public static function decode( stdClass $checkpoint ): array {
		$state = json_decode( (string) $checkpoint->state, true );

		if ( ! is_array( $state ) ) {
			return array();
		}

		return $state;
	}

	/**
	 * Build a checkpoint payload from the current state table.
	 *
	 * @since  [NEXT_VERSION]
	 * @return array<string, array<string, mixed>>
	 */
	private static function build_state(): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE blog_id = %d',
				VH_Installer::current_state_table(),
				get_current_blog_id()
			)
		);

		$state = array();

		foreach ( (array) $rows as $row ) {
			$key = VH_Inventory::key( $row->component_type, $row->component_file );

			$state[ $key ] = array(
				'component_type' => $row->component_type,
				'component_file' => $row->component_file,
				'component_slug' => $row->component_slug,
				'component_name' => $row->component_name,
				'version'        => $row->version,
				'status'         => $row->status,
				'present'        => (bool) $row->present,
			);
		}

		return $state;
	}
}
