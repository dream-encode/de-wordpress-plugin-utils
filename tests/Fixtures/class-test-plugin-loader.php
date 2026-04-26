<?php
/**
 * Test plugin loader fixtures.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures;

use Dream_Encode\WordPress_Plugin_Utils\Loader\Plugin_Loader;

/**
 * Concrete extension of Plugin_Loader for testing.
 */
class Test_Plugin_Loader extends Plugin_Loader {
}

/**
 * Hook callback component for loader tests.
 */
class Test_Plugin_Loader_Component {

	/**
	 * Captured action values.
	 *
	 * @var array<int, string>
	 */
	public array $actions = array();

	/**
	 * Record an action call.
	 *
	 * @param string $value Action payload.
	 * @return void
	 */
	public function record_action( string $value ): void {
		$this->actions[] = $value;
	}

	/**
	 * Append a suffix to the filtered value.
	 *
	 * @param string $value Value being filtered.
	 * @param string $suffix Suffix to append.
	 * @return string
	 */
	public function filter_value( string $value, string $suffix ): string {
		return $value . $suffix;
	}
}