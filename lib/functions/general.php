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
		$settings_css   = plugin_dir_url( __FILE__ ) . 'lib/css/admin-settings.css';
		$current_screen = get_current_screen();
		$plugin_data    = get_plugin_data( UCSCCOMMS_PLUGIN_DIR . '/plugin.php' );
		$plugin_version = $plugin_data['Version'];
		if ( strpos( $current_screen->base, 'ucsc-communications-functionality-settings' ) === false ) {
			return;
		}
		wp_register_style( 'ucsccomms-cf-admin-settings', $settings_css, '', $plugin_version );
		wp_enqueue_style( 'ucsccomms-cf-admin-settings' );
	}
}
add_action( 'admin_enqueue_scripts', 'ucsccomms_enqueue_admin_styles' );

