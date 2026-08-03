<?php
/**
 * Tests for the ACF JSON save/load path filters.
 *
 * @package ucsc-communications-functionality
 */

namespace UCSC\UcscCommunicationsFunctionality\Tests;

/**
 * ACF JSON path filters.
 */
final class AcfJsonPathsTest extends TestCase {

	/**
	 * The save point is redirected to this plugin's acf-json directory.
	 *
	 * @return void
	 */
	public function test_save_point_returns_plugin_acf_json_dir(): void {
		$this->assertSame(
			UCSCCOMMS_PLUGIN_DIR . 'acf-json',
			ucsccomms_acf_json_save_point( '/some/theme/acf-json' )
		);
	}

	/**
	 * The load point drops ACF's default path and appends the plugin's.
	 *
	 * @return void
	 */
	public function test_load_point_drops_default_and_appends_plugin_dir(): void {
		$paths = ucsccomms_acf_json_load_point( array( '/some/theme/acf-json' ) );

		$this->assertNotContains( '/some/theme/acf-json', $paths );
		$this->assertContains( UCSCCOMMS_PLUGIN_DIR . 'acf-json', $paths );
	}

	/**
	 * Pin the unset( $paths[0] ) quirk: the result is a gapped array.
	 *
	 * The function removes key 0 rather than reindexing, so the returned array
	 * is not a list. Nothing downstream depends on that today. This test exists
	 * so that changing it is a deliberate decision rather than a surprise.
	 *
	 * @return void
	 */
	public function test_load_point_leaves_a_gapped_array(): void {
		$paths = ucsccomms_acf_json_load_point( array( '/a', '/b' ) );

		$this->assertSame(
			array(
				1 => '/b',
				2 => UCSCCOMMS_PLUGIN_DIR . 'acf-json',
			),
			$paths
		);
	}

	/**
	 * Pin the second half of the quirk: no key 0 means nothing is dropped.
	 *
	 * @return void
	 */
	public function test_load_point_drops_nothing_when_key_zero_is_absent(): void {
		$paths = ucsccomms_acf_json_load_point( array( 5 => '/kept' ) );

		$this->assertContains( '/kept', $paths );
		$this->assertContains( UCSCCOMMS_PLUGIN_DIR . 'acf-json', $paths );
	}
}
