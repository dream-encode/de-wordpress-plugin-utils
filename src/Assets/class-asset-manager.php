<?php
/**
 * Asset manager.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils\Assets
 * @since   [NEXT_VERSION]
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Assets;

use WP_Screen;

defined( 'ABSPATH' ) || exit;

/**
 * Screen-based asset enqueuer.
 *
 * Manages registration, dependency resolution, and localization for both
 * admin and front-end assets. Configured entirely via constructor — no
 * subclassing required for common usage. Extend only when overriding
 * internal behavior (e.g. stubbing load_asset_file in tests).
 *
 * Expected shape of each asset definition array:
 *   - name (string, required) — handle suffix and base filename.
 *   - dependencies (string[], optional) — extra deps merged with .asset.php file.
 *   - types (string[], optional) — subset of { 'style', 'script' }.
 *   - localization (array<string, mixed>, optional) — inline wp_localize_script data.
 *   - localization_global (string, optional) — per-asset JS global override.
 *   - localized (bool, optional) — apply this screen's screens_localization_data entry.
 *   - conditions (bool, optional) — skip the asset when false.
 *   - enqueue_media (bool, optional) — call wp_enqueue_media() before the script.
 *
 * @since [NEXT_VERSION]
 */
class Asset_Manager {

	/**
	 * Whether wp_enqueue_media() has been invoked during the current request.
	 *
	 * @var bool
	 */
	protected bool $media_enqueued = false;

	/**
	 * Handle prefix for all registered scripts and styles.
	 *
	 * @var string
	 */
	protected string $handle_prefix;

	/**
	 * Absolute plugin base path (trailing-slash included).
	 *
	 * @var string
	 */
	protected string $plugin_path;

	/**
	 * Plugin base URL (trailing-slash included).
	 *
	 * @var string
	 */
	protected string $plugin_url;

	/**
	 * Plugin version used as a fallback when an asset file is missing.
	 *
	 * @var string
	 */
	protected string $plugin_version;

	/**
	 * Per-screen asset map, keyed by WP screen ID.
	 *
	 * @var array<string, array<int, array<string, mixed>>>
	 */
	protected array $screens_to_assets;

	/**
	 * Default JS global for wp_localize_script.
	 *
	 * @var string
	 */
	protected string $localization_global;

	/**
	 * Per-screen localization data map, keyed by WP screen ID.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	protected array $screens_localization_data;

	/**
	 * Subdirectory between the plugin base and the js/css dist subdirs.
	 * Pass 'admin/' for admin-only assets.
	 *
	 * @var string
	 */
	protected string $asset_subdir;

	/**
	 * JS distribution subdirectory.
	 *
	 * @var string
	 */
	protected string $js_subdir;

	/**
	 * CSS distribution subdirectory.
	 *
	 * @var string
	 */
	protected string $css_subdir;

	/**
	 * Prefix prepended to asset filenames.
	 * Pass 'admin-' for standard admin asset naming.
	 *
	 * @var string
	 */
	protected string $asset_file_prefix;

	/**
	 * WP-provided style handles retained when filtering script asset dependencies.
	 *
	 * @var string[]
	 */
	protected array $wp_style_dependencies;

