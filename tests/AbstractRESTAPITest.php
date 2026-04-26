<?php
/**
 * Tests for Abstract_REST_API.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Tests;

use Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures\Test_REST_API;
use Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures\Test_REST_Controller;
use WP_UnitTestCase;

/**
 * Test case for Abstract_REST_API.
 */
class AbstractRESTAPITest extends WP_UnitTestCase {

	/**
	 * Test that REST API has default version.
	 */
	public function test_rest_api_has_default_version(): void {
		$api = new Test_REST_API();

		$this->assertSame( 'v1', $api->version );
	}

	/**
	 * Test that REST API has endpoint property.
	 */
	public function test_rest_api_has_endpoint(): void {
		$api = new Test_REST_API();

		$this->assertSame( 'test-api', $api->endpoint );
	}

	/**
	 * Test that REST API has empty controllers by default.
	 */
	public function test_rest_api_has_empty_controllers(): void {
		$api = new Test_REST_API();

		$this->assertIsArray( $api->controllers );
		$this->assertEmpty( $api->controllers );
	}

	/**
	 * Test that rest_api_includes is called during init.
	 */
	public function test_rest_api_includes_is_called(): void {
		$api = new Test_REST_API();

		$this->assertTrue( $api->includes_called );
	}

	/**
	 * Test that rest_api_init hooks rest_api_register_routes.
	 */
	public function test_rest_api_init_hooks_register_routes(): void {
		$api = new Test_REST_API();

		$this->assertNotFalse(
			has_action( 'rest_api_init', array( $api, 'rest_api_register_routes' ) )
		);
	}

	/**
	 * Test that rest_api_register_routes does nothing with empty controllers.
	 */
	public function test_rest_api_register_routes_handles_empty_controllers(): void {
		$api = new Test_REST_API();

		do_action( 'rest_api_init' );

		$this->assertEmpty( $api->controllers );
	}

	/**
	 * Test that rest_api_register_routes instantiates string controllers.
	 */
	public function test_rest_api_register_routes_instantiates_string_controllers(): void {
		$api = new Test_REST_API();
		$api->controllers = array( 'Test_REST_Controller' );

		do_action( 'rest_api_init' );

		$this->assertInstanceOf( Test_REST_Controller::class, $api->controllers['Test_REST_Controller'] );
	}

	/**
	 * Test that rest_api_register_routes uses existing controller instances.
	 */
	public function test_rest_api_register_routes_uses_existing_instances(): void {
		$api = new Test_REST_API();
		$controller = new Test_REST_Controller();
		$api->controllers = array( 'test' => $controller );

		$api->rest_api_register_routes();

		$this->assertSame( $controller, $api->controllers['test'] );
	}

	/**
	 * Test that rest_api_register_routes calls register_routes on controllers.
	 */
	public function test_rest_api_register_routes_calls_register_routes(): void {
		$api = new Test_REST_API();
		$controller = new Test_REST_Controller();
		$controller->routes = array(
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $controller, 'get_items' ),
					'permission_callback' => '__return_true',
				),
			),
		);
		$api->controllers = array( 'test' => $controller );

		do_action( 'rest_api_init' );

		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/test-api/v1/test-items', $routes );
	}
}

