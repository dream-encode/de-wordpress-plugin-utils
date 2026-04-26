<?php
/**
 * Abstract plugin orchestrator.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils\Abstracts
 * @since   1.0.0
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Abstracts;

use Dream_Encode\WordPress_Plugin_Utils\Loader\Plugin_Loader;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract plugin orchestrator.
 *
 * Defines the common bootstrap lifecycle shared by the main plugin class in each
 * Dream Encode WordPress plugin: set plugin name and version, load dependencies,
 * set the locale, register admin/public hooks, and expose `run()`.
 *
 * @since 1.0.0
 */
abstract class Abstract_Plugin {

	/**
	 * Hook loader.
	 *
	 * @var Plugin_Loader
	 */
	protected Plugin_Loader $loader;

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	protected string $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	protected string $version;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->plugin_name = $this->get_plugin_slug();
		$this->version     = $this->resolve_version();

		$this->load_dependencies();
		$this->set_locale();
		$this->define_hooks();
	}

	/**
	 * Get the plugin slug.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	abstract protected function get_plugin_slug(): string;

	/**
	 * Get the name of the version constant for this plugin.
	 *
	 * Return an empty string to always fall back to `get_default_version()`.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	abstract protected function get_version_constant(): string;

	/**
	 * Create the hook loader instance.
	 *
	 * @since  1.0.0
	 * @return Plugin_Loader
	 */
	protected function create_loader(): Plugin_Loader {
		return new Plugin_Loader();
	}

	/**
	 * Default plugin version when no constant is defined.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	protected function get_default_version(): string {
		return '1.0.0';
	}

	/**
	 * Resolve the plugin version from the version constant.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	protected function resolve_version(): string {
		$constant = $this->get_version_constant();

		if ( '' !== $constant && defined( $constant ) ) {
			return (string) constant( $constant );
		}

		return $this->get_default_version();
	}

	/**
	 * Load plugin dependencies and create the loader.
	 *
	 * Override to require additional files before the loader is created. When
	 * overriding, either call `parent::load_dependencies()` or assign
	 * `$this->loader` manually.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function load_dependencies(): void {
		$this->loader = $this->create_loader();
	}

	/**
	 * Register internationalization hooks.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function set_locale(): void {
	}

	/**
	 * Register all plugin hooks.
	 *
	 * Calls `define_admin_hooks()` and `define_public_hooks()` by default.
	 * Override to add additional registration steps such as tables or CLI commands.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function define_hooks(): void {
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Register admin-area hooks.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function define_admin_hooks(): void {
	}

	/**
	 * Register public-facing hooks.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function define_public_hooks(): void {
	}

	/**
	 * Run the loader to execute all registered hooks.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function run(): void {
		$this->loader->run();
	}

	/**
	 * Get the plugin slug.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_plugin_name(): string {
		return $this->plugin_name;
	}

	/**
	 * Get the plugin version.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_version(): string {
		return $this->version;
	}

	/**
	 * Get the hook loader.
	 *
	 * @since  1.0.0
	 * @return Plugin_Loader
	 */
	public function get_loader(): Plugin_Loader {
		return $this->loader;
	}
}
