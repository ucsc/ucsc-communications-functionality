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
 * Register the A-Z Editorial Style Guide shortcode.
 *
 * @return string
 */
// This shortcode outputs the A-Z Editorial Style Guide definitions.

add_shortcode( 'style-definition','ucsccomms_a_z_style_guide_single_loop' );

function ucsccomms_a_z_style_guide_single_loop(){

	$finaldefs = '';

	if( have_rows('style_definitions') ):while( have_rows('style_definitions') ): the_row();
		$azItem = get_sub_field('editorial_style_item');
		$azDef = get_sub_field('editorial_style_definition');
		// esc_html() for the plain-text item; wp_kses_post() for the WYSIWYG definition.
		$finaldefs .= '<p><b>' . esc_html( $azItem ) . ':</b></p>' . wp_kses_post( $azDef ) . '<hr>';
		endwhile;
	endif;

return $finaldefs;
}

/**
 * Register the A-Z Editorial Style Guide archive shortcode.
 *
 * @return string
 */
// This shortcode outputs the A-Z Editorial Style Guide archive loop.
// It retrieves all posts of the 'a_z_style_guide' post type, ordered by title in ascending order, and displays each post's title along with its style definitions.
add_shortcode( 'style-archive','ucsccomms_a_z_styles_archive_loop' );

function ucsccomms_a_z_styles_archive_loop() {
	$finalloop = '';

	// Call Post
	$args = array (
	'post_type' => 'a_z_style_guide',
	'orderby' => 'title',
	'order' => 'ASC',
	'posts_per_page' => -1,
	);

	$azDir = new \WP_Query( $args );

	if ($azDir->have_posts()) :
		while ($azDir->have_posts()) :
			$azDir->the_post();
			$azTitle = get_the_title();
			$finalloop .= '<h2>' . esc_html( $azTitle ) . '</h2>';
			if( have_rows('style_definitions') ):
				while( have_rows('style_definitions') ):
					the_row();
					// vars
					$azItem = get_sub_field('editorial_style_item');
					$azDef = get_sub_field('editorial_style_definition');
					// esc_html() for the plain-text item; wp_kses_post() for the WYSIWYG definition.
					$finalloop .= '<p><b>' . esc_html( $azItem ) . ':</b></p>' . wp_kses_post( $azDef ) . '<hr>';
				endwhile;
			endif;
		endwhile;
		// Restore the global $post clobbered by the_post() above.
		wp_reset_postdata();
	endif;

	return $finalloop;
}