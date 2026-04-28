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

if ( defined( 'DE_WORDPRESS_PLUGIN_UTILS_VERSION' ) ) {
	return;
}

define( 'DE_WORDPRESS_PLUGIN_UTILS_VERSION', '1.6.0' );
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
