<?php
/**
 * Tests for Functions.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Tests;

use Dream_Encode\WordPress_Plugin_Utils\Common\Functions;
use WP_UnitTestCase;

/**
 * Test case for Functions.
 */
class HelpersTest extends WP_UnitTestCase {

	/**
	 * Test that maybe_define_constant defines a constant that does not exist.
	 */
	public function test_maybe_define_constant_defines_missing_constant(): void {
		$constant_name = 'DE_WPU_TEST_CONST_' . uniqid();

		Functions::maybe_define_constant( $constant_name, 'value' );

		$this->assertTrue( defined( $constant_name ) );
		$this->assertSame( 'value', constant( $constant_name ) );
	}

	/**
	 * Test that maybe_define_constant does not redefine an existing constant.
	 */
	public function test_maybe_define_constant_preserves_existing_constant(): void {
		$constant_name = 'DE_WPU_TEST_CONST_' . uniqid();

		define( $constant_name, 'original' );

		Functions::maybe_define_constant( $constant_name, 'replacement' );

		$this->assertSame( 'original', constant( $constant_name ) );
	}

	/**
	 * Test that get_mysql_datetime returns a valid MySQL datetime string.
	 */
	public function test_get_mysql_datetime_returns_formatted_string(): void {
		$result = Functions::get_mysql_datetime( mktime( 12, 0, 0, 1, 5, 2026 ), 'UTC' );

		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', (string) $result );
		$this->assertSame( '2026-01-05 12:00:00', $result );
	}

	/**
	 * Test that get_mysql_datetime defaults to the current time.
	 */
	public function test_get_mysql_datetime_defaults_to_now(): void {
		$before = time();
		$result = Functions::get_mysql_datetime();
		$after  = time();

		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', (string) $result );
		$this->assertGreaterThanOrEqual( gmdate( 'Y-m-d H:i:s', $before ), $result );
		$this->assertLessThanOrEqual( gmdate( 'Y-m-d H:i:s', $after ), $result );
	}

	/**
	 * Test that mysql_datetime_to_datetime_short returns a formatted short date.
	 */
	public function test_mysql_datetime_to_datetime_short_formats_correctly(): void {
		$result = Functions::mysql_datetime_to_datetime_short( '2026-01-05 12:00:00' );

		$this->assertStringContainsString( 'Jan', (string) $result );
		$this->assertStringContainsString( '2026', (string) $result );
	}

	/**
	 * Test that mysql_datetime_to_datetime_long returns a formatted long date.
	 */
	public function test_mysql_datetime_to_datetime_long_formats_correctly(): void {
		$result = Functions::mysql_datetime_to_datetime_long( '2026-01-05 12:00:00' );

		$this->assertStringContainsString( 'January', (string) $result );
		$this->assertStringContainsString( '2026', (string) $result );
	}

	/**
	 * Test that format_timestamp_to_datetime_long returns a formatted long date.
	 */
	public function test_format_timestamp_to_datetime_long_formats_correctly(): void {
		$result = Functions::format_timestamp_to_datetime_long( mktime( 12, 0, 0, 1, 5, 2026 ), 'UTC' );

		$this->assertStringContainsString( 'January', (string) $result );
		$this->assertStringContainsString( '2026', (string) $result );
	}

	/**
	 * Test that convert_seconds_to_minutes_seconds formats correctly.
	 */
	public function test_convert_seconds_to_minutes_seconds(): void {
		$this->assertSame( '05:13', Functions::convert_seconds_to_minutes_seconds( 313 ) );
		$this->assertSame( '00:00', Functions::convert_seconds_to_minutes_seconds( 0 ) );
		$this->assertSame( '01:00', Functions::convert_seconds_to_minutes_seconds( 60 ) );
	}

	/**
	 * Test that get_user_display_name returns 'N/A' for a non-existent user.
	 */
	public function test_get_user_display_name_returns_na_for_missing_user(): void {
		$this->assertSame( 'N/A', Functions::get_user_display_name( 999999 ) );
	}

	/**
	 * Test that get_user_display_name returns the nicename for a real user.
	 */
	public function test_get_user_display_name_returns_nicename(): void {
		$user_id = self::factory()->user->create( array( 'user_nicename' => 'john-doe' ) );

		$this->assertSame( 'john-doe', Functions::get_user_display_name( $user_id ) );
	}
}
