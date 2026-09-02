<?php
/**
 * Version History reconstruction queries.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils\VersionHistory
 * @since   1.10.0
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\VersionHistory;

use stdClass;

defined( 'ABSPATH' ) || exit;

/**
 * Version History reconstruction queries.
 *
 * Answers "what was installed at this moment" from recorded evidence, and says
 * so plainly when there is none. Nothing here extrapolates backwards from the
 * baseline: state before the baseline is unknown, not assumed.
 *
 * @since 1.10.0
 */
class VH_History_Query {

	/**
	 * Reconstruct the software state at a GMT timestamp.
	 *
	 * @since  1.10.0
	 * @param  string  $gmt  GMT timestamp in MySQL format.
	 * @return array<string, mixed>
	 */
	public static function state_at( string $gmt ): array {
		$baseline = VH_Options::baseline_gmt();

		$result = array(
			'known'              => false,
			'requested_gmt'      => $gmt,
			'history_started_at' => $baseline,
			'state'              => array(),
		);

		if ( '' === $baseline || $gmt < $baseline ) {
			return $result;
		}

		$checkpoint = VH_Checkpoints::latest_before( $gmt );

		$state = null !== $checkpoint ? VH_Checkpoints::decode( $checkpoint ) : array();
		$since = null !== $checkpoint ? (string) $checkpoint->checkpoint_time_gmt : '';

		foreach ( self::events_between( $since, $gmt ) as $event ) {
			$state = self::apply( $state, $event );
		}

		$result['known'] = true;
		$result['state'] = $state;

		return $result;
	}

	/**
	 * Reconstruct the state of a single component at a GMT timestamp.
	 *
	 * @since  1.10.0
	 * @param  string  $type  Component type.
	 * @param  string  $file  Component file.
	 * @param  string  $gmt   GMT timestamp in MySQL format.
	 * @return array<string, mixed>|null Null when the state is unknown or the component did not exist.
	 */
	public static function component_at( string $type, string $file, string $gmt ): ?array {
		$result = self::state_at( $gmt );

		if ( ! $result['known'] ) {
			return null;
		}

		$key = VH_Inventory::key( $type, $file );

		if ( ! isset( $result['state'][ $key ] ) ) {
			return null;
		}

		return $result['state'][ $key ];
	}

	/**
	 * Apply one event to a reconstructed state map.
	 *
	 * @since  1.10.0
	 * @param  array<string, array<string, mixed>>  $state  State map.
	 * @param  stdClass  $event  Event row.
	 * @return array<string, array<string, mixed>>
	 */
	public static function apply( array $state, stdClass $event ): array {
		$key = VH_Inventory::key( $event->component_type, $event->component_file );

		if ( ! isset( $state[ $key ] ) ) {
			$state[ $key ] = array(
				'component_type' => $event->component_type,
				'component_file' => $event->component_file,
				'component_slug' => $event->component_slug,
				'component_name' => $event->component_name,
				'version'        => null,
				'status'         => 'unknown',
				'present'        => true,
			);
		}

		if ( '' !== (string) $event->component_name ) {
			$state[ $key ]['component_name'] = $event->component_name;
		}

		if ( 'deleted' === $event->event_type ) {
			$state[ $key ]['present'] = false;
			$state[ $key ]['status']  = 'deleted';

			return $state;
		}

		$state[ $key ]['present'] = true;

		if ( null !== $event->new_version ) {
			$state[ $key ]['version'] = $event->new_version;
		}

		if ( null !== $event->new_status ) {
			$state[ $key ]['status'] = $event->new_status;
		}

		return $state;
	}

	/**
	 * Get events in a GMT window, in replay order.
	 *
	 * The secondary sort on `id` matters: a bulk update writes several events
	 * inside the same second, and replaying them out of order lands on the
	 * wrong version.
	 *
	 * @since  1.10.0
	 * @param  string  $after   Exclusive lower bound. Empty string for no lower bound.
	 * @param  string  $until   Inclusive upper bound.
	 * @return array<int, stdClass>
	 */
	public static function events_between( string $after, string $until ): array {
		global $wpdb;

		$table = VH_Installer::events_table();

		if ( '' === $after ) {
			return (array) $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i
					  WHERE blog_id = %d
					    AND event_time_gmt <= %s
					  ORDER BY event_time_gmt ASC, id ASC',
					$table,
					get_current_blog_id(),
					$until
				)
			);
		}

		return (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i
				  WHERE blog_id = %d
				    AND event_time_gmt > %s
				    AND event_time_gmt <= %s
				  ORDER BY event_time_gmt ASC, id ASC',
				$table,
				get_current_blog_id(),
				$after,
				$until
			)
		);
	}

	/**
	 * Get the complete event history for one component, oldest first.
	 *
	 * @since  1.10.0
	 * @param  string       $type    Component type.
	 * @param  string       $file    Component file.
	 * @param  string|null  $before  Optional. Inclusive GMT upper bound.
	 * @return array<int, stdClass>
	 */
	public static function component_history( string $type, string $file, ?string $before = null ): array {
		global $wpdb;

		$table = VH_Installer::events_table();

		if ( null === $before ) {
			return (array) $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i
					  WHERE blog_id = %d AND component_type = %s AND component_file = %s
					  ORDER BY event_time_gmt ASC, id ASC',
					$table,
					get_current_blog_id(),
					$type,
					$file
				)
			);
		}

		return (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i
				  WHERE blog_id = %d AND component_type = %s AND component_file = %s
				    AND event_time_gmt <= %s
				  ORDER BY event_time_gmt ASC, id ASC',
				$table,
				get_current_blog_id(),
				$type,
				$file,
				$before
			)
		);
	}

	/**
	 * Resolve a user-supplied plugin identifier to a component file.
	 *
	 * Accepts either a directory slug or a full plugin basename, so
	 * `woocommerce` and `woocommerce/woocommerce.php` both work.
	 *
	 * @since  1.10.0
	 * @param  string  $type        Component type.
	 * @param  string  $identifier  Slug or file.
	 * @return string|null
	 */
	public static function resolve_component( string $type, string $identifier ): ?string {
		global $wpdb;

		$table = VH_Installer::current_state_table();

		$file = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT component_file FROM %i
				  WHERE blog_id = %d AND component_type = %s AND ( component_file = %s OR component_slug = %s )
				  ORDER BY present DESC, id ASC
				  LIMIT 1',
				$table,
				get_current_blog_id(),
				$type,
				$identifier,
				$identifier
			)
		);

		return null !== $file ? (string) $file : null;
	}

	/**
	 * Get every current state row for this site.
	 *
	 * @since  1.10.0
	 * @param  bool  $present_only  Optional. Exclude components no longer on disk. Default true.
	 * @return array<int, stdClass>
	 */
	public static function current_state( bool $present_only = true ): array {
		global $wpdb;

		$table = VH_Installer::current_state_table();

		if ( $present_only ) {
			return (array) $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE blog_id = %d AND present = 1
					  ORDER BY component_type ASC, component_name ASC',
					$table,
					get_current_blog_id()
				)
			);
		}

		return (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE blog_id = %d
				  ORDER BY component_type ASC, component_name ASC',
				$table,
				get_current_blog_id()
			)
		);
	}

	/**
	 * Count recorded events for this site.
	 *
	 * @since  1.10.0
	 * @return int
	 */
	public static function count_events(): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE blog_id = %d',
				VH_Installer::events_table(),
				get_current_blog_id()
			)
		);
	}
}
