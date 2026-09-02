<?php
/**
 * Version History event recorder.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils\VersionHistory
 * @since   [NEXT_VERSION]
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\VersionHistory;

use stdClass;

defined( 'ABSPATH' ) || exit;

/**
 * Version History event recorder.
 *
 * The only class that writes to the ledger. Events are immutable: this class
 * inserts and never updates or deletes them. Every write also brings the
 * current state table into step, which is what makes reconciliation cheap and
 * keeps it from re-reporting a change that was already captured by a hook.
 *
 * @since [NEXT_VERSION]
 */
class VH_Event_Recorder {

	/**
	 * Record an event and bring current state into step.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  array<string, mixed>  $args  Event arguments.
	 * @return bool Whether a new event row was written.
	 */
	public static function record( array $args ): bool {
		global $wpdb;

		$event = self::normalize( $args );

		if ( '' === $event['component_type'] || '' === $event['event_type'] ) {
			return false;
		}

		$written = false;

		if ( ! self::fingerprint_exists( $event['fingerprint'] ) ) {
			$data = $event;

			$data['metadata'] = empty( $event['metadata'] ) ? null : wp_json_encode( $event['metadata'] );

			$suppress = $wpdb->suppress_errors( true );

			$written = (bool) $wpdb->insert( VH_Installer::events_table(), $data );

			$wpdb->suppress_errors( $suppress );
		}

		self::sync_current_state( $event );

		return $written;
	}

	/**
	 * Record the initial baseline for every installed component.
	 *
	 * The baseline timestamp becomes the earliest moment this site can answer
	 * for. Nothing before it is knowable, and nothing here pretends otherwise.
	 *
	 * @since  [NEXT_VERSION]
	 * @return int Number of components recorded.
	 */
	public static function record_baseline(): int {
		$snapshot = VH_Inventory::snapshot();

		$gmt = current_time( 'mysql', true );

		foreach ( $snapshot as $component ) {
			self::record(
				array(
					'component_type' => $component['component_type'],
					'component_file' => $component['component_file'],
					'component_slug' => $component['component_slug'],
					'component_name' => $component['component_name'],
					'event_type'     => 'baseline',
					'new_version'    => $component['version'],
					'new_status'     => $component['status'],
					'source'         => VH_Source_Detector::detect(),
					'metadata'       => $component['metadata'],
					'event_time_gmt' => $gmt,
				)
			);
		}

		VH_Checkpoints::create( 'baseline', $gmt );

		update_option( VH_Options::BASELINE_GMT, $gmt, true );

		return count( $snapshot );
	}

	/**
	 * Write or update the current state row for a component.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  array<string, mixed>  $event    Normalized event.
	 * @param  bool                  $present  Optional. Whether the component is still on disk. Default true.
	 * @return void
	 */
	public static function sync_current_state( array $event, bool $present = true ): void {
		global $wpdb;

		$table = VH_Installer::current_state_table();

		$existing = self::get_current_state_row( $event['component_type'], $event['component_file'] );

		$data = array(
			'blog_id'        => $event['blog_id'],
			'component_type' => $event['component_type'],
			'component_file' => $event['component_file'],
			'component_slug' => $event['component_slug'],
			'component_name' => $event['component_name'],
			'present'        => $present ? 1 : 0,
			'last_seen'      => $event['event_time'],
			'last_seen_gmt'  => $event['event_time_gmt'],
		);

		$data['version'] = null !== $event['new_version']
			? $event['new_version']
			: ( $existing->version ?? null );

		$data['status'] = null !== $event['new_status']
			? $event['new_status']
			: ( $existing->status ?? 'unknown' );

		$data['metadata'] = empty( $event['metadata'] ) ? null : wp_json_encode( $event['metadata'] );

		if ( null === $existing ) {
			$wpdb->insert( $table, $data );

			return;
		}

		$wpdb->update( $table, $data, array( 'id' => $existing->id ) );
	}

