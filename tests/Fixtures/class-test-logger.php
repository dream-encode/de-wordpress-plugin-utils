<?php
/**
 * Test logger fixture.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures;

use Dream_Encode\WordPress_Plugin_Utils\Abstracts\Abstract_WC_Logger;

/**
 * Concrete implementation of Abstract_WC_Logger for testing.
 */
class Test_Logger extends Abstract_WC_Logger {

	/**
	 * Log namespace.
	 *
	 * @var string
	 */
	protected static string $namespace = 'test-logger';

	/**
	 * Custom log level setting.
	 *
	 * @var string
	 */
	private static string $custom_log_level = 'debug';

	/**
	 * Buffered log entries for tests.
	 *
	 * @var array<int, array<string, string>>
	 */
	private static array $entries = array();

	/**
	 * Set the log level for testing.
	 *
	 * @param string $level Log level.
	 */
	public static function set_log_level( string $level ): void {
		self::$custom_log_level = $level;
	}

	/**
	 * Clear buffered log entries.
	 *
	 * @return void
	 */
	public static function clear_logs(): void {
		self::$entries = array();
	}

	/**
	 * Buffer log entries without writing to STDERR during tests.
	 *
	 * @param mixed  $data Data to log.
	 * @param string $level Optional. Log level. Default 'debug'.
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

		$level_index = array_search( $log_level_setting, static::$log_levels, true );

		if ( false === $level_index ) {
			return;
		}

		$loggable_log_levels = array_slice( static::$log_levels, 0, intval( $level_index ) + 1 );

		if ( ! in_array( $level, $loggable_log_levels, true ) ) {
			return;
		}

		if ( is_object( $data ) || is_array( $data ) ) {
			$data = print_r( $data, true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		}

		self::$entries[] = array(
			'level'   => $level,
			'message' => (string) $data,
		);
	}

	/**
	 * Get the current log level setting.
	 *
	 * @return string
	 */
	protected static function get_log_level_setting(): string {
		return self::$custom_log_level;
	}

	/**
	 * Get the namespace for testing.
	 *
	 * @return string
	 */
	public static function get_namespace(): string {
		return static::$namespace;
	}

	/**
	 * Get the log levels for testing.
	 *
	 * @return array<string>
	 */
	public static function get_log_levels(): array {
		return static::$log_levels;
	}
}

