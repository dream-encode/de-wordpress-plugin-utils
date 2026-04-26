<?php
/**
 * Tests for REST_Response.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

namespace Dream_Encode\WordPress_Plugin_Utils\Tests;

use Dream_Encode\WordPress_Plugin_Utils\RestApi\REST_Response;
use stdClass;
use WP_UnitTestCase;

/**
 * Test case for REST_Response.
 */
class RestResponseTest extends WP_UnitTestCase {

	/**
	 * Test default property values.
	 */
	public function test_defaults(): void {
		$response = new REST_Response();

		$this->assertSame( 'error', $response->status );
		$this->assertSame( '', $response->message );
		$this->assertInstanceOf( stdClass::class, $response->data );
		$this->assertNull( $response->success );
	}

	/**
	 * Test that data is initialised to a fresh stdClass, not a shared reference.
	 */
	public function test_data_is_independent_between_instances(): void {
		$a = new REST_Response();
		$b = new REST_Response();

		$a->data->foo = 'bar';

		$this->assertFalse( isset( $b->data->foo ) );
	}

	/**
	 * Test that all public properties can be mutated.
	 */
	public function test_properties_are_mutable(): void {
		$response = new REST_Response();

		$response->status  = 'success';
		$response->message = 'Done.';
		$response->success = true;
		$response->data    = array( 'id' => 42 );

		$this->assertSame( 'success', $response->status );
		$this->assertSame( 'Done.', $response->message );
		$this->assertTrue( $response->success );
		$this->assertSame( array( 'id' => 42 ), $response->data );
	}
}
