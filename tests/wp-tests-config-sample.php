<?php
/**
 * WordPress test configuration sample.
 *
 * Copy this file to wp-tests-config.php and update the values.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

/**
 * Path to the WordPress codebase to test.
 *
 * This must be a WordPress installation. Options:
 * 1. Clone wordpress-develop and use the build folder: /path/to/wordpress-develop/build/
 * 2. Download WordPress to a test location: /path/to/wordpress-test/
 * 3. Use an existing WordPress installation (not recommended for production sites)
 *
 * To set up wordpress-develop:
 *   git clone https://github.com/WordPress/wordpress-develop.git
 *   cd wordpress-develop
 *   npm install
 *   npm run build
 */
define( 'ABSPATH', '/path/to/wordpress/' );

/**
 * Database settings.
 *
 * WARNING: Use a dedicated test database. All data will be deleted during tests!
 */
define( 'DB_NAME', 'wordpress_test' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

/**
 * WordPress test domain.
 */
define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );

/**
 * PHP path for multisite.
 */
define( 'WP_PHP_BINARY', 'php' );

/**
 * WordPress debug mode.
 */
define( 'WP_DEBUG', true );

/**
 * WordPress table prefix.
 */
$table_prefix = 'wptests_';

