<?php
/**
 * Test asset manager fixture.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures;

use Dream_Encode\WordPress_Plugin_Utils\Assets\Asset_Manager;

/**
 * Thin Asset_Manager extension that stubs load_asset_file to avoid disk I/O.
 */
class Test_Asset_Manager extends Asset_Manager {

	/**
	 * In-memory asset file map keyed by asset name.
	 *
	 * @var array<string, array{dependencies: string[], version: string}>
	 */
	public array $asset_files = array();

	/**
	 * Override to return in-memory asset file data instead of reading from disk.
	 *
	 * @param  string  $name  Asset name.
	 * @return array{dependencies: string[], version: string}
	 */
	protected function load_asset_file( string $name ): array {
		if ( isset( $this->asset_files[ $name ] ) ) {
			return $this->asset_files[ $name ];
		}

		return array(
			'dependencies' => array(),
			'version'      => $this->plugin_version,
		);
	}
}
