<?php
/**
 * Tests for Abstract_Object_Data.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

namespace Dream_Encode\WordPress_Plugin_Utils\Tests;

use Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures\Test_Object_Data;
use WP_UnitTestCase;

/**
 * Test case for Abstract_Object_Data.
 */
class AbstractObjectDataTest extends WP_UnitTestCase {

	/**
	 * Test that default data is exposed.
	 */
	public function test_get_data_returns_defaults(): void {
		$object = new Test_Object_Data();

		$this->assertSame(
			array(
				'id'       => 0,
				'status'   => 'draft',
				'queue_id' => 0,
			),
			$object->get_data()
		);
		$this->assertSame( array( 'queue_id' => 0 ), $object->get_extra_data() );
		$this->assertSame( array(), $object->get_changes() );
	}

	/**
	 * Test that property updates are tracked as changes.
	 */
	public function test_set_prop_tracks_changes(): void {
		$object = new Test_Object_Data();

		$object->set_prop( 'status', 'queued' );

		$this->assertSame( 'queued', $object->get_prop( 'status' ) );
		$this->assertSame( array( 'status' => 'queued' ), $object->get_changes() );
	}

	/**
	 * Test that apply_changes persists updates and ignores unknown props.
	 */
	public function test_apply_changes_persists_updates(): void {
		$object = new Test_Object_Data();

		$object->set_props(
			array(
				'status'   => 'complete',
				'queue_id' => 25,
				'unknown'  => 'ignored',
			)
		);
		$object->apply_changes();

		$this->assertSame( 'complete', $object->get_prop( 'status' ) );
		$this->assertSame( 25, $object->get_prop( 'queue_id' ) );
		$this->assertSame( array(), $object->get_changes() );
		$this->assertArrayNotHasKey( 'unknown', $object->get_data() );
	}

	/**
	 * Test that no_cache can be toggled.
	 */
	public function test_no_cache_can_be_toggled(): void {
		$object = new Test_Object_Data();

		$this->assertFalse( $object->get_no_cache() );

		$object->set_no_cache( true );

		$this->assertTrue( $object->get_no_cache() );
	}
}