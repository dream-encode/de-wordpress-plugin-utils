<?php
/**
 * Abstract data migrator class.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils\Abstracts
 * @since   1.0.0
 */

namespace Dream_Encode\WordPress_Plugin_Utils\Abstracts;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract class Abstract_Data_Migrator
 *
 * Base class for batch-style data migrators that run via ActionScheduler.
 * Persistence operations are declared abstract so each consuming plugin can
 * wire its own repository/function library.
 *
 * @since 1.0.0
 */
abstract class Abstract_Data_Migrator {

	/**
	 * Migrator slug.
	 *
	 * @var string
	 */
	public $migrator;

	/**
	 * Is dry run?
	 *
	 * @var bool
	 */
	public $is_dry_run;

	/**
	 * Label.
	 *
	 * @var string
	 */
	public $label = 'Abstract Data Migrator';

	/**
	 * Params.
	 *
	 * @var array<mixed>
	 */
	protected $params = array();

	/**
	 * Current time.
	 *
	 * @var float|null
	 */
	public $current_time;

	/**
	 * Current migration run.
	 *
	 * @var array<mixed>
	 */
	public $current_migration_run = array();

	/**
	 * Migration runs.
	 *
	 * @var array<mixed>
	 */
	protected $migration_runs = array();

	/**
	 * Status.
	 *
	 * @var string
	 */
	public $status = self::MIGRATION_STATUS_QUEUED;

	/**
	 * Errors array.
	 *
	 * @var array<mixed>
	 */
	private $errors = array();

	/**
	 * Data.
	 *
	 * @var array<mixed>
	 */
	public $data = array();

	/**
	 * Queued time of current migration.
	 *
	 * @var null|float
	 */
	protected $queued_time = null;

	/**
	 * Start time of current migration.
	 *
	 * @var null|float
	 */
	protected $start_time = null;

	/**
	 * Time of last run.
	 *
	 * @var null|float
	 */
	protected $last_run_time = null;

	/**
	 * Completed time of current migration.
	 *
	 * @var null|float
	 */
	protected $completed_time = null;

	/**
	 * Total time of current migration.
	 *
	 * @var float
	 */
	protected $total_time = 0;

	/**
	 * Current position.
	 *
	 * @var int
	 */
	public $current_position = 0;

	/**
	 * Percentage complete.
	 *
	 * @var float
	 */
	public $percent_complete = 0;

	/**
	 * Complete.
	 *
	 * @var bool
	 */
	public $complete = false;

	/**
	 * Total rows.
	 *
	 * @var int
	 */
	public $total_rows = 0;

	/**
	 * Total rows limit.
	 *
	 * @var int
	 */
	public $total_rows_limit = -1;

	/**
	 * Total rows failed.
	 *
	 * @var int
	 */
	public $total_rows_failed = 0;

	/**
	 * Total rows skipped.
	 *
	 * @var int
	 */
	public $total_rows_skipped = 0;

	/**
	 * Total rows migrated.
	 *
	 * @var int
	 */
	public $total_rows_migrated = 0;

	/**
	 * Batch size.
	 *
	 * @var int
	 */
	protected $batch_size = 100;

	/**
	 * Default batch size.
	 *
	 * @var int
	 */
	protected $default_batch_size = 100;

	/**
	 * Prevent timeouts.
	 *
	 * @var bool
	 */
	protected $prevent_timeouts = false;

	/**
	 * Results of this run.
	 *
	 * @var array<mixed>
	 */
	public $migration_results = array();

	/**
	 * This migration ID.
	 *
	 * @var false|int
	 */
	public $migration_id = false;

	/**
	 * This migration run ID.
	 *
	 * @var false|int
	 */
	public $migration_run_id = false;

	/**
	 * This migration's action scheduler action ID.
	 *
	 * @var false|int
	 */
	public $as_action_id = false;

	/**
	 * Migration time limit.
	 *
	 * @var int
	 */
	const MIGRATION_TIME_LIMIT = 20;

	/**
	 * Migration memory limit.
	 *
	 * @var string
	 */
	const MIGRATION_MEMORY_LIMIT = '128M';

	/**
	 * Pending status.
	 *
	 * @var string
	 */
	const MIGRATION_STATUS_PENDING = 'pending';

	/**
	 * Queued status.
	 *
	 * @var string
	 */
	const MIGRATION_STATUS_QUEUED = 'queued';

	/**
	 * Processing status.
	 *
	 * @var string
	 */
	const MIGRATION_STATUS_PROCESSING = 'processing';

