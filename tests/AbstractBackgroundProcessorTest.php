<?php
/**
 * Tests for Abstract_Background_Processor.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Tests;

use Dream_Encode\WordPress_Plugin_Utils\Abstracts\Abstract_Background_Processor;
use Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures\Test_Background_Processor;
use WP_UnitTestCase;

/**
 * Test case for Abstract_Background_Processor.
 */
class AbstractBackgroundProcessorTest extends WP_UnitTestCase {

	/**
	 * Set up before each test.
	 */
	public function set_up(): void {
		parent::set_up();

		Test_Background_Processor::reset();
	}

	/**
	 * Constructor initializes the current time.
	 */
	public function test_constructor_sets_current_time(): void {
		$processor = new Test_Background_Processor();

		$this->assertNotNull( $processor->get_current_time() );
		$this->assertIsFloat( $processor->get_current_time() );
	}

	/**
	 * Default state returns expected values.
	 */
	public function test_default_state(): void {
		$processor = new Test_Background_Processor();

		$this->assertSame( 0, $processor->get_current_position() );
		$this->assertSame( 0, $processor->get_total_rows() );
		$this->assertSame( 0.0, (float) $processor->get_percent_complete() );
		$this->assertFalse( $processor->get_complete() );
		$this->assertFalse( $processor->get_background_processes_id() );
		$this->assertFalse( $processor->get_background_processes_run_id() );
		$this->assertFalse( $processor->get_parent_background_processes_id() );
	}

	/**
	 * The processor slug is exposed via get_processor().
	 */
	public function test_get_processor_returns_slug(): void {
		$processor = new Test_Background_Processor();

		$this->assertSame( 'test_processor', $processor->get_processor() );
	}

	/**
	 * get_is_runnable() returns the is_runnable flag.
	 */
	public function test_get_is_runnable_default_false(): void {
		$processor = new Test_Background_Processor();

		$this->assertFalse( $processor->get_is_runnable() );
	}

	/**
	 * get_label() returns the default label property.
	 */
	public function test_get_label_returns_default_label(): void {
		$processor = new Test_Background_Processor();

		$this->assertSame( 'Base Processor', $processor->get_label() );
	}

	/**
	 * set_sub_param() and get_sub_param() round-trip a value.
	 */
	public function test_sub_param_round_trip(): void {
		$processor = new Test_Background_Processor();

		$processor->set_sub_param( 'shop_id', 42 );

		$this->assertSame( 42, $processor->get_sub_param( 'shop_id' ) );
	}

	/**
	 * get_sub_param() returns null when no sub params exist.
	 */
	public function test_get_sub_param_returns_null_when_missing(): void {
		$processor = new Test_Background_Processor();

		$this->assertNull( $processor->get_sub_param( 'nonexistent' ) );
	}

	/**
	 * has_prerequisite_sub_background_processors() returns false by default.
	 */
	public function test_has_prerequisite_sub_background_processors_defaults_false(): void {
		$processor = new Test_Background_Processor();

		$this->assertFalse( $processor->has_prerequisite_sub_background_processors() );
	}

	/**
	 * has_prerequisite_sub_background_processors() returns true when processors are set.
	 */
	public function test_has_prerequisite_sub_background_processors_true_when_set(): void {
		$processor = new Test_Background_Processor();

		$processor->prerequisite_sub_background_processors = array( 'sub_a', 'sub_b' );

		$this->assertTrue( $processor->has_prerequisite_sub_background_processors() );
	}

	/**
	 * get_initial_prerequisite_sub_background_processes() builds pending entries.
	 */
	public function test_get_initial_prerequisite_sub_background_processes_builds_entries(): void {
		$processor = new Test_Background_Processor();

		$processor->prerequisite_sub_background_processors = array( 'sub_a', 'sub_b' );

		$initial = $processor->get_initial_prerequisite_sub_background_processes();

		$this->assertCount( 2, $initial );
		$this->assertSame( 'sub_a', $initial[0]['processor'] );
		$this->assertSame( Abstract_Background_Processor::PROCESS_STATUS_PENDING, $initial[0]['status'] );
		$this->assertFalse( $initial[0]['complete'] );
		$this->assertSame( 0, $initial[0]['current_position'] );
	}

