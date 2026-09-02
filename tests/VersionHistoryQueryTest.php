<?php
/**
 * Tests for VH_History_Query.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Tests;

use Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures\Version_History_Fixture;
use Dream_Encode\WordPress_Plugin_Utils\VersionHistory\VH_Checkpoints;
use Dream_Encode\WordPress_Plugin_Utils\VersionHistory\VH_History_Query;
use WP_UnitTestCase;

/**
 * Test case for historical state reconstruction.
 *
 * This is the class the whole feature exists to make trustworthy. If these
 * assertions pass, an answer about July 15 is based on recorded evidence.
 */
class VersionHistoryQueryTest extends WP_UnitTestCase {

	private const WOO = 'woocommerce/woocommerce.php';

	/**
	 * Set up a deterministic ledger before each test.
	 */
	public function set_up(): void {
		parent::set_up();

		Version_History_Fixture::reset();

		Version_History_Fixture::baseline(
			'2026-07-01 00:00:00',
			array(
				Version_History_Fixture::plugin( 'woocommerce', 'WooCommerce', '10.0.0' ),
				Version_History_Fixture::plugin( 'query-monitor', 'Query Monitor', '3.17.0' ),
			)
		);

		Version_History_Fixture::event(
			array(
				'event_type'     => 'updated',
				'old_version'    => '10.0.0',
				'new_version'    => '10.0.1',
				'event_time_gmt' => '2026-07-10 12:00:00',
			)
		);

		Version_History_Fixture::event(
			array(
				'event_type'     => 'updated',
				'old_version'    => '10.0.1',
				'new_version'    => '10.1.0',
				'event_time_gmt' => '2026-07-20 12:00:00',
			)
		);
	}

	/**
	 * A query between two updates returns the version in force at that moment.
	 */
	public function test_state_between_two_updates_returns_the_earlier_version(): void {
		$result = VH_History_Query::state_at( '2026-07-15 12:00:00' );

		$this->assertTrue( $result['known'] );
		$this->assertSame( '10.0.1', $result['state'][ 'plugin:' . self::WOO ]['version'] );
	}

	/**
	 * A query after the second update returns the later version.
	 */
	public function test_state_after_second_update_returns_the_later_version(): void {
		$result = VH_History_Query::state_at( '2026-07-25 12:00:00' );

		$this->assertSame( '10.1.0', $result['state'][ 'plugin:' . self::WOO ]['version'] );
	}

	/**
	 * A query at the baseline returns the baseline version.
	 */
	public function test_state_at_baseline_returns_baseline_version(): void {
		$result = VH_History_Query::state_at( '2026-07-01 00:00:00' );

		$this->assertTrue( $result['known'] );
		$this->assertSame( '10.0.0', $result['state'][ 'plugin:' . self::WOO ]['version'] );
	}

	/**
	 * The second before an update still reports the old version.
	 */
	public function test_state_one_second_before_update_returns_old_version(): void {
		$result = VH_History_Query::state_at( '2026-07-10 11:59:59' );

		$this->assertSame( '10.0.0', $result['state'][ 'plugin:' . self::WOO ]['version'] );
	}

	/**
	 * The exact second of an update reports the new version.
	 */
	public function test_state_at_exact_update_second_returns_new_version(): void {
		$result = VH_History_Query::state_at( '2026-07-10 12:00:00' );

		$this->assertSame( '10.0.1', $result['state'][ 'plugin:' . self::WOO ]['version'] );
	}

	/**
	 * State before the baseline is reported as unknown rather than guessed.
	 */
	public function test_state_before_baseline_is_unknown(): void {
		$result = VH_History_Query::state_at( '2025-07-15 12:00:00' );

		$this->assertFalse( $result['known'] );
		$this->assertSame( array(), $result['state'] );
		$this->assertSame( '2026-07-01 00:00:00', $result['history_started_at'] );
	}

	/**
	 * Reconstruction from a later checkpoint matches full replay from baseline.
	 */
	public function test_checkpoint_reconstruction_matches_full_replay(): void {
		$without_checkpoint = VH_History_Query::state_at( '2026-07-25 12:00:00' );

		VH_Checkpoints::create( 'scheduled', '2026-07-22 00:00:00' );

		$with_checkpoint = VH_History_Query::state_at( '2026-07-25 12:00:00' );

		$this->assertSame( $without_checkpoint['state'], $with_checkpoint['state'] );
	}

