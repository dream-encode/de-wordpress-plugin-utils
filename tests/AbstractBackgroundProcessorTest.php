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
	}
}
