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

define( 'DE_WORDPRESS_PLUGIN_UTILS_VERSION', '1.0.0' );
define( 'DE_WORDPRESS_PLUGIN_UTILS_PATH', __DIR__ );

require_once __DIR__ . '/src/Abstracts/class-abstract-wc-logger.php';
require_once __DIR__ . '/src/Abstracts/class-abstract-plugin-upgrader.php';
require_once __DIR__ . '/src/Abstracts/class-abstract-rest-api.php';
require_once __DIR__ . '/src/Abstracts/class-abstract-rest-controller.php';

