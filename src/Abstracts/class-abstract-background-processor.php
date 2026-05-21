<?php
/**
 * Abstract background processor class.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils\Abstracts
 * @since   1.0.0
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Abstracts;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract class Abstract_Background_Processor
 *
 * Base class for batch-style background processors that run via ActionScheduler.
 * Supports prerequisite sub-processor chains. Persistence operations are declared
 * abstract so each consuming plugin can wire its own repository/function library.
 *
 * @since 1.0.0
 */
abstract class Abstract_Background_Processor {

	/**
	 * Processor slug.
	 *
	 * @var string
	 */
	public $processor;

	/**
	 * Processor group slug.
	 *
	 * @var string
	 */
	public $processor_group = '';

	/**
	 * Is runnable?
	 *
	 * @var bool
	 */
	public $is_runnable = false;

	/**
	 * Is dry run?
	 *
	 * @var bool
	 */
	public $is_dry_run;

	/**
	 * Default batch size.
	 *
	 * @var int
	 */
	public $default_batch_size = 100;

	/**
	 * Label.
	 *
	 * @var string
	 */
	public $label = 'Base Processor';

	/**
	 * Params.
	 *
	 * @var array<mixed>
	 */
	protected $params = array();

	/**
	 * Sub params.
	 *
	 * @var array<mixed>
	 */
	protected $sub_params = array();

	/**
	 * Prerequisite sub processors.
	 *
	 * @var array<string>
	 */
	public $prerequisite_sub_background_processors = array();

	/**
	 * Prerequisite sub processes.
	 *
	 * @var array<mixed>
	 */
	public $prerequisite_sub_background_processes = array();

	/**
	 * Prerequisite sub process results.
	 *
	 * @var array<mixed>
	 */
	public $prerequisite_sub_background_process_results = array();

	/**
	 * Current time.
	 *
	 * @var float|null
	 */
	public $current_time;

	/**
	 * Current process run.
	 *
	 * @var array<mixed>
	 */
	public $current_background_process_run = array();

	/**
	 * Process runs.
	 *
	 * @var array<mixed>
	 */
	protected $background_process_runs = array();

	/**
	 * Status.
	 *
	 * @var string
	 */
	public $status = self::PROCESS_STATUS_QUEUED;

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
	 * Scheduled time of current process.
	 *
	 * @var null|float
	 */
	protected $scheduled_time = null;

	/**
	 * Queued time of current process.
	 *
	 * @var null|float
	 */
	protected $queued_time = null;

	/**
	 * Start time of current process.
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
	 * Completed time of current process.
	 *
	 * @var null|float
	 */
	protected $completed_time = null;

	/**
	 * Total time of current process.
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
	 * Total rows processed.
	 *
	 * @var int
	 */
	public $total_rows_processed = 0;

	/**
	 * Batch size.
	 *
	 * @var int
	 */
	protected $batch_size = 100;

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
	public $background_process_results = array();

	/**
	 * This background process ID.
	 *
	 * @var false|int
	 */
	public $background_processes_id = false;

	/**
	 * This background process parent's ID.
	 *
	 * @var false|int
	 */
	public $parent_background_processes_id = false;

	/**
	 * This background process run ID.
	 *
	 * @var false|int
	 */
	public $background_processes_run_id = false;

	/**
	 * This process's action scheduler action ID.
	 *
	 * @var false|int
	 */
	public $as_action_id = false;

	/**
	 * Queued prerequisite sub processes.
	 *
	 * @var array<string>
	 */
	public $queued_prerequisite_sub_background_processors = array();

	/**
	 * Unqueued prerequisite sub processes.
	 *
	 * @var array<string>
	 */
	public $unqueued_prerequisite_sub_background_processors = array();

	/**
	 * Incomplete prerequisite sub processes.
	 *
	 * @var array<mixed>
	 */
	public $incomplete_prerequisite_sub_background_processors = array();


	/**
	 * Process time limit.
	 *
	 * @var int
	 */
	const PROCESS_TIME_LIMIT = 20;

	/**
	 * Process memory limit.
	 *
	 * @var string
	 */
	const PROCESS_MEMORY_LIMIT = '128M';

	/**
	 * Scheduled status.
	 *
	 * @var string
	 */
	const PROCESS_STATUS_SCHEDULED = 'scheduled';

