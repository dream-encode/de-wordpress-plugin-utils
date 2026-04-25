<?php
/**
 * Tests for Plugin_Loader.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

namespace Dream_Encode\WordPress_Plugin_Utils\Tests;

use Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures\Test_Plugin_Loader;
use Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures\Test_Plugin_Loader_Component;
use WP_UnitTestCase;

/**
 * Test case for Plugin_Loader.
 */
class AbstractPluginLoaderTest extends WP_UnitTestCase {

	/**
	 * Test that action hooks are registered and executed.
	 */
	public function test_add_action_registers_and_runs_hook(): void {
		$loader    = new Test_Plugin_Loader();
		$component = new Test_Plugin_Loader_Component();

		$loader->add_action( __FUNCTION__, $component, 'record_action', 20 );
		$loader->run();

		do_action( __FUNCTION__, 'payload' );

		$this->assertSame( array( 'payload' ), $component->actions );
	}

	/**
	 * Test that filter hooks are registered and executed.
	 */
	public function test_add_filter_registers_and_runs_hook(): void {
		$loader    = new Test_Plugin_Loader();
		$component = new Test_Plugin_Loader_Component();

		$loader->add_filter( __FUNCTION__, $component, 'filter_value', 15, 2 );
		$loader->run();

		$value = apply_filters( __FUNCTION__, 'value', '-suffix' );

		$this->assertSame( 'value-suffix', $value );
	}
}