<?php
/**
 * Registration-contract tests.
 *
 * These assert against the hook registry captured when the bootstrap included
 * plugin.php. A rename that misses one side of a registration is silent in
 * production - a shortcode simply renders as literal text.
 *
 * @package ucsc-communications-functionality
 */

namespace UCSC\UcscCommunicationsFunctionality\Tests;

/**
 * Hook, shortcode and settings-page registration.
 */
final class RegistrationTest extends TestCase {

	/**
	 * Both shortcodes are registered against their documented callbacks.
	 *
	 * @return void
	 */
	public function test_shortcodes_are_registered_with_expected_callbacks(): void {
		$shortcodes = $GLOBALS['ucsccomms_test']['hooks']['shortcode'];

		$this->assertSame( 'ucsccomms_a_z_style_guide_single_loop', $shortcodes['style-definition'] );
		$this->assertSame( 'ucsccomms_a_z_styles_archive_loop', $shortcodes['style-archive'] );
	}

	/**
	 * The ACF JSON filters are wired up.
	 *
	 * @return void
	 */
	public function test_acf_json_filters_are_registered(): void {
		$filters = $GLOBALS['ucsccomms_test']['hooks']['filter'];

		$this->assertContains( 'ucsccomms_acf_json_save_point', $filters['acf/settings/save_json'] );
		$this->assertContains( 'ucsccomms_acf_json_load_point', $filters['acf/settings/load_json'] );
	}

	/**
	 * The admin hooks are wired up.
	 *
	 * @return void
	 */
	public function test_admin_actions_are_registered(): void {
		$actions = $GLOBALS['ucsccomms_test']['hooks']['action'];

		$this->assertContains( 'ucsccomms_add_settings_page', $actions['admin_menu'] );
		$this->assertContains( 'ucsccomms_enqueue_admin_styles', $actions['admin_enqueue_scripts'] );
	}

	/**
	 * The settings page is gated on manage_options.
	 *
	 * @return void
	 */
	public function test_settings_page_requires_manage_options(): void {
		ucsccomms_add_settings_page();

		$calls = ucsccomms_test_calls_to( 'add_options_page' );

		$this->assertCount( 1, $calls );
		$this->assertSame( 'manage_options', $calls[0][2] );
		$this->assertSame( 'ucsccomms_render_plugin_settings_page', $calls[0][4] );
	}

	/**
	 * The settings slug and the stylesheet screen check must agree.
	 *
	 * settings.php passes the literal 'ucsc-communications-functionality-settings'
	 * to add_options_page(), and general.php strpos()-matches the *same literal*
	 * against $screen->base. Two independent string literals in two files, with
	 * no shared constant tying them together - so a rename on one side silently
	 * stops the admin stylesheet from loading.
	 *
	 * @return void
	 */
	public function test_settings_slug_matches_admin_style_screen_check(): void {
		ucsccomms_add_settings_page();

		$calls = ucsccomms_test_calls_to( 'add_options_page' );
		$slug  = $calls[0][3];

		// Build the screen base WordPress derives from that slug, then confirm
		// the enqueue function recognises it.
		ucsccomms_test_set_screen( new \WP_Screen( 'settings_page_' . $slug ) );
		ucsccomms_enqueue_admin_styles();

		$this->assertContains( 'ucsccomms-cf-admin-settings', $GLOBALS['ucsccomms_test']['enqueued'] );
	}
}
