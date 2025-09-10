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
		<h1><?php echo $plugin_data['Name']; ?></h1>
		<h2>Version: <?php echo $plugin_data['Version']; ?> <a href="https://github.com/ucsc/ucsc-communications-functionality/releases">(release notes)</a></h2>
		<p><?php echo $plugin_data['Description']; ?></p>
		<hr>
		<h3>Features added by this plugin:</h3>
		<ul>
			<li><strong>Shortcodes:</strong>
				<ul>
					<li><code>[style-definition]</code>: Displays the style definitions for each Editorial Style Guide post type</li>
					<li><code>[style-archive]</code>: Displays a loop of the style guide posts on the Editorial Style Guide page</li>
				</ul>
			</li>
            <li><strong>ACF JSON:</strong> Saves and loads ACF field groups to/from the plugin's <code>acf-json</code> folder</li>
		</ul>
		</div>
		<?php
	}
}

