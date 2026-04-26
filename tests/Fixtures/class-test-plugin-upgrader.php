<?php
/**
 * Test plugin upgrader fixture.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures;

use Dream_Encode\WordPress_Plugin_Utils\Abstracts\Abstract_Plugin_Upgrader;

/**
 * Concrete implementation of Abstract_Plugin_Upgrader for testing.
 */
class Test_Plugin_Upgrader extends Abstract_Plugin_Upgrader {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private static string $plugin_version = '1.0.0';

	/**
	 * Database version.
	 *
	 * @var int
	 */
	private static int $database_version = 1;

	/**
	 * DB updates.
	 *
	 * @var array<int, array<string>>
	 */
	private static array $db_updates = array();

	/**
	 * Tables.
	 *
	 * @var array<string, string>
	 */
	private static array $tables = array();

	/**
	 * Schema.
	 *
	 * @var string
	 */
	private static string $schema = '';

	/**
	 * Set the plugin version for testing.
	 *
	 * @param string $version Plugin version.
	 */
	public static function set_plugin_version( string $version ): void {
		self::$plugin_version = $version;
	}

	/**
	 * Set the database version for testing.
	 *
	 * @param int $version Database version.
	 */
	public static function set_database_version( int $version ): void {
		self::$database_version = $version;
	}

	/**
	 * Set the DB updates for testing.
	 *
	 * @param array<int, array<string>> $updates DB updates.
	 */
	public static function set_db_updates( array $updates ): void {
		self::$db_updates = $updates;
	}

	/**
	 * Set the tables for testing.
	 *
	 * @param array<string, string> $tables Tables.
	 */
	public static function set_tables( array $tables ): void {
		self::$tables = $tables;
	}

	/**
	 * Set the schema for testing.
	 *
	 * @param string $schema Schema.
	 */
	public static function set_schema( string $schema ): void {
		self::$schema = $schema;
	}

	/**
	 * {@inheritdoc}
	 */
	protected static function get_prefix(): string {
		return 'test_upgrader';
	}

	/**
	 * {@inheritdoc}
	 */
	protected static function get_plugin_version(): string {
		return self::$plugin_version;
	}

	/**
	 * {@inheritdoc}
	 */
	protected static function get_database_version(): int {
		return self::$database_version;
	}

	/**
	 * {@inheritdoc}
	 */
	protected static function get_plugin_path(): string {
		return dirname( __DIR__, 2 );
	}

	/**
	 * {@inheritdoc}
	 */
	protected static function get_text_domain(): string {
		return 'test-upgrader';
	}

	/**
	 * {@inheritdoc}
	 */
	protected static function get_action_scheduler_group(): string {
		return 'test-upgrader';
	}

	/**
	 * {@inheritdoc}
	 */
	protected static function get_db_updates(): array {
		return self::$db_updates;
	}

	/**
	 * {@inheritdoc}
	 */
	protected static function get_upgrader_functions_path(): string {
		return __DIR__ . '/upgrader-functions.php';
	}

	/**
	 * {@inheritdoc}
	 */
	protected static function get_logger_class(): string {
		return Test_Logger::class;
	}

	/**
	 * {@inheritdoc}
	 */
	protected static function get_schema(): string {
		return self::$schema;
	}

	/**
	 * {@inheritdoc}
	 */
	protected static function get_tables(): array {
		return self::$tables;
	}
}

