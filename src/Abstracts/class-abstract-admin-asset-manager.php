<?php
/**
 * Abstract admin asset manager.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils\Abstracts
 * @since   1.0.0
 */

namespace Dream_Encode\WordPress_Plugin_Utils\Abstracts;

use WP_Screen;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract admin asset manager.
 *
 * Encapsulates the screen-based admin asset enqueuing pattern used across the
 * Max Marine plugin suite. Subclasses describe the plugin's base paths, handle
 * prefix and per-screen asset map, and the manager handles registration,
 * dependency resolution and localization.
 *
 * Expected shape of each asset array:
 *   - name (string, required) Handle suffix and base filename.
 *   - dependencies (string[], optional) Extra dependencies merged with the
 *     `.asset.php` file dependencies.
 *   - types (string[], optional) Subset of { 'style', 'script' } controlling
 *     which asset types to enqueue.
 *   - localization (array<string, mixed>, optional) Data for `wp_localize_script`.
 *   - localization_global (string, optional) Override the JS global used for
 *     `wp_localize_script`.
 *   - conditions (bool, optional) Skip the asset when false.
 *   - enqueue_media (bool, optional) Call `wp_enqueue_media()` before the script.
 *
 * @since 1.0.0
 */
abstract class Abstract_Admin_Asset_Manager {

	/**
	 * Tracks whether `wp_enqueue_media()` has been invoked during the current request.
	 *
	 * @var bool
	 */
	protected bool $media_enqueued = false;

	/**
	 * Get the handle prefix used for registered scripts and styles.
	 *
	 * Example: `max-marine-product-listing-queues-admin-`.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	abstract protected function get_handle_prefix(): string;

	/**
	 * Get the absolute plugin base path (trailing slash included).
	 *
	 * @since  1.0.0
	 * @return string
	 */
	abstract protected function get_plugin_path(): string;

	/**
	 * Get the plugin base URL (trailing slash included).
	 *
	 * @since  1.0.0
	 * @return string
	 */
	abstract protected function get_plugin_url(): string;

	/**
	 * Get the plugin version used as a fallback when an asset file is missing.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	abstract protected function get_plugin_version(): string;

	/**
	 * Get the per-screen asset map.
	 *
	 * @since  1.0.0
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	abstract protected function get_screens_to_assets(): array;

	/**
	 * Get the admin subdirectory relative to the plugin base path.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	protected function get_admin_subdir(): string {
		return 'admin/';
	}

	/**
	 * Get the JS distribution subdirectory relative to the admin subdirectory.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	protected function get_js_subdir(): string {
		return 'assets/dist/js/';
	}

	/**
	 * Get the CSS distribution subdirectory relative to the admin subdirectory.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	protected function get_css_subdir(): string {
		return 'assets/dist/css/';
	}

	/**
	 * Get the prefix prepended to asset filenames.
	 *
	 * Example: with a prefix of `admin-` and an asset name of `queue-edit`, the
	 * generated filename is `admin-queue-edit.min.js`.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	protected function get_asset_file_prefix(): string {
		return 'admin-';
	}

	/**
	 * Get the default JS global used for `wp_localize_script`.
	 *
	 * Subclasses that use a single shared JS global across assets should return
	 * it here. Individual assets may override via `localization_global`.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	protected function get_localization_global(): string {
		return '';
	}

	/**
	 * Get the list of WP-provided style handles to retain from script asset
	 * dependency arrays when enqueuing stylesheets.
	 *
	 * @since  1.0.0
	 * @return string[]
	 */
	protected function get_wp_style_dependencies(): array {
		return array( 'wp-components' );
	}

	/**
	 * Enqueue all styles registered for the current admin screen.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function enqueue_styles(): void {
		$this->enqueue_for_current_screen( 'style' );
	}

	/**
	 * Enqueue all scripts registered for the current admin screen.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function enqueue_scripts(): void {
		$this->enqueue_for_current_screen( 'script' );
	}

	/**
	 * Enqueue the assets of the given type for the current admin screen.
	 *
	 * @since  1.0.0
	 * @param  string  $type Either 'style' or 'script'.
	 * @return void
	 */
	protected function enqueue_for_current_screen( string $type ): void {
		$current_screen = get_current_screen();

		if ( ! $current_screen instanceof WP_Screen ) {
			return;
		}

		$assets = $this->get_assets_for_screen( $current_screen );

		if ( empty( $assets ) ) {
			return;
		}

		foreach ( $assets as $asset ) {
			if ( ! $this->should_enqueue_asset( $asset, $type ) ) {
				continue;
			}

			if ( 'style' === $type ) {
				$this->enqueue_style_asset( $asset );

				continue;
			}

			$this->enqueue_script_asset( $asset );
		}
	}

	/**
	 * Get the assets registered for the given screen.
	 *
	 * @since  1.0.0
	 * @param  WP_Screen  $screen Current admin screen.
	 * @return array<int, array<string, mixed>>
	 */
	protected function get_assets_for_screen( WP_Screen $screen ): array {
		$screens_to_assets = $this->get_screens_to_assets();

		if ( empty( $screens_to_assets[ $screen->id ] ) ) {
			return array();
		}

		return $screens_to_assets[ $screen->id ];
	}