	/**
	 * Constructor.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  string    $handle_prefix             Handle prefix for all assets.
	 * @param  string    $plugin_path               Absolute plugin base path.
	 * @param  string    $plugin_url                Plugin base URL.
	 * @param  string    $plugin_version            Plugin version string.
	 * @param  array<string, array<int, array<string, mixed>>>  $screens_to_assets         Optional. Screen-to-assets map.
	 * @param  string    $localization_global       Optional. Default JS global.
	 * @param  array<string, array<string, mixed>>  $screens_localization_data  Optional. Screen localization map.
	 * @param  string    $asset_subdir              Optional. Subdir between plugin root and dist dirs. Default ''.
	 * @param  string    $js_subdir                 Optional. JS dist subdir. Default 'assets/dist/js/'.
	 * @param  string    $css_subdir                Optional. CSS dist subdir. Default 'assets/dist/css/'.
	 * @param  string    $asset_file_prefix         Optional. Filename prefix. Default ''.
	 * @param  string[]  $wp_style_dependencies     Optional. Allowed WP style deps. Default ['wp-components'].
	 */
	public function __construct(
		string $handle_prefix,
		string $plugin_path,
		string $plugin_url,
		string $plugin_version,
		array $screens_to_assets = array(),
		string $localization_global = '',
		array $screens_localization_data = array(),
		string $asset_subdir = '',
		string $js_subdir = 'assets/dist/js/',
		string $css_subdir = 'assets/dist/css/',
		string $asset_file_prefix = '',
		array $wp_style_dependencies = array( 'wp-components' ),
	) {
		$this->handle_prefix             = $handle_prefix;
		$this->plugin_path               = $plugin_path;
		$this->plugin_url                = $plugin_url;
		$this->plugin_version            = $plugin_version;
		$this->screens_to_assets         = $screens_to_assets;
		$this->localization_global       = $localization_global;
		$this->screens_localization_data = $screens_localization_data;
		$this->asset_subdir              = $asset_subdir;
		$this->js_subdir                 = $js_subdir;
		$this->css_subdir                = $css_subdir;
		$this->asset_file_prefix         = $asset_file_prefix;
		$this->wp_style_dependencies     = $wp_style_dependencies;
	}

	/**
	 * Merge additional screen-to-asset entries into the existing map.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  array<string, array<int, array<string, mixed>>>  $screens  Entries to add.
	 * @return void
	 */
	public function add_screens( array $screens ): void {
		$this->screens_to_assets = array_merge( $this->screens_to_assets, $screens );
	}

	/**
	 * Merge additional screen localization entries into the existing map.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  array<string, array<string, mixed>>  $data  Entries to add.
	 * @return void
	 */
	public function add_screens_localization_data( array $data ): void {
		$this->screens_localization_data = array_merge( $this->screens_localization_data, $data );
	}

	/**
	 * Get the localization data for a specific screen.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  WP_Screen  $screen  Screen to look up.
	 * @return array<string, mixed>
	 */
	public function screen_get_localized_data( WP_Screen $screen ): array {
		$data = $this->screens_localization_data;

		return ! empty( $data[ $screen->id ] ) && is_array( $data[ $screen->id ] ) ? $data[ $screen->id ] : array();
	}

	/**
	 * Enqueue all styles registered for the current screen.
	 *
	 * @since  [NEXT_VERSION]
	 * @return void
	 */
	public function enqueue_styles(): void {
		$this->enqueue_for_current_screen( 'style' );
	}

	/**
	 * Enqueue all scripts registered for the current screen.
	 *
	 * @since  [NEXT_VERSION]
	 * @return void
	 */
	public function enqueue_scripts(): void {
		$this->enqueue_for_current_screen( 'script' );
	}

	/**
	 * Get the asset definitions registered for the current screen.
	 *
	 * @since  [NEXT_VERSION]
	 * @return array<int, array<string, mixed>>
	 */
	public function current_screen_assets(): array {
		$current_screen = get_current_screen();

		if ( ! $current_screen instanceof WP_Screen ) {
			return array();
		}

		return $this->get_assets_for_screen( $current_screen );
	}

	/**
	 * Get the number of asset definitions registered for the current screen.
	 *
	 * @since  [NEXT_VERSION]
	 * @return int
	 */
	public function current_screen_has_assets(): int {
		return count( $this->current_screen_assets() );
	}

	/**
	 * Get the asset definitions registered for a given screen.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  WP_Screen  $screen  Screen to check.
	 * @return array<int, array<string, mixed>>
	 */
	public function screen_assets( WP_Screen $screen ): array {
		return $this->get_assets_for_screen( $screen );
	}

	/**
	 * Get the number of asset definitions registered for a given screen.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  WP_Screen  $screen  Screen to check.
	 * @return int
	 */
	public function screen_has_assets( WP_Screen $screen ): int {
		return count( $this->get_assets_for_screen( $screen ) );
	}

	/**
	 * Enqueue the assets of the given type for the current screen.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  string  $type  Either 'style' or 'script'.
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
	 * @since  [NEXT_VERSION]
	 * @param  WP_Screen  $screen  Screen to look up.
	 * @return array<int, array<string, mixed>>
	 */
	protected function get_assets_for_screen( WP_Screen $screen ): array {
		if ( empty( $this->screens_to_assets[ $screen->id ] ) ) {
			return array();
		}

		return $this->screens_to_assets[ $screen->id ];
	}


