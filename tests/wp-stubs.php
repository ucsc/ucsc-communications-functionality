<?php
/**
 * Minimal WordPress + ACF doubles for the unit suite.
 *
 * The plugin is procedural and calls WordPress and ACF functions directly, with
 * no injection points. These doubles stand in for the small set of functions it
 * actually touches, so the suite runs with no WordPress install, no database and
 * no ACF Pro.
 *
 * Two design decisions are load-bearing and documented at their definitions:
 *
 * - have_rows()/the_row() are a stateful iterator pair. have_rows() peeks and
 *   never advances; only the_row() moves the cursor. A double that returns a
 *   constant true would loop forever.
 * - esc_html() is faithful, wp_kses_post() is a pass-through spy. The suite
 *   asserts the escape-before-concatenate *contract*, not real KSES filtering.
 *
 * @package ucsc-communications-functionality
 */

// -----------------------------------------------------------------------------
// Shared state.
// -----------------------------------------------------------------------------

/**
 * Reset every double to a clean slate.
 *
 * @param bool $preserve_hooks Keep the hook registry recorded at include time.
 * @return void
 */
function ucsccomms_test_reset( $preserve_hooks = false ) {
	$hooks = $preserve_hooks && isset( $GLOBALS['ucsccomms_test']['hooks'] )
		? $GLOBALS['ucsccomms_test']['hooks']
		: array();

	$GLOBALS['ucsccomms_test'] = array(
		'hooks'          => $hooks,   // Recorded add_action/add_filter/add_shortcode.
		'rows'           => array(),  // Repeater rows for the current post context.
		'cursor'         => -1,       // Current row index; -1 = before the first row.
		'guard'          => 0,        // Runaway-loop tripwire.
		'posts'          => array(),  // Fake WP_Query result set.
		'post_i'         => -1,
		'title'          => '',
		'calls'          => array(),  // Spy log.
		'query_args'     => array(),
		'styles'         => array(),
		'enqueued'       => array(),
		'screen'         => null,
		'reset_postdata' => 0,
	);

	unset( $GLOBALS['post'] );
}

/**
 * Record a spied call.
 *
 * @param string $fn      Function name.
 * @param mixed  ...$args Arguments the function received.
 * @return void
 */
function ucsccomms_test_record( $fn, ...$args ) {
	$GLOBALS['ucsccomms_test']['calls'][] = array_merge( array( $fn ), $args );
}

/**
 * Every recorded call to a function, as a list of argument arrays.
 *
 * @param string $fn Function name.
 * @return array
 */
function ucsccomms_test_calls_to( $fn ) {
	$matched = array_filter(
		$GLOBALS['ucsccomms_test']['calls'],
		static function ( $call ) use ( $fn ) {
			return $call[0] === $fn;
		}
	);

	return array_values(
		array_map(
			static function ( $call ) {
				return array_slice( $call, 1 );
			},
			$matched
		)
	);
}

/**
 * Seed the repeater rows for the single-post case.
 *
 * @param array $rows List of row arrays keyed by sub-field name.
 * @return void
 */
function ucsccomms_test_set_rows( array $rows ) {
	$GLOBALS['ucsccomms_test']['rows']   = $rows;
	$GLOBALS['ucsccomms_test']['cursor'] = -1;
	$GLOBALS['ucsccomms_test']['guard']  = 0;
}

/**
 * Seed the archive result set.
 *
 * @param array $posts List of arrays with 'title' and 'rows' keys.
 * @return void
 */
function ucsccomms_test_set_posts( array $posts ) {
	$GLOBALS['ucsccomms_test']['posts']  = $posts;
	$GLOBALS['ucsccomms_test']['post_i'] = -1;
}

/**
 * Set the current admin screen.
 *
 * @param mixed $screen A WP_Screen instance, or null for "not an admin screen".
 * @return void
 */
function ucsccomms_test_set_screen( $screen ) {
	$GLOBALS['ucsccomms_test']['screen'] = $screen;
}

// -----------------------------------------------------------------------------
// ACF repeater iterator.
// -----------------------------------------------------------------------------

/**
 * Is there a NEXT row?
 *
 * Peeks; never advances. This mirrors ACF, where the shortcode's `if` and its
 * `while` both call have_rows() and only the_row() moves the pointer. A double
 * that advanced here would consume every other row.
 *
 * @param string $selector Field name.
 * @param mixed  $post_id  Unused; present for signature fidelity.
 * @return bool
 */
function have_rows( $selector, $post_id = false ) {
	$state = &$GLOBALS['ucsccomms_test'];

	// Tripwire: if the production loop ever drops the_row(), the cursor stops
	// advancing and this spins forever. Fail loudly rather than hanging CI.
	++$state['guard'];
	if ( $state['guard'] > 1000 ) {
		throw new RuntimeException(
			'have_rows() called 1000 times without the cursor advancing - the_row() is probably missing from the loop body.'
		);
	}

	ucsccomms_test_record( 'have_rows', $selector );

	// A typo'd selector must fail loudly, not silently render an empty string.
	if ( 'style_definitions' !== $selector ) {
		return false;
	}

	return ( $state['cursor'] + 1 ) < count( $state['rows'] );
}

