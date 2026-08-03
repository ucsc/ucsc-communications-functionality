<?php
/**
 * Escaping-contract tests for the shortcode output.
 *
 * These are the reason this suite exists. Both shortcode callbacks build and
 * *return* a string rather than echoing it, and PHPCS's
 * WordPress.Security.EscapeOutput sniff only inspects echo/print - so an
 * unescaped value concatenated into the returned markup lints clean. That is
 * the bug class that actually shipped (ROADMAP item 4).
 *
 * What these tests assert is the *contract*: every dynamic value passes through
 * an escaper before concatenation. They are not proof of XSS safety - see the
 * note on the wp_kses_post() double in tests/wp-stubs.php.
 *
 * @package ucsc-communications-functionality
 */

namespace UCSC\UcscCommunicationsFunctionality\Tests;

/**
 * Escaping contract.
 */
final class ShortcodeEscapingTest extends TestCase {

	/**
	 * The plain-text sub-field is escaped and the WYSIWYG one is KSES-filtered.
	 *
	 * @return void
	 */
	public function test_single_loop_escapes_item_and_kses_filters_definition(): void {
		ucsccomms_test_set_rows(
			array(
				array(
					'editorial_style_item'       => '<script>alert(1)</script>',
					'editorial_style_definition' => '<em>Use sparingly.</em><script>x</script>',
				),
			)
		);

		$html = ucsccomms_a_z_style_guide_single_loop();

		// The plain-text sub-field must be HTML-escaped at the point of concatenation.
		$this->assertStringNotContainsString( '<script>alert(1)</script>', $html );
		$this->assertStringContainsString( '&lt;script&gt;alert(1)&lt;/script&gt;', $html );

		// The WYSIWYG sub-field must be routed through wp_kses_post(), not concatenated raw.
		$this->assertSame(
			array( array( '<em>Use sparingly.</em><script>x</script>' ) ),
			ucsccomms_test_calls_to( 'wp_kses_post' )
		);
	}

	/**
	 * Every dynamic value in the archive path is escaped, across multiple posts.
	 *
	 * Also proves the per-post cursor rewind: a broken rewind renders post 1's
	 * rows twice, or renders nothing for post 2.
	 *
	 * @return void
	 */
	public function test_archive_loop_escapes_title_item_and_definition(): void {
		ucsccomms_test_set_posts(
			array(
				array(
					'title' => '<img src=x onerror=alert(1)>',
					'rows'  => array(
						array(
							'editorial_style_item'       => 'Ampersand & co',
							'editorial_style_definition' => '<p>First.</p>',
						),
					),
				),
				array(
					'title' => 'Chancellor',
					'rows'  => array(
						array(
							'editorial_style_item'       => '"quoted"',
							'editorial_style_definition' => '<p>Second.</p>',
						),
					),
				),
			)
		);

		$html = ucsccomms_a_z_styles_archive_loop();

		// The raw title must never reach the output.
		$this->assertStringNotContainsString( '<img src=x onerror=alert(1)>', $html );
		$this->assertStringContainsString( '&lt;img src=x onerror=alert(1)&gt;', $html );

		// Titles and items alike go through esc_html(), in document order.
		$this->assertSame(
			array(
				array( '<img src=x onerror=alert(1)>' ),
				array( 'Ampersand & co' ),
				array( 'Chancellor' ),
				array( '"quoted"' ),
			),
			ucsccomms_test_calls_to( 'esc_html' )
		);

		// Both posts' definitions were KSES-filtered - i.e. the cursor rewound
		// for post 2 rather than re-running post 1 or stopping.
		$this->assertSame(
			array(
				array( '<p>First.</p>' ),
				array( '<p>Second.</p>' ),
			),
			ucsccomms_test_calls_to( 'wp_kses_post' )
		);
	}

	/**
	 * An empty repeater renders nothing at all.
	 *
	 * @return void
	 */
	public function test_empty_repeater_returns_empty_string(): void {
		ucsccomms_test_set_rows( array() );

		$this->assertSame( '', ucsccomms_a_z_style_guide_single_loop() );
	}

	/**
	 * Multiple rows render in order, with the documented markup shape.
	 *
	 * @return void
	 */
	public function test_single_loop_renders_each_row_in_order(): void {
		ucsccomms_test_set_rows(
			array(
				array(
					'editorial_style_item'       => 'alumni',
					'editorial_style_definition' => '<p>Plural.</p>',
				),
				array(
					'editorial_style_item'       => 'banana slug',
					'editorial_style_definition' => '<p>Lowercase.</p>',
				),
			)
		);

		$this->assertSame(
			'<p><b>alumni:</b></p><p>Plural.</p><hr>'
			. '<p><b>banana slug:</b></p><p>Lowercase.</p><hr>',
			ucsccomms_a_z_style_guide_single_loop()
		);
	}
}