	/**
	 * Pending status.
	 *
	 * @var string
	 */
	const PROCESS_STATUS_PENDING = 'pending';

	/**
	 * Queued status.
	 *
	 * @var string
	 */
	const PROCESS_STATUS_QUEUED = 'queued';

	/**
	 * Processing status.
	 *
	 * @var string
	 */
	const PROCESS_STATUS_PROCESSING = 'processing';

	/**
	 * Failed status.
	 *
	 * @var string
	 */
	const PROCESS_STATUS_FAILED = 'failed';

	/**
	 * Cancelled status.
	 *
	 * @var string
	 */
	const PROCESS_STATUS_CANCELLED = 'cancelled';

	/**
	 * Complete status.
	 *
	 * @var string
	 */
	const PROCESS_STATUS_COMPLETE = 'complete';

	/**
	 * Initialize processor.
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

		$this->parse_sub_params();

		$this->set_incomplete_prerequisite_sub_background_processors();

		$this->update_background_process_option( $this->get_background_processes_id(), $this->get_progress() );
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
	 * Initiate new processor.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed>  $init_params  Initial processor params.
	 * @return void
	 */
	public function init_background_processor( $init_params = array() ) {
		$this->prerequisite_sub_background_processes = $this->get_initial_prerequisite_sub_background_processes();

		if ( ! empty( $init_params['sub_params'] ) ) {
			$this->parse_sub_params( $init_params['sub_params'] );
		}

		$queued_background_process = wp_parse_args( $init_params, $this->get_progress() );

		$new_background_process = $this->queue_new_background_process( $queued_background_process );

		if ( ! $new_background_process ) {
			return;
		}

		$new_background_process = $this->create_background_process_run( $new_background_process );

		if ( false === $new_background_process || ! $new_background_process['background_processes_id'] ) {
			return;
		}

		$this->update_background_process_option( $new_background_process['background_processes_id'], $new_background_process );
	}


	/**
	 * Queue prerequisite sub processor.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed>  $init_params  Initial sub processor params.
	 * @return false|array<string, mixed>
	 */
	public function queue_prerequisite_sub_background_processor( $init_params ) {
		$new_background_process = $this->create_new_background_process( $init_params );

		if ( ! $new_background_process ) {
			return false;
		}

		$new_prerequisite_sub_background_process = $this->create_background_process_run( $new_background_process );

		if ( false === $new_prerequisite_sub_background_process || ! $new_prerequisite_sub_background_process['parent_background_processes_id'] ) {
			return false;
		}

		$this->update_background_process_option( $new_prerequisite_sub_background_process['background_processes_id'], $new_prerequisite_sub_background_process );

		return $new_prerequisite_sub_background_process;
	}

	/**
	 * Parse params.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function parse_params() {
		$current_background_process_run = $this->get_background_process_run_option( $this->get_background_processes_run_id() );

		if ( ! $current_background_process_run ) {
			return;
		}

		if ( empty( $current_background_process_run['background_processes_id'] ) ) {
			return;
		}

		$background_processes_id = $current_background_process_run['background_processes_id'];

		$background_process = $this->get_background_process_option( $background_processes_id );

		foreach ( $this->get_all_background_process_param_keys() as $key ) {
			if ( isset( $background_process[ $key ] ) ) {
				$this->{$key} = $background_process[ $key ];
			} elseif ( 'prerequisite_sub_background_processes' === $key ) {
				$this->{$key} = $this->get_initial_prerequisite_sub_background_processes();
			}
		}
	}

	/**
	 * Get sub processor parameters.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed>  $args  Arguments.
	 * @return void
	 */
	public function parse_sub_params( $args = array() ) {
		$processors_sub_params = $this->get_processor_sub_params();

		if ( ! isset( $processors_sub_params[ $this->get_processor() ] ) ) {
			return;
		}

		$sub_param_keys = $processors_sub_params[ $this->get_processor() ];

		if ( empty( $args ) && ! empty( $this->background_processes_id ) ) {
			$background_process_data = $this->get_background_process_by_id( $this->background_processes_id );

			if ( $background_process_data && ! empty( $background_process_data->params ) ) {
				$stored_params = maybe_unserialize( $background_process_data->params );

				if ( is_array( $stored_params ) ) {
					foreach ( $sub_param_keys as $key ) {
						if ( isset( $stored_params[ $key ] ) ) {
							$this->sub_params[ $key ] = $stored_params[ $key ];
						}
					}
				}
			}
		} else {
			foreach ( $sub_param_keys as $key ) {
				if ( isset( $args[ $key ] ) ) {
					$this->sub_params[ $key ] = $args[ $key ];
				}
			}
		}
	}

