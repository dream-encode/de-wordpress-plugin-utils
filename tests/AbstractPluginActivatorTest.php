<?php
/**
 * Tests for Abstract_Plugin_Activator.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

namespace Dream_Encode\WordPress_Plugin_Utils\Tests;

use Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures\Test_Activator_Upgrader;
use Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures\Test_Plugin_Activator;
use WP_UnitTestCase;

/**
 * Test case for Abstract_Plugin_Activator.
 */
class AbstractPluginActivatorTest extends WP_UnitTestCase {

	/**
	 * Set up before each test.
	 */
	public function set_up(): void {
		parent::set_up();

		Test_Plugin_Activator::reset();
		Test_Activator_Upgrader::$installed = false;
	}

	/**
	 * Test that activate fires the before and after hooks.
	 */
	public function test_activate_fires_before_and_after_hooks(): void {
		Test_Plugin_Activator::activate();

		$this->assertSame( array( 'before', 'after' ), Test_Plugin_Activator::$calls );
	}

	/**
	 * Test that activate does not invoke an installer when none is configured.
	 */
	public function test_activate_skips_install_without_upgrader_class(): void {
		Test_Plugin_Activator::activate();

		$this->assertFalse( Test_Activator_Upgrader::$installed );
	}

	/**
	 * Test that activate runs install() on the configured upgrader class.
	 */
	public function test_activate_runs_install_on_configured_upgrader(): void {
		Test_Plugin_Activator::$upgrader_class = Test_Activator_Upgrader::class;

		Test_Plugin_Activator::activate();

		$this->assertTrue( Test_Activator_Upgrader::$installed );
		$this->assertSame( array( 'before', 'install', 'after' ), Test_Plugin_Activator::$calls );
	}

	/**
	 * Test that a missing upgrader class does not trigger install.
	 */
	public function test_activate_skips_install_when_class_missing(): void {
		Test_Plugin_Activator::$upgrader_class = 'This_Class_Does_Not_Exist';

		Test_Plugin_Activator::activate();

		$this->assertSame( array( 'before', 'after' ), Test_Plugin_Activator::$calls );
	}
}
