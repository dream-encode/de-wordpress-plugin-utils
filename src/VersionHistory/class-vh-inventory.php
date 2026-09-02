<?php
/**
 * Version History inventory reader.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils\VersionHistory
 * @since   [NEXT_VERSION]
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\VersionHistory;

defined( 'ABSPATH' ) || exit;

/**
 * Version History inventory reader.
 *
 * Reads the observable software state of the site through WordPress APIs and
 * normalises every component into a single shape. Nothing here writes.
 *
 * A component array is:
 *
 *     array(
 *         'component_type' => 'core|plugin|mu-plugin|dropin|theme',
 *         'component_file' => 'woocommerce/woocommerce.php',
 *         'component_slug' => 'woocommerce',
 *         'component_name' => 'WooCommerce',
 *         'version'        => '10.1.2',
 *         'status'         => 'active|inactive|network-active|parent',
 *         'metadata'       => array(),
 *     )
 *
 * @since [NEXT_VERSION]
 */
class VH_Inventory {

	/**
	 * Get the complete current inventory, keyed by type and file.
	 *
	 * @since  [NEXT_VERSION]
	 * @return array<string, array<string, mixed>>
	 */
	public static function snapshot(): array {
		$components = array_merge(
			self::core(),
			self::plugins(),
			self::mu_plugins(),
			self::dropins(),
			self::themes()
		);

		$keyed = array();

		foreach ( $components as $component ) {
			$keyed[ self::key( $component['component_type'], $component['component_file'] ) ] = $component;
		}

		return $keyed;
	}

	/**
	 * Build the map key for a component.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  string  $type  Component type.
	 * @param  string  $file  Component file.
	 * @return string
	 */
	public static function key( string $type, string $file ): string {
		return $type . ':' . $file;
	}

	/**
	 * Get WordPress core as a single component.
	 *
	 * @since  [NEXT_VERSION]
	 * @return array<int, array<string, mixed>>
	 */
	public static function core(): array {
		global $wp_version;

		return array(
			array(
				'component_type' => 'core',
				'component_file' => '',
				'component_slug' => 'wordpress',
				'component_name' => 'WordPress',
				'version'        => (string) $wp_version,
				'status'         => 'active',
				'metadata'       => array(),
			),
		);
	}

	/**
	 * Get every installed plugin.
	 *
	 * @since  [NEXT_VERSION]
	 * @return array<int, array<string, mixed>>
	 */
	public static function plugins(): array {
		self::load_plugin_api();

		$components = array();

		foreach ( get_plugins() as $file => $data ) {
			$components[] = array(
				'component_type' => 'plugin',
				'component_file' => $file,
				'component_slug' => self::slug_from_file( $file ),
				'component_name' => (string) $data['Name'],
				'version'        => self::clean_version( $data['Version'] ?? '' ),
				'status'         => self::plugin_status( $file ),
				'metadata'       => array(),
			);
		}

		return $components;
	}

	/**
	 * Get every must-use plugin.
	 *
	 * Must-use plugins are always loaded, so their status is always active.
	 *
	 * @since  [NEXT_VERSION]
	 * @return array<int, array<string, mixed>>
	 */
	public static function mu_plugins(): array {
		self::load_plugin_api();

		$components = array();

		foreach ( get_mu_plugins() as $file => $data ) {
			$components[] = array(
				'component_type' => 'mu-plugin',
				'component_file' => $file,
				'component_slug' => self::slug_from_file( $file ),
				'component_name' => (string) $data['Name'],
				'version'        => self::clean_version( $data['Version'] ?? '' ),
				'status'         => 'active',
				'metadata'       => array(),
			);
		}

		return $components;
	}

	/**
	 * Get every installed drop-in.
	 *
	 * Drop-ins rarely carry a version header, so a short content hash and the
	 * file mtime stand in for one. A hash change is what makes a drop-in look
	 * like a version change during reconciliation.
	 *
	 * @since  [NEXT_VERSION]
	 * @return array<int, array<string, mixed>>
	 */
	public static function dropins(): array {
		self::load_plugin_api();

		$components = array();

		foreach ( get_dropins() as $file => $data ) {
			$path = WP_CONTENT_DIR . '/' . $file;

			$metadata = array();

			if ( is_readable( $path ) ) {
				$contents = file_get_contents( $path );

				if ( false !== $contents ) {
					$metadata['content_hash'] = substr( sha1( $contents ), 0, 12 );
				}

				$mtime = filemtime( $path );

				if ( false !== $mtime ) {
					$metadata['mtime_gmt'] = gmdate( 'Y-m-d H:i:s', $mtime );
				}
			}

			$components[] = array(
				'component_type' => 'dropin',
				'component_file' => $file,
				'component_slug' => self::slug_from_file( $file ),
				'component_name' => (string) $data['Name'],
				'version'        => self::clean_version( $data['Version'] ?? '' ),
				'status'         => 'active',
				'metadata'       => $metadata,
			);
		}

		return $components;
	}

	/**
	 * Get every installed theme.
	 *
	 * @since  [NEXT_VERSION]
	 * @return array<int, array<string, mixed>>
	 */
	public static function themes(): array {
		$stylesheet = get_stylesheet();
		$template   = get_template();

		$components = array();

		foreach ( wp_get_themes() as $slug => $theme ) {
			$status = 'inactive';

			if ( $slug === $stylesheet ) {
				$status = 'active';
			} elseif ( $slug === $template ) {
				$status = 'parent';
			}

			$components[] = array(
				'component_type' => 'theme',
				'component_file' => (string) $slug,
				'component_slug' => (string) $slug,
				'component_name' => (string) $theme->get( 'Name' ),
				'version'        => self::clean_version( (string) $theme->get( 'Version' ) ),
				'status'         => $status,
				'metadata'       => array(),
			);
		}

		return $components;
	}

	/**
	 * Determine the recorded status of a plugin.
	 *
	 * A network-active plugin must never read as inactive on an individual site.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  string  $file  Plugin basename.
	 * @return string
	 */
	public static function plugin_status( string $file ): string {
		self::load_plugin_api();

		if ( is_multisite() && is_plugin_active_for_network( $file ) ) {
			return 'network-active';
		}

		if ( is_plugin_active( $file ) ) {
			return 'active';
		}

		return 'inactive';
	}

	/**
	 * Derive a directory slug from a plugin or drop-in file path.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  string  $file  File path relative to its container directory.
	 * @return string
	 */
	public static function slug_from_file( string $file ): string {
		$directory = dirname( $file );

		if ( '.' !== $directory && '' !== $directory ) {
			return $directory;
		}

		return basename( $file, '.php' );
	}

	/**
	 * Normalise a version string without interpreting it.
	 *
	 * Version values are stored exactly as reported. Nothing here assumes they
	 * are valid semantic versions.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  string  $version  Raw version string.
	 * @return string|null
	 */
	public static function clean_version( string $version ): ?string {
		$version = trim( $version );

		if ( '' === $version ) {
			return null;
		}

		return substr( $version, 0, 100 );
	}

	/**
	 * Load the admin plugin API on front-end and cron requests.
	 *
	 * @since  [NEXT_VERSION]
	 * @return void
	 */
	private static function load_plugin_api(): void {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}
}