	/**
	 * Set a processor sub param.
	 *
	 * @since  1.0.0
	 * @param  string  $param  Param name.
	 * @param  mixed   $value  Value.
	 * @return void
	 */
	public function set_sub_param( $param, $value ) {
		$this->sub_params[ $param ] = $value;
	}

	/**
	 * Get a processor sub param, checking if it exists first.
	 *
	 * @since  1.0.0
	 * @param  string  $param  Param name.
	 * @return mixed
	 */
	public function get_sub_param( $param ) {
		$sub_params = $this->get_sub_params();

		if ( empty( $sub_params ) ) {
			return null;
		}

		if ( ! isset( $sub_params[ $param ] ) ) {
			return null;
		}

		return $sub_params[ $param ];
	}

	/**
	 * Set the initial sub processes array.
	 *
	 * @since  1.0.0
	 * @return array<mixed>
	 */
	public function get_initial_prerequisite_sub_background_processes() {
		$initial = array();

		foreach ( $this->get_prerequisite_sub_background_processors() as $prerequisite_sub_background_processor ) {
			$initial[] = array(
				'processor'        => $prerequisite_sub_background_processor,
				'status'           => self::PROCESS_STATUS_PENDING,
				'complete'         => false,
				'current_position' => 0,
			);
		}

		return $initial;
	}

	/**
	 * Queue prerequisite processors.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function maybe_queue_prerequisite_sub_background_processors() {
		if ( empty( $this->get_prerequisite_sub_background_processors() ) || ! $this->get_background_processes_id() ) {
			return;
		}

		$prerequisite_sub_background_processes = $this->get_prerequisite_sub_background_processes();

		$queued_prerequisite_sub_background_processes = wp_list_filter( $prerequisite_sub_background_processes, array( 'status' => self::PROCESS_STATUS_PENDING ), 'NOT' );

		$this->queued_prerequisite_sub_background_processors = wp_list_pluck( $queued_prerequisite_sub_background_processes, 'processor' );

		$this->unqueued_prerequisite_sub_background_processors = array_diff( $this->get_prerequisite_sub_background_processors(), $this->queued_prerequisite_sub_background_processors );

		if ( empty( $this->unqueued_prerequisite_sub_background_processors ) ) {
			return;
		}

		$prerequisite_sub_background_processes = $queued_prerequisite_sub_background_processes;

		$as_queue_mode = $this->get_plugin_setting( 'background_process_action_scheduler_queue_mode' );

		if ( empty( $as_queue_mode ) || 'concurrent' === $as_queue_mode ) {
			foreach ( $this->unqueued_prerequisite_sub_background_processors as $unqueued_prerequisite_sub_background_processor ) {
				$prerequisite_sub_background_processor_to_queue = array(
					'processor'                      => $unqueued_prerequisite_sub_background_processor,
					'parent_background_processes_id' => $this->get_background_processes_id(),
					'status'                         => self::PROCESS_STATUS_PENDING,
					'complete'                       => false,
					'sub_params'                     => $this->get_sub_params(),
				);

				$prerequisite_sub_background_processes[] = $this->queue_prerequisite_sub_background_processor( $prerequisite_sub_background_processor_to_queue );
			}
		} else {
			$unqueued_prerequisite_sub_background_processor = $this->unqueued_prerequisite_sub_background_processors[0];

			$prerequisite_sub_background_processor_to_queue = array(
				'processor'                      => $unqueued_prerequisite_sub_background_processor,
				'parent_background_processes_id' => $this->get_background_processes_id(),
				'status'                         => self::PROCESS_STATUS_PENDING,
				'complete'                       => false,
				'sub_params'                     => $this->get_sub_params(),
			);

			$prerequisite_sub_background_processes[] = $this->queue_prerequisite_sub_background_processor( $prerequisite_sub_background_processor_to_queue );
		}

		$this->prerequisite_sub_background_processes = $prerequisite_sub_background_processes;

		$parent_process = $this->get_background_process_option( $this->get_background_processes_id() );

		$parent_process['prerequisite_sub_background_processes'] = $this->get_prerequisite_sub_background_processes();

		$this->update_background_process_option( $this->get_background_processes_id(), $parent_process );
	}

	/**
	 * Check if this process has prerequisite sub processors.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public function has_prerequisite_sub_background_processors() {
		return ! empty( $this->get_prerequisite_sub_background_processors() );
	}

	/**
	 * Set incomplete prerequisite sub processors.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function set_incomplete_prerequisite_sub_background_processors() {
		$prerequisite_sub_background_processes = $this->get_prerequisite_sub_background_processes();

		foreach ( $prerequisite_sub_background_processes as &$sub_process ) {
			if ( empty( $sub_process['background_processes_id'] ) ) {
				continue;
			}

			$db_process = $this->get_background_process_by_id( $sub_process['background_processes_id'] );

			if ( $db_process && in_array( $db_process->status, array( self::PROCESS_STATUS_COMPLETE, self::PROCESS_STATUS_FAILED, self::PROCESS_STATUS_CANCELLED ), true ) ) {
				$sub_process['status']   = $db_process->status;
				$sub_process['complete'] = true;
			}
		}

		$this->prerequisite_sub_background_processes = $prerequisite_sub_background_processes;

		$incomplete = wp_list_filter( $this->prerequisite_sub_background_processes, array( 'complete' => false ) );

		$this->incomplete_prerequisite_sub_background_processors = $incomplete;
	}

	/**
	 * Re-queue a parent process when there are incomplete prerequisite sub processes.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function maybe_requeue_parent_background_process() {
		$this->schedule_background_process_run( $this->get_background_processes_run_id(), 60 );
	}

	/**
	 * Maybe update a parent process option.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function maybe_update_parent_background_process() {
		if ( ! $this->get_parent_background_processes_id() || ! $this->get_background_processes_id() ) {
			return;
		}

		$this->update_prerequisite_sub_process_parent_background_process( $this->get_background_processes_id() );
	}

	/**
	 * Get info on the progress of the processing.
	 *
	 * @since  1.0.0
	 * @return array<string, mixed>
	 */
	public function get_progress() {
		$defaults = array();

		foreach ( $this->get_progress_keys() as $progress_key ) {
			$getter = "get_{$progress_key}";

			if ( is_callable( array( $this, $getter ), true ) ) {
				$defaults[ $progress_key ] = $this->$getter();
			}
		}

		return array_merge( $defaults, $this->extra_process_fields() );
	}

