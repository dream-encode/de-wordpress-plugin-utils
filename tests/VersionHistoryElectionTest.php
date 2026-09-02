<?php
/**
 * Tests for the Version History module election.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Tests;

use Dream_Encode\WordPress_Plugin_Utils\VersionHistory\Version_History;
use WP_UnitTestCase;

/**
 * Test case for the additive module election.
 *
 * Every consuming plugin bundles its own copy of this library, and the first one
 * WordPress loads declares the base classes for the whole request. The election
 * runs alongside that, without changing it, so the newest Version History module
 * on the site is the one that records regardless of which copy loaded first.
 */
class VersionHistoryElectionTest extends WP_UnitTestCase {

	/**
	 * The highest module revision wins.
	 */
	public function test_highest_revision_wins(): void {
		$elected = de_wpu_elect_version_history_copy(
			array(
				'/plugins/a/vendor/dream-encode/de-wordpress-plugin-utils' => 1,
				'/plugins/b/vendor/dream-encode/de-wordpress-plugin-utils' => 3,
				'/plugins/c/vendor/dream-encode/de-wordpress-plugin-utils' => 2,
			)
		);

		$this->assertSame( '/plugins/b/vendor/dream-encode/de-wordpress-plugin-utils', $elected );
	}

	/**
	 * Registration order does not decide the winner.
	 */
	public function test_registration_order_does_not_decide(): void {
		$elected = de_wpu_elect_version_history_copy(
			array(
				'/plugins/z/vendor/dream-encode/de-wordpress-plugin-utils' => 9,
				'/plugins/a/vendor/dream-encode/de-wordpress-plugin-utils' => 1,
			)
		);

		$this->assertSame( '/plugins/z/vendor/dream-encode/de-wordpress-plugin-utils', $elected );
	}

	/**
	 * Equal revisions are interchangeable, so the first registered is kept.
	 */
	public function test_equal_revisions_keep_the_first_registered(): void {
		$elected = de_wpu_elect_version_history_copy(
			array(
				'/plugins/a/vendor/dream-encode/de-wordpress-plugin-utils' => 2,
				'/plugins/b/vendor/dream-encode/de-wordpress-plugin-utils' => 2,
			)
		);

		$this->assertSame( '/plugins/a/vendor/dream-encode/de-wordpress-plugin-utils', $elected );
	}

	/**
	 * An empty registry elects nothing rather than erroring.
	 *
	 * This is the state on a site where every copy predates the module. Nothing
	 * loads, nothing records, and nothing breaks.
	 */
	public function test_empty_registry_elects_nothing(): void {
		$this->assertSame( '', de_wpu_elect_version_history_copy( array() ) );
	}

	/**
	 * A single registered copy wins by default.
	 */
	public function test_single_copy_wins(): void {
		$elected = de_wpu_elect_version_history_copy(
			array( '/plugins/only/vendor/dream-encode/de-wordpress-plugin-utils' => 1 )
		);

		$this->assertSame( '/plugins/only/vendor/dream-encode/de-wordpress-plugin-utils', $elected );
	}

	/**
	 * The bootstrap actually elected a copy and booted the module.
	 */
	public function test_module_booted_during_bootstrap(): void {
		$this->assertTrue( class_exists( Version_History::class ) );
		$this->assertTrue( isset( $GLOBALS['de_wpu_version_history_registry'] ) );
		$this->assertNotEmpty( $GLOBALS['de_wpu_version_history_registry'] );
	}

	/**
	 * The registered revision matches the module constant.
	 *
	 * These are declared in two files and have to stay in step, because the
	 * bootstrap cannot read the class constant without loading the class it is
	 * trying to decide about.
	 */
	public function test_registered_revision_matches_module_constant(): void {
		$revisions = array_values( $GLOBALS['de_wpu_version_history_registry'] );

		$this->assertSame( Version_History::MODULE_REVISION, $revisions[0] );
	}

	/**
	 * The library still defines its version constant.
	 *
	 * The election is additive: it must not disturb the existing bootstrap,
	 * which is the only loader for every other class in this library.
	 */
	public function test_existing_bootstrap_is_untouched(): void {
		$this->assertTrue( defined( 'DE_WORDPRESS_PLUGIN_UTILS_VERSION' ) );
		$this->assertTrue( defined( 'DE_WORDPRESS_PLUGIN_UTILS_PATH' ) );
		$this->assertTrue( class_exists( 'Dream_Encode\WordPress_Plugin_Utils\Abstracts\Abstract_Plugin' ) );
	}
}