	/**
	 * get_progress_keys() returns the processor progress keys.
	 */
	public function test_get_progress_keys_returns_processor_keys(): void {
		$processor = new Test_Background_Processor();

		$keys = $processor->get_progress_keys();

		$this->assertContains( 'processor', $keys );
		$this->assertContains( 'background_processes_id', $keys );
		$this->assertContains( 'current_position', $keys );
		$this->assertContains( 'prerequisite_sub_background_processes', $keys );
	}

	/**
	 * Verify get_progress() includes synced prerequisite_sub_background_processes after set_incomplete_prerequisite_sub_background_processors().
	 */
	public function test_get_progress_includes_synced_prerequisites(): void {
		$child = new Test_Background_Processor();
		$child->init_background_processor();
		$child_id = $child->get_background_processes_id();

		Test_Background_Processor::$db_processes[ $child_id ]['status']   = Abstract_Background_Processor::PROCESS_STATUS_COMPLETE;
		Test_Background_Processor::$db_processes[ $child_id ]['complete'] = true;
		Test_Background_Processor::$processes[ $child_id ]['status']       = Abstract_Background_Processor::PROCESS_STATUS_PROCESSING;
		Test_Background_Processor::$processes[ $child_id ]['complete']    = false;

		$parent_id = 999;
		$run_id    = 888;

		Test_Background_Processor::$processes[ $parent_id ] = array(
			'processor'                              => 'test_processor',
			'background_processes_id'               => $parent_id,
			'prerequisite_sub_background_processes' => array(
				array(
					'processor'               => 'sub_a',
					'background_processes_id' => $child_id,
					'status'                  => Abstract_Background_Processor::PROCESS_STATUS_PROCESSING,
					'complete'                => false,
				),
			),
		);

		Test_Background_Processor::$runs[ $run_id ] = array(
			'background_processes_id' => $parent_id,
			'processor'               => 'test_processor',
		);

		$parent = new Test_Background_Processor();
		$parent->background_processes_run_id = $run_id;
		$parent->parse_params();
		$parent->set_incomplete_prerequisite_sub_background_processors();

		$progress = $parent->get_progress();
		$this->assertTrue( $progress['prerequisite_sub_background_processes'][0]['complete'] );
	}


	/**
	 * set_incomplete_prerequisite_sub_background_processors() syncs child status from DB when option is stale.
	 */
	public function test_set_incomplete_prerequisite_sub_background_processors_syncs_from_db(): void {
		$child = new Test_Background_Processor();
		$child->init_background_processor();
		$child_id = $child->get_background_processes_id();

		// Mark child complete in DB but stale in option.
		Test_Background_Processor::$db_processes[ $child_id ]['status']   = Abstract_Background_Processor::PROCESS_STATUS_COMPLETE;
		Test_Background_Processor::$db_processes[ $child_id ]['complete'] = true;
		Test_Background_Processor::$processes[ $child_id ]['status']       = Abstract_Background_Processor::PROCESS_STATUS_PROCESSING;
		Test_Background_Processor::$processes[ $child_id ]['complete']    = false;

		$parent = new Test_Background_Processor();
		$parent->prerequisite_sub_background_processes = array(
			array(
				'processor'                => 'sub_a',
				'background_processes_id'  => $child_id,
				'status'                   => Abstract_Background_Processor::PROCESS_STATUS_PROCESSING,
				'complete'                 => false,
			),
		);

		$parent->set_incomplete_prerequisite_sub_background_processors();

		$this->assertEmpty( $parent->get_incomplete_prerequisite_sub_background_processors() );
		$this->assertTrue( $parent->prerequisite_sub_background_processes[0]['complete'] );
		$this->assertSame( Abstract_Background_Processor::PROCESS_STATUS_COMPLETE, $parent->prerequisite_sub_background_processes[0]['status'] );
	}