	/**
	 * Determine whether an asset should be enqueued for the given type.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed> $asset Asset configuration.
	 * @param  string               $type  Either 'style' or 'script'.
	 * @return bool
	 */
	protected function should_enqueue_asset( array $asset, string $type ): bool {
		if ( array_key_exists( 'conditions', $asset ) && false === $asset['conditions'] ) {
			return false;
		}

		if ( empty( $asset['name'] ) || ! is_string( $asset['name'] ) ) {
			return false;
		}

		if ( ! empty( $asset['types'] ) && is_array( $asset['types'] ) && ! in_array( $type, $asset['types'], true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Enqueue a single style asset.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed> $asset Asset configuration.
	 * @return void
	 */
	protected function enqueue_style_asset( array $asset ): void {
		$name       = (string) $asset['name'];
		$asset_file = $this->load_asset_file( $name );
		$handle     = $this->get_handle_prefix() . $name;

		wp_enqueue_style(
			$handle,
			$this->get_plugin_url() . $this->get_admin_subdir() . $this->get_css_subdir() . $this->get_asset_file_prefix() . $name . '.min.css',
			$this->filter_style_dependencies( $asset_file['dependencies'] ),
			$asset_file['version'],
			'all'
		);
	}

	/**
	 * Enqueue a single script asset.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed> $asset Asset configuration.
	 * @return void
	 */
	protected function enqueue_script_asset( array $asset ): void {
		$name         = (string) $asset['name'];
		$asset_file   = $this->load_asset_file( $name );
		$handle       = $this->get_handle_prefix() . $name;
		$dependencies = $asset_file['dependencies'];

		if ( ! empty( $asset['dependencies'] ) && is_array( $asset['dependencies'] ) ) {
			$dependencies = array_values( array_unique( array_merge( $dependencies, $asset['dependencies'] ) ) );
		}

		$this->maybe_enqueue_media( $asset );

		wp_register_script(
			$handle,
			$this->get_plugin_url() . $this->get_admin_subdir() . $this->get_js_subdir() . $this->get_asset_file_prefix() . $name . '.min.js',
			$dependencies,
			$asset_file['version'],
			array(
				'in_footer' => true,
			)
		);

		if ( ! empty( $asset['localization'] ) && is_array( $asset['localization'] ) ) {
			$global = ( ! empty( $asset['localization_global'] ) && is_string( $asset['localization_global'] ) ) ? $asset['localization_global'] : $this->get_localization_global();

			if ( '' !== $global ) {
				wp_localize_script( $handle, $global, $asset['localization'] );
			}
		}

		wp_enqueue_script( $handle );
	}

	/**
	 * Load the generated `.asset.php` file for the given asset name.
	 *
	 * Returns a safe fallback when the file is missing so callers can rely on
	 * the `dependencies` and `version` keys.
	 *
	 * @since  1.0.0
	 * @param  string  $name Asset name.
	 * @return array{dependencies: string[], version: string}
	 */
	protected function load_asset_file( string $name ): array {
		$path = $this->get_plugin_path() . $this->get_admin_subdir() . $this->get_js_subdir() . $this->get_asset_file_prefix() . $name . '.min.asset.php';

		if ( file_exists( $path ) ) {
			$asset_file = include $path;

			if ( is_array( $asset_file ) ) {
				return array(
					'dependencies' => ( isset( $asset_file['dependencies'] ) && is_array( $asset_file['dependencies'] ) ) ? $asset_file['dependencies'] : array(),
					'version'      => ( isset( $asset_file['version'] ) && is_string( $asset_file['version'] ) ) ? $asset_file['version'] : $this->get_plugin_version(),
				);
			}
		}

		return array(
			'dependencies' => array(),
			'version'      => $this->get_plugin_version(),
		);
	}

	/**
	 * Filter a raw dependency array down to the supported WP style handles.
	 *
	 * @since  1.0.0
	 * @param  string[] $dependencies Raw dependencies.
	 * @return string[]
	 */
	protected function filter_style_dependencies( array $dependencies ): array {
		$allowed      = $this->get_wp_style_dependencies();
		$dependencies = array_values(
			array_filter(
				$dependencies,
				static function ( $dependency ) use ( $allowed ): bool {
					return is_string( $dependency ) && in_array( $dependency, $allowed, true );
				}
			)
		);

		return $dependencies;
	}

	/**
	 * Conditionally enqueue the WP media library for the given asset.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed> $asset Asset configuration.
	 * @return void
	 */
	protected function maybe_enqueue_media( array $asset ): void {
		if ( $this->media_enqueued ) {
			return;
		}

		if ( empty( $asset['enqueue_media'] ) ) {
			return;
		}

		wp_enqueue_media();

		$this->media_enqueued = true;
	}
}