	/**
	 * Update the percent completed based on the current process run and the total rows.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function update_percent_complete() {
		if ( 0 === $this->get_total_rows() ) {
			return;
		}

		$this->percent_complete = min( ( $this->get_current_position() / $this->get_total_rows() ) * 100, 100 );
	}


	/**
	 * Do stuff before starting a new process.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function pre_background_process() {
	}

	/**
	 * Perform pre process actions.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function background_process_start() {
		if ( 0 === $this->get_total_rows() ) {
			$this->set_total_rows();
		}

		$current_time = $this->get_current_time();

		if ( ! $current_time ) {
			return;
		}

		$this->status        = self::PROCESS_STATUS_PROCESSING;
		$this->last_run_time = $this->get_current_time();

		if ( ! $this->get_start_time() ) {
			$this->start_time = $this->get_current_time();

			$this->update_background_process( $this->get_progress() );
		}

		$this->get_data();
	}

	/**
	 * Perform pre process run actions.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function background_process_run_start() {
		if ( ! $this->get_background_processes_run_id() ) {
			return;
		}

		$last_run = $this->get_background_process_run_option( $this->get_background_processes_run_id() );

		$this->current_background_process_run = array(
			'as_action_id'                   => $last_run['as_action_id'],
			'background_processes_run_id'    => $last_run['background_processes_run_id'],
			'background_processes_id'        => $last_run['background_processes_id'],
			'parent_background_processes_id' => $last_run['parent_background_processes_id'],
			'status'                         => self::PROCESS_STATUS_PROCESSING,
			'start_time'                     => $this->get_current_time(),
			'last_attempt_time'              => $this->get_current_time(),
			'attempts'                       => $last_run['attempts'] + 1,
		);

		$this->update_background_process_run( $this->get_current_background_process_run() );

		$this->background_process_results = array(
			'skipped'   => array(),
			'failed'    => array(),
			'processed' => array(),
		);

		$this->update_background_process_option( $this->get_background_processes_id(), $this->get_progress() );
	}

	/**
	 * Run processor.
	 *
	 * @since  1.0.0
	 * @param  int  $background_processes_run_id  Run ID.
	 * @return void
	 */
	public function run( $background_processes_run_id ) {
		$this->background_processes_run_id = $background_processes_run_id;

		$this->init();

		$latest_status = $this->get_background_process_option( $this->get_background_processes_id() );

		if ( isset( $latest_status['status'] ) && self::PROCESS_STATUS_CANCELLED === $latest_status['status'] ) {
			return;
		}

		if ( ! empty( $this->get_incomplete_prerequisite_sub_background_processors() ) ) {
			$this->maybe_queue_prerequisite_sub_background_processors();

			$this->maybe_requeue_parent_background_process();

			return;
		}

		$this->pre_background_process();

		$this->background_process_run_start();

		$this->background_process_start();

		$this->process_data();

		$this->post_background_process();
	}