/**
 * Advance the cursor to the next row.
 *
 * @param bool $format Unused; present for signature fidelity.
 * @return array|false The row now under the cursor.
 */
function the_row( $format = false ) {
	$state = &$GLOBALS['ucsccomms_test'];

	++$state['cursor'];
	$state['guard'] = 0;

	return isset( $state['rows'][ $state['cursor'] ] ) ? $state['rows'][ $state['cursor'] ] : false;
}

/**
 * Read a sub-field from the row the cursor is parked on.
 *
 * @param string $selector     Sub-field name.
 * @param bool   $format_value Unused; present for signature fidelity.
 * @return mixed
 */
function get_sub_field( $selector, $format_value = true ) {
	$state = $GLOBALS['ucsccomms_test'];

	ucsccomms_test_record( 'get_sub_field', $selector );

	$row = isset( $state['rows'][ $state['cursor'] ] ) ? $state['rows'][ $state['cursor'] ] : array();

	return isset( $row[ $selector ] ) ? $row[ $selector ] : false;
}

// -----------------------------------------------------------------------------
// Escaping.
// -----------------------------------------------------------------------------

/**
 * Faithful double plus a spy.
 *
 * Behaves closely enough to the real thing that deleting esc_html() from the
 * production concatenation shows up directly in an output assertion.
 *
 * @param mixed $text Value to escape.
 * @return string
 */
function esc_html( $text ) {
	ucsccomms_test_record( 'esc_html', $text );

	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

/**
 * Pass-through spy - deliberately does NOT filter.
 *
 * The real KSES allowlist is not reproducible here, and reproducing it is not
 * the point. The regression to catch is "the WYSIWYG value was concatenated
 * without going through an escaper at all". Returning the input unchanged means
 * that regression fails the call assertion, independently of the output
 * assertion that guards esc_html(). Do not read this suite as proof of XSS
 * safety - it proves the escape-before-concatenate contract only.
 *
 * @param mixed $data Value that should have been filtered.
 * @return mixed The input, unchanged.
 */
function wp_kses_post( $data ) {
	ucsccomms_test_record( 'wp_kses_post', $data );

	return $data;
}

/**
 * Pass-through double.
 *
 * @param mixed $data              Value.
 * @param mixed $allowed_html      Unused.
 * @param array $allowed_protocols Unused.
 * @return mixed
 */
function wp_kses( $data, $allowed_html, $allowed_protocols = array() ) {
	ucsccomms_test_record( 'wp_kses', $data );

	return $data;
}

/**
 * Pass-through double.
 *
 * @param string $url URL.
 * @return string
 */
function esc_url( $url ) {
	return $url;
}

/**
 * Attribute-escaping double.
 *
 * @param mixed $text Value to escape.
 * @return string
 */
function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

/**
 * Translation double.
 *
 * @param string $text   Text.
 * @param string $domain Unused.
 * @return string
 */
function __( $text, $domain = 'default' ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames
	return $text;
}

// -----------------------------------------------------------------------------
// Hook registry.
// -----------------------------------------------------------------------------

/**
 * Record an action registration.
 *
 * @param string   $hook          Hook name.
 * @param callable $callback      Callback.
 * @param int      $priority      Unused.
 * @param int      $accepted_args Unused.
 * @return bool
 */
function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['ucsccomms_test']['hooks']['action'][ $hook ][] = $callback;

	return true;
}

/**
 * Record a filter registration.
 *
 * @param string   $hook          Hook name.
 * @param callable $callback      Callback.
 * @param int      $priority      Unused.
 * @param int      $accepted_args Unused.
 * @return bool
 */
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['ucsccomms_test']['hooks']['filter'][ $hook ][] = $callback;

	return true;
}

/**
 * Record a shortcode registration.
 *
 * @param string   $tag      Shortcode tag.
 * @param callable $callback Callback.
 * @return void
 */
function add_shortcode( $tag, $callback ) {
	$GLOBALS['ucsccomms_test']['hooks']['shortcode'][ $tag ] = $callback;
}

// -----------------------------------------------------------------------------
// Plugin path helpers.
// -----------------------------------------------------------------------------

/**
 * Path to the directory holding a file, with a trailing slash.
 *
 * @param string $file File path.
 * @return string
 */
function plugin_dir_path( $file ) {
	return rtrim( dirname( $file ), '/' ) . '/';
}

/**
 * URL of the directory holding a file, with a trailing slash.
 *
 * @param string $file File path.
 * @return string
 */
function plugin_dir_url( $file ) {
	return 'https://example.test/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
}

/**
 * Plugin basename.
 *
 * @param string $file File path.
 * @return string
 */
function plugin_basename( $file ) {
	return basename( dirname( $file ) ) . '/' . basename( $file );
}

