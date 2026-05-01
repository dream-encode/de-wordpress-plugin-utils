<?php
/**
 * Abstract plugin upgrader class.
 *
 * Provides reusable upgrade/install logic for WordPress plugins with
 * cache-clearing fix for Redis/object cache environments.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils\Abstracts
 * @since   1.0.0
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Abstracts;

use Dream_Encode\WordPress_Plugin_Utils\Common\Functions as DEPU_Functions;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract class Abstract_Plugin_Upgrader
 *
 * Base class for plugin upgraders with ActionScheduler integration.
 *
 * @since 1.0.0
 */
abstract class Abstract_Plugin_Upgrader {

	/**
	 * Plugin prefix (e.g., 'mmma', 'mmbp').
	 *
	 * @since  1.0.0
	 * @return string
	 */
	abstract protected static function get_prefix(): string;

	/**
	 * Get the plugin version constant value.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	abstract protected static function get_plugin_version(): string;

	/**
	 * Get the database version constant value.
	 *
	 * @since  1.0.0
	 * @return int
	 */
	abstract protected static function get_database_version(): int;

	/**
	 * Get the plugin path constant value.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	abstract protected static function get_plugin_path(): string;

	/**
	 * Get the text domain for translations.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	abstract protected static function get_text_domain(): string;

	/**
	 * Get the ActionScheduler group name.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	abstract protected static function get_action_scheduler_group(): string;

	/**
	 * Get DB updates and callbacks that need to be run per version.
	 *
	 * @since  1.0.0
	 * @return array<int, array<string>>
	 */
	abstract protected static function get_db_updates(): array;

	/**
	 * Get the path to the upgrader functions file.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	abstract protected static function get_upgrader_functions_path(): string;

	/**
	 * Get the logger class name.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	abstract protected static function get_logger_class(): string;

	/**
	 * Get the table schema for dbDelta.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	abstract protected static function get_schema(): string;

	/**
	 * Get the list of plugin tables.
	 *
	 * @since  1.0.0
	 * @return array<string, string>
	 */
	abstract protected static function get_tables(): array;

	/**
	 * Create default options during install.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected static function create_default_options(): void {
	}

	/**
	 * Define the "updating" constant.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected static function define_updating_constant(): void {
	}

	/**
	 * Define the "installing" constant.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected static function define_installing_constant(): void {
	}

	/**
	 * Hook in tabs.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function init(): void {
		$prefix = static::get_prefix();

		add_action( 'init', array( static::class, 'check_version' ), 5 );
		add_action( "{$prefix}_run_update_callback", array( static::class, 'run_update_callback' ) );
		add_action( "{$prefix}_update_db_to_current_version", array( static::class, 'update_db_version' ) );
	}

	/**
	 * Check plugin version and run the updater if required.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function check_version(): void {
		if ( DEPU_Functions::environment_is_local() ) {
			return;
		}

		$prefix              = static::get_prefix();
		$option_name         = "{$prefix}_plugin_version";
		$plugin_version      = get_option( $option_name, '' );
		$plugin_code_version = static::get_plugin_version();

		if ( version_compare( $plugin_version, $plugin_code_version, '>=' ) ) {
			return;
		}

		wp_cache_delete( $option_name, 'options' );

		$plugin_version = get_option( $option_name, '' );

		if ( version_compare( $plugin_version, $plugin_code_version, '>=' ) ) {
			return;
		}

		static::install();
	}

	/**
	 * Run an update callback when triggered by ActionScheduler.
	 *
	 * @since  1.0.0
	 * @param  string  $update_callback  Callback name.
	 * @return void
	 */
	public static function run_update_callback( string $update_callback ): void {
		$functions_path = static::get_upgrader_functions_path();

		if ( file_exists( $functions_path ) ) {
			include_once $functions_path;
		}

		if ( is_callable( $update_callback ) ) {
			static::run_update_callback_start( $update_callback );

			$result = (bool) call_user_func( $update_callback );

			static::run_update_callback_end( $update_callback, $result );
		}
	}

