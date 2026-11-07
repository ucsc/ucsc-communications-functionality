<?php
/**
 * Double-inclusion tests.
 *
 * A second copy of the plugin in another directory loads plugin.php again
 * under a different path, so require_once does not stop it. These tests
 * include the files a second time and assert that nothing breaks.
 *
 * @package ucsc-communications-functionality
 */

namespace UCSC\UcscCommunicationsFunctionality\Tests;

/**
 * plugin.php and shortcodes.php survive a second include.
 */
final class DoubleInclusionTest extends TestCase {

	/**
	 * The hook registry as the bootstrap recorded it.
	 *
	 * @var array
	 */
	private $hooks;

	/**
	 * Save the hook registry, because TestCase keeps it between tests and
	 * the add_action/add_filter doubles append rather than dedupe.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->hooks = $GLOBALS['ucsccomms_test']['hooks'];
	}

	/**
	 * Restore the hook registry for the tests that run after this one.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$GLOBALS['ucsccomms_test']['hooks'] = $this->hooks;

		parent::tearDown();
	}

	/**
	 * A second include of plugin.php returns before it registers anything.
	 *
	 * @return void
	 */
	public function test_second_include_of_plugin_php_is_a_no_op(): void {
		include dirname( __DIR__ ) . '/plugin.php';

		$this->assertSame( $this->hooks, $GLOBALS['ucsccomms_test']['hooks'] );
	}

	/**
	 * A second include of shortcodes.php does not redeclare its functions.
	 *
	 * @return void
	 */
	public function test_second_include_of_shortcodes_php_is_safe(): void {
		include dirname( __DIR__ ) . '/lib/functions/shortcodes.php';

		$shortcodes = $GLOBALS['ucsccomms_test']['hooks']['shortcode'];

		$this->assertSame( 'ucsccomms_a_z_style_guide_single_loop', $shortcodes['style-definition'] );
		$this->assertSame( 'ucsccomms_a_z_styles_archive_loop', $shortcodes['style-archive'] );
	}
}
