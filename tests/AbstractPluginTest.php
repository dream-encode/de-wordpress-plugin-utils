<?php
/**
 * Tests for Abstract_Plugin.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

namespace Dream_Encode\WordPress_Plugin_Utils\Tests;

use Dream_Encode\WordPress_Plugin_Utils\Abstracts\Abstract_Plugin_Loader;
use Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures\Test_Plugin;
use WP_UnitTestCase;

/**
 * Test case for Abstract_Plugin.
 */
class AbstractPluginTest extends WP_UnitTestCase {

	/**
	 * Test that the constructor runs the lifecycle in order.
	 */
	public function test_constructor_runs_lifecycle_in_order(): void {
		$plugin = new Test_Plugin();

		$this->assertSame(
			array(
				'load_dependencies',
				'set_locale',
				'define_admin_hooks',
				'define_public_hooks',
			),
			$plugin->calls
		);
	}

	/**
	 * Test that the plugin slug is stored and exposed.
	 */
	public function test_plugin_name_is_stored_and_exposed(): void {
		$plugin = new Test_Plugin();

		$this->assertSame( 'test-plugin', $plugin->get_plugin_name() );
	}

	/**
	 * Test that the default version is used when no constant is configured.
	 */
	public function test_default_version_is_used_when_no_constant_configured(): void {
		$plugin = new Test_Plugin();

		$this->assertSame( '1.0.0', $plugin->get_version() );
	}

	/**
	 * Test that the version constant is resolved when defined.
	 */
	public function test_version_constant_is_resolved_when_defined(): void {
		$constant_name = 'DE_WPU_TEST_PLUGIN_VERSION_' . uniqid();

		define( $constant_name, '4.2.1' );

		$plugin                   = new Test_Plugin();
		$plugin->version_constant = $constant_name;
		$plugin->__construct();

		$this->assertSame( '4.2.1', $plugin->get_version() );
	}

	/**
	 * Test that get_loader() returns the loader created by the subclass.
	 */
	public function test_get_loader_returns_instance_from_create_loader(): void {
		$plugin = new Test_Plugin();

		$this->assertInstanceOf( Abstract_Plugin_Loader::class, $plugin->get_loader() );
	}

	/**
	 * Test that run() delegates to the loader.
	 */
	public function test_run_registers_hooks_through_loader(): void {
		$plugin    = new Test_Plugin();
		$component = new \Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures\Test_Plugin_Loader_Component();

		$plugin->get_loader()->add_action( __FUNCTION__, $component, 'record_action' );
		$plugin->run();

		do_action( __FUNCTION__, 'payload' );

		$this->assertSame( array( 'payload' ), $component->actions );
	}
}