	/**
	 * init() persists synced prerequisite statuses back to the option store.
	 */
	public function test_init_persists_synced_prerequisite_statuses(): void {
		$child = new Test_Background_Processor();
		$child->init_background_processor();
		$child_id = $child->get_background_processes_id();

		// Mark child complete in DB but stale in option.
		Test_Background_Processor::$db_processes[ $child_id ]['status']   = Abstract_Background_Processor::PROCESS_STATUS_COMPLETE;
		Test_Background_Processor::$db_processes[ $child_id ]['complete'] = true;
		Test_Background_Processor::$processes[ $child_id ]['status']       = Abstract_Background_Processor::PROCESS_STATUS_PROCESSING;
		Test_Background_Processor::$processes[ $child_id ]['complete']    = false;

		$parent_id = 999;
		$run_id    = 888;

		// Seed the parent option with the stale child reference.
		Test_Background_Processor::$processes[ $parent_id ] = array(
			'processor'                              => 'test_processor',
			'background_processes_id'               => $parent_id,
			'prerequisite_sub_background_processes' => array(
				array(
					'processor'               => 'sub_a',
					'background_processes_id' => $child_id,
					'status'                  => Abstract_Background_Processor::PROCESS_STATUS_PROCESSING,
					'complete'                => false,
				),
			),
		);

		// Seed the run option so parse_params() can locate the parent.
		Test_Background_Processor::$runs[ $run_id ] = array(
			'background_processes_id' => $parent_id,
			'processor'               => 'test_processor',
		);

		$parent = new Test_Background_Processor();
		$parent->background_processes_run_id = $run_id;
		$parent->init();

		// The parent option should have been overwritten with the synced (complete) state.
		$saved_option = Test_Background_Processor::$processes[ $parent_id ];
		$this->assertTrue( $saved_option['prerequisite_sub_background_processes'][0]['complete'] );
		$this->assertSame( Abstract_Background_Processor::PROCESS_STATUS_COMPLETE, $saved_option['prerequisite_sub_background_processes'][0]['status'] );
	}

	/**
	 * post_background_process_run() saves the option after pushing the completed run into the array.
	 */
	public function test_post_background_process_run_saves_option_with_run_in_array(): void {
		$processor = new Test_Background_Processor();
		$processor->init_background_processor();
		$process_id = $processor->get_background_processes_id();

		$processor->background_processes_run_id = Test_Background_Processor::$processes[ $process_id ]['background_processes_run_id'];

		$processor->background_process_run_start();
		$processor->post_background_process_run();

		$saved_option = Test_Background_Processor::$processes[ $process_id ];
		$this->assertNotEmpty( $saved_option['background_process_runs'] );
		$this->assertCount( 1, $saved_option['background_process_runs'] );
		$this->assertSame( Abstract_Background_Processor::PROCESS_STATUS_COMPLETE, $saved_option['background_process_runs'][0]['status'] );
	}

	/**
	 * Parent process proceeds when a child is DB-complete but its option entry is still stale.
	 */
	public function test_parent_proceeds_when_child_db_complete_but_option_incomplete(): void {
		$child = new Test_Background_Processor();
		$child->init_background_processor();
		$child_id = $child->get_background_processes_id();

		// Mark child complete in DB but stale in option.
		Test_Background_Processor::$db_processes[ $child_id ]['status']   = Abstract_Background_Processor::PROCESS_STATUS_COMPLETE;
		Test_Background_Processor::$db_processes[ $child_id ]['complete'] = true;
		Test_Background_Processor::$processes[ $child_id ]['status']       = Abstract_Background_Processor::PROCESS_STATUS_PROCESSING;
		Test_Background_Processor::$processes[ $child_id ]['complete']    = false;

		$parent_id = 999;
		$run_id    = 888;

		// Seed the parent option with the stale child reference.
		Test_Background_Processor::$processes[ $parent_id ] = array(
			'processor'                              => 'test_processor',
			'background_processes_id'               => $parent_id,
			'prerequisite_sub_background_processes' => array(
				array(
					'processor'               => 'sub_a',
					'background_processes_id' => $child_id,
					'status'                  => Abstract_Background_Processor::PROCESS_STATUS_PROCESSING,
					'complete'                => false,
				),
			),
			'total_rows'                             => 10,
			'current_position'                       => 0,
		);

		// Seed the run option.
		Test_Background_Processor::$runs[ $run_id ] = array(
			'background_processes_id' => $parent_id,
			'processor'               => 'test_processor',
		);

		$parent = new Test_Background_Processor();
		$parent->background_processes_run_id = $run_id;
		$parent->init();

		$this->assertEmpty( $parent->get_incomplete_prerequisite_sub_background_processors() );

		// Verify the option was also updated so the REST API sees the correct state.
		$saved_option = Test_Background_Processor::$processes[ $parent_id ];
		$this->assertTrue( $saved_option['prerequisite_sub_background_processes'][0]['complete'] );
		$this->assertSame( Abstract_Background_Processor::PROCESS_STATUS_COMPLETE, $saved_option['prerequisite_sub_background_processes'][0]['status'] );
	}
}
