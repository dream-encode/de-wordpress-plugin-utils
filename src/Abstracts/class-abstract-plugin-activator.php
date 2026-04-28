<?php
/**
 * Abstract plugin activator.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils\Abstracts
 * @since   1.0.0
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Abstracts;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract plugin activator.
 *
 * Provides a consistent entry point for `register_activation_hook`.
 *
 * @since 1.0.0
 */
abstract class Abstract_Plugin_Activator {

	/**
	 * Activator.
	 *
	 * Runs on plugin activation.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function activate(): void {
		static::before_activate();
		static::run_install();
		static::after_activate();
	}

	/**
	 * Hook that runs before install.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected static function before_activate(): void {
	}

	/**
	 * Hook that runs after install.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected static function after_activate(): void {
	}

	/**
	 * Run the plugin install routine.
	 *
	 * Invokes `install()` on the upgrader class returned by `get_upgrader_class()`.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected static function run_install(): void {
		$upgrader_class = static::get_upgrader_class();

		if ( null === $upgrader_class ) {
			return;
		}

		if ( class_exists( $upgrader_class ) && method_exists( $upgrader_class, 'install' ) ) {
			$upgrader_class::install();
		}
	}

	/**
	 * Get the upgrader class to invoke during activation.
	 *
	 * Return `null` to skip install.
	 *
	 * @since  1.0.0
	 * @return class-string|null
	 */
	protected static function get_upgrader_class(): ?string {
		return null;
	}
}
