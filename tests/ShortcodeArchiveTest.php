<?php
/**
 * Structural tests for the [style-archive] shortcode.
 *
 * @package ucsc-communications-functionality
 */

namespace UCSC\UcscCommunicationsFunctionality\Tests;

/**
 * Archive query and post-data handling.
 */
final class ShortcodeArchiveTest extends TestCase {

	/**
	 * Seed two posts' worth of fixture data.
	 *
	 * @return void
	 */
	private function seed_two_posts(): void {
		ucsccomms_test_set_posts(
			array(
				array(
					'title' => 'Alumni',
					'rows'  => array(
						array(
							'editorial_style_item'       => 'alumni',
							'editorial_style_definition' => '<p>Plural.</p>',
						),
					),
				),
				array(
					'title' => 'Banana slug',
					'rows'  => array(
						array(
							'editorial_style_item'       => 'banana slug',
							'editorial_style_definition' => '<p>Lowercase.</p>',
						),
					),
				),
			)
		);
	}

	/**
	 * wp_reset_postdata() runs, exactly once, and actually clears the global.
	 *
	 * Guards ROADMAP item 2, where the reset sat after `return` and was
	 * therefore unreachable. A no-op double could not catch that; the double
	 * counts calls instead.
	 *
	 * @return void
	 */
	public function test_reset_postdata_runs_once_after_the_loop(): void {
		$this->seed_two_posts();

		ucsccomms_a_z_styles_archive_loop();

		$this->assertSame( 1, $GLOBALS['ucsccomms_test']['reset_postdata'] );
		$this->assertArrayNotHasKey( 'post', $GLOBALS );
	}

	/**
	 * The reset is deliberately inside the have_posts() guard.
	 *
	 * @return void
	 */
	public function test_archive_with_no_posts_returns_empty_and_skips_reset(): void {
		ucsccomms_test_set_posts( array() );

		$this->assertSame( '', ucsccomms_a_z_styles_archive_loop() );
		$this->assertSame( 0, $GLOBALS['ucsccomms_test']['reset_postdata'] );
	}

	/**
	 * The query targets the right post type, in the right order, unpaginated.
	 *
	 * A typo'd CPT slug produces an empty archive with no error anywhere.
	 *
	 * @return void
	 */
	public function test_archive_query_args(): void {
		$this->seed_two_posts();

		ucsccomms_a_z_styles_archive_loop();

		$this->assertSame(
			array(
				array(
					'post_type'      => 'a_z_style_guide',
					'orderby'        => 'title',
					'order'          => 'ASC',
					'posts_per_page' => -1,
				),
			),
			$GLOBALS['ucsccomms_test']['query_args']
		);
	}

	/**
	 * Each post renders an h2 title followed by its own definitions.
	 *
	 * @return void
	 */
	public function test_archive_renders_title_then_definitions_per_post(): void {
		$this->seed_two_posts();

		$this->assertSame(
			'<h2>Alumni</h2><p><b>alumni:</b></p><p>Plural.</p><hr>'
			. '<h2>Banana slug</h2><p><b>banana slug:</b></p><p>Lowercase.</p><hr>',
			ucsccomms_a_z_styles_archive_loop()
		);
	}

	/**
	 * A post with no definitions still renders its title.
	 *
	 * @return void
	 */
	public function test_archive_renders_title_for_post_without_definitions(): void {
		ucsccomms_test_set_posts(
			array(
				array(
					'title' => 'Empty entry',
					'rows'  => array(),
				),
			)
		);

		$this->assertSame( '<h2>Empty entry</h2>', ucsccomms_a_z_styles_archive_loop() );
	}
}
