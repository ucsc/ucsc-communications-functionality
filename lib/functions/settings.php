<?php
/**
 * Add Plugin settings and info page
 *
 * This file contains functions to add a settings/info page below WordPress Settings menu
 *
 * @package      ucsc-communications-functionality
 * @since        1.7.0
 * @link         https://github.com/ucsc/ucsc-communications-functionality.git
 * @author       UC Santa Cruz
 * @license      http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/** Register new menu and page */

if ( ! function_exists( 'ucsccomms_add_settings_page' ) ) {
	function ucsccomms_add_settings_page() {
		add_options_page( 'UCSC Communications Functionality plugin page', 'UCSC Communications Functionality', 'manage_options', 'ucsc-communications-functionality-settings', 'ucsccomms_render_plugin_settings_page' );
	}
}
add_action( 'admin_menu', 'ucsccomms_add_settings_page' );


/** 
 * HTML output of Settings page 
 *
 * note: This page typically displays a form for displaying any settings options. 
 * It is not needed at this point.
 * https://developer.wordpress.org/plugins/settings/custom-settings-page/
 *
 */

if ( ! function_exists( 'ucsccomms_render_plugin_settings_page' ) ) {
	function ucsccomms_render_plugin_settings_page() {
		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/ucsc-communications-functionality/plugin.php');
		?>
		<div class="wrap cf-admin-settings-page">
		<h1><?php echo esc_html( $plugin_data['Name'] ); ?></h1>
		<h2>Version: <?php echo esc_html( $plugin_data['Version'] ); ?> <a href="https://github.com/ucsc/ucsc-communications-functionality/releases">(release notes)</a></h2>
		<p><?php echo wp_kses_post( $plugin_data['Description'] ); ?></p>
		<hr>
		<h3>Features added by this plugin</h3>
		<h4>Shortcodes</h4>
		<ul>
			<li><code>[style-definition]</code> — Displays the style definitions for each Editorial Style Guide post.</li>
			<li><code>[style-archive]</code> — Displays a loop of all Editorial Style Guide posts on an archive page.</li>
		</ul>
		<h4>ACF JSON</h4>
		<p>Field group definitions are saved to and loaded from the plugin's <code>acf-json</code> folder, keeping them version-controlled rather than stored in the database.</p>
		</div>
		<?php
	}
}