/**
 * Plugin header data double.
 *
 * @param string $file      Plugin file.
 * @param bool   $markup    Unused.
 * @param bool   $translate Unused.
 * @return array
 */
function get_plugin_data( $file, $markup = true, $translate = true ) {
	ucsccomms_test_record( 'get_plugin_data', $file );

	return array(
		'Name'        => 'UCSC Communications Custom Functionality',
		'Version'     => '9.9.9',
		'Description' => 'Fixture description.',
	);
}

// -----------------------------------------------------------------------------
// Admin.
// -----------------------------------------------------------------------------

/**
 * Minimal WP_Screen stand-in.
 */
class WP_Screen {

	/**
	 * Screen base.
	 *
	 * @var string
	 */
	public $base = '';

	/**
	 * Constructor.
	 *
	 * @param string $base Screen base.
	 */
	public function __construct( $base = '' ) {
		$this->base = $base;
	}
}

/**
 * Current screen double.
 *
 * @return WP_Screen|null
 */
function get_current_screen() {
	return $GLOBALS['ucsccomms_test']['screen'];
}

/**
 * Options-page registration spy.
 *
 * @param string   $page_title Page title.
 * @param string   $menu_title Menu title.
 * @param string   $capability Required capability.
 * @param string   $slug       Menu slug.
 * @param callable $callback   Render callback.
 * @return string
 */
function add_options_page( $page_title, $menu_title, $capability, $slug, $callback ) {
	ucsccomms_test_record( 'add_options_page', $page_title, $menu_title, $capability, $slug, $callback );

	return 'settings_page_' . $slug;
}

/**
 * Style-registration spy.
 *
 * @param string $handle Handle.
 * @param string $src    Source URL.
 * @param array  $deps   Dependencies.
 * @param mixed  $ver    Version.
 * @param string $media  Media.
 * @return bool
 */
function wp_register_style( $handle, $src, $deps = array(), $ver = false, $media = 'all' ) {
	$GLOBALS['ucsccomms_test']['styles'][ $handle ] = array(
		'src'   => $src,
		'deps'  => $deps,
		'ver'   => $ver,
		'media' => $media,
	);

	return true;
}

/**
 * Style-enqueue spy.
 *
 * @param string $handle Handle.
 * @param string $src    Unused.
 * @param array  $deps   Unused.
 * @param mixed  $ver    Unused.
 * @param string $media  Unused.
 * @return void
 */
function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
	$GLOBALS['ucsccomms_test']['enqueued'][] = $handle;
}

// -----------------------------------------------------------------------------
// Query.
// -----------------------------------------------------------------------------

/**
 * Minimal WP_Query stand-in.
 *
 * The archive shortcode does `new \WP_Query( $args )` with no injection point.
 * PHP resolves class names at runtime, so defining the class here is enough.
 */
class WP_Query {

	/**
	 * Query vars the shortcode passed in.
	 *
	 * @var array
	 */
	public $query_vars;

	/**
	 * Constructor.
	 *
	 * @param array $args Query args.
	 */
	public function __construct( $args = array() ) {
		$this->query_vars = $args;

		// Spy, so a test can assert post_type / orderby / order / posts_per_page.
		$GLOBALS['ucsccomms_test']['query_args'][] = $args;
		$GLOBALS['ucsccomms_test']['post_i']       = -1;
	}

	/**
	 * Is there a NEXT post? Peeks; never advances.
	 *
	 * @return bool
	 */
	public function have_posts() {
		$state = $GLOBALS['ucsccomms_test'];

		return ( $state['post_i'] + 1 ) < count( $state['posts'] );
	}

	/**
	 * Advance to the next post and set up its context.
	 *
	 * @return void
	 */
	public function the_post() {
		$state = &$GLOBALS['ucsccomms_test'];

		++$state['post_i'];
		$post = $state['posts'][ $state['post_i'] ];

		$state['title'] = isset( $post['title'] ) ? $post['title'] : '';

		// Switching post context re-seeds the repeater and rewinds its cursor,
		// which is what real ACF does when the global $post changes. Without
		// this the archive renders post 1's rows and then nothing.
		$state['rows']   = isset( $post['rows'] ) ? $post['rows'] : array();
		$state['cursor'] = -1;
		$state['guard']  = 0;

		$GLOBALS['post'] = (object) array( 'ID' => $state['post_i'] + 1 );
	}
}

/**
 * Post title double.
 *
 * @param mixed $post Unused.
 * @return string
 */
function get_the_title( $post = 0 ) {
	return $GLOBALS['ucsccomms_test']['title'];
}

/**
 * Post-data reset counter.
 *
 * A counter rather than a no-op: this is what turns the "wp_reset_postdata()
 * was unreachable after return" bug into a real regression test.
 *
 * @return void
 */
function wp_reset_postdata() {
	++$GLOBALS['ucsccomms_test']['reset_postdata'];

	unset( $GLOBALS['post'] );
}