	/**
	 * Flag a component as no longer present without removing its row.
	 *
	 * The row is kept so the last known version survives deletion. That history
	 * is one of the reasons this ledger exists.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  string  $type  Component type.
	 * @param  string  $file  Component file.
	 * @return void
	 */
	public static function mark_absent( string $type, string $file ): void {
		global $wpdb;

		$wpdb->update(
			VH_Installer::current_state_table(),
			array(
				'present'       => 0,
				'status'        => 'deleted',
				'last_seen'     => current_time( 'mysql' ),
				'last_seen_gmt' => current_time( 'mysql', true ),
			),
			array(
				'blog_id'        => get_current_blog_id(),
				'component_type' => $type,
				'component_file' => $file,
			)
		);
	}

	/**
	 * Get the current state row for a component.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  string  $type  Component type.
	 * @param  string  $file  Component file.
	 * @return stdClass|null
	 */
	public static function get_current_state_row( string $type, string $file ): ?stdClass {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE blog_id = %d AND component_type = %s AND component_file = %s',
				VH_Installer::current_state_table(),
				get_current_blog_id(),
				$type,
				$file
			)
		);

		if ( null === $row ) {
			return null;
		}

		return $row;
	}

	/**
	 * Build the deduplication fingerprint for an event.
	 *
	 * Day granularity is deliberate. It collapses a hook-recorded change and a
	 * same-day reconciliation of the identical transition into one row, while
	 * still allowing the same transition to legitimately recur on a later date.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  array<string, mixed>  $event  Normalized event.
	 * @return string
	 */
	public static function fingerprint( array $event ): string {
		return sha1(
			implode(
				'|',
				array(
					(string) $event['blog_id'],
					$event['component_type'],
					$event['component_file'],
					$event['event_type'],
					(string) $event['old_version'],
					(string) $event['new_version'],
					(string) $event['old_status'],
					(string) $event['new_status'],
					substr( $event['event_time_gmt'], 0, 10 ),
				)
			)
		);
	}

	/**
	 * Whether an event with this fingerprint has already been recorded.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  string  $fingerprint  Event fingerprint.
	 * @return bool
	 */
	public static function fingerprint_exists( string $fingerprint ): bool {
		global $wpdb;

		$found = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM %i WHERE fingerprint = %s LIMIT 1',
				VH_Installer::events_table(),
				$fingerprint
			)
		);

		return null !== $found;
	}

	/**
	 * Get the acting user ID, or null when nobody is logged in.
	 *
	 * WP-Cron, WP-CLI, automatic updates and deployment systems all change
	 * software without a WordPress user, and the ledger records that honestly
	 * rather than attributing the change to nobody in particular.
	 *
	 * @since  [NEXT_VERSION]
	 * @return int|null
	 */
	private static function current_user_id(): ?int {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return null;
		}

		return $user_id;
	}

	/**
	 * Fill in defaults and derived values for an event.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  array<string, mixed>  $args  Raw event arguments.
	 * @return array<string, mixed>
	 */
	private static function normalize( array $args ): array {
		$gmt = $args['event_time_gmt'] ?? current_time( 'mysql', true );

		$event = array(
			'blog_id'        => (int) ( $args['blog_id'] ?? get_current_blog_id() ),
			'event_time'     => $args['event_time'] ?? get_date_from_gmt( $gmt ),
			'event_time_gmt' => $gmt,
			'component_type' => (string) ( $args['component_type'] ?? '' ),
			'component_file' => (string) ( $args['component_file'] ?? '' ),
			'component_slug' => (string) ( $args['component_slug'] ?? '' ),
			'component_name' => (string) ( $args['component_name'] ?? '' ),
			'event_type'     => (string) ( $args['event_type'] ?? '' ),
			'old_version'    => $args['old_version'] ?? null,
			'new_version'    => $args['new_version'] ?? null,
			'old_status'     => $args['old_status'] ?? null,
			'new_status'     => $args['new_status'] ?? null,
			'source'         => (string) ( $args['source'] ?? 'unknown' ),
			'user_id'        => $args['user_id'] ?? self::current_user_id(),
			'metadata'       => $args['metadata'] ?? array(),
		);

		$event['fingerprint'] = self::fingerprint( $event );

		return $event;
	}
}
