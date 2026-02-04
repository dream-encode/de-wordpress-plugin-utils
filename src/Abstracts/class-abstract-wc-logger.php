<?php
/**
 * Abstract logger class that relies on the WC_Logger instance.
 *
 * Falls back to error_log if WooCommerce is not available.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils\Abstracts
 * @since   1.0.0
 */

namespace Dream_Encode\WordPress_Plugin_Utils\Abstracts;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract logger class to log data to custom files.
 *
 * Relies on the bundled logger class in WooCommerce with fallback to error_log.
 *
 * @since   1.0.0
 * @package Dream_Encode\WordPress_Plugin_Utils\Abstracts
 */
abstract class Abstract_WC_Logger {

	/**
	 * Log namespace.
	 *
	 * @since  1.0.0
	 * @var    string
	 */
	protected static string $namespace = 'de-wordpress-plugin-utils';

	/**
	 * Log levels in order of severity (most severe first).
	 *
	 * @since  1.0.0
	 * @var    string[]
	 */
	protected static array $log_levels = array(
		'emergency',
		'alert',
		'critical',
		'error',
		'warning',
		'notice',
		'info',
		'debug',
	);

	/**
	 * Get the current log level setting.
	 *
	 * Override this method in child classes to provide plugin-specific log level settings.
	 *
	 * @since  1.0.0
	 * @return string  Log level setting. Return 'off' to disable logging.
	 */
	protected static function get_log_level_setting(): string {
		return 'debug';
	}

	/**
	 * Log data.
	 *
	 * @since  1.0.0
	 * @param  mixed   $data   Data to log.
	 * @param  string  $level  Optional. Log level. Default 'debug'.
	 * @return void
	 */
	public static function log( mixed $data, string $level = 'debug' ): void {
		if ( ! in_array( $level, static::$log_levels, true ) ) {
			return;
		}

		$log_level_setting = static::get_log_level_setting();

		if ( 'off' === $log_level_setting || ! in_array( $log_level_setting, static::$log_levels, true ) ) {
			return;
		}

		static $loggable_log_levels = false;

		if ( false === $loggable_log_levels ) {
			$level_index = array_search( $log_level_setting, static::$log_levels, true );

			if ( false === $level_index ) {
				return;
			}

			$loggable_log_levels = array_slice( static::$log_levels, 0, intval( $level_index ) + 1 );
		}

		if ( ! is_array( $loggable_log_levels ) || ! in_array( $level, $loggable_log_levels, true ) ) {
			return;
		}

		if ( is_object( $data ) || is_array( $data ) ) {
			$data = print_r( $data, true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		}

		if ( function_exists( 'wc_get_logger' ) ) {
			$logger = wc_get_logger();
			$logger->{$level}( $data, array( 'source' => static::$namespace ) );
		} else {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log(
				sprintf(
					'[%1$s] [%2$s] %3$s',
					strtoupper( $level ),
					static::$namespace,
					$data
				)
			);
		}
	}

	/**
	 * Adds an emergency level message.
	 *
	 * @since  1.0.0
	 * @param  mixed  $data  Data to log.
	 * @return void
	 */
	public static function emergency( mixed $data ): void {
		static::log( $data, 'emergency' );
	}

	/**
	 * Adds an alert level message.
	 *
	 * @since  1.0.0
	 * @param  mixed  $data  Data to log.
	 * @return void
	 */
	public static function alert( mixed $data ): void {
		static::log( $data, 'alert' );
	}

	/**
	 * Adds a critical level message.
	 *
	 * @since  1.0.0
	 * @param  mixed  $data  Data to log.
	 * @return void
	 */
	public static function critical( mixed $data ): void {
		static::log( $data, 'critical' );
	}

	/**
	 * Adds an error level message.
	 *
	 * @since  1.0.0
	 * @param  mixed  $data  Data to log.
	 * @return void
	 */
	public static function error( mixed $data ): void {
		static::log( $data, 'error' );
	}

	/**
	 * Adds a warning level message.
	 *
	 * @since  1.0.0
	 * @param  mixed  $data  Data to log.
	 * @return void
	 */
	public static function warning( mixed $data ): void {
		static::log( $data, 'warning' );
	}

	/**
	 * Adds a notice level message.
	 *
	 * @since  1.0.0
	 * @param  mixed  $data  Data to log.
	 * @return void
	 */
	public static function notice( mixed $data ): void {
		static::log( $data, 'notice' );
	}

	/**
	 * Adds an info level message.
	 *
	 * @since  1.0.0
	 * @param  mixed  $data  Data to log.
	 * @return void
	 */
	public static function info( mixed $data ): void {
		static::log( $data, 'info' );
	}

	/**
	 * Adds a debug level message.
	 *
	 * @since  1.0.0
	 * @param  mixed  $data  Data to log.
	 * @return void
	 */
	public static function debug( mixed $data ): void {
		static::log( $data, 'debug' );
	}
}

