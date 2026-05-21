<?php
/**
 * Test background processor fixture.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures;

use Dream_Encode\WordPress_Plugin_Utils\Abstracts\Abstract_Background_Processor;

/**
 * Concrete Abstract_Background_Processor implementation backed by static arrays.
 */
class Test_Background_Processor extends Abstract_Background_Processor {

	/**
	 * Processor slug.
	 *
	 * @var string
	 */
	public $processor = 'test_processor';

	/**
	 * Background process option records keyed by ID.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public static array $processes = array();

	/**
	 * Background process DB records keyed by ID.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public static array $db_processes = array();

	/**
	 * Background process run records keyed by ID.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public static array $runs = array();

	/**
	 * Messages captured during the test run.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public static array $messages = array();

	/**
	 * Completed processes captured during the test run.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public static array $completed_processes = array();

	/**
	 * Completed process runs captured during the test run.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public static array $completed_runs = array();

	/**
	 * Captured log messages.
	 *
	 * @var array<int, array{level: string, message: string}>
	 */
	public static array $logs = array();

	/**
	 * Plugin-level settings consulted by the abstract.
	 *
	 * @var array<string, mixed>
	 */
	public static array $plugin_settings = array(
		'background_process_action_scheduler_queue_mode'                  => 'async',
		'background_process_action_scheduler_queue_mode_scheduled_delay' => 10,
	);

	/**
	 * Per-processor settings consulted by the abstract.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	public static array $processor_settings = array();

	/**
	 * Processor-to-sub-param keys map.
	 *
	 * @var array<string, array<string>>
	 */
	public static array $processor_sub_params = array();

	/**
	 * Next auto-increment ID for new process records.
	 *
	 * @var int
	 */
	private static int $next_process_id = 1;

	/**
	 * Next auto-increment ID for new process run records.
	 *
	 * @var int
	 */
	private static int $next_run_id = 1;

	/**
	 * Reset all captured state between tests.
	 */
	public static function reset(): void {
		self::$processes            = array();
		self::$db_processes         = array();
		self::$runs                 = array();
		self::$messages             = array();
		self::$completed_processes  = array();
		self::$completed_runs       = array();
		self::$logs                 = array();
		self::$processor_settings   = array();
		self::$processor_sub_params = array();
		self::$next_process_id      = 1;
		self::$next_run_id          = 1;
	}

	protected function queue_new_background_process( $args ) {
		$args['background_processes_id'] = self::$next_process_id++;

		self::$processes[ $args['background_processes_id'] ] = $args;
		self::$db_processes[ $args['background_processes_id'] ] = $args;

		$this->background_processes_id = $args['background_processes_id'];

		return $args;
	}

	protected function create_new_background_process( $args ) {
		return $this->queue_new_background_process( $args );
	}

	protected function create_new_background_process_run( $background_process ) {
		$run_id = self::$next_run_id++;

		self::$runs[ $run_id ] = array_merge(
			$background_process,
			array( 'background_processes_run_id' => $run_id )
		);

		return $run_id;
	}

	protected function get_background_process_option( $background_processes_id, $force_refresh = false ) {
		return self::$processes[ $background_processes_id ] ?? false;
	}

	protected function update_background_process_option( $background_processes_id, $data ) {
		self::$processes[ $background_processes_id ] = $data;
	}

	protected function update_background_process( $data ) {
		if ( ! empty( $data['background_processes_id'] ) ) {
			self::$processes[ $data['background_processes_id'] ]    = $data;
			self::$db_processes[ $data['background_processes_id'] ] = $data;
		}
	}

	protected function get_background_process_run_option( $background_processes_run_id ) {
		return self::$runs[ $background_processes_run_id ] ?? false;
	}

	protected function update_background_process_run_option( $background_processes_run_id, $data ) {
		self::$runs[ $background_processes_run_id ] = $data;
	}

	protected function update_background_process_run( $data ) {
		if ( ! empty( $data['background_processes_run_id'] ) ) {
			self::$runs[ $data['background_processes_run_id'] ] = $data;
		}
	}

	protected function save_completed_background_process( $data ) {
		self::$completed_processes[] = $data;

		if ( ! empty( $data['background_processes_id'] ) ) {
			if ( isset( self::$db_processes[ $data['background_processes_id'] ] ) ) {
				self::$db_processes[ $data['background_processes_id'] ]['status']   = self::PROCESS_STATUS_COMPLETE;
				self::$db_processes[ $data['background_processes_id'] ]['complete'] = true;
			}

			unset( self::$processes[ $data['background_processes_id'] ] );
		}
	}

	protected function save_completed_background_process_run( $data ) {
		self::$completed_runs[] = $data;
	}

	protected function get_background_process_by_id( $background_processes_id ) {
		if ( ! isset( self::$db_processes[ $background_processes_id ] ) ) {
			return null;
		}

		return (object) self::$db_processes[ $background_processes_id ];
	}

	protected function update_prerequisite_sub_process_parent_background_process( $background_processes_id ) {
		if ( isset( self::$processes[ $background_processes_id ] ) ) {
			self::$processes[ $background_processes_id ]['parent_background_processes_id'] = $this->background_processes_id;
		}
	}

	protected function get_all_background_process_param_keys() {
		return array(
			'processor',
			'is_dry_run',
			'label',
			'current_position',
			'total_rows',
			'percent_complete',
			'complete',
			'status',
			'background_processes_id',
			'parent_background_processes_id',
			'prerequisite_sub_background_processors',
			'prerequisite_sub_background_processes',
		);
	}

	protected function get_background_process_progress_keys() {
		return array(
			'processor',
			'is_dry_run',
			'label',
			'status',
			'background_processes_id',
			'background_processes_run_id',
			'parent_background_processes_id',
			'current_position',
			'total_rows',
			'percent_complete',
			'complete',
			'total_rows_skipped',
			'total_rows_failed',
			'total_rows_processed',
			'background_process_runs',
			'prerequisite_sub_background_processors',
			'prerequisite_sub_background_processes',
		);
	}

	protected function get_processor_sub_params() {
		return self::$processor_sub_params;
	}

	protected function get_background_processor_label( $processor ) {
		return ucfirst( str_replace( '_', ' ', (string) $processor ) );
	}

	protected function create_background_processes_message( $args ) {
		self::$messages[] = $args;
	}

	protected function get_plugin_setting( $key ) {
		return self::$plugin_settings[ $key ] ?? null;
	}

	protected function get_processor_setting( $processor, $key, $default = false ) {
		return self::$processor_settings[ $processor ][ $key ] ?? $default;
	}

	protected function get_action_scheduler_hook() {
		return 'test-background-processor/run';
	}

	protected function get_action_scheduler_group() {
		return 'test-background-processor';
	}

	public function schedule_background_process_run( $background_processes_run_id, $delay = false ) {
		return 123;
	}

	public function background_process_run_start() {
		parent::background_process_run_start();
	}
}

