<?php
/**
 * Tests for Plugin_Settings_Repository.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

namespace Dream_Encode\WordPress_Plugin_Utils\Tests;

use Dream_Encode\WordPress_Plugin_Utils\Settings\Plugin_Settings_Repository;
use WP_UnitTestCase;

/**
 * Test case for Plugin_Settings_Repository.
 */
class PluginSettingsRepositoryTest extends WP_UnitTestCase {

	private const OPTION_NAME = 'test_plugin_settings_repository';

	/**
	 * Tear down after each test.
	 */
	public function tear_down(): void {
		delete_option( self::OPTION_NAME );

		parent::tear_down();
	}

	/**
	 * Test that all() returns defaults when the option does not exist.
	 */
	public function test_all_returns_defaults_when_option_missing(): void {
		$repo = new Plugin_Settings_Repository(
			self::OPTION_NAME,
			array(
				'log_level' => 'off',
				'enabled'   => false,
			)
		);

		$this->assertSame(
			array(
				'log_level' => 'off',
				'enabled'   => false,
			),
			$repo->all()
		);
	}

	/**
	 * Test that stored values override defaults.
	 */
	public function test_all_merges_stored_values_over_defaults(): void {
		update_option(
			self::OPTION_NAME,
			array(
				'log_level' => 'debug',
			)
		);

		$repo = new Plugin_Settings_Repository(
			self::OPTION_NAME,
			array(
				'log_level' => 'off',
				'enabled'   => false,
			)
		);

		$this->assertSame(
			array(
				'log_level' => 'debug',
				'enabled'   => false,
			),
			$repo->all()
		);
	}

	/**
	 * Test that get() returns the stored value or a supplied default.
	 */
	public function test_get_returns_value_or_default(): void {
		$repo = new Plugin_Settings_Repository( self::OPTION_NAME, array( 'a' => 1 ) );

		$this->assertSame( 1, $repo->get( 'a' ) );
		$this->assertSame( 'fallback', $repo->get( 'missing', 'fallback' ) );
	}

	/**
	 * Test that set() persists a single value and refreshes the cache.
	 */
	public function test_set_persists_single_value(): void {
		$repo = new Plugin_Settings_Repository( self::OPTION_NAME, array( 'a' => 1 ) );

		$this->assertTrue( $repo->set( 'a', 42 ) );
		$this->assertSame( 42, $repo->get( 'a' ) );
		$this->assertSame( 42, get_option( self::OPTION_NAME )['a'] );
	}

	/**
	 * Test that update() replaces the entire stored payload.
	 */
	public function test_update_replaces_settings(): void {
		$repo = new Plugin_Settings_Repository( self::OPTION_NAME, array( 'a' => 1 ) );

		$repo->set( 'a', 2 );
		$repo->update( array( 'b' => 3 ) );

		$stored = get_option( self::OPTION_NAME );

		$this->assertArrayHasKey( 'b', $stored );
		$this->assertArrayNotHasKey( 'a', $stored );
		$this->assertSame( 1, $repo->get( 'a' ) );
		$this->assertSame( 3, $repo->get( 'b' ) );
	}

	/**
	 * Test that delete() removes the option and clears the cache.
	 */
	public function test_delete_removes_option(): void {
		$repo = new Plugin_Settings_Repository( self::OPTION_NAME, array( 'a' => 1 ) );

		$repo->set( 'a', 99 );

		$this->assertTrue( $repo->delete() );
		$this->assertFalse( get_option( self::OPTION_NAME ) );
		$this->assertSame( 1, $repo->get( 'a' ) );
	}

	/**
	 * Test that refresh() clears the in-memory cache so subsequent reads hit the DB.
	 */
	public function test_refresh_clears_cache(): void {
		$repo = new Plugin_Settings_Repository( self::OPTION_NAME, array( 'a' => 1 ) );

		$this->assertSame( 1, $repo->get( 'a' ) );

		update_option( self::OPTION_NAME, array( 'a' => 7 ) );

		$this->assertSame( 1, $repo->get( 'a' ) );

		$repo->refresh();

		$this->assertSame( 7, $repo->get( 'a' ) );
	}
}
