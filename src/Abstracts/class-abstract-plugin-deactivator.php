<?php
/**
 * Abstract plugin deactivator.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils\Abstracts
 * @since   1.0.0
 */

namespace Dream_Encode\WordPress_Plugin_Utils\Abstracts;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract plugin deactivator.
 *
 * Provides a consistent entry point for `register_deactivation_hook`.
 *
 * @since 1.0.0
 */
abstract class Abstract_Plugin_Deactivator {

	/**
	 * Deactivator.
	 *
	 * Runs on plugin deactivation.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function deactivate(): void {
		static::before_deactivate();
		static::delete_options();
		static::after_deactivate();
	}

	/**
	 * Hook that runs before option cleanup.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected static function before_deactivate(): void {
	}

	/**
	 * Hook that runs after option cleanup.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected static function after_deactivate(): void {
	}

	/**
	 * Delete options registered via `get_options_to_delete()`.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected static function delete_options(): void {
		foreach ( static::get_options_to_delete() as $option ) {
			delete_option( $option );
		}
	}

	/**
	 * Get a list of option names to delete on deactivation.
	 *
	 * @since  1.0.0
	 * @return string[]
	 */
	protected static function get_options_to_delete(): array {
		return array();
	}
}