	/**
	 * Process data.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function process_data() {
	}


	/**
	 * Perform post process actions.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function post_background_process() {
		$background_process_run_skipped_rows   = count( $this->background_process_results['skipped'] );
		$background_process_run_failed_rows    = count( $this->background_process_results['failed'] );
		$background_process_run_processed_rows = count( $this->background_process_results['processed'] );

		$this->current_background_process_run['total_rows_skipped']   = $background_process_run_skipped_rows;
		$this->current_background_process_run['total_rows_failed']    = $background_process_run_failed_rows;
		$this->current_background_process_run['total_rows_processed'] = $background_process_run_processed_rows;

		$this->total_rows_skipped   += $background_process_run_skipped_rows;
		$this->total_rows_failed    += $background_process_run_failed_rows;
		$this->total_rows_processed += $background_process_run_processed_rows;

		$this_run_total_time = microtime( true ) - $this->get_last_run_time();

		$this->total_time += $this_run_total_time;

		$this->current_background_process_run['total_time'] = $this_run_total_time;

		$this->update_percent_complete();

		$this->post_background_process_run();

		$latest_status = $this->get_background_process_option( $this->get_background_processes_id() );

		if ( isset( $latest_status['status'] ) && self::PROCESS_STATUS_CANCELLED === $latest_status['status'] ) {
			$this->status = self::PROCESS_STATUS_CANCELLED;
			$this->update_background_process_option( $this->get_background_processes_id(), $this->get_progress() );
			return;
		}

		if ( false !== $this->is_background_process_complete() ) {
			$this->background_process_complete();
		} else {
			$this->queue_next_background_process_run();
		}

		$this->update_background_process_option( $this->get_background_processes_id(), $this->get_progress() );
	}

	/**
	 * Create a process run.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed>  $background_process  Process data.
	 * @return false|array<string, mixed>
	 */
	public function create_background_process_run( $background_process ) {
		$background_processes_run_id = $this->create_new_background_process_run( $background_process );

		if ( empty( $background_processes_run_id ) ) {
			$this->log_error( 'Problem creating new bg process.' );

			return false;
		}

		$action_id = $this->schedule_background_process_run( $background_processes_run_id );

		$background_process['background_processes_run_id'] = $background_processes_run_id;
		$background_process['as_action_id']                = $action_id;
		$background_process['attempts']                    = 0;

		$this->update_background_process_run( $background_process );

		$this->update_background_process_run_option( $background_processes_run_id, $background_process );

		return $background_process;
	}

