<?php
/**
 * Tests for Abstract_Plugin_Deactivator.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Tests;

use Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures\Test_Plugin_Deactivator;
use WP_UnitTestCase;

/**
 * Test case for Abstract_Plugin_Deactivator.
 */
class AbstractPluginDeactivatorTest extends WP_UnitTestCase {

	/**
	 * Set up before each test.
	 */
	public function set_up(): void {
		parent::set_up();

		Test_Plugin_Deactivator::reset();

		delete_option( 'test_deactivator_option_a' );
		delete_option( 'test_deactivator_option_b' );
	}

	/**
	 * Tear down after each test.
	 */
	public function tear_down(): void {
		delete_option( 'test_deactivator_option_a' );
		delete_option( 'test_deactivator_option_b' );

		parent::tear_down();
	}

	/**
	 * Test that deactivate fires the before and after hooks.
	 */
	public function test_deactivate_fires_before_and_after_hooks(): void {
		Test_Plugin_Deactivator::deactivate();

		$this->assertSame( array( 'before', 'after' ), Test_Plugin_Deactivator::$calls );
	}

	/**
	 * Test that deactivate deletes every configured option.
	 */
	public function test_deactivate_deletes_configured_options(): void {
		update_option( 'test_deactivator_option_a', 'value_a' );
		update_option( 'test_deactivator_option_b', 'value_b' );

		Test_Plugin_Deactivator::$options_to_delete = array(
			'test_deactivator_option_a',
			'test_deactivator_option_b',
		);

		Test_Plugin_Deactivator::deactivate();

		$this->assertFalse( get_option( 'test_deactivator_option_a' ) );
		$this->assertFalse( get_option( 'test_deactivator_option_b' ) );
	}
}
