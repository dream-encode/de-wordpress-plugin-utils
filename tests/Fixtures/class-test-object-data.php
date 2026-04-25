<?php
/**
 * Test object data fixture.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

namespace Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures;

use Dream_Encode\WordPress_Plugin_Utils\Abstracts\Abstract_Object_Data;

/**
 * Concrete implementation of Abstract_Object_Data for testing.
 */
class Test_Object_Data extends Abstract_Object_Data {

	/** @var array<string, mixed> */
	protected array $data = array(
		'id'     => 0,
		'status' => 'draft',
	);

	/** @var array<string, mixed> */
	protected array $extra_data = array(
		'queue_id' => 0,
	);
}