<?php
/**
 * Tests for REST_Authentication.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

namespace Dream_Encode\WordPress_Plugin_Utils\Tests;

use Dream_Encode\WordPress_Plugin_Utils\RestApi\REST_Authentication;
use WP_UnitTestCase;

/**
 * Test case for REST_Authentication.
 */
class RestAuthenticationTest extends WP_UnitTestCase {

	/**
	 * Saved REQUEST_URI value, restored after each test.
	 *
	 * @var string|null
	 */
	private ?string $saved_request_uri = null;

	/**
	 * Set up before each test.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->saved_request_uri = $_SERVER['REQUEST_URI'] ?? null;
	}

	/**
	 * Tear down after each test.
	 */
	public function tear_down(): void {
		if ( null === $this->saved_request_uri ) {
			unset( $_SERVER['REQUEST_URI'] );
		} else {
			$_SERVER['REQUEST_URI'] = $this->saved_request_uri;
		}

		wp_set_current_user( 0 );

		parent::tear_down();
	}

	// -----------------------------------------------------------------------
	// Static accessors
	// -----------------------------------------------------------------------

	/**
	 * Test that get_wp_rest_nonce() returns a string.
	 */
	public function test_get_wp_rest_nonce_returns_string(): void {
		$this->assertIsString( REST_Authentication::get_wp_rest_nonce() );
	}

	/**
	 * Test that get_wp_user_id() returns an int.
	 */
	public function test_get_wp_user_id_returns_int(): void {
		$this->assertIsInt( REST_Authentication::get_wp_user_id() );
	}

	// -----------------------------------------------------------------------
	// is_rest_api_request
	// -----------------------------------------------------------------------

	/**
	 * Test that is_rest_api_request() returns false when REQUEST_URI is not set.
	 */
	public function test_is_rest_api_request_false_when_no_uri(): void {
		unset( $_SERVER['REQUEST_URI'] );

		$auth = new REST_Authentication();

		$this->assertFalse( $auth->is_rest_api_request() );
	}

	/**
	 * Test that is_rest_api_request() returns true for a REST API URI.
	 */
	public function test_is_rest_api_request_true_for_rest_uri(): void {
		$_SERVER['REQUEST_URI'] = '/' . rest_get_url_prefix() . '/dream-encode/v1/test';

		$auth = new REST_Authentication();

		$this->assertTrue( $auth->is_rest_api_request() );
	}

	/**
	 * Test that is_rest_api_request() returns false for a non-REST URI.
	 */
	public function test_is_rest_api_request_false_for_non_rest_uri(): void {
		$_SERVER['REQUEST_URI'] = '/shop/my-product/';

		$auth = new REST_Authentication();

		$this->assertFalse( $auth->is_rest_api_request() );
	}

	// -----------------------------------------------------------------------
	// authenticate
	// -----------------------------------------------------------------------

	/**
	 * Test that authenticate() passes through a non-empty user ID without touching it.
	 */
	public function test_authenticate_returns_existing_user_id_unchanged(): void {
		$auth = new REST_Authentication();

		$this->assertSame( 5, $auth->authenticate( 5 ) );
	}

	/**
	 * Test that authenticate() returns false when not a REST request.
	 */
	public function test_authenticate_returns_false_outside_rest_context(): void {
		$_SERVER['REQUEST_URI'] = '/shop/';

		$auth = new REST_Authentication();

		$this->assertFalse( $auth->authenticate( false ) );
	}

	// -----------------------------------------------------------------------
	// authenticate_app_password
	// -----------------------------------------------------------------------

