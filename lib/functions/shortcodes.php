<?php
/**
 * Plugin Name:       UCSC Communications Custom Functionality
 * Description:       Shortcodes for UCSC Communications and Marketing Website.
 * Version:           0.1.0
 * Requires at least: 6.1
 * Requires PHP:      7.0   
 */


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
		$finaldefs .= '<p><b>'.$azItem.':</b></p>'.$azDef.'<hr>';
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
			$finalloop .= '<h2>'.$azTitle.'</h2>';
			if( have_rows('style_definitions') ):
				while( have_rows('style_definitions') ):
					the_row();
					// vars
					$azItem = get_sub_field('editorial_style_item');
					$azDef = get_sub_field('editorial_style_definition');
					$finalloop .= '<p><b>'.$azItem.':</b></p>'.$azDef.'<hr>';
				endwhile;
			endif;
		endwhile;
	endif;

	return $finalloop;

	wp_reset_postdata();
}