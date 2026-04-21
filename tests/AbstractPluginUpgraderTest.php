<?php
/**
 * Tests for Abstract_Plugin_Upgrader.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

namespace Dream_Encode\WordPress_Plugin_Utils\Tests;

use Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures\Test_Plugin_Upgrader;
use Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures\Test_Logger;
use WP_UnitTestCase;

/**
 * Test case for Abstract_Plugin_Upgrader.
 */
class AbstractPluginUpgraderTest extends WP_UnitTestCase {

	/**
	 * Set up before each test.
	 */
	public function set_up(): void {
		parent::set_up();

		Test_Plugin_Upgrader::set_plugin_version( '1.0.0' );
		Test_Plugin_Upgrader::set_database_version( 1 );
		Test_Plugin_Upgrader::set_db_updates( array() );
		Test_Plugin_Upgrader::set_tables( array() );
		Test_Plugin_Upgrader::set_schema( '' );
		Test_Logger::set_log_level( 'debug' );
		Test_Logger::clear_logs();

		delete_option( 'test_upgrader_plugin_version' );
		delete_option( 'test_upgrader_database_version' );
		delete_transient( 'test_upgrader_installing' );
	}

	/**
	 * Tear down after each test.
	 */
	public function tear_down(): void {
		delete_option( 'test_upgrader_plugin_version' );
		delete_option( 'test_upgrader_database_version' );
		delete_transient( 'test_upgrader_installing' );

		parent::tear_down();
	}

	/**
	 * Test that is_new_install returns true when no version is set.
	 */
	public function test_is_new_install_returns_true_when_no_version(): void {
		$this->assertTrue( Test_Plugin_Upgrader::is_new_install() );
	}

	/**
	 * Test that is_new_install returns false when version is set.
	 */
	public function test_is_new_install_returns_false_when_version_exists(): void {
		update_option( 'test_upgrader_plugin_version', '1.0.0' );

		$this->assertFalse( Test_Plugin_Upgrader::is_new_install() );
	}

	/**
	 * Test that check_version triggers install on new install.
	 */
	public function test_check_version_triggers_install_on_new_install(): void {
		Test_Plugin_Upgrader::check_version();

		$this->assertSame( '1.0.0', get_option( 'test_upgrader_plugin_version' ) );
	}

	/**
	 * Test that check_version does not run when version matches.
	 */
	public function test_check_version_skips_when_version_matches(): void {
		update_option( 'test_upgrader_plugin_version', '1.0.0' );

		Test_Plugin_Upgrader::check_version();

		$this->assertSame( '1.0.0', get_option( 'test_upgrader_plugin_version' ) );
	}

	/**
	 * Test that check_version triggers install on version upgrade.
	 */
	public function test_check_version_triggers_install_on_upgrade(): void {
		update_option( 'test_upgrader_plugin_version', '0.9.0' );

		Test_Plugin_Upgrader::check_version();

		$this->assertSame( '1.0.0', get_option( 'test_upgrader_plugin_version' ) );
	}

	/**
	 * Test that install sets the plugin version.
	 */
	public function test_install_sets_plugin_version(): void {
		Test_Plugin_Upgrader::install();

		$this->assertSame( '1.0.0', get_option( 'test_upgrader_plugin_version' ) );
	}

	/**
	 * Test that install sets the database version.
	 */
	public function test_install_sets_database_version(): void {
		Test_Plugin_Upgrader::install();

		$this->assertSame( 1, get_option( 'test_upgrader_database_version' ) );
	}

	/**
	 * Test that needs_db_update returns false when no updates defined.
	 */
	public function test_needs_db_update_returns_false_when_no_updates(): void {
		update_option( 'test_upgrader_database_version', 1 );

		$this->assertFalse( Test_Plugin_Upgrader::needs_db_update() );
	}

	/**
	 * Test that needs_db_update returns false when db version is null.
	 */
	public function test_needs_db_update_returns_false_when_db_version_null(): void {
		Test_Plugin_Upgrader::set_db_updates(
			array(
				2 => array( 'test_upgrader_update_200_dummy_callback' ),
			)
		);

		$this->assertFalse( Test_Plugin_Upgrader::needs_db_update() );
	}

