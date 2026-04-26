<?php
/**
 * Tests for Abstract_Plugin_I18n.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Tests;

use Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures\Test_Plugin_I18n;
use ReflectionClass;
use WP_UnitTestCase;

/**
 * Test case for Abstract_Plugin_I18n.
 */
class AbstractPluginI18nTest extends WP_UnitTestCase {

	/**
	 * Test that plugin textdomain loading stores the custom path.
	 */
	public function test_load_plugin_textdomain_sets_custom_path(): void {
		global $wp_textdomain_registry;

		$text_domain    = 'test-plugin-i18n';
		$languages_path = 'de-wordpress-plugin-utils/languages';
		$i18n           = new Test_Plugin_I18n( $text_domain, $languages_path );

		$this->assertTrue( $i18n->load_plugin_textdomain() );

		$reflection = new ReflectionClass( $wp_textdomain_registry );
		$property   = $reflection->getProperty( 'custom_paths' );
		$property->setAccessible( true );

		/** @var array<string, string> $custom_paths */
		$custom_paths = $property->getValue( $wp_textdomain_registry );

		$this->assertArrayHasKey( $text_domain, $custom_paths );
		$this->assertSame( WP_PLUGIN_DIR . '/' . $languages_path, $custom_paths[ $text_domain ] );
	}
}