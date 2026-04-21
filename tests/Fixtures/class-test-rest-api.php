<?php
/**
 * Test REST API fixture.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

namespace Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures;

use Dream_Encode\WordPress_Plugin_Utils\Abstracts\Abstract_REST_API;

/**
 * Concrete implementation of Abstract_REST_API for testing.
 */
class Test_REST_API extends Abstract_REST_API {

	/**
	 * The current version.
	 *
	 * @var string
	 */
	public string $version = 'v1';

	/**
	 * The current endpoint.
	 *
	 * @var string
	 */
	public string $endpoint = 'test-api';

	/**
	 * Controllers to load.
	 *
	 * @var array<string, mixed>
	 */
	public array $controllers = array();

	/**
	 * Track if rest_api_includes was called.
	 *
	 * @var bool
	 */
	public bool $includes_called = false;

	/**
	 * {@inheritdoc}
	 */
	protected function get_controller_namespace(): string {
		return 'Dream_Encode\\WordPress_Plugin_Utils\\Tests\\Fixtures\\';
	}

	/**
	 * {@inheritdoc}
	 */
	public function rest_api_includes(): void {
		$this->includes_called = true;
	}
}

