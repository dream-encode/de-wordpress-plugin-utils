<?php
/**
 * Shared helper functions.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils\Functions
 * @since   1.0.0
 */

namespace Dream_Encode\WordPress_Plugin_Utils\Functions;

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( __NAMESPACE__ . '\\maybe_define_constant' ) ) {
	/**
	 * Define a constant if it is not already defined.
	 *
	 * @since  1.0.0
	 * @param  string $name  Constant name.
	 * @param  mixed  $value Constant value.
	 * @return void
	 */
	function maybe_define_constant( string $name, mixed $value ): void {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}
}
