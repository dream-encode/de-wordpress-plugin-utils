<?php
/**
 * Test admin asset manager fixture.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

namespace Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures;

use Dream_Encode\WordPress_Plugin_Utils\Abstracts\Abstract_Admin_Asset_Manager;

/**
 * Concrete asset manager used to exercise the abstract enqueue flow.
 */
class Test_Admin_Asset_Manager extends Abstract_Admin_Asset_Manager {

	/**
	 * Screen-to-assets map configured by the test.
	 *
	 * @var array<string, array<int, array<string, mixed>>>
	 */
	public array $screens_to_assets = array();

	/**
	 * Base plugin path returned by `get_plugin_path()`.
	 *
	 * @var string
	 */
	public string $plugin_path = '/tmp/test-plugin/';

	/**
	 * Base plugin URL returned by `get_plugin_url()`.
	 *
	 * @var string
	 */
	public string $plugin_url = 'https://example.com/wp-content/plugins/test-plugin/';

	/**
	 * Plugin version returned by `get_plugin_version()`.
	 *
	 * @var string
	 */
	public string $plugin_version = '1.0.0';

	/**
	 * Handle prefix returned by `get_handle_prefix()`.
	 *
	 * @var string
	 */
	public string $handle_prefix = 'test-plugin-admin-';

	/**
	 * Localization global returned by `get_localization_global()`.
	 *
	 * @var string
	 */
	public string $localization_global = '';

	/**
	 * In-memory asset file map keyed by asset name.
	 *
	 * @var array<string, array{dependencies: string[], version: string}>
	 */
	public array $asset_files = array();

	protected function get_handle_prefix(): string {
		return $this->handle_prefix;
	}

	protected function get_plugin_path(): string {
		return $this->plugin_path;
	}

	protected function get_plugin_url(): string {
		return $this->plugin_url;
	}

	protected function get_plugin_version(): string {
		return $this->plugin_version;
	}

	protected function get_screens_to_assets(): array {
		return $this->screens_to_assets;
	}

	protected function get_localization_global(): string {
		return $this->localization_global;
	}

	/**
	 * Override to return in-memory asset file data instead of reading from disk.
	 *
	 * @param  string  $name Asset name.
	 * @return array{dependencies: string[], version: string}
	 */
	protected function load_asset_file( string $name ): array {
		if ( isset( $this->asset_files[ $name ] ) ) {
			return $this->asset_files[ $name ];
		}

		return array(
			'dependencies' => array(),
			'version'      => $this->get_plugin_version(),
		);
	}
}