	/**
	 * Failed status.
	 *
	 * @var string
	 */
	const MIGRATION_STATUS_FAILED = 'failed';

	/**
	 * Cancelled status.
	 *
	 * @var string
	 */
	const MIGRATION_STATUS_CANCELLED = 'cancelled';

	/**
	 * Complete status.
	 *
	 * @var string
	 */
	const MIGRATION_STATUS_COMPLETE = 'complete';

	/**
	 * Initialize migrator.
	 */
	public function __construct() {
		$this->current_time = microtime( true );
	}


	/**
	 * Init.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function init() {
		$this->parse_params();
	}

	/**
	 * Get label.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_label() {
		return $this->label;
	}

	/**
	 * Get is dry run.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public function get_is_dry_run() {
		return $this->is_dry_run;
	}

	/**
	 * Queue new migrator.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed>  $init_params  Initial migrator params.
	 * @return void
	 */
	public function queue_migrator( $init_params = array() ) {
		$queued_migration = wp_parse_args( $init_params, $this->get_progress() );

		$new_migration = $this->create_new_migration( $queued_migration );

		if ( ! $new_migration ) {
			return;
		}

		$new_migration = $this->create_migration_run( $new_migration );

		if ( false === $new_migration || ! $new_migration['migration_id'] ) {
			return;
		}

		$this->update_migration_option( $new_migration['migration_id'], $new_migration );
	}

	/**
	 * Parse params.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function parse_params() {
		$current_migration_run = $this->get_migration_run_option( $this->get_migration_run_id() );

		if ( ! $current_migration_run ) {
			return;
		}

		if ( empty( $current_migration_run['migration_id'] ) ) {
			return;
		}

		$migration_id = $current_migration_run['migration_id'];

		$migration = $this->get_migration_option( $migration_id );

		foreach ( $this->get_all_migration_param_keys() as $key ) {
			if ( isset( $migration[ $key ] ) ) {
				$this->{$key} = $migration[ $key ];
			}
		}
	}

	/**
	 * Maybe update a migration option.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function maybe_update_migration() {
		if ( ! $this->get_migration_id() ) {
			return;
		}

		$migration = $this->get_migration_option( $this->get_migration_id() );

		$this->update_migration_option( $this->get_migration_id(), $migration );
	}

	/**
	 * Get info on the progress of the migration.
	 *
	 * @since  1.0.0
	 * @return array<string, mixed>
	 */
	public function get_progress() {
		$progress = array(
			'migration_id'          => $this->get_migration_id(),
			'migration_run_id'      => $this->get_migration_run_id(),
			'as_action_id'          => $this->get_as_action_id(),
			'migrator'              => $this->get_migrator(),
			'migrator_instance'     => $this,
			'status'                => $this->get_status(),
			'is_dry_run'            => $this->get_is_dry_run(),
			'complete'              => $this->get_complete(),
			'start_time'            => $this->get_start_time(),
			'queued_time'           => $this->get_queued_time(),
			'last_run_time'         => $this->get_last_run_time(),
			'completed_time'        => $this->get_completed_time(),
			'total_rows'            => $this->get_total_rows(),
			'current_position'      => $this->get_current_position(),
			'current_migration_run' => $this->get_current_migration_run(),
			'percent_complete'      => $this->get_percent_complete(),
			'total_rows_migrated'   => $this->get_total_rows_migrated(),
			'total_rows_skipped'    => $this->get_total_rows_skipped(),
			'total_rows_failed'     => $this->get_total_rows_failed(),
			'total_time'            => $this->get_total_time(),
			'migration_results'     => $this->migration_results,
			'migration_runs'        => $this->migration_runs,
		);

		return $progress;
	}

