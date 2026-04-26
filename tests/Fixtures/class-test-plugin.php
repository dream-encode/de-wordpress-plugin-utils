<?php
/**
 * Test plugin orchestrator fixture.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures;

use Dream_Encode\WordPress_Plugin_Utils\Abstracts\Abstract_Plugin;

/**
 * Concrete implementation of Abstract_Plugin for testing.
 */
class Test_Plugin extends Abstract_Plugin {

	/**
	 * Calls captured in lifecycle order.
	 *
	 * @var array<int, string>
	 */
	public array $calls = array();

	/**
	 * Slug returned from get_plugin_slug().
	 *
	 * @var string
	 */
	public string $slug = 'test-plugin';

	/**
	 * Version constant name to resolve.
	 *
	 * @var string
	 */
	public string $version_constant = '';

	/**
	 * Default version fallback.
	 *
	 * @var string
	 */
	public string $default_version = '1.0.0';

	protected function get_plugin_slug(): string {
		return $this->slug;
	}

	protected function get_version_constant(): string {
		return $this->version_constant;
	}

	protected function get_default_version(): string {
		return $this->default_version;
	}

	protected function load_dependencies(): void {
		$this->calls[] = 'load_dependencies';

		parent::load_dependencies();
	}

	protected function set_locale(): void {
		$this->calls[] = 'set_locale';
	}

	protected function define_admin_hooks(): void {
		$this->calls[] = 'define_admin_hooks';
	}

	protected function define_public_hooks(): void {
		$this->calls[] = 'define_public_hooks';
	}
}
