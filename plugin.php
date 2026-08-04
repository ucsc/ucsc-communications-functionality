<?php
/**
 * Plugin Name:       UCSC Communications Custom Functionality
 * Plugin URI:        https://github.com/ucsc/ucsc-communications-functionality
 * Description:       Custom functionality for UCSC Communications and Marketing Website.
 * Version:           1.0.5
 * Requires at least: 6.1
 * Requires PHP:      7.4
 * Author:            UC Santa Cruz
 * Author URI:        https://github.com/ucsc
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Update URI:        https://github.com/ucsc/ucsc-communications-functionality/releases
 * Requires Plugins:  advanced-custom-fields-pro
 * Text Domain:       ucsccomms
 *
 * @package           ucsc-communications-functionality
 */

defined( 'ABSPATH' ) || exit;

// Set plugin directory, URL and base name.
define( 'UCSCCOMMS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) ); // Path to plugin directory.
define( 'UCSCCOMMS_PLUGIN_URL', plugin_dir_url( __FILE__ ) ); // URL of plugin directory, for enqueuing assets.
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
 * Point ACF at this plugin's acf-json directory when saving field groups.
 *
 * @param string $path Default save path supplied by ACF.
 * @return string Path to this plugin's acf-json directory.
 */
function ucsccomms_acf_json_save_point( $path ) {
	$path = UCSCCOMMS_PLUGIN_DIR . 'acf-json';
	return $path;
}
// Set plugin directory for saving ACF JSON files.
add_filter( 'acf/settings/save_json', 'ucsccomms_acf_json_save_point' );

/**
 * Point ACF at this plugin's acf-json directory when loading field groups.
 *
 * Drops ACF's default load path so field groups come from this plugin rather
 * than the active theme.
 *
 * @param array $paths Default load paths supplied by ACF.
 * @return array Load paths with this plugin's acf-json directory appended.
 */
function ucsccomms_acf_json_load_point( $paths ) {
	unset( $paths[0] );
	$paths[] = UCSCCOMMS_PLUGIN_DIR . 'acf-json';
	return $paths;
}
// Set plugin directory for loading ACF JSON files.
add_filter( 'acf/settings/load_json', 'ucsccomms_acf_json_load_point' );
