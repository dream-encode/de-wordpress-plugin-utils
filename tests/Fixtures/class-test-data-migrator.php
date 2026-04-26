<?php
/**
 * Test data migrator fixture.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures;

use Dream_Encode\WordPress_Plugin_Utils\Abstracts\Abstract_Data_Migrator;

/**
 * Concrete Abstract_Data_Migrator implementation that persists state in memory.
 */
class Test_Data_Migrator extends Abstract_Data_Migrator {

	/**
	 * Migrator slug.
	 *
	 * @var string
	 */
	public $migrator = 'test_data_migrator';

	/**
	 * Migration records keyed by ID.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public static array $stored_migrations = array();

	/**
	 * Migration run records keyed by ID.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public static array $stored_migration_runs = array();

	/**
	 * Messages captured during the test run.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public static array $messages = array();

	/**
	 * Completed migrations captured during the test run.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public static array $completed_migrations = array();

	/**
	 * Completed migration runs captured during the test run.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public static array $completed_migration_runs = array();

	/**
	 * Plugin-level settings consulted by the abstract.
	 *
	 * @var array<string, mixed>
	 */
	public static array $plugin_settings = array(
		'migration_action_scheduler_queue_mode'                 => 'async',
		'migration_action_scheduler_queue_mode_scheduled_delay' => 10,
	);

	/**
	 * Per-migrator settings consulted by the abstract.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	public static array $migrator_settings = array();

	/**
	 * Next auto-increment ID for new migration records.
	 *
	 * @var int
	 */
	private static int $next_migration_id = 1;

	/**
	 * Next auto-increment ID for new migration run records.
	 *
	 * @var int
	 */
	private static int $next_migration_run_id = 1;

	/**
	 * Reset all captured state between tests.
	 */
	public static function reset(): void {
		self::$stored_migrations        = array();
		self::$stored_migration_runs    = array();
		self::$messages                 = array();
		self::$completed_migrations     = array();
		self::$completed_migration_runs = array();
		self::$migrator_settings        = array();
		self::$next_migration_id        = 1;
		self::$next_migration_run_id    = 1;
	}

	protected function create_new_migration( array $migration ) {
		$migration['migration_id'] = self::$next_migration_id++;

		self::$stored_migrations[ $migration['migration_id'] ] = $migration;

		return $migration;
	}

	protected function create_new_migration_run( array $migration ) {
		$run_id = self::$next_migration_run_id++;

		self::$stored_migration_runs[ $run_id ] = array_merge(
			$migration,
			array( 'migration_run_id' => $run_id )
		);

		return $run_id;
	}

	protected function get_migration_option( $migration_id ) {
		return self::$stored_migrations[ $migration_id ] ?? false;
	}

	protected function update_migration_option( $migration_id, $data ) {
		self::$stored_migrations[ $migration_id ] = $data;
	}

	protected function update_migration( array $progress ) {
		if ( ! empty( $progress['migration_id'] ) ) {
			self::$stored_migrations[ $progress['migration_id'] ] = $progress;
		}
	}

	protected function update_migration_run( array $migration_run ) {
		if ( ! empty( $migration_run['migration_run_id'] ) ) {
			self::$stored_migration_runs[ $migration_run['migration_run_id'] ] = $migration_run;
		}
	}

	protected function get_migration_run_option( $migration_run_id ) {
		return self::$stored_migration_runs[ $migration_run_id ] ?? false;
	}

	protected function update_migration_run_option( $migration_run_id, $data ) {
		self::$stored_migration_runs[ $migration_run_id ] = $data;
	}

	protected function save_completed_migration_run( array $migration_run ) {
		self::$completed_migration_runs[] = $migration_run;
	}

	protected function save_completed_migration( array $progress ) {
		self::$completed_migrations[] = $progress;
	}

	protected function create_migration_message( array $args ) {
		self::$messages[] = $args;
	}

	protected function get_migrator_label( $migrator ) {
		return ucfirst( str_replace( '_', ' ', (string) $migrator ) );
	}

	protected function get_plugin_setting( $key ) {
		return self::$plugin_settings[ $key ] ?? null;
	}

	protected function get_migrator_setting( $migrator, $key, $default_value = false ) {
		return self::$migrator_settings[ $migrator ][ $key ] ?? $default_value;
	}

	protected function get_all_migration_param_keys() {
		return array(
			'migrator',
			'is_dry_run',
			'label',
			'current_position',
			'total_rows',
			'percent_complete',
			'complete',
			'status',
		);
	}

	protected function get_migration_progress_keys() {
		return array(
			'migrator',
			'is_dry_run',
			'label',
			'status',
			'migration_id',
			'migration_run_id',
			'parent_migration_id',
			'current_position',
			'total_rows',
			'percent_complete',
			'complete',
			'total_rows_skipped',
			'total_rows_failed',
			'total_rows_processed',
		);
	}

	protected function get_action_scheduler_hook() {
		return 'test-data-migrator/run-migration';
	}

	protected function get_action_scheduler_group() {
		return 'test-data-migrator';
	}
}
