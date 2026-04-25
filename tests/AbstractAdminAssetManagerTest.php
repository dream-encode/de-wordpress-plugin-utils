<?php
/**
 * Tests for Abstract_Admin_Asset_Manager.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

namespace Dream_Encode\WordPress_Plugin_Utils\Tests;

use Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures\Test_Admin_Asset_Manager;
use WP_UnitTestCase;

/**
 * Test case for Abstract_Admin_Asset_Manager.
 */
class AbstractAdminAssetManagerTest extends WP_UnitTestCase {

	/**
	 * Set up before each test.
	 */
	public function set_up(): void {
		parent::set_up();

		require_once ABSPATH . 'wp-admin/includes/screen.php';

		set_current_screen( 'edit-post' );

		global $wp_scripts, $wp_styles;

		$wp_scripts = null;
		$wp_styles  = null;
	}

	/**
	 * Build a manager populated for a given screen id.
	 *
	 * @param  string                                             $screen_id         Screen id.
	 * @param  array<int, array<string, mixed>>                   $assets            Asset config list.
	 * @param  array<string, array{dependencies: string[], version: string}> $asset_files Stubbed asset files.
	 * @return Test_Admin_Asset_Manager
	 */
	private function make_manager( string $screen_id, array $assets, array $asset_files = array() ): Test_Admin_Asset_Manager {
		$manager = new Test_Admin_Asset_Manager();

		$manager->screens_to_assets = array(
			$screen_id => $assets,
		);
		$manager->asset_files       = $asset_files;

		return $manager;
	}

	/**
	 * Test that nothing is enqueued when no current screen is set.
	 */
	public function test_enqueue_noops_without_current_screen(): void {
		global $current_screen;
		$current_screen = null;

		$manager = $this->make_manager(
			'edit-post',
			array(
				array( 'name' => 'widget' ),
			)
		);

		$manager->enqueue_scripts();
		$manager->enqueue_styles();

		$this->assertFalse( wp_script_is( 'test-plugin-admin-widget', 'registered' ) );
		$this->assertFalse( wp_style_is( 'test-plugin-admin-widget', 'registered' ) );
	}

	/**
	 * Test that a script asset is registered, enqueued and receives extra dependencies.
	 */
	public function test_enqueue_script_registers_handle_and_merges_dependencies(): void {
		set_current_screen( 'edit-post' );

		$manager = $this->make_manager(
			'edit-post',
			array(
				array(
					'name'         => 'widget',
					'dependencies' => array( 'jquery' ),
					'types'        => array( 'script' ),
				),
			),
			array(
				'widget' => array(
					'dependencies' => array( 'wp-element' ),
					'version'      => '2.1.0',
				),
			)
		);

		$manager->enqueue_scripts();

		$this->assertTrue( wp_script_is( 'test-plugin-admin-widget', 'enqueued' ) );

		$registered = wp_scripts()->registered['test-plugin-admin-widget'];

		$this->assertContains( 'wp-element', $registered->deps );
		$this->assertContains( 'jquery', $registered->deps );
		$this->assertSame( '2.1.0', $registered->ver );
	}

	/**
	 * Test that a style asset is enqueued with filtered dependencies.
	 */
	public function test_enqueue_style_registers_filtered_dependencies(): void {
		set_current_screen( 'edit-post' );

		$manager = $this->make_manager(
			'edit-post',
			array(
				array(
					'name'  => 'widget',
					'types' => array( 'style' ),
				),
			),
			array(
				'widget' => array(
					'dependencies' => array( 'wp-components', 'jquery' ),
					'version'      => '3.0.0',
				),
			)
		);

		$manager->enqueue_styles();

		$this->assertTrue( wp_style_is( 'test-plugin-admin-widget', 'enqueued' ) );

		$registered = wp_styles()->registered['test-plugin-admin-widget'];

		$this->assertContains( 'wp-components', $registered->deps );
		$this->assertNotContains( 'jquery', $registered->deps );
	}

	/**
	 * Test that the `types` key restricts which type is enqueued.
	 */
	public function test_types_filter_excludes_non_matching_type(): void {
		set_current_screen( 'edit-post' );

		$manager = $this->make_manager(
			'edit-post',
			array(
				array(
					'name'  => 'widget',
					'types' => array( 'style' ),
				),
			)
		);

		$manager->enqueue_scripts();

		$this->assertFalse( wp_script_is( 'test-plugin-admin-widget', 'registered' ) );
	}

	/**
	 * Test that assets for other screens are not enqueued.
	 */
	public function test_assets_for_other_screens_are_ignored(): void {
		set_current_screen( 'edit-page' );

		$manager = $this->make_manager(
			'edit-post',
			array(
				array( 'name' => 'widget' ),
			)
		);

		$manager->enqueue_scripts();

		$this->assertFalse( wp_script_is( 'test-plugin-admin-widget', 'registered' ) );
	}

	/**
	 * Test that `conditions => false` skips the asset.
	 */
	public function test_false_conditions_skip_asset(): void {
		set_current_screen( 'edit-post' );

		$manager = $this->make_manager(
			'edit-post',
			array(
				array(
					'name'       => 'widget',
					'conditions' => false,
				),
			)
		);

		$manager->enqueue_scripts();

		$this->assertFalse( wp_script_is( 'test-plugin-admin-widget', 'registered' ) );
	}

	/**
	 * Test that localization uses the asset-level global when provided.
	 */
	public function test_localization_uses_asset_global(): void {
		set_current_screen( 'edit-post' );

		$manager = $this->make_manager(
			'edit-post',
			array(
				array(
					'name'                => 'widget',
					'localization'        => array( 'REST_URL' => 'https://example.com/wp-json' ),
					'localization_global' => 'TEST_WIDGET',
				),
			)
		);

		$manager->enqueue_scripts();

		$registered = wp_scripts()->registered['test-plugin-admin-widget'];

		$this->assertStringContainsString( 'TEST_WIDGET', (string) $registered->extra['data'] );
		$this->assertStringContainsString( 'example.com', (string) $registered->extra['data'] );
	}
}