	/**
	 * Determine whether an asset should be enqueued for the given type.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  array<string, mixed>  $asset  Asset configuration.
	 * @param  string               $type   Either 'style' or 'script'.
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
	 * @since  [NEXT_VERSION]
	 * @param  array<string, mixed>  $asset  Asset configuration.
	 * @return void
	 */
	protected function enqueue_style_asset( array $asset ): void {
		$name       = (string) $asset['name'];
		$asset_file = $this->load_asset_file( $name );
		$handle     = $this->handle_prefix . $name;

		wp_enqueue_style(
			$handle,
			$this->plugin_url . $this->asset_subdir . $this->css_subdir . $this->asset_file_prefix . $name . '.min.css',
			$this->filter_style_dependencies( $asset_file['dependencies'] ),
			$asset_file['version'],
			'all'
		);
	}

	/**
	 * Enqueue a single script asset.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  array<string, mixed>  $asset  Asset configuration.
	 * @return void
	 */
	protected function enqueue_script_asset( array $asset ): void {
		$name         = (string) $asset['name'];
		$asset_file   = $this->load_asset_file( $name );
		$handle       = $this->handle_prefix . $name;
		$dependencies = $asset_file['dependencies'];

		if ( ! empty( $asset['dependencies'] ) && is_array( $asset['dependencies'] ) ) {
			$dependencies = array_values( array_unique( array_merge( $dependencies, $asset['dependencies'] ) ) );
		}

		$this->maybe_enqueue_media( $asset );

		wp_register_script(
			$handle,
			$this->plugin_url . $this->asset_subdir . $this->js_subdir . $this->asset_file_prefix . $name . '.min.js',
			$dependencies,
			$asset_file['version'],
			array( 'in_footer' => true )
		);

		$global = ( ! empty( $asset['localization_global'] ) && is_string( $asset['localization_global'] ) )
			? $asset['localization_global']
			: $this->localization_global;

		if ( ! empty( $asset['localization'] ) && is_array( $asset['localization'] ) && '' !== $global ) {
			wp_localize_script( $handle, $global, $asset['localization'] );
		}

		if ( ! empty( $asset['localized'] ) && '' !== $global ) {
			$current_screen = get_current_screen();

			if ( $current_screen instanceof WP_Screen ) {
				$screen_data = $this->screen_get_localized_data( $current_screen );

				if ( ! empty( $screen_data ) ) {
					wp_localize_script( $handle, $global, $screen_data );
				}
			}
		}

		wp_enqueue_script( $handle );
	}

	/**
	 * Load the generated .asset.php file for the given asset name.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  string  $name  Asset name.
	 * @return array{dependencies: string[], version: string}
	 */
	protected function load_asset_file( string $name ): array {
		$path = $this->plugin_path . $this->asset_subdir . $this->js_subdir . $this->asset_file_prefix . $name . '.min.asset.php';

		if ( file_exists( $path ) ) {
			$asset_file = include $path;

			if ( is_array( $asset_file ) ) {
				return array(
					'dependencies' => ( isset( $asset_file['dependencies'] ) && is_array( $asset_file['dependencies'] ) ) ? $asset_file['dependencies'] : array(),
					'version'      => ( isset( $asset_file['version'] ) && is_string( $asset_file['version'] ) ) ? $asset_file['version'] : $this->plugin_version,
				);
			}
		}

		return array(
			'dependencies' => array(),
			'version'      => $this->plugin_version,
		);
	}

	/**
	 * Filter a raw dependency array down to the supported WP style handles.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  string[]  $dependencies  Raw dependencies.
	 * @return string[]
	 */
	protected function filter_style_dependencies( array $dependencies ): array {
		$allowed = $this->wp_style_dependencies;

		return array_values(
			array_filter(
				$dependencies,
				static function ( $dependency ) use ( $allowed ): bool {
					return is_string( $dependency ) && in_array( $dependency, $allowed, true );
				}
			)
		);
	}

	/**
	 * Conditionally enqueue the WP media library for the given asset.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  array<string, mixed>  $asset  Asset configuration.
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
