<?php
/**
 * Tests for the admin stylesheet enqueue.
 *
 * @package ucsc-communications-functionality
 */

namespace UCSC\UcscCommunicationsFunctionality\Tests;

/**
 * Admin stylesheet enqueue.
 */
final class AdminStylesTest extends TestCase {

	/**
	 * Put us on the plugin's settings screen.
	 *
	 * @return void
	 */
	private function on_settings_screen(): void {
		ucsccomms_test_set_screen(
			new \WP_Screen( 'settings_page_ucsc-communications-functionality-settings' )
		);
	}

	/**
	 * The stylesheet URL resolves from the plugin root, not from lib/functions/.
	 *
	 * Guards ROADMAP item 1: the enqueue previously built its URL from __FILE__
	 * inside lib/functions/, so the path picked up a lib/functions/ segment and
	 * the stylesheet 404'd.
	 *
	 * @return void
	 */
	public function test_admin_stylesheet_url_resolves_from_plugin_root(): void {
		$this->on_settings_screen();

		ucsccomms_enqueue_admin_styles();

		$style = $GLOBALS['ucsccomms_test']['styles']['ucsccomms-cf-admin-settings'];

		$this->assertSame( UCSCCOMMS_PLUGIN_URL . 'lib/css/admin-settings.css', $style['src'] );
		$this->assertStringNotContainsString( 'lib/functions', $style['src'] );
	}

	/**
	 * The stylesheet is versioned with the plugin version, for cache busting.
	 *
	 * @return void
	 */
	public function test_admin_stylesheet_is_versioned(): void {
		$this->on_settings_screen();

		ucsccomms_enqueue_admin_styles();

		$style = $GLOBALS['ucsccomms_test']['styles']['ucsccomms-cf-admin-settings'];

		$this->assertSame( '9.9.9', $style['ver'] );
	}

	/**
	 * Nothing is enqueued on other admin screens.
	 *
	 * @return void
	 */
	public function test_no_styles_enqueued_on_other_admin_screens(): void {
		ucsccomms_test_set_screen( new \WP_Screen( 'edit-post' ) );

		ucsccomms_enqueue_admin_styles();

		$this->assertSame( array(), $GLOBALS['ucsccomms_test']['enqueued'] );
		$this->assertSame( array(), $GLOBALS['ucsccomms_test']['styles'] );
	}

	/**
	 * The function bails when there is no screen at all.
	 *
	 * get_current_screen() returns null outside a real admin screen, and the
	 * instanceof check is what stops a fatal on ->base.
	 *
	 * @return void
	 */
	public function test_no_styles_enqueued_when_there_is_no_screen(): void {
		ucsccomms_test_set_screen( null );

		ucsccomms_enqueue_admin_styles();

		$this->assertSame( array(), $GLOBALS['ucsccomms_test']['enqueued'] );
	}
}
