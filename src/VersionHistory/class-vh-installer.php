<?php
/**
 * Version History schema installer.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils\VersionHistory
 * @since   [NEXT_VERSION]
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\VersionHistory;

defined( 'ABSPATH' ) || exit;

/**
 * Version History schema installer.
 *
 * Creates and upgrades the three site-wide ledger tables. The tables are
 * prefixed `de_vh_` rather than per-plugin: there is one ledger per site, and it
 * has to outlive whichever plugin happens to be carrying the library.
 *
 * @since [NEXT_VERSION]
 */
class VH_Installer {

	/**
	 * Current schema version.
	 *
	 * @since [NEXT_VERSION]
	 * @var   int
	 */
	public const DB_VERSION = 1;

	/**
	 * Get the events table name.
	 *
	 * @since  [NEXT_VERSION]
	 * @return string
	 */
	public static function events_table(): string {
		global $wpdb;

		return $wpdb->prefix . 'de_vh_events';
	}

	/**
	 * Get the current state table name.
	 *
	 * @since  [NEXT_VERSION]
	 * @return string
	 */
	public static function current_state_table(): string {
		global $wpdb;

		return $wpdb->prefix . 'de_vh_current_state';
	}

	/**
	 * Get the checkpoints table name.
	 *
	 * @since  [NEXT_VERSION]
	 * @return string
	 */
	public static function checkpoints_table(): string {
		global $wpdb;

		return $wpdb->prefix . 'de_vh_checkpoints';
	}

	/**
	 * Install or upgrade the schema when the stored version is behind.
	 *
	 * @since  [NEXT_VERSION]
	 * @return bool Whether the schema was touched.
	 */
	public static function maybe_install(): bool {
		$installed = (int) get_option( VH_Options::DB_VERSION, 0 );

		if ( $installed >= self::DB_VERSION ) {
			return false;
		}

		self::install();

		return true;
	}

	/**
	 * Run dbDelta against the schema and record the version.
	 *
	 * @since  [NEXT_VERSION]
	 * @return void
	 */
	public static function install(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( self::get_schema() );

		update_option( VH_Options::DB_VERSION, self::DB_VERSION, true );
	}

	/**
	 * Whether every ledger table exists.
	 *
	 * @since  [NEXT_VERSION]
	 * @return bool
	 */
	public static function tables_exist(): bool {
		global $wpdb;

		foreach ( self::get_tables() as $table ) {
			$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

			if ( $found !== $table ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the ledger table names.
	 *
	 * @since  [NEXT_VERSION]
	 * @return array<string, string>
	 */
	public static function get_tables(): array {
		return array(
			'events'        => self::events_table(),
			'current_state' => self::current_state_table(),
			'checkpoints'   => self::checkpoints_table(),
		);
	}

	/**
	 * Get the dbDelta schema.
	 *
	 * Index prefix lengths are capped at 150 because these are utf8mb4 columns
	 * and the composite keys have to stay inside the InnoDB key length limit.
	 *
	 * @since  [NEXT_VERSION]
	 * @return string
	 */
	public static function get_schema(): string {
		global $wpdb;

		$collate = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';

		$events        = self::events_table();
		$current_state = self::current_state_table();
		$checkpoints   = self::checkpoints_table();

		return "
CREATE TABLE {$events} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  blog_id bigint(20) unsigned NOT NULL DEFAULT 1,
  event_time datetime NOT NULL,
  event_time_gmt datetime NOT NULL,
  component_type varchar(20) NOT NULL,
  component_file varchar(255) NOT NULL DEFAULT '',
  component_slug varchar(255) NOT NULL DEFAULT '',
  component_name varchar(255) NOT NULL DEFAULT '',
  event_type varchar(50) NOT NULL,
  old_version varchar(100) DEFAULT NULL,
  new_version varchar(100) DEFAULT NULL,
  old_status varchar(50) DEFAULT NULL,
  new_status varchar(50) DEFAULT NULL,
  source varchar(50) NOT NULL DEFAULT 'unknown',
  user_id bigint(20) unsigned DEFAULT NULL,
  fingerprint char(40) NOT NULL,
  metadata longtext DEFAULT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY fingerprint (fingerprint),
  KEY component_time (component_type,component_file(150),event_time_gmt),
  KEY event_time_gmt (event_time_gmt),
  KEY event_type (event_type)
) {$collate};
CREATE TABLE {$current_state} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  blog_id bigint(20) unsigned NOT NULL DEFAULT 1,
  component_type varchar(20) NOT NULL,
  component_file varchar(255) NOT NULL DEFAULT '',
  component_slug varchar(255) NOT NULL DEFAULT '',
  component_name varchar(255) NOT NULL DEFAULT '',
  version varchar(100) DEFAULT NULL,
  status varchar(50) NOT NULL DEFAULT 'unknown',
  present tinyint(1) NOT NULL DEFAULT 1,
  metadata longtext DEFAULT NULL,
  last_seen datetime NOT NULL,
  last_seen_gmt datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY component (blog_id,component_type,component_file(150))
) {$collate};
CREATE TABLE {$checkpoints} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  blog_id bigint(20) unsigned NOT NULL DEFAULT 1,
  checkpoint_time datetime NOT NULL,
  checkpoint_time_gmt datetime NOT NULL,
  reason varchar(50) NOT NULL DEFAULT 'scheduled',
  component_count int(11) unsigned NOT NULL DEFAULT 0,
  state longtext NOT NULL,
  PRIMARY KEY  (id),
  KEY checkpoint_time_gmt (blog_id,checkpoint_time_gmt)
) {$collate};
";
	}
}
