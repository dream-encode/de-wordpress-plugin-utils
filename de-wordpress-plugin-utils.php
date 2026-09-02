<?php
/**
 * DE WordPress Plugin Utils
 *
 * Reusable WordPress plugin utilities including abstractions for logging, upgrader, and REST API.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( is_dir( __DIR__ . '/src/VersionHistory' ) ) {
	if ( ! isset( $GLOBALS['de_wpu_version_history_registry'] ) ) {
		$GLOBALS['de_wpu_version_history_registry'] = array();

		add_action( 'plugins_loaded', 'de_wpu_boot_version_history', -20 );
	}

	$GLOBALS['de_wpu_version_history_registry'][ __DIR__ ] = 1;
}

if ( ! function_exists( 'de_wpu_elect_version_history_copy' ) ) {
	/**
	 * Pick the library copy that will host the Version History module.
	 *
	 * The highest module revision wins. Copies registering an equal revision are
	 * interchangeable, so the first one registered is kept.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  array<string, int>  $registry  Map of library path to module revision.
	 * @return string Elected library path, or an empty string when nothing registered.
	 */
	function de_wpu_elect_version_history_copy( array $registry ) {
		if ( empty( $registry ) ) {
			return '';
		}

		arsort( $registry, SORT_NUMERIC );

		return (string) array_key_first( $registry );
	}
}

if ( ! function_exists( 'de_wpu_boot_version_history' ) ) {
	/**
	 * Load and initialize the Version History module from the elected library copy.
	 *
	 * Every copy of this library registers itself above, including copies that
	 * return early below because another copy already declared the base classes.
	 * The copy with the highest module revision is the only one that loads
	 * `src/VersionHistory/`, so the newest module present on the site is the one
	 * that records, whichever copy won the include-time race for the base classes.
	 *
	 * The registered value is a module revision integer rather than the library
	 * version. Bump it when a change to `src/VersionHistory/` should win an
	 * election. The library version is rewritten on every release and would make
	 * unrelated releases fight over the module.
	 *
	 * @since  [NEXT_VERSION]
	 * @return void
	 */
	function de_wpu_boot_version_history() {
		$elected = de_wpu_elect_version_history_copy( $GLOBALS['de_wpu_version_history_registry'] );

		if ( '' === $elected ) {
			return;
		}

		$path = $elected . '/src/VersionHistory';

		require_once $path . '/class-vh-options.php';
		require_once $path . '/class-vh-installer.php';
		require_once $path . '/class-vh-inventory.php';
		require_once $path . '/class-vh-source-detector.php';
		require_once $path . '/class-vh-event-recorder.php';
		require_once $path . '/class-vh-checkpoints.php';
		require_once $path . '/class-vh-history-query.php';
		require_once $path . '/class-version-history.php';

		\Dream_Encode\WordPress_Plugin_Utils\VersionHistory\Version_History::instance()->init();
	}
}

if ( defined( 'DE_WORDPRESS_PLUGIN_UTILS_VERSION' ) ) {
	return;
}

define( 'DE_WORDPRESS_PLUGIN_UTILS_VERSION', '1.9.5' );
define( 'DE_WORDPRESS_PLUGIN_UTILS_PATH', __DIR__ );

require_once DE_WORDPRESS_PLUGIN_UTILS_PATH . '/src/Common/class-functions.php';
require_once DE_WORDPRESS_PLUGIN_UTILS_PATH . '/src/Loaders/class-plugin-loader.php';
require_once DE_WORDPRESS_PLUGIN_UTILS_PATH . '/src/Data/class-object-data.php';
require_once DE_WORDPRESS_PLUGIN_UTILS_PATH . '/src/Abstracts/class-abstract-wc-logger.php';
require_once DE_WORDPRESS_PLUGIN_UTILS_PATH . '/src/Abstracts/class-abstract-plugin-i18n.php';
require_once DE_WORDPRESS_PLUGIN_UTILS_PATH . '/src/Abstracts/class-abstract-plugin-upgrader.php';
require_once DE_WORDPRESS_PLUGIN_UTILS_PATH . '/src/Abstracts/class-abstract-plugin-activator.php';
require_once DE_WORDPRESS_PLUGIN_UTILS_PATH . '/src/Abstracts/class-abstract-plugin-deactivator.php';
require_once DE_WORDPRESS_PLUGIN_UTILS_PATH . '/src/Assets/class-asset-manager.php';
require_once DE_WORDPRESS_PLUGIN_UTILS_PATH . '/src/Abstracts/class-abstract-rest-api.php';
require_once DE_WORDPRESS_PLUGIN_UTILS_PATH . '/src/Abstracts/class-abstract-rest-controller.php';
require_once DE_WORDPRESS_PLUGIN_UTILS_PATH . '/src/Abstracts/class-abstract-plugin.php';
require_once DE_WORDPRESS_PLUGIN_UTILS_PATH . '/src/Abstracts/class-abstract-data-migrator.php';
require_once DE_WORDPRESS_PLUGIN_UTILS_PATH . '/src/Abstracts/class-abstract-background-processor.php';
require_once DE_WORDPRESS_PLUGIN_UTILS_PATH . '/src/RestApi/class-rest-authentication.php';
require_once DE_WORDPRESS_PLUGIN_UTILS_PATH . '/src/RestApi/class-rest-response.php';
require_once DE_WORDPRESS_PLUGIN_UTILS_PATH . '/src/Settings/class-plugin-settings-repository.php';
require_once DE_WORDPRESS_PLUGIN_UTILS_PATH . '/src/Export/class-export-csv.php';
require_once DE_WORDPRESS_PLUGIN_UTILS_PATH . '/src/Upload/class-csv-upload.php';
