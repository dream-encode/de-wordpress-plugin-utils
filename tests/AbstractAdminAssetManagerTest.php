<?php
/**
 * Tests for Asset_Manager.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Tests;

use Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures\Test_Asset_Manager;
use WP_UnitTestCase;

/**
 * Test case for Asset_Manager.
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
	 * @param  string                                                         $screen_id          Screen id.
	 * @param  array<int, array<string, mixed>>                               $assets             Asset config list.
	 * @param  array<string, array{dependencies: string[], version: string}>  $asset_files        Stubbed asset files.
	 * @param  array<string, array<string, mixed>>                            $localization_data  Optional screen localization data.
	 * @param  string                                                         $localization_global Optional JS global.
	 * @return Test_Asset_Manager
	 */
	private function make_manager(
		string $screen_id,
		array $assets,
		array $asset_files = array(),
		array $localization_data = array(),
		string $localization_global = ''
	): Test_Asset_Manager {
		$manager = new Test_Asset_Manager(
			handle_prefix: 'test-plugin-admin-',
			plugin_path: '/tmp/test-plugin/',
			plugin_url: 'https://example.com/wp-content/plugins/test-plugin/',
			plugin_version: '1.0.0',
			screens_to_assets: array( $screen_id => $assets ),
			localization_global: $localization_global,
			screens_localization_data: $localization_data,
		);

		$manager->asset_files = $asset_files;

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

	/**
	 * Test that `localized => true` applies screen-level localization data.
	 */
	public function test_localized_flag_applies_screen_localization_data(): void {
		set_current_screen( 'edit-post' );

		$manager = $this->make_manager(
			'edit-post',
			array(
				array(
					'name'      => 'widget',
					'localized' => true,
				),
			),
			array(),
			array( 'edit-post' => array( 'REST_URL' => 'https://example.com/wp-json' ) ),
			'MY_PLUGIN'
		);

		$manager->enqueue_scripts();

		$registered = wp_scripts()->registered['test-plugin-admin-widget'];

		$this->assertStringContainsString( 'MY_PLUGIN', (string) $registered->extra['data'] );
		$this->assertStringContainsString( 'example.com', (string) $registered->extra['data'] );
	}

	/**
	 * Test that `localized => true` is a no-op when the global is empty.
	 */
	public function test_localized_flag_noops_without_global(): void {
		set_current_screen( 'edit-post' );

		$manager = $this->make_manager(
			'edit-post',
			array(
				array(
					'name'      => 'widget',
					'localized' => true,
				),
			),
			array(),
			array( 'edit-post' => array( 'REST_URL' => 'https://example.com/wp-json' ) )
		);

		$manager->enqueue_scripts();

		$registered = wp_scripts()->registered['test-plugin-admin-widget'];

		$this->assertEmpty( $registered->extra['data'] ?? '' );
	}

	/**
	 * Test current_screen_assets returns assets for the current screen.
	 */
	public function test_current_screen_assets(): void {
		set_current_screen( 'edit-post' );

		$manager = $this->make_manager(
			'edit-post',
			array(
				array( 'name' => 'widget-a' ),
				array( 'name' => 'widget-b' ),
			)
		);

		$this->assertCount( 2, $manager->current_screen_assets() );
	}

	/**
	 * Test current_screen_assets returns empty for an unregistered screen.
	 */
	public function test_current_screen_assets_empty_for_other_screen(): void {
		set_current_screen( 'edit-page' );

		$manager = $this->make_manager( 'edit-post', array( array( 'name' => 'widget' ) ) );

		$this->assertSame( array(), $manager->current_screen_assets() );
	}

	/**
	 * Test current_screen_assets returns empty when no screen is set.
	 */
	public function test_current_screen_assets_empty_without_screen(): void {
		global $current_screen;
		$current_screen = null;

		$manager = $this->make_manager( 'edit-post', array( array( 'name' => 'widget' ) ) );

		$this->assertSame( array(), $manager->current_screen_assets() );
	}

	/**
	 * Test current_screen_has_assets returns the count.
	 */
	public function test_current_screen_has_assets_count(): void {
		set_current_screen( 'edit-post' );

		$manager = $this->make_manager(
			'edit-post',
			array(
				array( 'name' => 'widget-a' ),
				array( 'name' => 'widget-b' ),
			)
		);

		$this->assertSame( 2, $manager->current_screen_has_assets() );
	}

	/**
	 * Test screen_assets returns assets for the given WP_Screen.
	 */
	public function test_screen_assets(): void {
		$screen  = \WP_Screen::get( 'edit-post' );
		$manager = $this->make_manager( 'edit-post', array( array( 'name' => 'widget' ) ) );
		$assets  = $manager->screen_assets( $screen );

		$this->assertCount( 1, $assets );
		$this->assertSame( 'widget', $assets[0]['name'] );
	}

	/**
	 * Test screen_has_assets returns 0 for an unregistered screen.
	 */
	public function test_screen_has_assets_zero_for_unknown_screen(): void {
		$screen  = \WP_Screen::get( 'edit-page' );
		$manager = $this->make_manager( 'edit-post', array( array( 'name' => 'widget' ) ) );

		$this->assertSame( 0, $manager->screen_has_assets( $screen ) );
	}

	/**
	 * Test screen_get_localized_data returns data for the given screen.
	 */
	public function test_screen_get_localized_data_returns_screen_data(): void {
		$screen  = \WP_Screen::get( 'edit-post' );
		$manager = $this->make_manager(
			'edit-post',
			array(),
			array(),
			array( 'edit-post' => array( 'REST_URL' => 'https://example.com/wp-json' ) )
		);

		$this->assertSame(
			array( 'REST_URL' => 'https://example.com/wp-json' ),
			$manager->screen_get_localized_data( $screen )
		);
	}

	/**
	 * Test add_screens merges additional screen entries at runtime.
	 */
	public function test_add_screens_merges_entries(): void {
		$manager = $this->make_manager( 'edit-post', array( array( 'name' => 'widget-a' ) ) );

		$manager->add_screens( array( 'edit-page' => array( array( 'name' => 'widget-b' ) ) ) );

		$this->assertSame( 1, $manager->screen_has_assets( \WP_Screen::get( 'edit-post' ) ) );
		$this->assertSame( 1, $manager->screen_has_assets( \WP_Screen::get( 'edit-page' ) ) );
	}

	/**
	 * Test add_screens_localization_data merges entries at runtime.
	 */
	public function test_add_screens_localization_data_merges_entries(): void {
		$screen  = \WP_Screen::get( 'edit-post' );
		$manager = $this->make_manager( 'edit-post', array() );

		$manager->add_screens_localization_data( array( 'edit-post' => array( 'KEY' => 'VALUE' ) ) );

		$this->assertSame( array( 'KEY' => 'VALUE' ), $manager->screen_get_localized_data( $screen ) );
	}
}
