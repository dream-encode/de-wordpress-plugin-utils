<?php
/**
 * Tests for Abstract_Data_Migrator.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Tests;

use Dream_Encode\WordPress_Plugin_Utils\Abstracts\Abstract_Data_Migrator;
use Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures\Test_Data_Migrator;
use WP_UnitTestCase;

/**
 * Test case for Abstract_Data_Migrator.
 */
class AbstractDataMigratorTest extends WP_UnitTestCase {

	/**
	 * Set up before each test.
	 */
	public function set_up(): void {
		parent::set_up();

		Test_Data_Migrator::reset();
	}

	/**
	 * Constructor initializes the current time.
	 */
	public function test_constructor_sets_current_time(): void {
		$migrator = new Test_Data_Migrator();

		$this->assertNotNull( $migrator->get_current_time() );
		$this->assertIsFloat( $migrator->get_current_time() );
	}

	/**
	 * Default state returns expected values.
	 */
	public function test_default_state(): void {
		$migrator = new Test_Data_Migrator();

		$this->assertSame( 0, $migrator->get_current_position() );
		$this->assertSame( 0, $migrator->get_total_rows() );
		$this->assertSame( 0.0, (float) $migrator->get_percent_complete() );
		$this->assertFalse( $migrator->get_complete() );
		$this->assertFalse( $migrator->get_migration_id() );
		$this->assertFalse( $migrator->get_migration_run_id() );
		$this->assertSame( Abstract_Data_Migrator::MIGRATION_STATUS_QUEUED, $migrator->get_status() );
	}

	/**
	 * The migrator slug is exposed via get_migrator().
	 */
	public function test_get_migrator_returns_slug(): void {
		$migrator = new Test_Data_Migrator();

		$this->assertSame( 'test_data_migrator', $migrator->get_migrator() );
	}

	/**
	 * set_is_dry_run() coerces to bool.
	 */
	public function test_set_is_dry_run_coerces_to_bool(): void {
		$migrator = new Test_Data_Migrator();

		$migrator->set_is_dry_run( 1 );
		$this->assertTrue( $migrator->get_is_dry_run() );

		$migrator->set_is_dry_run( 0 );
		$this->assertFalse( $migrator->get_is_dry_run() );
	}

	/**
	 * update_percent_complete() is a no-op when total rows is zero.
	 */
	public function test_update_percent_complete_noop_when_total_rows_zero(): void {
		$migrator = new Test_Data_Migrator();

		$migrator->update_percent_complete();

		$this->assertSame( 0.0, (float) $migrator->get_percent_complete() );
	}

	/**
	 * update_percent_complete() calculates a rounded ratio.
	 */
	public function test_update_percent_complete_calculates_ratio(): void {
		$migrator                   = new Test_Data_Migrator();
		$migrator->total_rows       = 200;
		$migrator->current_position = 50;

		$migrator->update_percent_complete();

		$this->assertSame( 25.0, (float) $migrator->get_percent_complete() );
	}

	/**
	 * update_percent_complete() caps at 100.
	 */
	public function test_update_percent_complete_caps_at_hundred(): void {
		$migrator                   = new Test_Data_Migrator();
		$migrator->total_rows       = 100;
		$migrator->current_position = 500;

		$migrator->update_percent_complete();

		$this->assertSame( 100.0, (float) $migrator->get_percent_complete() );
	}

	/**
	 * get_label() returns the label property.
	 */
	public function test_get_label_returns_default_label(): void {
		$migrator = new Test_Data_Migrator();

		$this->assertSame( 'Abstract Data Migrator', $migrator->get_label() );
	}

	/**
	 * get_progress() exposes runtime state.
	 */
	public function test_get_progress_exposes_state(): void {
		$migrator                   = new Test_Data_Migrator();
		$migrator->total_rows       = 100;
		$migrator->current_position = 25;

		$progress = $migrator->get_progress();

		$this->assertIsArray( $progress );
		$this->assertSame( 100, $progress['total_rows'] );
		$this->assertSame( 25, $progress['current_position'] );
		$this->assertSame( 'test_data_migrator', $progress['migrator'] );
		$this->assertSame( $migrator, $progress['migrator_instance'] );
	}

	/**
	 * get_progress_keys() returns the migration progress keys.
	 */
	public function test_get_progress_keys_returns_migration_keys(): void {
		$migrator = new Test_Data_Migrator();

		$keys = $migrator->get_progress_keys();

		$this->assertContains( 'migrator', $keys );
		$this->assertContains( 'migration_id', $keys );
		$this->assertContains( 'current_position', $keys );
	}
}
