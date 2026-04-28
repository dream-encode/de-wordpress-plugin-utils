<?php
/**
 * Shared static utility functions.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils\Common
 * @since   1.0.0
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Common;

use DateTimeZone;
use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * Shared static utility functions used across Dream Encode plugins.
 *
 * @since 1.0.0
 */
class Functions {

	/**
	 * Define a constant if it is not already defined.
	 *
	 * @since  1.0.0
	 * @param  string  $name   Constant name.
	 * @param  mixed   $value  Constant value.
	 * @return void
	 */
	public static function maybe_define_constant( string $name, mixed $value ): void {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Get a MySQL DateTime string from a timestamp.
	 *
	 * @since  1.0.0
	 * @param  false|float|int  $time             Optional. Unix timestamp. Default false (uses current time).
	 * @param  string           $timezone_string  Optional. Timezone identifier. Default 'UTC'.
	 * @return string|false
	 */
	public static function get_mysql_datetime( false|float|int $time = false, string $timezone_string = 'UTC' ): string|false {
		if ( ! $time ) {
			$time = time();
		}

		if ( ! $timezone_string ) {
			$timezone_string = 'UTC';
		}

		$timezone = new DateTimeZone( $timezone_string );

		return wp_date( 'Y-m-d H:i:s', intval( $time ), $timezone );
	}

	/**
	 * Format a MySQL DateTime string to a short human-readable date/time.
	 *
	 * Example output: "Mon Jan 5, 2026 3:42:00 pm"
	 *
	 * @since  [NEXT_VERSION]
	 * @param  string  $datetime  MySQL DateTime string.
	 * @return string|false
	 */
	public static function mysql_datetime_to_datetime_short( string $datetime ): string|false {
		return mysql2date( 'D M j, Y g:i:s a', $datetime );
	}

	/**
	 * Format a MySQL DateTime string to a long human-readable date/time.
	 *
	 * Example output: "Monday January 5, 2026 at 3:42:00 pm"
	 *
	 * @since  1.0.0
	 * @param  string  $datetime  MySQL DateTime string.
	 * @return string|false
	 */
	public static function mysql_datetime_to_datetime_long( string $datetime ): string|false {
		return mysql2date( 'l F j, Y \a\t g:i:s a', $datetime );
	}

	/**
	 * Format a Unix timestamp to a long human-readable date/time.
	 *
	 * Example output: "Monday January 5, 2026 at 3:42:00 pm"
	 *
	 * @since  1.0.0
	 * @param  null|float|int  $timestamp        Optional. Unix timestamp. Default null (uses current time).
	 * @param  string          $timezone_string  Optional. Timezone identifier. Default 'UTC'.
	 * @return string|false
	 */
	public static function format_timestamp_to_datetime_long( null|float|int $timestamp = null, string $timezone_string = 'UTC' ): string|false {
		if ( ! $timestamp ) {
			$timestamp = time();
		}

		if ( ! $timezone_string ) {
			$timezone_string = 'UTC';
		}

		$timezone = new DateTimeZone( $timezone_string );

		return wp_date( 'l F j, Y \a\t g:i:s a', (int) $timestamp, $timezone );
	}

	/**
	 * Convert a number of seconds to a MM:SS string.
	 *
	 * Example: 313 seconds → "05:13"
	 *
	 * @since  1.0.0
	 * @param  int  $seconds  Number of seconds.
	 * @return string
	 */
	public static function convert_seconds_to_minutes_seconds( int $seconds ): string {
		return sprintf(
			'%02d:%02d',
			intdiv( $seconds, MINUTE_IN_SECONDS ) % MINUTE_IN_SECONDS,
			$seconds % MINUTE_IN_SECONDS
		);
	}

	/**
	 * Get a user's display name by ID, returning 'N/A' when not found.
	 *
	 * @since  1.0.0
	 * @param  int  $user_id  User ID.
	 * @return string
	 */
	public static function get_user_display_name( int $user_id ): string {
		$user = get_user_by( 'id', $user_id );

		if ( ! $user instanceof WP_User ) {
			return 'N/A';
		}

		return $user->user_nicename;
	}
}
