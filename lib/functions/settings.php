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
		$plugin_file = UCSCCOMMS_PLUGIN_DIR . 'plugin.php';
		$plugin_data = file_exists( $plugin_file ) ? get_plugin_data( $plugin_file ) : array();
		$plugin_name    = ! empty( $plugin_data['Name'] ) ? $plugin_data['Name'] : '';
		$plugin_version = ! empty( $plugin_data['Version'] ) ? $plugin_data['Version'] : '';
		$plugin_desc    = ! empty( $plugin_data['Description'] ) ? $plugin_data['Description'] : '';
		?>
		<div class="wrap cf-admin-settings-page">
		<h1><?php echo esc_html( $plugin_name ); ?></h1>
		<h2><?php echo esc_html( __( 'Version:', 'ucsccomms' ) ); ?> <?php echo esc_html( $plugin_version ); ?> <a href="<?php echo esc_url( 'https://github.com/ucsc/ucsc-communications-functionality/releases' ); ?>"><?php echo esc_html( __( '(release notes)', 'ucsccomms' ) ); ?></a></h2>
		<p><?php echo wp_kses_post( $plugin_desc ); ?></p>
		<hr>
		<h3><?php echo esc_html( __( 'Features added by this plugin', 'ucsccomms' ) ); ?></h3>
		<h4><?php echo esc_html( __( 'Shortcodes', 'ucsccomms' ) ); ?></h4>
		<ul>
			<li><code>[style-definition]</code> — <?php echo esc_html( __( 'Displays the style definitions for each Editorial Style Guide post.', 'ucsccomms' ) ); ?></li>
			<li><code>[style-archive]</code> — <?php echo esc_html( __( 'Displays a loop of all Editorial Style Guide posts on an archive page.', 'ucsccomms' ) ); ?></li>
		</ul>
		<h4><?php echo esc_html( __( 'ACF JSON', 'ucsccomms' ) ); ?></h4>
		<p><?php
			echo wp_kses(
				sprintf(
					/* translators: %s: acf-json folder name in code tags */
					__( "Field group definitions are saved to and loaded from the plugin's %s folder, which supports version control and syncing alongside ACF's database storage.", 'ucsccomms' ),
					'<code>acf-json</code>'
				),
				array( 'code' => array() )
			);
		?></p>
		</div>
		<?php
	}
}

