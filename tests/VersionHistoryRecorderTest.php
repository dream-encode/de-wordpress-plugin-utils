<?php
/**
 * Tests for VH_Event_Recorder and VH_Installer.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Tests;

use Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures\Version_History_Fixture;
use Dream_Encode\WordPress_Plugin_Utils\VersionHistory\VH_Event_Recorder;
use Dream_Encode\WordPress_Plugin_Utils\VersionHistory\VH_History_Query;
use Dream_Encode\WordPress_Plugin_Utils\VersionHistory\VH_Installer;
use WP_UnitTestCase;

/**
 * Test case for event recording, current state sync and duplicate prevention.
 */
class VersionHistoryRecorderTest extends WP_UnitTestCase {

	private const WOO = 'woocommerce/woocommerce.php';

	/**
	 * Set up a clean ledger before each test.
	 */
	public function set_up(): void {
		parent::set_up();

		Version_History_Fixture::reset();
	}

	/**
	 * The installer creates all three ledger tables.
	 */
	public function test_installer_creates_every_table(): void {
		$this->assertTrue( VH_Installer::tables_exist() );
		$this->assertCount( 3, VH_Installer::get_tables() );
	}

	/**
	 * Running the installer again does not fail or duplicate anything.
	 */
	public function test_install_is_idempotent(): void {
		VH_Installer::install();
		VH_Installer::install();

		$this->assertTrue( VH_Installer::tables_exist() );
		$this->assertSame( VH_Installer::DB_VERSION, (int) get_option( 'de_vh_db_version' ) );
	}

	/**
	 * Recording an event writes a row and brings current state into step.
	 */
	public function test_record_writes_event_and_syncs_current_state(): void {
		$written = Version_History_Fixture::event(
			array(
				'event_type'     => 'installed',
				'new_version'    => '10.1.2',
				'new_status'     => 'active',
				'event_time_gmt' => '2026-07-01 00:00:00',
			)
		);

		$this->assertTrue( $written );
		$this->assertSame( 1, VH_History_Query::count_events() );

		$row = VH_Event_Recorder::get_current_state_row( 'plugin', self::WOO );

		$this->assertNotNull( $row );
		$this->assertSame( '10.1.2', $row->version );
		$this->assertSame( 'active', $row->status );
		$this->assertSame( 1, (int) $row->present );
	}

	/**
	 * A hook-recorded change and a same-day reconciliation collapse into one row.
	 *
	 * This is the duplicate-prevention case that matters: the reconciler sees
	 * the same transition the upgrader hook already captured.
	 */
	public function test_same_transition_on_same_day_is_recorded_once(): void {
		$args = array(
			'event_type'     => 'updated',
			'old_version'    => '10.1.2',
			'new_version'    => '10.1.3',
			'event_time_gmt' => '2026-07-10 12:00:00',
		);

		$first = Version_History_Fixture::event( $args );

		$args['event_time_gmt'] = '2026-07-10 23:45:00';
		$args['source']         = 'reconciliation';

		$second = Version_History_Fixture::event( $args );

		$this->assertTrue( $first );
		$this->assertFalse( $second );
		$this->assertSame( 1, VH_History_Query::count_events() );
	}

	/**
	 * The same transition on a later date is recorded again.
	 *
	 * A rollback followed by a re-upgrade is real history, not a duplicate.
	 */
	public function test_same_transition_on_a_later_day_is_recorded_again(): void {
		$args = array(
			'event_type'     => 'updated',
			'old_version'    => '10.1.2',
			'new_version'    => '10.1.3',
			'event_time_gmt' => '2026-07-10 12:00:00',
		);

		Version_History_Fixture::event( $args );

		$args['event_time_gmt'] = '2026-08-14 12:00:00';

		$this->assertTrue( Version_History_Fixture::event( $args ) );
		$this->assertSame( 2, VH_History_Query::count_events() );
	}

	/**
	 * An event without a new version leaves the recorded version alone.
	 *
	 * A deactivation says nothing about which version is installed.
	 */
	public function test_status_only_event_preserves_recorded_version(): void {
		Version_History_Fixture::event(
			array(
				'event_type'     => 'installed',
				'new_version'    => '10.1.2',
				'new_status'     => 'active',
				'event_time_gmt' => '2026-07-01 00:00:00',
			)
		);

		Version_History_Fixture::event(
			array(
				'event_type'     => 'deactivated',
				'old_status'     => 'active',
				'new_status'     => 'inactive',
				'event_time_gmt' => '2026-07-05 00:00:00',
			)
		);

		$row = VH_Event_Recorder::get_current_state_row( 'plugin', self::WOO );

		$this->assertSame( '10.1.2', $row->version );
		$this->assertSame( 'inactive', $row->status );
	}

	/**
	 * A deleted component keeps its row and its last known version.
	 */
	public function test_mark_absent_keeps_the_row_and_the_last_version(): void {
		Version_History_Fixture::event(
			array(
				'event_type'     => 'installed',
				'new_version'    => '10.1.2',
				'new_status'     => 'active',
				'event_time_gmt' => '2026-07-01 00:00:00',
			)
		);

		VH_Event_Recorder::mark_absent( 'plugin', self::WOO );

		$row = VH_Event_Recorder::get_current_state_row( 'plugin', self::WOO );

		$this->assertNotNull( $row );
		$this->assertSame( 0, (int) $row->present );
		$this->assertSame( '10.1.2', $row->version );
	}

	/**
	 * An event missing a type or component type is rejected.
	 */
	public function test_incomplete_events_are_rejected(): void {
		$this->assertFalse( VH_Event_Recorder::record( array( 'component_type' => 'plugin' ) ) );
		$this->assertFalse( VH_Event_Recorder::record( array( 'event_type' => 'updated' ) ) );
		$this->assertSame( 0, VH_History_Query::count_events() );
	}

	/**
	 * The fingerprint ignores time below day granularity but nothing else.
	 */
	public function test_fingerprint_granularity(): void {
		$base = array(
			'blog_id'        => 1,
			'component_type' => 'plugin',
			'component_file' => self::WOO,
			'event_type'     => 'updated',
			'old_version'    => '10.1.2',
			'new_version'    => '10.1.3',
			'old_status'     => null,
			'new_status'     => null,
			'event_time_gmt' => '2026-07-10 12:00:00',
		);

		$same_day = array_merge( $base, array( 'event_time_gmt' => '2026-07-10 23:59:59' ) );
		$next_day = array_merge( $base, array( 'event_time_gmt' => '2026-07-11 00:00:00' ) );
		$other_ver = array_merge( $base, array( 'new_version' => '10.1.4' ) );

		$this->assertSame(
			VH_Event_Recorder::fingerprint( $base ),
			VH_Event_Recorder::fingerprint( $same_day )
		);

		$this->assertNotSame(
			VH_Event_Recorder::fingerprint( $base ),
			VH_Event_Recorder::fingerprint( $next_day )
		);

		$this->assertNotSame(
			VH_Event_Recorder::fingerprint( $base ),
			VH_Event_Recorder::fingerprint( $other_ver )
		);
	}
}