	/**
	 * Activation status is reconstructed alongside version.
	 */
	public function test_status_changes_are_reconstructed(): void {
		Version_History_Fixture::event(
			array(
				'component_file' => 'query-monitor/query-monitor.php',
				'component_slug' => 'query-monitor',
				'component_name' => 'Query Monitor',
				'event_type'     => 'deactivated',
				'old_status'     => 'active',
				'new_status'     => 'inactive',
				'event_time_gmt' => '2026-07-12 09:00:00',
			)
		);

		$before = VH_History_Query::state_at( '2026-07-11 00:00:00' );
		$after  = VH_History_Query::state_at( '2026-07-13 00:00:00' );

		$this->assertSame( 'active', $before['state']['plugin:query-monitor/query-monitor.php']['status'] );
		$this->assertSame( 'inactive', $after['state']['plugin:query-monitor/query-monitor.php']['status'] );
	}

	/**
	 * A deleted plugin stops being present but keeps its recorded history.
	 */
	public function test_deleted_component_is_absent_but_history_survives(): void {
		Version_History_Fixture::event(
			array(
				'component_file' => 'query-monitor/query-monitor.php',
				'component_slug' => 'query-monitor',
				'component_name' => 'Query Monitor',
				'event_type'     => 'deleted',
				'old_version'    => '3.17.0',
				'event_time_gmt' => '2026-07-14 09:00:00',
			)
		);

		$after = VH_History_Query::state_at( '2026-07-16 00:00:00' );

		$this->assertFalse( $after['state']['plugin:query-monitor/query-monitor.php']['present'] );

		$history = VH_History_Query::component_history( 'plugin', 'query-monitor/query-monitor.php' );

		$this->assertCount( 2, $history );
		$this->assertSame( 'baseline', $history[0]->event_type );
		$this->assertSame( 'deleted', $history[1]->event_type );
	}

	/**
	 * A plugin deleted and later reinstalled keeps its earlier history.
	 */
	public function test_reinstalled_component_keeps_earlier_history(): void {
		Version_History_Fixture::event(
			array(
				'event_type'     => 'deleted',
				'old_version'    => '10.1.0',
				'event_time_gmt' => '2026-08-01 09:00:00',
			)
		);

		Version_History_Fixture::event(
			array(
				'event_type'     => 'installed',
				'new_version'    => '10.2.0',
				'new_status'     => 'active',
				'event_time_gmt' => '2026-09-01 09:00:00',
			)
		);

		$gone = VH_History_Query::state_at( '2026-08-15 00:00:00' );
		$back = VH_History_Query::state_at( '2026-09-15 00:00:00' );

		$this->assertFalse( $gone['state'][ 'plugin:' . self::WOO ]['present'] );
		$this->assertTrue( $back['state'][ 'plugin:' . self::WOO ]['present'] );
		$this->assertSame( '10.2.0', $back['state'][ 'plugin:' . self::WOO ]['version'] );
		$this->assertCount( 5, VH_History_Query::component_history( 'plugin', self::WOO ) );
	}

	/**
	 * Two events in the same second replay in insertion order.
	 */
	public function test_same_second_events_replay_in_id_order(): void {
		Version_History_Fixture::event(
			array(
				'event_type'     => 'updated',
				'old_version'    => '10.1.0',
				'new_version'    => '10.1.1',
				'event_time_gmt' => '2026-07-21 08:00:00',
			)
		);

		Version_History_Fixture::event(
			array(
				'event_type'     => 'updated',
				'old_version'    => '10.1.1',
				'new_version'    => '10.1.2',
				'event_time_gmt' => '2026-07-21 08:00:00',
			)
		);

		$result = VH_History_Query::state_at( '2026-07-21 08:00:00' );

		$this->assertSame( '10.1.2', $result['state'][ 'plugin:' . self::WOO ]['version'] );
	}

	/**
	 * A plugin can be resolved by directory slug or by full basename.
	 */
	public function test_component_resolves_by_slug_or_basename(): void {
		$this->assertSame( self::WOO, VH_History_Query::resolve_component( 'plugin', 'woocommerce' ) );
		$this->assertSame( self::WOO, VH_History_Query::resolve_component( 'plugin', self::WOO ) );
		$this->assertNull( VH_History_Query::resolve_component( 'plugin', 'not-installed' ) );
	}
}
