<?php
/**
 * Version History source detector.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils\VersionHistory
 * @since   1.10.0
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\VersionHistory;

defined( 'ABSPATH' ) || exit;

/**
 * Version History source detector.
 *
 * Attributes an event to the context that produced it. When the context cannot
 * be established the answer is `unknown`. Nothing here guesses.
 *
 * @since 1.10.0
 */
class VH_Source_Detector {

	/**
	 * Source to report regardless of context.
	 *
	 * @since 1.10.0
	 * @var   string
	 */
	private static string $forced = '';

	/**
	 * Whether this request is running WordPress automatic updates.
	 *
	 * @since 1.10.0
	 * @var   bool
	 */
	private static bool $automatic_update = false;

	/**
	 * Determine the source of the change being recorded.
	 *
	 * @since  1.10.0
	 * @return string
	 */
	public static function detect(): string {
		if ( '' !== self::$forced ) {
			return self::$forced;
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return 'wp-cli';
		}

		if ( self::$automatic_update ) {
			return 'automatic-update';
		}

		if ( wp_doing_cron() ) {
			return 'cron';
		}

		if ( is_admin() && get_current_user_id() ) {
			return 'admin';
		}

		return 'unknown';
	}

	/**
	 * Force a source for the remainder of the request.
	 *
	 * Used by the reconciler, which knows exactly what it is.
	 *
	 * @since  1.10.0
	 * @param  string  $source  Source to report.
	 * @return void
	 */
	public static function force( string $source ): void {
		self::$forced = $source;
	}

	/**
	 * Stop forcing a source.
	 *
	 * @since  1.10.0
	 * @return void
	 */
	public static function release(): void {
		self::$forced = '';
	}

	/**
	 * Flag this request as a WordPress automatic update.
	 *
	 * @since  1.10.0
	 * @return void
	 */
	public static function mark_automatic_update(): void {
		self::$automatic_update = true;
	}
}
