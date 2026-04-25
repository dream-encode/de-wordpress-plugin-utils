<?php
/**
 * Test plugin deactivator fixture.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

namespace Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures;

use Dream_Encode\WordPress_Plugin_Utils\Abstracts\Abstract_Plugin_Deactivator;

/**
 * Concrete deactivator used to capture lifecycle calls during tests.
 */
class Test_Plugin_Deactivator extends Abstract_Plugin_Deactivator {

	/**
	 * Ordered list of hook invocations captured during deactivation.
	 *
	 * @var string[]
	 */
	public static array $calls = array();

	/**
	 * Option names returned by `get_options_to_delete()`.
	 *
	 * @var string[]
	 */
	public static array $options_to_delete = array();

	/**
	 * Reset captured state between tests.
	 */
	public static function reset(): void {
		self::$calls             = array();
		self::$options_to_delete = array();
	}

	protected static function before_deactivate(): void {
		self::$calls[] = 'before';
	}

	protected static function after_deactivate(): void {
		self::$calls[] = 'after';
	}

	protected static function get_options_to_delete(): array {
		return self::$options_to_delete;
	}
}
