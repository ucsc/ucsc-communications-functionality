<?php
/**
 * General Functions
 *
 * This file contains general functions for the UCSC Communications Functionality plugin.
 *
 * @package      ucsc-communications-functionality
 * @since        1.7.0
 * @link         https://github.com/ucsc/ucsc-communications-functionality.git
 * @author       UC Santa Cruz
 * @license      http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Enqueue admin settings styles.
if ( ! function_exists( 'ucsccomms_enqueue_admin_styles' ) ) {
	/**
	 * Enqueue admin settings styles
	 *
	 * @since 0.5.0
	 * @author UCSC
	 * @package ucsc-communications-functionality
	 */
	function ucsccomms_enqueue_admin_styles(): void {
		$current_screen = get_current_screen();

		// get_current_screen() returns null outside a real admin screen.
		if ( ! $current_screen instanceof WP_Screen ) {
			return;
		}

		// Bail before doing any work on every other admin page.
		if ( strpos( $current_screen->base, 'ucsc-communications-functionality-settings' ) === false ) {
			return;
		}

		$plugin_data    = get_plugin_data( UCSCCOMMS_PLUGIN_DIR . 'plugin.php' );
		$plugin_version = $plugin_data['Version'];
		$settings_css   = UCSCCOMMS_PLUGIN_URL . 'lib/css/admin-settings.css';

		wp_register_style( 'ucsccomms-cf-admin-settings', $settings_css, array(), $plugin_version );
		wp_enqueue_style( 'ucsccomms-cf-admin-settings' );
	}
}
add_action( 'admin_enqueue_scripts', 'ucsccomms_enqueue_admin_styles' );