	/**
	 * Test that needs_db_update returns true when updates are pending.
	 */
	public function test_needs_db_update_returns_true_when_updates_pending(): void {
		update_option( 'test_upgrader_database_version', 1 );

		Test_Plugin_Upgrader::set_db_updates(
			array(
				2 => array( 'test_upgrader_update_200_dummy_callback' ),
			)
		);

		$this->assertTrue( Test_Plugin_Upgrader::needs_db_update() );
	}

	/**
	 * Test that needs_db_update returns false when db version is current.
	 */
	public function test_needs_db_update_returns_false_when_current(): void {
		update_option( 'test_upgrader_database_version', 2 );

		Test_Plugin_Upgrader::set_db_updates(
			array(
				2 => array( 'test_upgrader_update_200_dummy_callback' ),
			)
		);

		$this->assertFalse( Test_Plugin_Upgrader::needs_db_update() );
	}

	/**
	 * Test that update_db_version updates the database version.
	 */
	public function test_update_db_version_updates_option(): void {
		Test_Plugin_Upgrader::update_db_version( 5 );

		$this->assertSame( 5, get_option( 'test_upgrader_database_version' ) );
	}

	/**
	 * Test that update_db_version uses default version when null.
	 */
	public function test_update_db_version_uses_default_when_null(): void {
		Test_Plugin_Upgrader::set_database_version( 10 );
		Test_Plugin_Upgrader::update_db_version();

		$this->assertSame( 10, get_option( 'test_upgrader_database_version' ) );
	}

	/**
	 * Test that define_tables adds tables to wpdb.
	 */
	public function test_define_tables_adds_to_wpdb(): void {
		global $wpdb;

		Test_Plugin_Upgrader::set_tables(
			array(
				'test_table' => $wpdb->prefix . 'test_table',
			)
		);

		Test_Plugin_Upgrader::define_tables();

		$this->assertSame( $wpdb->prefix . 'test_table', $wpdb->test_table );
		$this->assertContains( 'test_table', $wpdb->tables );
	}

	/**
	 * Test that create_tables runs without error when schema is empty.
	 */
	public function test_create_tables_handles_empty_schema(): void {
		Test_Plugin_Upgrader::set_schema( '' );

		Test_Plugin_Upgrader::create_tables();

		$this->assertTrue( true );
	}

	/**
	 * Test that create_tables creates table from schema.
	 */
	public function test_create_tables_creates_table(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name = $wpdb->prefix . 'test_upgrader_items';

		$schema = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			KEY name (name)
		) {$wpdb->get_charset_collate()};";

		Test_Plugin_Upgrader::set_schema( $schema );
		Test_Plugin_Upgrader::create_tables();

		$this->assertSame( '', $wpdb->last_error );

		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
	}

	/**
	 * Test that drop_tables removes tables.
	 */
	public function test_drop_tables_removes_tables(): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'test_upgrader_drop';

		$wpdb->query(
			"CREATE TABLE {$table_name} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				PRIMARY KEY (id)
			) {$wpdb->get_charset_collate()}"
		);

		Test_Plugin_Upgrader::set_tables(
			array(
				'test_upgrader_drop' => $table_name,
			)
		);

		Test_Plugin_Upgrader::drop_tables();

		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table_name
			)
		);

		$this->assertNull( $table_exists );
	}

	/**
	 * Test that run_update_callback executes callback.
	 */
	public function test_run_update_callback_executes_callback(): void {
		global $test_upgrade_callbacks_run;
		$test_upgrade_callbacks_run = array();

		require_once __DIR__ . '/Fixtures/upgrader-functions.php';

		Test_Plugin_Upgrader::run_update_callback( 'test_upgrader_update_200_dummy_callback' );

		$this->assertSame( 1, $test_upgrade_callbacks_run['test_upgrader_update_200_dummy_callback'] );
	}

	/**
	 * Test that init hooks are registered.
	 */
	public function test_init_registers_hooks(): void {
		Test_Plugin_Upgrader::init();

		$this->assertSame(
			5,
			has_action( 'init', array( Test_Plugin_Upgrader::class, 'check_version' ) )
		);

		$this->assertNotFalse(
			has_action( 'test_upgrader_run_update_callback', array( Test_Plugin_Upgrader::class, 'run_update_callback' ) )
		);

		$this->assertNotFalse(
			has_action( 'test_upgrader_update_db_to_current_version', array( Test_Plugin_Upgrader::class, 'update_db_version' ) )
		);
	}
}

