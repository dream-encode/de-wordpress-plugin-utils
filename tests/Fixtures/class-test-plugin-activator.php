<?php
/**
 * Test plugin activator fixture.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures;

use Dream_Encode\WordPress_Plugin_Utils\Abstracts\Abstract_Plugin_Activator;

/**
 * Concrete activator used to capture lifecycle calls during tests.
 */
class Test_Plugin_Activator extends Abstract_Plugin_Activator {

	/**
	 * Ordered list of hook invocations captured during activation.
	 *
	 * @var string[]
	 */
	public static array $calls = array();

	/**
	 * Upgrader class name returned by `get_upgrader_class()`.
	 *
	 * @var class-string|null
	 */
	public static ?string $upgrader_class = null;

	/**
	 * Reset captured state between tests.
	 */
	public static function reset(): void {
		self::$calls          = array();
		self::$upgrader_class = null;
	}

	protected static function before_activate(): void {
		self::$calls[] = 'before';
	}

	protected static function after_activate(): void {
		self::$calls[] = 'after';
	}

	protected static function get_upgrader_class(): ?string {
		return self::$upgrader_class;
	}
}

/**
 * Test upgrader fixture used to verify `run_install()` invocation.
 */
class Test_Activator_Upgrader {

	/**
	 * Tracks whether `install()` has been invoked.
	 *
	 * @var bool
	 */
	public static bool $installed = false;

	/**
	 * Install routine invoked by `Abstract_Plugin_Activator::run_install()`.
	 */
	public static function install(): void {
		self::$installed = true;

		Test_Plugin_Activator::$calls[] = 'install';
	}
}
