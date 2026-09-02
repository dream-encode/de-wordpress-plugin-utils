<?php
/**
 * Version History option accessor.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils\VersionHistory
 * @since   1.10.0
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\VersionHistory;

defined( 'ABSPATH' ) || exit;

/**
 * Version History option accessor.
 *
 * Deliberately self-contained rather than built on `Plugin_Settings_Repository`.
 * The elected module may run beside base classes from an older copy of the
 * library, so it depends on nothing outside its own namespace.
 *
 * @since 1.10.0
 */
class VH_Options {

	/**
	 * Option holding the installed schema version.
	 *
	 * @since 1.10.0
	 * @var   string
	 */
	public const DB_VERSION = 'de_vh_db_version';

	/**
	 * Option holding the GMT timestamp of the first baseline.
	 *
	 * @since 1.10.0
	 * @var   string
	 */
	public const BASELINE_GMT = 'de_vh_baseline_gmt';

	/**
	 * Option holding the GMT timestamp of the last reconciliation pass.
	 *
	 * @since 1.10.0
	 * @var   string
	 */
	public const LAST_RECONCILE_GMT = 'de_vh_last_reconcile_gmt';

	/**
	 * Option holding the path and revision of the elected library copy.
	 *
	 * @since 1.10.0
	 * @var   string
	 */
	public const OWNER = 'de_vh_owner';

	/**
	 * Option holding the module settings array.
	 *
	 * @since 1.10.0
	 * @var   string
	 */
	public const SETTINGS = 'de_vh_settings';

	/**
	 * Default settings.
	 *
	 * @since 1.10.0
	 * @var   array<string, mixed>
	 */
	private const DEFAULTS = array(
		'reconcile_frequency'      => 'daily',
		'checkpoint_frequency'     => 'monthly',
		'delete_data_on_uninstall' => false,
	);

	/**
	 * In-memory cache of the resolved settings.
	 *
	 * @since 1.10.0
	 * @var   array<string, mixed>|null
	 */
	private static ?array $cache = null;

	/**
	 * Get all settings merged with defaults.
	 *
	 * @since  1.10.0
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$stored = get_option( self::SETTINGS, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		self::$cache = array_replace( self::DEFAULTS, $stored );

		return self::$cache;
	}

	/**
	 * Get a single setting.
	 *
	 * @since  1.10.0
	 * @param  string  $key      Setting key.
	 * @param  mixed   $default  Optional. Value when the key is missing. Default null.
	 * @return mixed
	 */
	public static function get( string $key, mixed $default = null ): mixed {
		$settings = self::all();

		if ( array_key_exists( $key, $settings ) ) {
			return $settings[ $key ];
		}

		return $default;
	}

	/**
	 * Set a single setting.
	 *
	 * @since  1.10.0
	 * @param  string  $key    Setting key.
	 * @param  mixed   $value  Setting value.
	 * @return bool
	 */
	public static function set( string $key, mixed $value ): bool {
		$settings = self::all();

		$settings[ $key ] = $value;

		self::$cache = $settings;

		return update_option( self::SETTINGS, $settings, false );
	}

	/**
	 * Clear the in-memory settings cache.
	 *
	 * @since  1.10.0
	 * @return void
	 */
	public static function refresh(): void {
		self::$cache = null;
	}

	/**
	 * Get the GMT timestamp at which this site began recording history.
	 *
	 * @since  1.10.0
	 * @return string Empty string when no baseline has been recorded.
	 */
	public static function baseline_gmt(): string {
		return (string) get_option( self::BASELINE_GMT, '' );
	}
}