	/**
	 * Schedule a process run using ActionScheduler.
	 *
	 * @since  1.0.0
	 * @param  false|int  $background_processes_run_id  Process run ID.
	 * @param  false|int  $delay                        Optional. Delay, in seconds. Default false.
	 * @return int|false
	 */
	public function schedule_background_process_run( $background_processes_run_id, $delay = false ) {
		if ( false === $background_processes_run_id ) {
			return false;
		}

		$as_queue_mode = $this->get_plugin_setting( 'background_process_action_scheduler_queue_mode' );

		if ( empty( $as_queue_mode ) ) {
			$this->log_error( static::class . '::schedule_background_process_run - No AS queue mode setting.' );

			return false;
		}

		$hook  = $this->get_action_scheduler_hook();
		$group = $this->get_action_scheduler_group();

		if ( 'async' === $as_queue_mode && false === $delay ) {
			return as_enqueue_async_action( $hook, array( $background_processes_run_id ), $group );
		}

		$as_queue_mode_scheduled_delay = $this->get_plugin_setting( 'background_process_action_scheduler_queue_mode_scheduled_delay' );

		if ( false === $delay && empty( $as_queue_mode_scheduled_delay ) ) {
			$this->log_error( static::class . '::schedule_background_process_run - No AS queue mode scheduled delay.' );

			return false;
		}

		$next_run_timestamp = time() + ( ( false === $delay ) ? $as_queue_mode_scheduled_delay : absint( $delay ) );

		return as_schedule_single_action( $next_run_timestamp, $hook, array( $background_processes_run_id ), $group );
	}

