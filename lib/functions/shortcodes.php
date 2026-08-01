<?php
/**
 * Shortcodes
 *
 * This file registers the A-Z Editorial Style Guide shortcodes.
 *
 * @package      ucsc-communications-functionality
 * @since        1.7.0
 * @link         https://github.com/ucsc/ucsc-communications-functionality.git
 * @author       UC Santa Cruz
 * @license      http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render the style definitions for the current post.
 *
 * Handles the [style-definition] shortcode, which outputs the
 * `style_definitions` repeater for whichever post is in context.
 *
 * @return string HTML markup for the current post's style definitions.
 */
function ucsccomms_a_z_style_guide_single_loop() {
	$finaldefs = '';

	if ( have_rows( 'style_definitions' ) ) :
		while ( have_rows( 'style_definitions' ) ) :
			the_row();
			$az_item = get_sub_field( 'editorial_style_item' );
			$az_def  = get_sub_field( 'editorial_style_definition' );
			// esc_html() for the plain-text item; wp_kses_post() for the WYSIWYG definition.
			$finaldefs .= '<p><b>' . esc_html( $az_item ) . ':</b></p>' . wp_kses_post( $az_def ) . '<hr>';
		endwhile;
	endif;

	return $finaldefs;
}
add_shortcode( 'style-definition', 'ucsccomms_a_z_style_guide_single_loop' );

/**
 * Render every Editorial Style Guide entry as an archive.
 *
 * Handles the [style-archive] shortcode. Retrieves all `a_z_style_guide` posts
 * ordered by title ascending, and renders each title followed by its style
 * definitions.
 *
 * @return string HTML markup for the full style guide archive.
 */
function ucsccomms_a_z_styles_archive_loop() {
	$finalloop = '';

	$args = array(
		'post_type'      => 'a_z_style_guide',
		'orderby'        => 'title',
		'order'          => 'ASC',
		'posts_per_page' => -1,
	);

	$az_dir = new \WP_Query( $args );

	if ( $az_dir->have_posts() ) :
		while ( $az_dir->have_posts() ) :
			$az_dir->the_post();
			$az_title   = get_the_title();
			$finalloop .= '<h2>' . esc_html( $az_title ) . '</h2>';
			if ( have_rows( 'style_definitions' ) ) :
				while ( have_rows( 'style_definitions' ) ) :
					the_row();
					$az_item = get_sub_field( 'editorial_style_item' );
					$az_def  = get_sub_field( 'editorial_style_definition' );
					// esc_html() for the plain-text item; wp_kses_post() for the WYSIWYG definition.
					$finalloop .= '<p><b>' . esc_html( $az_item ) . ':</b></p>' . wp_kses_post( $az_def ) . '<hr>';
				endwhile;
			endif;
		endwhile;
		// Restore the global $post clobbered by the_post() above.
		wp_reset_postdata();
	endif;

	return $finalloop;
}
add_shortcode( 'style-archive', 'ucsccomms_a_z_styles_archive_loop' );
