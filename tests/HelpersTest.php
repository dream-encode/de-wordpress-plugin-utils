<?php
/**
 * Tests for shared helper functions.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

namespace Dream_Encode\WordPress_Plugin_Utils\Tests;

use WP_UnitTestCase;

use function Dream_Encode\WordPress_Plugin_Utils\Functions\maybe_define_constant;

/**
 * Test case for helper functions.
 */
class HelpersTest extends WP_UnitTestCase {

	/**
	 * Test that maybe_define_constant defines a constant that does not exist.
	 */
	public function test_maybe_define_constant_defines_missing_constant(): void {
		$constant_name = 'DE_WPU_TEST_CONST_' . uniqid();

		maybe_define_constant( $constant_name, 'value' );

		$this->assertTrue( defined( $constant_name ) );
		$this->assertSame( 'value', constant( $constant_name ) );
	}

	/**
	 * Test that maybe_define_constant does not redefine an existing constant.
	 */
	public function test_maybe_define_constant_preserves_existing_constant(): void {
		$constant_name = 'DE_WPU_TEST_CONST_' . uniqid();

		define( $constant_name, 'original' );

		maybe_define_constant( $constant_name, 'replacement' );

		$this->assertSame( 'original', constant( $constant_name ) );
	}
}