	/**
	 * Queue the next process run.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function queue_next_background_process_run() {
		$this->create_background_process_run( $this->get_progress() );
	}


	/**
	 * Completed actions from this process run.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function background_process_run_complete() {
		$this->current_background_process_run['status']         = self::PROCESS_STATUS_COMPLETE;
		$this->current_background_process_run['completed_time'] = microtime( true );

		$this->current_background_process_run['total_time'] = $this->current_background_process_run['completed_time'] - $this->current_background_process_run['start_time'];

		$this->save_completed_background_process_run( $this->get_current_background_process_run() );

		if ( $this->current_background_process_run['attempts'] > 1 ) {
			$this->create_background_processes_message(
				array(
					'background_processes_id'     => $this->get_background_processes_id(),
					'background_processes_run_id' => $this->get_background_processes_run_id(),
					'message'                     => sprintf(
						/* translators: 1: Conditional parent background process ID, 2: Processor label, 3: Dry run?, 4: Background process ID, 5: Date. */
						__( '%1$s%2$s%3$s run ID %4$d successfully recovered after %5$d previous failure(s).', 'de-wordpress-plugin-utils' ),
						( ! empty( $this->get_parent_background_processes_id() ) ) ? __( 'Prerequisite ', 'de-wordpress-plugin-utils' ) : '',
						$this->get_background_processor_label( $this->get_processor() ),
						( $this->get_is_dry_run() ) ? ' (DRY RUN)' : '',
						$this->get_background_processes_id(),
						$this->current_background_process_run['attempts']
					),
					'type'                        => 'success',
				)
			);
		}

		$this->save_background_process_run_results();
	}

	/**
	 * Add process run.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function post_background_process_run() {
		if ( false === $this->get_background_processes_id() ) {
			return;
		}

		$this->background_process_run_complete();

		$this->update_percent_complete();

		$this->background_process_runs[] = $this->get_current_background_process_run();

		$this->update_background_process_option( $this->get_background_processes_id(), $this->get_progress() );
	}

	/**
	 * Check if the process is complete.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	protected function is_background_process_complete() {
		return $this->get_current_position() >= $this->get_total_rows();
	}

	/**
	 * Perform post process actions.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function background_process_complete() {
		$completed_time = microtime( true );

		$this->complete       = true;
		$this->completed_time = $completed_time;
		$this->status         = self::PROCESS_STATUS_COMPLETE;

		$this->total_time = round( $this->get_total_time(), 4 );

		$this->save_completed_background_process( $this->get_progress() );
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
	 * Memory exceeded.
	 *
	 * Ensures the current process run never exceeds 90% of the maximum WordPress memory.
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
			$memory_limit = self::PROCESS_MEMORY_LIMIT;
		}

		if ( ! $memory_limit || -1 === intval( $memory_limit ) ) {
			$memory_limit = '32000M';
		}

		return intval( $memory_limit ) * 1024 * 1024;
	}


	/**
	 * Check time exceeded.
	 *
	 * Ensures the current process run never exceeds a sensible time limit.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	protected function check_time_exceeded() {
		$finish = $this->last_run_time + self::PROCESS_TIME_LIMIT;
		$return = false;

		if ( time() >= $finish ) {
			$return = true;
		}

		return $return;
	}

	/**
	 * Get extra process fields.
	 *
	 * @since  1.0.0
	 * @return array<string, mixed>
	 */
	protected function extra_process_fields() {
		return array();
	}

	/**
	 * Get is runnable.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public function get_is_runnable() {
		return $this->is_runnable;
	}

	/**
	 * Get processor parameters.
	 *
	 * @since  1.0.0
	 * @return array<mixed>
	 */
	public function get_params() {
		return $this->params;
	}

	/**
	 * Get processor sub parameters.
	 *
	 * @since  1.0.0
	 * @return array<mixed>
	 */
	public function get_sub_params() {
		return $this->sub_params;
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
		$setting_batch_size = $this->get_processor_setting( $this->processor, 'batch_size', false );

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
	 * @return array<string>
	 */
	public function get_progress_keys() {
		return $this->get_background_process_progress_keys();
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
	 * Get scheduled time.
	 *
	 * @since  1.0.0
	 * @return null|float
	 */
	public function get_scheduled_time() {
		return $this->scheduled_time;
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
	 * Get the total rows limit which is sometimes overridden in processors.
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
	 * Get the total number of rows processed.
	 *
	 * @since  1.0.0
	 * @return int
	 */
	public function get_total_rows_processed() {
		return $this->total_rows_processed;
	}

	/**
	 * Get current processor.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_processor() {
		return $this->processor;
	}

	/**
	 * Get current processor group.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_processor_group() {
		return $this->processor_group;
	}

	/**
	 * Get current background process ID.
	 *
	 * @since  1.0.0
	 * @return false|int
	 */
	public function get_background_processes_id() {
		return $this->background_processes_id;
	}

	/**
	 * Get current background process parent's ID.
	 *
	 * @since  1.0.0
	 * @return false|int
	 */
	public function get_parent_background_processes_id() {
		return $this->parent_background_processes_id;
	}

	/**
	 * Get current background process run ID.
	 *
	 * @since  1.0.0
	 * @return false|int
	 */
	public function get_background_processes_run_id() {
		return $this->background_processes_run_id;
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
	 * Get current background process results.
	 *
	 * @since  1.0.0
	 * @return array<mixed>
	 */
	public function get_background_process_results() {
		return $this->background_process_results;
	}

	/**
	 * Get incomplete prerequisite sub background processors.
	 *
	 * @since  1.0.0
	 * @return array<mixed>
	 */
	public function get_incomplete_prerequisite_sub_background_processors() {
		return $this->incomplete_prerequisite_sub_background_processors;
	}

	/**
	 * Get prerequisite sub processes.
	 *
	 * @since  1.0.0
	 * @return array<mixed>
	 */
	public function get_prerequisite_sub_background_processes() {
		return $this->prerequisite_sub_background_processes;
	}

	/**
	 * Get prerequisite sub background process results.
	 *
	 * @since  1.0.0
	 * @return array<mixed>
	 */
	public function get_prerequisite_sub_background_process_results() {
		return $this->prerequisite_sub_background_process_results;
	}

	/**
	 * Get current prerequisite sub background processes.
	 *
	 * @since  1.0.0
	 * @return array<string>
	 */
	public function get_prerequisite_sub_background_processors() {
		return $this->prerequisite_sub_background_processors;
	}

	/**
	 * Get process runs.
	 *
	 * @since  1.0.0
	 * @return array<mixed>
	 */
	public function get_background_process_runs() {
		return $this->background_process_runs;
	}

	/**
	 * Get current background process run.
	 *
	 * @since  1.0.0
	 * @return array<mixed>
	 */
	public function get_current_background_process_run() {
		return $this->current_background_process_run;
	}

	/**
	 * Save process run results.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function save_background_process_run_results() {
	}

	/**
	 * Log a debug message. Override in subclasses to integrate with a logger.
	 *
	 * @since  1.0.0
	 * @param  string  $message  Message to log.
	 * @return void
	 */
	protected function log_debug( $message ) {
	}

	/**
	 * Log an error message. Override in subclasses to integrate with a logger.
	 *
	 * @since  1.0.0
	 * @param  string  $message  Message to log.
	 * @return void
	 */
	protected function log_error( $message ) {
	}


	/**
	 * Queue a new background process.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed>  $args  Process args.
	 * @return false|array<string, mixed>
	 */
	abstract protected function queue_new_background_process( $args );

	/**
	 * Create a new background process record.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed>  $args  Process args.
	 * @return false|array<string, mixed>
	 */
	abstract protected function create_new_background_process( $args );

	/**
	 * Create a new background process run record.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed>  $background_process  Process data.
	 * @return false|int
	 */
	abstract protected function create_new_background_process_run( $background_process );

	/**
	 * Get a background process option by ID.
	 *
	 * @since  1.0.0
	 * @param  false|int  $background_processes_id  Process ID.
	 * @param  bool       $force_refresh            Optional. Force refresh.
	 * @return false|mixed
	 */
	abstract protected function get_background_process_option( $background_processes_id, $force_refresh = false );

	/**
	 * Update the option for a background process.
	 *
	 * @since  1.0.0
	 * @param  false|int     $background_processes_id  Process ID.
	 * @param  array<mixed>  $data                     Process data.
	 * @return void
	 */
	abstract protected function update_background_process_option( $background_processes_id, $data );

	/**
	 * Update a background process record.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed>  $data  Process data.
	 * @return void
	 */
	abstract protected function update_background_process( $data );

	/**
	 * Get a background process run option by ID.
	 *
	 * @since  1.0.0
	 * @param  false|int  $background_processes_run_id  Run ID.
	 * @return false|mixed
	 */
	abstract protected function get_background_process_run_option( $background_processes_run_id );

	/**
	 * Update the option for a background process run.
	 *
	 * @since  1.0.0
	 * @param  false|int     $background_processes_run_id  Run ID.
	 * @param  array<mixed>  $data                         Run data.
	 * @return void
	 */
	abstract protected function update_background_process_run_option( $background_processes_run_id, $data );

	/**
	 * Update a background process run record.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed>  $data  Run data.
	 * @return void
	 */
	abstract protected function update_background_process_run( $data );

	/**
	 * Save a completed background process.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed>  $data  Process data.
	 * @return void
	 */
	abstract protected function save_completed_background_process( $data );

	/**
	 * Save a completed background process run.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed>  $data  Run data.
	 * @return void
	 */
	abstract protected function save_completed_background_process_run( $data );

	/**
	 * Get a background process database row by ID.
	 *
	 * @since  1.0.0
	 * @param  int  $background_processes_id  Process ID.
	 * @return \stdClass|null
	 */
	abstract protected function get_background_process_by_id( $background_processes_id );

	/**
	 * Update the parent background process of a prerequisite sub process.
	 *
	 * @since  1.0.0
	 * @param  int  $background_processes_id  Process ID.
	 * @return void
	 */
	abstract protected function update_prerequisite_sub_process_parent_background_process( $background_processes_id );

	/**
	 * Get the list of all known background process param keys.
	 *
	 * @since  1.0.0
	 * @return array<string>
	 */
	abstract protected function get_all_background_process_param_keys();

	/**
	 * Get the list of progress keys to include when saving process state.
	 *
	 * @since  1.0.0
	 * @return array<string>
	 */
	abstract protected function get_background_process_progress_keys();


	/**
	 * Get the processor-to-sub-param-keys map.
	 *
	 * @since  1.0.0
	 * @return array<string, array<string>>
	 */
	abstract protected function get_processor_sub_params();

	/**
	 * Get a human-readable label for a processor slug.
	 *
	 * @since  1.0.0
	 * @param  string  $processor  Processor slug.
	 * @return string
	 */
	abstract protected function get_background_processor_label( $processor );

	/**
	 * Create a message associated with a background process.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed>  $args  Message args.
	 * @return void
	 */
	abstract protected function create_background_processes_message( $args );

	/**
	 * Get a plugin-level setting value.
	 *
	 * @since  1.0.0
	 * @param  string  $key  Setting key.
	 * @return mixed
	 */
	abstract protected function get_plugin_setting( $key );

	/**
	 * Get a per-processor setting value.
	 *
	 * @since  1.0.0
	 * @param  string  $processor  Processor slug.
	 * @param  string  $key        Setting key.
	 * @param  mixed   $default    Optional. Default value.
	 * @return mixed
	 */
	abstract protected function get_processor_setting( $processor, $key, $default = false );

	/**
	 * Get the ActionScheduler hook name that triggers background process runs.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	abstract protected function get_action_scheduler_hook();

	/**
	 * Get the ActionScheduler group name for this processor.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	abstract protected function get_action_scheduler_group();
}
