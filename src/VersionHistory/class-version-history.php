<?php
/**
 * Version History module.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils\VersionHistory
 * @since   1.10.0
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\VersionHistory;

defined( 'ABSPATH' ) || exit;

/**
 * Version History module.
 *
 * Permanent history of WordPress core, plugin, theme, must-use plugin and
 * drop-in versions for one site. Loaded once per request by the elected copy of
 * the library, never instantiated from plugin code.
 *
 * Deliberately self-contained: the elected module can run beside base classes
 * from an older copy of the library that won the include-time race, so it
 * depends on nothing outside its own namespace.
 *
 * @since 1.10.0
 */
class Version_History {

	/**
	 * Module revision.
	 *
	 * Kept in step with the value registered in the library bootstrap, which is
	 * what the election compares.
	 *
	 * @since 1.10.0
	 * @var   int
	 */
	public const MODULE_REVISION = 1;

	/**
	 * Singleton instance.
	 *
	 * @since 1.10.0
	 * @var   self|null
	 */
	private static ?self $instance = null;

	/**
	 * Whether init has already run this request.
	 *
	 * @since 1.10.0
	 * @var   bool
	 */
	private bool $initialized = false;

	/**
	 * Get the singleton instance.
	 *
	 * @since  1.10.0
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Boot the module.
	 *
	 * Installs the schema when it is missing or behind, records the baseline the
	 * first time it runs, and stamps which copy of the library is recording.
	 *
	 * @since  1.10.0
	 * @return void
	 */
	public function init(): void {
		if ( $this->initialized ) {
			return;
		}

		$this->initialized = true;

		VH_Installer::maybe_install();

		if ( ! VH_Installer::tables_exist() ) {
			return;
		}

		$this->record_owner();

		$this->maybe_record_baseline();
	}

	/**
	 * Whether this site has recorded a baseline yet.
	 *
	 * @since  1.10.0
	 * @return bool
	 */
	public function has_baseline(): bool {
		return '' !== VH_Options::baseline_gmt();
	}

	/**
	 * Get a status summary for the admin screen and CLI.
	 *
	 * @since  1.10.0
	 * @return array<string, mixed>
	 */
	public function status(): array {
		$owner = get_option( VH_Options::OWNER, array() );

		return array(
			'baseline_gmt'        => VH_Options::baseline_gmt(),
			'last_reconcile_gmt'  => (string) get_option( VH_Options::LAST_RECONCILE_GMT, '' ),
			'event_count'         => VH_History_Query::count_events(),
			'component_count'     => count( VH_History_Query::current_state( false ) ),
			'components_present'  => count( VH_History_Query::current_state( true ) ),
			'checkpoint_count'    => VH_Checkpoints::count(),
			'db_version'          => (int) get_option( VH_Options::DB_VERSION, 0 ),
			'module_revision'     => self::MODULE_REVISION,
			'recording_from'      => is_array( $owner ) ? ( $owner['path'] ?? '' ) : '',
		);
	}

	/**
	 * Record the baseline once, the first time the module runs on a site.
	 *
	 * @since  1.10.0
	 * @return void
	 */
	private function maybe_record_baseline(): void {
		if ( $this->has_baseline() ) {
			return;
		}

		VH_Event_Recorder::record_baseline();
	}

	/**
	 * Stamp which copy of the library won the election.
	 *
	 * Purely diagnostic, and the first thing worth checking when recording
	 * looks stale.
	 *
	 * @since  1.10.0
	 * @return void
	 */
	private function record_owner(): void {
		$owner = array(
			'path'     => dirname( __DIR__, 2 ),
			'revision' => self::MODULE_REVISION,
		);

		$stored = get_option( VH_Options::OWNER, array() );

		if ( $stored === $owner ) {
			return;
		}

		update_option( VH_Options::OWNER, $owner, false );
	}
}