	/**
	 * Test that authenticate_app_password() returns false when no HTTP auth headers present.
	 */
	public function test_authenticate_app_password_false_without_headers(): void {
		unset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] );

		$auth = new REST_Authentication();

		$this->assertFalse( $auth->authenticate_app_password() );
	}

	// -----------------------------------------------------------------------
	// rest_cookie_check_errors
	// -----------------------------------------------------------------------

	/**
	 * Test that rest_cookie_check_errors() passes through a non-empty result unchanged.
	 */
	public function test_rest_cookie_check_errors_returns_existing_result(): void {
		$auth   = new REST_Authentication();
		$error  = new \WP_Error( 'test', 'Test error' );

		$this->assertSame( $error, $auth->rest_cookie_check_errors( $error ) );
	}

	// -----------------------------------------------------------------------
	// authentication_fallback
	// -----------------------------------------------------------------------

	/**
	 * Test that authentication_fallback() returns the error unchanged when a user is already set.
	 */
	public function test_authentication_fallback_passthrough_when_user_logged_in(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$auth  = new REST_Authentication();
		$error = new \WP_Error( 'test', 'Some error' );

		$this->assertSame( $error, $auth->authentication_fallback( $error ) );
	}

	// -----------------------------------------------------------------------
	// check_logged_in_permission
	// -----------------------------------------------------------------------

	/**
	 * Test that check_logged_in_permission() returns false when no stored user id.
	 */
	public function test_check_logged_in_permission_false_by_default(): void {
		$this->assertFalse( REST_Authentication::check_logged_in_permission() );
	}

	// -----------------------------------------------------------------------
	// check_admin_permission
	// -----------------------------------------------------------------------

	/**
	 * Test that check_admin_permission() returns false for a subscriber.
	 */
	public function test_check_admin_permission_false_for_subscriber(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$this->assertFalse( REST_Authentication::check_admin_permission() );
	}

	/**
	 * Test that check_admin_permission() returns true for an administrator.
	 */
	public function test_check_admin_permission_true_for_admin(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$this->assertTrue( REST_Authentication::check_admin_permission() );
	}

	// -----------------------------------------------------------------------
	// check_editor_permission / check_user_permission
	// -----------------------------------------------------------------------

	/**
	 * Test that check_editor_permission() returns false for a subscriber.
	 */
	public function test_check_editor_permission_false_for_subscriber(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$this->assertFalse( REST_Authentication::check_editor_permission() );
	}

	/**
	 * Test that check_editor_permission() returns true for an editor.
	 */
	public function test_check_editor_permission_true_for_editor(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$this->assertTrue( REST_Authentication::check_editor_permission() );
	}

	/**
	 * Test that check_user_permission() returns false for a subscriber.
	 */
	public function test_check_user_permission_false_for_subscriber(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$this->assertFalse( REST_Authentication::check_user_permission() );
	}

	/**
	 * Test that check_user_permission() returns true for an editor.
	 */
	public function test_check_user_permission_true_for_editor(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$this->assertTrue( REST_Authentication::check_user_permission() );
	}

	// -----------------------------------------------------------------------
	// check_post_permissions
	// -----------------------------------------------------------------------

	/**
	 * Test that check_post_permissions() always returns false for 'revision' post type.
	 */
	public function test_check_post_permissions_false_for_revision(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$this->assertFalse( REST_Authentication::check_post_permissions( 'revision', 'read', $user_id ) );
	}

	/**
	 * Test that check_post_permissions() returns false for an unregistered post type.
	 */
	public function test_check_post_permissions_false_for_invalid_post_type(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$this->assertFalse( REST_Authentication::check_post_permissions( 'nonexistent_type', 'read', $user_id ) );
	}

	/**
	 * Test that check_post_permissions() returns true for an admin reading 'post'.
	 */
	public function test_check_post_permissions_true_for_admin_read(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$this->assertTrue( REST_Authentication::check_post_permissions( 'post', 'read', $user_id ) );
	}

	/**
	 * Test that check_post_permissions() returns false for a subscriber creating 'post'.
	 */
	public function test_check_post_permissions_false_for_subscriber_create(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$this->assertFalse( REST_Authentication::check_post_permissions( 'post', 'create', $user_id ) );
	}
}
