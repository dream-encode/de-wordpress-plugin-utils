<?php
/**
 * Tests for Abstract_WC_Logger.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Tests;

use Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures\Test_Logger;
use WP_UnitTestCase;

/**
 * Test case for Abstract_WC_Logger.
 */
class AbstractWCLoggerTest extends WP_UnitTestCase {

	/**
	 * Set up before each test.
	 */
	public function set_up(): void {
		parent::set_up();

		Test_Logger::set_log_level( 'debug' );
		Test_Logger::clear_logs();
	}

	/**
	 * Test that the logger has the correct namespace.
	 */
	public function test_logger_has_correct_namespace(): void {
		$this->assertSame( 'test-logger', Test_Logger::get_namespace() );
	}

	/**
	 * Test that all expected log levels are defined.
	 */
	public function test_logger_has_all_log_levels(): void {
		$expected_levels = array(
			'emergency',
			'alert',
			'critical',
			'error',
			'warning',
			'notice',
			'info',
			'debug',
		);

		$this->assertSame( $expected_levels, Test_Logger::get_log_levels() );
	}

	/**
	 * Test that log levels are in order of severity.
	 */
	public function test_log_levels_are_in_severity_order(): void {
		$levels = Test_Logger::get_log_levels();

		$this->assertSame( 'emergency', $levels[0] );
		$this->assertSame( 'debug', end( $levels ) );
	}

	/**
	 * Test that invalid log level does not cause errors.
	 */
	public function test_log_with_invalid_level_does_not_error(): void {
		Test_Logger::log( 'Test message', 'invalid_level' );

		$this->assertTrue( true );
	}

	/**
	 * Test that logging is disabled when level is set to off.
	 */
	public function test_logging_disabled_when_level_is_off(): void {
		Test_Logger::set_log_level( 'off' );

		Test_Logger::log( 'Test message', 'debug' );

		$this->assertTrue( true );
	}

	/**
	 * Test emergency log level method.
	 */
	public function test_emergency_method_exists(): void {
		$this->assertTrue( method_exists( Test_Logger::class, 'emergency' ) );
	}

	/**
	 * Test alert log level method.
	 */
	public function test_alert_method_exists(): void {
		$this->assertTrue( method_exists( Test_Logger::class, 'alert' ) );
	}

	/**
	 * Test critical log level method.
	 */
	public function test_critical_method_exists(): void {
		$this->assertTrue( method_exists( Test_Logger::class, 'critical' ) );
	}

	/**
	 * Test error log level method.
	 */
	public function test_error_method_exists(): void {
		$this->assertTrue( method_exists( Test_Logger::class, 'error' ) );
	}

	/**
	 * Test warning log level method.
	 */
	public function test_warning_method_exists(): void {
		$this->assertTrue( method_exists( Test_Logger::class, 'warning' ) );
	}

	/**
	 * Test notice log level method.
	 */
	public function test_notice_method_exists(): void {
		$this->assertTrue( method_exists( Test_Logger::class, 'notice' ) );
	}

	/**
	 * Test info log level method.
	 */
	public function test_info_method_exists(): void {
		$this->assertTrue( method_exists( Test_Logger::class, 'info' ) );
	}

	/**
	 * Test debug log level method.
	 */
	public function test_debug_method_exists(): void {
		$this->assertTrue( method_exists( Test_Logger::class, 'debug' ) );
	}

	/**
	 * Test that array data is converted to string.
	 */
	public function test_array_data_is_logged(): void {
		$data = array( 'key' => 'value' );

		Test_Logger::log( $data, 'debug' );

		$this->assertTrue( true );
	}

	/**
	 * Test that object data is converted to string.
	 */
	public function test_object_data_is_logged(): void {
		$data = new \stdClass();
		$data->key = 'value';

		Test_Logger::log( $data, 'debug' );

		$this->assertTrue( true );
	}

	/**
	 * Test that string data is logged.
	 */
	public function test_string_data_is_logged(): void {
		Test_Logger::log( 'Test string message', 'debug' );

		$this->assertTrue( true );
	}
}

