<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

// Check for config file.
$_config_file = __DIR__ . '/wp-tests-config.php';

if ( ! file_exists( $_config_file ) ) {
	echo 'Could not find tests/wp-tests-config.php. Please copy tests/wp-tests-config-sample.php to tests/wp-tests-config.php and update the values.' . PHP_EOL;
	exit( 1 );
}

// Get tests directory - wp-phpunit package location.
$_tests_dir = dirname( __DIR__ ) . '/vendor/wp-phpunit/wp-phpunit';

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php." . PHP_EOL;
	echo 'Please run composer install.' . PHP_EOL;
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( __DIR__ ) . '/de-wordpress-plugin-utils.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
ob_start();
require "{$_tests_dir}/includes/bootstrap.php";
ob_end_clean();

// Load test fixtures.
require_once __DIR__ . '/Fixtures/class-test-logger.php';
require_once __DIR__ . '/Fixtures/class-test-plugin-loader.php';
require_once __DIR__ . '/Fixtures/class-test-plugin-i18n.php';
require_once __DIR__ . '/Fixtures/class-test-object-data.php';
require_once __DIR__ . '/Fixtures/class-test-plugin-upgrader.php';
require_once __DIR__ . '/Fixtures/class-test-plugin-activator.php';
require_once __DIR__ . '/Fixtures/class-test-plugin-deactivator.php';
require_once __DIR__ . '/Fixtures/class-test-asset-manager.php';
require_once __DIR__ . '/Fixtures/class-test-rest-api.php';
require_once __DIR__ . '/Fixtures/class-test-rest-controller.php';
require_once __DIR__ . '/Fixtures/class-test-plugin.php';
require_once __DIR__ . '/Fixtures/class-test-data-migrator.php';
require_once __DIR__ . '/Fixtures/class-test-background-processor.php';

