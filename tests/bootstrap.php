<?php
/**
 * PHPUnit bootstrap.
 *
 * @package ucsc-communications-functionality
 */

// MUST come first: every plugin file opens with `defined( 'ABSPATH' ) || exit;`,
// so including one without this silently exits and takes the test runner with it.
define( 'ABSPATH', dirname( __DIR__ ) . '/' );

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
require_once __DIR__ . '/wp-stubs.php';

ucsccomms_test_reset();

// Include the real plugin bootstrap exactly once. plugin.php and shortcodes.php
// have no function_exists() guards, so a second include is fatal. This defines
// the UCSCCOMMS_* constants, pulls in the three lib files, and fires every
// add_action/add_filter/add_shortcode into the recording registry.
require_once dirname( __DIR__ ) . '/plugin.php';

// Keep the include-time registrations; drop the transient state they wrote.
ucsccomms_test_reset( true );
