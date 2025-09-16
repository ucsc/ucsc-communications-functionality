<?php
/**
 * Plugin Name:       UCSC Communications Custom Functionality
 * Plugin URI:        https://github.com/ucsc/ucsc-communications-functionality
 * Description:       Custom functionality for UCSC Communications and Marketing Website.
 * Version:           1.0.2
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Author:            UC Santa Cruz
 * Author URI:        https://github.com/ucsc 
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Update URI:       https://github.com/ucsc/ucsc-communications-functionality-plugin/releases
 * Requires Plugins:  advanced-custom-fields-pro
 * Text Domain:       ucsccomms
 *
 * @package           ucsc-communications-functionality
 */

// Set plugin directory and base name.
define( 'UCSCCOMMS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) ); // Path to plugin directory.
define( 'UCSCCOMMS_PLUGIN_BASE', plugin_basename( __FILE__ ) ); // Plugin base name 'plugin.php' at root.

// Include general functions.
if ( file_exists( UCSCCOMMS_PLUGIN_DIR . 'lib/functions/general.php' ) ) {
	require_once UCSCCOMMS_PLUGIN_DIR . 'lib/functions/general.php';
}
// Include settings.
if ( file_exists( UCSCCOMMS_PLUGIN_DIR . '/lib/functions/settings.php' ) ) {
	include_once UCSCCOMMS_PLUGIN_DIR . '/lib/functions/settings.php';
}
// Include shortcodes.
if ( file_exists( UCSCCOMMS_PLUGIN_DIR . '/lib/functions/shortcodes.php' ) ) {
	include_once UCSCCOMMS_PLUGIN_DIR . '/lib/functions/shortcodes.php';
}

/**
 * ACF JSON Save Point
 *
 * @param [type] $path
 * @return $path
 * @package ucsc-communications-functionality
 */
function ucsccomms_acf_json_save_point( $path ) {
	$path = UCSCCOMMS_PLUGIN_DIR . 'acf-json';
	return $path;
}
// Set plugin directory for saving ACF JSON files.
add_filter( 'acf/settings/save_json', 'ucsccomms_acf_json_save_point' );

/**
 * ACF JSON Load Point
 *
 * @param [type] $paths
 * @return $paths
 * @package ucsc-giving-functionality
 */
function ucsccomms_acf_json_load_point( $paths ) {
	unset( $paths[0] );
	$paths[] = UCSCCOMMS_PLUGIN_DIR . 'acf-json';
	return $paths;
}
// Set plugin directory for loading ACF JSON files.
add_filter( 'acf/settings/load_json', 'ucsccomms_acf_json_load_point' );