	/**
	 * Triggered when a callback will run.
	 *
	 * @since  1.0.0
	 * @param  string  $callback  Callback name.
	 * @return void
	 */
	protected static function run_update_callback_start( string $callback ): void {
		static::define_updating_constant();
	}

	/**
	 * Triggered when a callback has ran.
	 *
	 * @since  1.0.0
	 * @param  string  $callback  Callback name.
	 * @param  bool    $result    Return value from callback. Non-false need to run again.
	 * @return void
	 */
	protected static function run_update_callback_end( string $callback, bool $result ): void {
		if ( $result && is_callable( $callback ) ) {
			$callback( $result );
		}
	}

	/**
	 * Install plugin.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function install(): void {
		if ( ! is_blog_installed() ) {
			return;
		}

		if ( self::is_installing() ) {
			return;
		}

		$logger_class = static::get_logger_class();
		$prefix       = static::get_prefix();
		$text_domain  = static::get_text_domain();

		if ( class_exists( $logger_class ) && method_exists( $logger_class, 'log' ) ) {
			$logger_class::log(
				__( '=================== Beginning Install ===================', $text_domain ) // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
			);
		}

		set_transient( "{$prefix}_installing", 'yes', MINUTE_IN_SECONDS * 10 );

		static::define_installing_constant();

		static::create_tables();

		static::create_default_options();

		self::update_plugin_version();

		self::maybe_update_db_version();

		delete_transient( "{$prefix}_installing" );

		if ( class_exists( $logger_class ) && method_exists( $logger_class, 'log' ) ) {
			$logger_class::log(
				__( '=================== End Install ===================', $text_domain ) // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
			);
		}
	}

	/**
	 * Returns true if we're installing.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	private static function is_installing(): bool {
		$prefix = static::get_prefix();

		return 'yes' === get_transient( "{$prefix}_installing" );
	}

	/**
	 * Is this a brand new plugin install?
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public static function is_new_install(): bool {
		$prefix = static::get_prefix();

		return is_null( get_option( "{$prefix}_plugin_version", null ) );
	}

	/**
	 * Is a DB update needed?
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public static function needs_db_update(): bool {
		$logger_class = static::get_logger_class();
		$text_domain  = static::get_text_domain();

		if ( class_exists( $logger_class ) && method_exists( $logger_class, 'log' ) ) {
			$logger_class::log(
				__( 'Checking if updates are needed for this version...', $text_domain ) // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
			);
		}

		$updates = static::get_db_updates();

		if ( count( $updates ) < 1 ) {
			if ( class_exists( $logger_class ) && method_exists( $logger_class, 'log' ) ) {
				$logger_class::log(
					__( 'No updates found.', $text_domain ) // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
				);
			}

			return false;
		}

		$prefix             = static::get_prefix();
		$current_db_version = get_option( "{$prefix}_database_version", null );

		if ( is_null( $current_db_version ) ) {
			return false;
		}

		$update_versions = array_keys( $updates );

		sort( $update_versions, SORT_NUMERIC );

		return (int) $current_db_version < (int) end( $update_versions );
	}

	/**
	 * See if we need to show or run database updates during install.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private static function maybe_update_db_version(): void {
		$logger_class = static::get_logger_class();
		$text_domain  = static::get_text_domain();

		if ( static::needs_db_update() ) {
			if ( class_exists( $logger_class ) && method_exists( $logger_class, 'log' ) ) {
				$logger_class::log(
					__( 'Version requires updates.', $text_domain ) // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
				);
			}

				self::update();
		} else {
			static::update_db_version();
		}
	}

	/**
	 * Update plugin version to current.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private static function update_plugin_version(): void {
		$prefix = static::get_prefix();

		update_option( "{$prefix}_plugin_version", static::get_plugin_version(), true );
	}

	/**
	 * Push all needed DB updates to the queue for processing.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private static function update(): void {
		$logger_class = static::get_logger_class();
		$text_domain  = static::get_text_domain();
		$prefix       = static::get_prefix();
		$as_group     = static::get_action_scheduler_group();

		if ( class_exists( $logger_class ) && method_exists( $logger_class, 'log' ) ) {
			$logger_class::log(
				__( 'Checking updates...', $text_domain ) // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
			);
		}

		$current_db_version = (int) get_option( "{$prefix}_database_version" );
		$loop               = 0;

		if ( class_exists( $logger_class ) && method_exists( $logger_class, 'log' ) ) {
			$logger_class::log(
				sprintf(
					/* translators: %d: Database version. */
					__( 'Current database version: %d', $text_domain ), // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
					$current_db_version
				)
			);
		}

		foreach ( static::get_db_updates() as $version => $update_callbacks ) {
			if ( $current_db_version < (int) $version ) {
				if ( class_exists( $logger_class ) && method_exists( $logger_class, 'log' ) ) {
					$logger_class::log(
						sprintf(
							/* translators: %d: Database version. */
							__( 'Parsing needed updates for version %d', $text_domain ), // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
							$version
						)
					);
				}

				foreach ( $update_callbacks as $update_callback ) {
					if ( as_has_scheduled_action( "{$prefix}_run_update_callback", array( $update_callback ), $as_group ) ) {
						continue;
					}

					as_schedule_single_action(
						time() + $loop,
						"{$prefix}_run_update_callback",
						array( $update_callback ),
						$as_group
					);

					if ( class_exists( $logger_class ) && method_exists( $logger_class, 'log' ) ) {
						$logger_class::log(
							sprintf(
								/* translators: %s: Update callback. */
								__( 'Scheduled async update for `%s`', $text_domain ), // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
								$update_callback
							)
						);
					}

					++$loop;
				}
			}
		}

		$current_db_define_version = static::get_database_version();

		if ( $current_db_version < $current_db_define_version && ! as_has_scheduled_action( "{$prefix}_update_db_to_current_version", array(), $as_group ) ) {
			as_schedule_single_action(
				time() + $loop,
				"{$prefix}_update_db_to_current_version",
				array( $current_db_define_version ),
				$as_group
			);

			if ( class_exists( $logger_class ) && method_exists( $logger_class, 'log' ) ) {
				$logger_class::log(
					__( 'Scheduled async database version update.', $text_domain ) // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
				);
			}
		}
	}

	/**
	 * Update DB version to current.
	 *
	 * @since  1.0.0
	 * @param  int|null  $version  New DB version or null for current.
	 * @return void
	 */
	public static function update_db_version( ?int $version = null ): void {
		$prefix       = static::get_prefix();
		$new_version  = is_null( $version ) ? static::get_database_version() : $version;
		$logger_class = static::get_logger_class();
		$text_domain  = static::get_text_domain();

		update_option( "{$prefix}_database_version", $new_version, true );

		if ( class_exists( $logger_class ) && method_exists( $logger_class, 'log' ) ) {
			$logger_class::log(
				sprintf(
					/* translators: %d: Database version. */
					__( 'Updated database version to %d.', $text_domain ), // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
					$new_version
				)
			);
		}
	}

	/**
	 * Set up the database tables which the plugin needs to function.
	 *
	 * Uses dbDelta which can update existing tables.
	 *
	 * @since  1.0.0
	 * @global \wpdb  $wpdb  WordPress database instance global.
	 * @return void
	 */
	public static function create_tables(): void {
		global $wpdb;

		$wpdb->hide_errors();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$schema = static::get_schema();

		if ( ! empty( $schema ) ) {
			dbDelta( $schema );
		}
	}

	/**
	 * Drop plugin tables.
	 *
	 * @since  1.0.0
	 * @global \wpdb  $wpdb  WordPress database instance global.
	 * @return void
	 */
	public static function drop_tables(): void {
		global $wpdb;

		$tables = static::get_tables();

		foreach ( $tables as $name => $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}

	/**
	 * Define plugin tables in the `$wpdb` global.
	 *
	 * @since  1.0.0
	 * @global \wpdb  $wpdb  WordPress database instance global.
	 * @return void
	 */
	public static function define_tables(): void {
		global $wpdb;

		$tables = static::get_tables();

		foreach ( $tables as $name => $table ) {
			$wpdb->{$name} = $table;

			$wpdb->tables[] = $name;
		}
	}
}