	/**
	 * Update the percent completed based on the current migration run and the total rows.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function update_percent_complete() {
		if ( 0 === $this->get_total_rows() ) {
			return;
		}

		$this->percent_complete = round( min( ( $this->get_current_position() / $this->get_total_rows() ) * 100, 100 ), 1 );
	}


	/**
	 * Do stuff before starting a new migration.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function pre_migration() {
	}

	/**
	 * Perform pre migration actions.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function migration_start() {
		if ( 0 === $this->get_total_rows() ) {
			$this->set_total_rows();
		}

		$current_time = $this->get_current_time();

		if ( ! $current_time ) {
			return;
		}

		$this->status        = self::MIGRATION_STATUS_PROCESSING;
		$this->last_run_time = $this->get_current_time();

		if ( ! $this->get_start_time() ) {
			$this->start_time = $this->get_current_time();

			$this->update_migration( $this->get_progress() );
		}

		$this->get_data();
	}

	/**
	 * Perform pre migration run actions.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function migration_run_start() {
		if ( ! $this->get_migration_run_id() ) {
			return;
		}

		$last_run = $this->get_migration_run_option( $this->get_migration_run_id() );

		$this->current_migration_run = array(
			'as_action_id'      => $last_run['as_action_id'],
			'migration_run_id'  => $last_run['migration_run_id'],
			'migration_id'      => $last_run['migration_id'],
			'status'            => self::MIGRATION_STATUS_PROCESSING,
			'start_time'        => $this->get_current_time(),
			'last_attempt_time' => $this->get_current_time(),
			'attempts'          => $last_run['attempts'] + 1,
		);

		$this->update_migration_run( $this->get_current_migration_run() );

		$this->migration_results = array(
			'skipped'  => array(),
			'failed'   => array(),
			'migrated' => array(),
		);

		$this->update_migration_option( $this->get_migration_id(), $this->get_progress() );
	}

	/**
	 * Run migrator.
	 *
	 * @since  1.0.0
	 * @param  int  $migration_run_id  Run ID.
	 * @return void
	 */
	public function run( $migration_run_id ) {
		$this->migration_run_id = $migration_run_id;

		$this->init();

		$this->pre_migration();

		$this->migration_run_start();

		$this->migration_start();

		$this->process_migration();

		$this->post_migration();
	}

	/**
	 * Process migration.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function process_migration() {
	}

	/**
	 * Perform post migration actions.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function post_migration() {
		$migration_run_skipped_rows  = count( $this->migration_results['skipped'] );
		$migration_run_failed_rows   = count( $this->migration_results['failed'] );
		$migration_run_migrated_rows = count( $this->migration_results['migrated'] );

		$this->current_migration_run['total_rows_skipped']  = $migration_run_skipped_rows;
		$this->current_migration_run['total_rows_failed']   = $migration_run_failed_rows;
		$this->current_migration_run['total_rows_migrated'] = $migration_run_migrated_rows;

		$this->total_rows_skipped  += $migration_run_skipped_rows;
		$this->total_rows_failed   += $migration_run_failed_rows;
		$this->total_rows_migrated += $migration_run_migrated_rows;

		$this_run_total_time = microtime( true ) - $this->get_last_run_time();

		$this->total_time += $this_run_total_time;

		$this->current_migration_run['total_time'] = $this_run_total_time;

		$this->update_percent_complete();

		$this->post_migration_run();

		if ( false !== $this->is_migration_complete() ) {
			$this->migration_complete();
		} else {
			$this->queue_next_migration_run();
		}

		$this->update_migration_option( $this->get_migration_id(), $this->get_progress() );
	}


	/**
	 * Create a migration run.
	 *
	 * @since  1.0.0
	 * @param  array<mixed>  $migration  Migration data.
	 * @return false|array<mixed>
	 */
	public function create_migration_run( $migration ) {
		$migration_run_id = $this->create_new_migration_run( $migration );

		if ( empty( $migration_run_id ) ) {
			return false;
		}

		$action_id = $this->schedule_migration_run( $migration_run_id );

		$migration['migration_run_id'] = $migration_run_id;
		$migration['as_action_id']     = $action_id;
		$migration['attempts']         = 0;

		$this->update_migration_run( $migration );

		$this->update_migration_run_option( $migration_run_id, $migration );

		return $migration;
	}

	/**
	 * Schedule a migration run using ActionScheduler.
	 *
	 * @since  1.0.0
	 * @param  false|int  $migration_run_id  Migration run ID.
	 * @param  false|int  $delay             Optional. Delay, in seconds. Default false.
	 * @return int|false
	 */
	public function schedule_migration_run( $migration_run_id, $delay = false ) {
		if ( false === $migration_run_id ) {
			return false;
		}

		$as_queue_mode = $this->get_plugin_setting( 'migration_action_scheduler_queue_mode' );

		if ( ! $as_queue_mode ) {
			return false;
		}

		$hook  = $this->get_action_scheduler_hook();
		$group = $this->get_action_scheduler_group();

		if ( 'async' === $as_queue_mode && false === $delay ) {
			return as_enqueue_async_action( $hook, array( $migration_run_id ), $group );
		}

		$scheduled_mode_delay = $this->get_plugin_setting( 'migration_action_scheduler_queue_mode_scheduled_delay' );

		if ( false === $delay && ! $scheduled_mode_delay ) {
			return false;
		}

		$next_run_timestamp = time() + ( ( false === $delay ) ? $scheduled_mode_delay : absint( $delay ) );

		return as_schedule_single_action( $next_run_timestamp, $hook, array( $migration_run_id ), $group );
	}

