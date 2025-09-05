<?php
/**
 * General Functions
 *
 * This file contains general functions for the UCSC Communications Functionality plugin.
 *
 * @package      ucsc-communications-functionality
 * @since        1.7.0
 * @link         https://github.com/ucsc/ucsc-communications-functionality.git
 * @author       UC Santa Cruz
 * @license      http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Enqueue admin settings styles.
if ( ! function_exists( 'ucsccomms_enqueue_admin_styles' ) ) {
	/**
	 * Enqueue admin settings styles
	 *
	 * @since 0.5.0
	 * @author UCSC
	 * @package ucsc-communications-functionality
	 */
	function ucsccomms_enqueue_admin_styles(): void {
		$settings_css   = plugin_dir_url( __FILE__ ) . 'lib/css/admin-settings.css';
		$current_screen = get_current_screen();
		$plugin_data    = get_plugin_data( UCSCCOMMS_PLUGIN_DIR . '/plugin.php' );
		$plugin_version = $plugin_data['Version'];
		if ( strpos( $current_screen->base, 'ucsc-communications-functionality-settings' ) === false ) {
			return;
		}
		wp_register_style( 'ucsccomms-cf-admin-settings', $settings_css, '', $plugin_version );
		wp_enqueue_style( 'ucsccomms-cf-admin-settings' );
	}
}
add_action( 'admin_enqueue_scripts', 'ucsccomms_enqueue_admin_styles' );

/**
 * Register Search block variation for A-Z Style Guide post type
 * description: Registers a custom block variation for the A-Z Style Guide post type
 *
 * @param mixed         $variations
 * @param WP_Block_Type $block_type The block type being filtered.
 * @return mixed
 */
function ucsccomms_create_style_guide_search_variation( $variations, $block_type ) {
	if ( 'core/search' !== $block_type->name ) {
			return $variations;
	}

		$variations[] = array(
			'name'        => 'styleguide-search',
			'title'       => __( 'Style Guide Search', 'ucsccommunications' ),
			'description' => __( 'Search only Style Guide posts', 'ucsccommunications' ),
			'attributes'  => array(
				'query'       => array(
					'post_type' => 'a_z_style_guide',
				),
				'placeholder' => __( 'Search Style Guide', 'ucsccommunications' ),
				'buttonText'  => __( 'Search Style Guide', 'ucsccommunications' ),
				'label'       => __( 'Search Style Guide', 'ucsccommunications' ),
			),
		);

		return $variations;
}

add_filter( 'get_block_type_variations', 'ucsccomms_create_style_guide_search_variation', 10, 2 );

/**
 * Return Fund search results in Fund archive template
 * description: Returns the Fund search results in its archive template.
 *
 * @param string $template
 * @return string
 */
function ucsccomms_style_guide_search_template( $template ) {
	if ( is_search() && 'a_z_style_guide' === get_query_var( 'post_type' ) ) {
		return locate_template( '' ); // this will return search results in the archive template.
	}

	return $template;
}

add_action( 'search_template', 'ucsccomms_style_guide_search_template' );

function ucsccomms_custom_filter_posts( $query ) {
    if ( ! is_admin() && $query->is_main_query() && is_search() && 'a_z_style_guide' === get_query_var( 'post_type' ) ) {
        // Only proceed if there's a search term
        if ( ! empty( $query->query_vars['s'] ) ) {
            global $wpdb;
            
            $search_term = $query->query_vars['s'];
            
            // Get posts that have matching sub-field values
            $sql = $wpdb->prepare("
                SELECT DISTINCT p.ID 
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = 'a_z_style_guide'
                AND p.post_status = 'publish'
                AND (
                    (pm.meta_key LIKE %s AND pm.meta_value LIKE %s)
                    OR 
                    (pm.meta_key LIKE %s AND pm.meta_value LIKE %s)
                )
                ORDER BY p.post_title ASC
            ", 
                'style_definitions_%_editorial_style_item',
                '%' . $wpdb->esc_like($search_term) . '%',
                'style_definitions_%_editorial_style_definition', 
                '%' . $wpdb->esc_like($search_term) . '%'
            );
            
            $post_ids = $wpdb->get_col($sql);
            
            if (!empty($post_ids)) {
                $query->set('post__in', $post_ids);
                $query->set('orderby', 'post__in'); // Maintain the order from the SQL query
                $query->set('s', ''); // Remove the default search to avoid conflicts
            } else {
                // If no matches found, return no posts
                $query->set('post__in', array(0));
                $query->set('s', '');
            }
        }
    }
}
add_action( 'pre_get_posts', 'ucsccomms_custom_filter_posts' );