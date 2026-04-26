<?php
/**
 * Tests for Abstract_REST_Controller.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Tests;

use Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures\Test_REST_Controller;
use WP_REST_Response;
use WP_UnitTestCase;

/**
 * Test case for Abstract_REST_Controller.
 */
class AbstractRESTControllerTest extends WP_UnitTestCase {

	/**
	 * Test controller instance.
	 *
	 * @var Test_REST_Controller
	 */
	private Test_REST_Controller $controller;

	/**
	 * Set up before each test.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->controller = new Test_REST_Controller();
	}

	/**
	 * Test that controller has namespace property.
	 */
	public function test_controller_has_namespace(): void {
		$this->assertSame( 'test-api/v1', $this->controller->namespace );
	}

	/**
	 * Test that controller has rest_base property.
	 */
	public function test_controller_has_rest_base(): void {
		$this->assertSame( 'test-items', $this->controller->rest_base );
	}

	/**
	 * Test that controller has empty routes by default.
	 */
	public function test_controller_has_empty_routes(): void {
		$this->assertIsArray( $this->controller->routes );
		$this->assertEmpty( $this->controller->routes );
	}

	/**
	 * Test that register_routes does nothing with empty routes.
	 */
	public function test_register_routes_handles_empty_routes(): void {
		$this->controller->register_routes();

		$this->assertTrue( true );
	}

	/**
	 * Test that register_routes registers numeric key routes.
	 */
	public function test_register_routes_registers_numeric_routes(): void {
		$this->controller->routes = array(
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this->controller, 'get_items' ),
					'permission_callback' => '__return_true'
				),
			),
		);

		add_action(
			'rest_api_init',
			array( $this->controller, 'register_routes' )
		);

		do_action( 'rest_api_init' );

		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/test-api/v1/test-items', $routes );
	}

	/**
	 * Test that register_routes registers named routes.
	 */
	public function test_register_routes_registers_named_routes(): void {
		$this->controller->routes = array(
			'(?P<id>[\d]+)' => array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this->controller, 'get_items' ),
					'permission_callback' => '__return_true'
				),
			),
		);

		add_action(
			'rest_api_init',
			array( $this->controller, 'register_routes' )
		);

		do_action( 'rest_api_init' );

		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/test-api/v1/test-items/(?P<id>[\d]+)', $routes );
	}

	/**
	 * Test that register_routes handles override flag.
	 */
	public function test_register_routes_handles_override_flag(): void {
		$this->controller->routes = array(
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this->controller, 'get_items' ),
					'permission_callback' => '__return_true'
				),
				true,
			),
		);

		add_action(
			'rest_api_init',
			array( $this->controller, 'register_routes' )
		);

		do_action( 'rest_api_init' );

		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/test-api/v1/test-items', $routes );
	}

	/**
	 * Test that check_admin_permission returns expected value.
	 */
	public function test_check_admin_permission_returns_true_by_default(): void {
		$this->assertTrue( $this->controller->check_admin_permission() );
	}

	/**
	 * Test that check_admin_permission can be set to false.
	 */
	public function test_check_admin_permission_can_be_false(): void {
		$this->controller->set_admin_permission( false );

		$this->assertFalse( $this->controller->check_admin_permission() );
	}

	/**
	 * Test that check_user_permission returns expected value.
	 */
	public function test_check_user_permission_returns_true_by_default(): void {
		$this->assertTrue( $this->controller->check_user_permission() );
	}

	/**
	 * Test that check_user_permission can be set to false.
	 */
	public function test_check_user_permission_can_be_false(): void {
		$this->controller->set_user_permission( false );

		$this->assertFalse( $this->controller->check_user_permission() );
	}

	/**
	 * Test that ensure_response returns WP_REST_Response.
	 */
	public function test_ensure_response_returns_wp_rest_response(): void {
		$data = array( 'test' => 'data' );

		$response = $this->controller->ensure_response( $data );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
	}

	/**
	 * Test that ensure_response preserves data.
	 */
	public function test_ensure_response_preserves_data(): void {
		$data = array( 'test' => 'data' );

		$response = $this->controller->ensure_response( $data );

		$this->assertSame( $data, $response->get_data() );
	}
}

