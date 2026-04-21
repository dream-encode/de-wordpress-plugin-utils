<?php
/**
 * Dummy upgrader functions for testing.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

defined( 'ABSPATH' ) || exit;

/**
 * Track which upgrade callbacks have been run.
 *
 * @var array<string, int>
 */
global $test_upgrade_callbacks_run;
$test_upgrade_callbacks_run = array();

/**
 * Dummy upgrade callback for version 2.
 *
 * @return bool
 */
function test_upgrader_update_200_dummy_callback(): bool {
	global $test_upgrade_callbacks_run;

	if ( ! isset( $test_upgrade_callbacks_run['test_upgrader_update_200_dummy_callback'] ) ) {
		$test_upgrade_callbacks_run['test_upgrader_update_200_dummy_callback'] = 0;
	}

	++$test_upgrade_callbacks_run['test_upgrader_update_200_dummy_callback'];

	return false;
}

/**
 * Dummy upgrade callback for version 3.
 *
 * @return bool
 */
function test_upgrader_update_300_dummy_callback(): bool {
	global $test_upgrade_callbacks_run;

	if ( ! isset( $test_upgrade_callbacks_run['test_upgrader_update_300_dummy_callback'] ) ) {
		$test_upgrade_callbacks_run['test_upgrader_update_300_dummy_callback'] = 0;
	}

	++$test_upgrade_callbacks_run['test_upgrader_update_300_dummy_callback'];

	return false;
}

/**
 * Dummy upgrade callback that needs to run again.
 *
 * @return bool
 */
function test_upgrader_update_needs_rerun_callback(): bool {
	global $test_upgrade_callbacks_run;

	if ( ! isset( $test_upgrade_callbacks_run['test_upgrader_update_needs_rerun_callback'] ) ) {
		$test_upgrade_callbacks_run['test_upgrader_update_needs_rerun_callback'] = 0;
	}

	++$test_upgrade_callbacks_run['test_upgrader_update_needs_rerun_callback'];

	return $test_upgrade_callbacks_run['test_upgrader_update_needs_rerun_callback'] < 3;
}

