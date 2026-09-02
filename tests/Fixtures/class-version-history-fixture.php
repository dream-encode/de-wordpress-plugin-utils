<?php
/**
 * Version History test fixture.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures;

use Dream_Encode\WordPress_Plugin_Utils\VersionHistory\VH_Checkpoints;
use Dream_Encode\WordPress_Plugin_Utils\VersionHistory\VH_Event_Recorder;
use Dream_Encode\WordPress_Plugin_Utils\VersionHistory\VH_Installer;
use Dream_Encode\WordPress_Plugin_Utils\VersionHistory\VH_Options;

/**
 * Seeds a deterministic ledger for Version History tests.
 *
 * The module records a real baseline for the test site during bootstrap. Tests
 * clear that and seed their own so assertions do not depend on whatever plugins
 * and themes happen to be present in the test environment.
 */
class Version_History_Fixture {

	/**
	 * Clear the ledger and every module option.
	 *
	 * Uses DELETE rather than TRUNCATE so the work stays inside the transaction
	 * WP_UnitTestCase wraps each test in and is rolled back afterwards.
	 *
	 * @return void
	 */
	public static function reset(): void {
		global $wpdb;

		foreach ( VH_Installer::get_tables() as $table ) {
			$wpdb->query( $wpdb->prepare( 'DELETE FROM %i', $table ) );
		}

		delete_option( VH_Options::BASELINE_GMT );
		delete_option( VH_Options::LAST_RECONCILE_GMT );
		delete_option( VH_Options::SETTINGS );

		VH_Options::refresh();
	}

	/**
	 * Seed a baseline at a fixed GMT timestamp.
	 *
	 * @param  string                              $gmt         GMT timestamp.
	 * @param  array<int, array<string, mixed>>    $components  Components present at baseline.
	 * @return void
	 */
	public static function baseline( string $gmt, array $components ): void {
		foreach ( $components as $component ) {
			self::event(
				array_merge(
					$component,
					array(
						'event_type'     => 'baseline',
						'new_version'    => $component['version'] ?? null,
						'new_status'     => $component['status'] ?? 'active',
						'event_time_gmt' => $gmt,
					)
				)
			);
		}

		VH_Checkpoints::create( 'baseline', $gmt );

		update_option( VH_Options::BASELINE_GMT, $gmt, true );
	}

	/**
	 * Record one event through the real recorder.
	 *
	 * @param  array<string, mixed>  $args  Event arguments.
	 * @return bool
	 */
	public static function event( array $args ): bool {
		$defaults = array(
			'component_type' => 'plugin',
			'component_file' => 'woocommerce/woocommerce.php',
			'component_slug' => 'woocommerce',
			'component_name' => 'WooCommerce',
			'source'         => 'test',
		);

		return VH_Event_Recorder::record( array_merge( $defaults, $args ) );
	}

	/**
	 * Build a plugin component array.
	 *
	 * @param  string  $slug     Plugin directory slug.
	 * @param  string  $name     Display name.
	 * @param  string  $version  Version string.
	 * @param  string  $status   Optional. Activation status. Default 'active'.
	 * @return array<string, mixed>
	 */
	public static function plugin( string $slug, string $name, string $version, string $status = 'active' ): array {
		return array(
			'component_type' => 'plugin',
			'component_file' => $slug . '/' . $slug . '.php',
			'component_slug' => $slug,
			'component_name' => $name,
			'version'        => $version,
			'status'         => $status,
		);
	}
}