	/**
	 * Queue the next migration run.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function queue_next_migration_run() {
		$this->create_migration_run( $this->get_progress() );
	}

	/**
	 * Save the results from this migration run.
	 *
	 * Intentionally empty in the base class. Override in subclasses when
	 * persistence of per-row results is required.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function save_migration_run_results() {
	}

	/**
	 * Called when a migration run is complete.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function migration_run_complete() {
		$this->current_migration_run['status']         = self::MIGRATION_STATUS_COMPLETE;
		$this->current_migration_run['completed_time'] = microtime( true );

		$this->current_migration_run['total_time'] = $this->current_migration_run['completed_time'] - $this->current_migration_run['start_time'];

		$this->save_completed_migration_run( $this->get_current_migration_run() );

		if ( $this->current_migration_run['attempts'] > 1 ) {
			$message_text = sprintf(
				/* translators: 1: Migrator label, 2: Dry run?, 3: Migration ID, 4: Attempts. */
				__( '%1$s%2$s Migration ID %3$d successfully recovered after %4$d previous failure(s).', 'de-wordpress-plugin-utils' ),
				$this->get_migrator_label( $this->get_migrator() ),
				( $this->get_is_dry_run() ) ? ' (DRY RUN)' : '',
				$this->current_migration_run['migration_id'],
				$this->current_migration_run['attempts']
			);

			$this->create_migration_message(
				array(
					'migration_id'     => $this->current_migration_run['migration_id'],
					'migration_run_id' => $this->current_migration_run['migration_run_id'],
					'message'          => $message_text,
					'type'             => 'success',
					'user_id'          => get_current_user_id(),
				)
			);
		}
	}

	/**
	 * Add migration run.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function post_migration_run() {
		if ( false === $this->get_migration_id() ) {
			return;
		}

		$this->migration_run_complete();

		$this->update_percent_complete();

		$this->migration_runs[] = $this->get_current_migration_run();

		$this->save_migration_run_results();
	}


	/**
	 * Check if the migration is complete.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	protected function is_migration_complete() {
		return $this->get_current_position() >= $this->get_total_rows();
	}

	/**
	 * Perform post migration actions.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function migration_complete() {
		$completed_time = microtime( true );

		$this->complete       = true;
		$this->completed_time = $completed_time;
		$this->status         = self::MIGRATION_STATUS_COMPLETE;

		$this->total_time = round( $this->get_total_time(), 4 );

		$this->save_completed_migration( $this->get_progress() );
	}

	/**
	 * Add an error to the errors array.
	 *
	 * @since  1.0.0
	 * @param  string  $error  Error text to add.
	 * @return void
	 */
	protected function add_error( $error ) {
		$this->errors[] = $error;
	}

	/**
	 * Generic set total rows.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function set_total_rows() {
	}

	/**
	 * Set data.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function set_data() {
	}

	/**
	 * Set whether this is a dry run.
	 *
	 * @since  1.0.0
	 * @param  bool  $is_dry_run  Whether this is a dry run.
	 * @return void
	 */
	public function set_is_dry_run( $is_dry_run ) {
		$this->is_dry_run = (bool) $is_dry_run;
	}

	/**
	 * Memory exceeded.
	 *
	 * Ensures the current migration run never exceeds 90% of the maximum WordPress memory.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	protected function check_memory_exceeded() {
		$memory_limit   = $this->get_memory_limit() * 0.9;
		$current_memory = memory_get_usage( true );
		$return         = false;

		if ( $current_memory >= $memory_limit ) {
			$return = true;
		}

		return $return;
	}

	/**
	 * Get memory limit.
	 *
	 * @since  1.0.0
	 * @return int
	 */
	protected function get_memory_limit() {
		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			$memory_limit = self::MIGRATION_MEMORY_LIMIT;
		}

		if ( ! $memory_limit || -1 === intval( $memory_limit ) ) {
			$memory_limit = '32000M';
		}

		return intval( $memory_limit ) * 1024 * 1024;
	}

	/**
	 * Check time exceeded.
	 *
	 * Ensures the current migration run never exceeds a sensible time limit.
	 * A timeout limit of 30s is common on shared hosting.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	protected function check_time_exceeded() {
		$finish = $this->last_run_time + self::MIGRATION_TIME_LIMIT;
		$return = false;

		if ( time() >= $finish ) {
			$return = true;
		}

		return $return;
	}

	/**
	 * Get extra migration fields.
	 *
	 * @since  1.0.0
	 * @return array<mixed>
	 */
	protected function extra_migration_fields() {
		return array();
	}

	/**
	 * Get migrator parameters.
	 *
	 * @since  1.0.0
	 * @return array<mixed>
	 */
	public function get_params() {
		return $this->params;
	}

	/**
	 * Generic get data.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function get_data() {
	}


	/**
	 * Get percentage complete.
	 *
	 * @since  1.0.0
	 * @return float
	 */
	public function get_percent_complete() {
		return $this->percent_complete;
	}

	/**
	 * Get batch size.
	 *
	 * @since  1.0.0
	 * @return int
	 */
	public function get_batch_size() {
		$setting_batch_size = $this->get_migrator_setting( $this->migrator, 'batch_size', false );

		if ( $setting_batch_size ) {
			$this->batch_size = $setting_batch_size;
		} else {
			$this->batch_size = $this->default_batch_size;
		}

		if ( -1 === $this->get_total_rows_limit() ) {
			return $this->batch_size;
		}

		$limit = $this->batch_size;

		if ( $this->get_current_position() + $limit > $this->get_total_rows_limit() ) {
			$limit = $this->get_total_rows_limit() - $this->get_current_position();
		}

		return $limit;
	}

	/**
	 * Get progress keys.
	 *
	 * @since  1.0.0
	 * @return string[]
	 */
	public function get_progress_keys() {
		return $this->get_migration_progress_keys();
	}

	/**
	 * Get current time.
	 *
	 * @since  1.0.0
	 * @return null|float
	 */
	public function get_current_time() {
		return $this->current_time;
	}

	/**
	 * Get status.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * Get queued time.
	 *
	 * @since  1.0.0
	 * @return null|float
	 */
	public function get_queued_time() {
		return $this->queued_time;
	}

	/**
	 * Get start time.
	 *
	 * @since  1.0.0
	 * @return null|float
	 */
	public function get_start_time() {
		return $this->start_time;
	}

	/**
	 * Get last run time.
	 *
	 * @since  1.0.0
	 * @return null|float
	 */
	public function get_last_run_time() {
		return $this->last_run_time;
	}

	/**
	 * Get completed time.
	 *
	 * @since  1.0.0
	 * @return null|float
	 */
	public function get_completed_time() {
		return $this->completed_time;
	}

	/**
	 * Get total time.
	 *
	 * @since  1.0.0
	 * @return float
	 */
	public function get_total_time() {
		return $this->total_time;
	}

	/**
	 * Get the current position.
	 *
	 * @since  1.0.0
	 * @return int
	 */
	public function get_current_position() {
		return $this->current_position;
	}

	/**
	 * Get the complete status.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public function get_complete() {
		return $this->complete;
	}

	/**
	 * Get the total number of rows.
	 *
	 * @since  1.0.0
	 * @return int
	 */
	public function get_total_rows() {
		return $this->total_rows;
	}


	/**
	 * Get the total rows limit which is sometimes overridden in migrators.
	 *
	 * @since  1.0.0
	 * @return int
	 */
	public function get_total_rows_limit() {
		return $this->total_rows_limit;
	}

	/**
	 * Get the total number of rows failed.
	 *
	 * @since  1.0.0
	 * @return int
	 */
	public function get_total_rows_failed() {
		return $this->total_rows_failed;
	}

	/**
	 * Get the total number of rows skipped.
	 *
	 * @since  1.0.0
	 * @return int
	 */
	public function get_total_rows_skipped() {
		return $this->total_rows_skipped;
	}

	/**
	 * Get the total number of rows migrated.
	 *
	 * @since  1.0.0
	 * @return int
	 */
	public function get_total_rows_migrated() {
		return $this->total_rows_migrated;
	}

	/**
	 * Get current migrator.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_migrator() {
		return $this->migrator;
	}

	/**
	 * Get current migration ID.
	 *
	 * @since  1.0.0
	 * @return false|int
	 */
	public function get_migration_id() {
		return $this->migration_id;
	}

	/**
	 * Get current migration run ID.
	 *
	 * @since  1.0.0
	 * @return false|int
	 */
	public function get_migration_run_id() {
		return $this->migration_run_id;
	}

	/**
	 * Get current action scheduler action ID.
	 *
	 * @since  1.0.0
	 * @return false|int
	 */
	public function get_as_action_id() {
		return $this->as_action_id;
	}

	/**
	 * Get current migration results.
	 *
	 * @since  1.0.0
	 * @return array<mixed>
	 */
	public function get_migration_results() {
		return $this->migration_results;
	}

	/**
	 * Get migration runs.
	 *
	 * @since  1.0.0
	 * @return array<mixed>
	 */
	public function get_migration_runs() {
		return $this->migration_runs;
	}

	/**
	 * Get current migration run.
	 *
	 * @since  1.0.0
	 * @return array<mixed>
	 */
	public function get_current_migration_run() {
		return $this->current_migration_run;
	}

	/**
	 * Create a new migration record and return the record array.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed>  $migration  Queued migration data.
	 * @return false|array<string, mixed>
	 */
	abstract protected function create_new_migration( array $migration );

	/**
	 * Create a new migration run record and return its ID.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed>  $migration  Migration data.
	 * @return false|int
	 */
	abstract protected function create_new_migration_run( array $migration );

	/**
	 * Get a migration option by ID.
	 *
	 * @since  1.0.0
	 * @param  false|int  $migration_id  Migration ID.
	 * @return false|mixed
	 */
	abstract protected function get_migration_option( $migration_id );

	/**
	 * Update a migration option by ID.
	 *
	 * @since  1.0.0
	 * @param  false|int     $migration_id  Migration ID.
	 * @param  array<mixed>  $data          Migration data.
	 * @return void
	 */
	abstract protected function update_migration_option( $migration_id, $data );


	/**
	 * Persist the current migration state.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed>  $progress  Current progress payload.
	 * @return void
	 */
	abstract protected function update_migration( array $progress );

	/**
	 * Persist a migration run record.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed>  $migration_run  Migration run payload.
	 * @return void
	 */
	abstract protected function update_migration_run( array $migration_run );

	/**
	 * Get a migration run option by ID.
	 *
	 * @since  1.0.0
	 * @param  false|int  $migration_run_id  Migration run ID.
	 * @return false|mixed
	 */
	abstract protected function get_migration_run_option( $migration_run_id );

	/**
	 * Update a migration run option by ID.
	 *
	 * @since  1.0.0
	 * @param  false|int     $migration_run_id  Migration run ID.
	 * @param  array<mixed>  $data              Migration run data.
	 * @return void
	 */
	abstract protected function update_migration_run_option( $migration_run_id, $data );

	/**
	 * Persist a completed migration run.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed>  $migration_run  Migration run payload.
	 * @return void
	 */
	abstract protected function save_completed_migration_run( array $migration_run );

	/**
	 * Persist a completed migration.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed>  $progress  Current progress payload.
	 * @return void
	 */
	abstract protected function save_completed_migration( array $progress );

	/**
	 * Create a message for the migration.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed>  $args  Message arguments.
	 * @return void
	 */
	abstract protected function create_migration_message( array $args );

	/**
	 * Get a human-readable label for a migrator slug.
	 *
	 * @since  1.0.0
	 * @param  string  $migrator  Migrator slug.
	 * @return string
	 */
	abstract protected function get_migrator_label( $migrator );

	/**
	 * Get a plugin-level setting.
	 *
	 * @since  1.0.0
	 * @param  string  $key  Setting key.
	 * @return mixed
	 */
	abstract protected function get_plugin_setting( $key );

	/**
	 * Get a migrator-level setting.
	 *
	 * @since  1.0.0
	 * @param  string  $migrator       Migrator slug.
	 * @param  string  $key            Setting key.
	 * @param  mixed   $default_value  Default value.
	 * @return mixed
	 */
	abstract protected function get_migrator_setting( $migrator, $key, $default_value = false );

	/**
	 * Get all migration param keys that should be copied from the migration option.
	 *
	 * @since  1.0.0
	 * @return string[]
	 */
	abstract protected function get_all_migration_param_keys();

	/**
	 * Get the progress keys exposed by the migration progress payload.
	 *
	 * @since  1.0.0
	 * @return string[]
	 */
	abstract protected function get_migration_progress_keys();

	/**
	 * Get the ActionScheduler hook used to run a migration run.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	abstract protected function get_action_scheduler_hook();

	/**
	 * Get the ActionScheduler group used when scheduling migration runs.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	abstract protected function get_action_scheduler_group();
}
