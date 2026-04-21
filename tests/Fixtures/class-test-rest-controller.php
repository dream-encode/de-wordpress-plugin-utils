<?php
/**
 * Test REST Controller fixture.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

namespace Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures;

use Dream_Encode\WordPress_Plugin_Utils\Abstracts\Abstract_REST_Controller;

/**
 * Concrete implementation of Abstract_REST_Controller for testing.
 */
class Test_REST_Controller extends Abstract_REST_Controller {

	/**
	 * The current namespace.
	 *
	 * @var string
	 */
	public $namespace = 'test-api/v1';

	/**
	 * The current rest_base.
	 *
	 * @var string
	 */
	public $rest_base = 'test-items';

	/**
	 * Array of routes.
	 *
	 * @var array<int|string, mixed>
	 */
	public array $routes = array();

	/**
	 * Admin permission result.
	 *
	 * @var bool
	 */
	private bool $admin_permission = true;

	/**
	 * User permission result.
	 *
	 * @var bool
	 */
	private bool $user_permission = true;

	/**
	 * Set admin permission for testing.
	 *
	 * @param bool $permission Permission.
	 */
	public function set_admin_permission( bool $permission ): void {
		$this->admin_permission = $permission;
	}

	/**
	 * Set user permission for testing.
	 *
	 * @param bool $permission Permission.
	 */
	public function set_user_permission( bool $permission ): void {
		$this->user_permission = $permission;
	}

	/**
	 * {@inheritdoc}
	 */
	public function check_admin_permission(): bool {
		return $this->admin_permission;
	}

	/**
	 * {@inheritdoc}
	 */
	public function check_user_permission(): bool {
		return $this->user_permission;
	}

	/**
	 * Sample GET endpoint callback.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return array<string, mixed>
	 */
	public function get_items( $request ) {
		return array( 'items' => array() );
	}

	/**
	 * Sample POST endpoint callback.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return array<string, mixed>
	 */
	public function create_item( $request ) {
		return array( 'created' => true );
	}
}

