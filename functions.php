<?php
/**
 * Summit Law Theme Functions
 */

// Include required files
require_once get_template_directory() . '/inc/vite.php';
require_once get_template_directory() . '/inc/acf-blocks.php';
require_once get_template_directory() . '/inc/helpers.php';
require_once get_template_directory() . '/inc/svg-icons.php';
require_once get_template_directory() . '/inc/class-icon-walker.php';
require_once get_template_directory() . '/inc/class-mega-menu-walker.php';
require_once get_template_directory() . '/inc/vcard-generator.php';

/**
 * Theme Setup
 */
if (!function_exists('summit_theme_setup')) {
  function summit_theme_setup() {
    // Add theme support for various features
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', [
      'search-form',
      'comment-form',
      'comment-list',
      'gallery',
      'caption',
      'style',
      'script'
    ]);

    // Register custom image sizes for team member photos
    // These provide more granular options for responsive images
    add_image_size('team-photo-small', 400, 400, true);   // Mobile devices
    add_image_size('team-photo-medium', 600, 600, true);  // Tablets
    add_image_size('team-photo-large', 800, 800, true);   // Desktop
    add_image_size('team-photo-xlarge', 1000, 1000, true); // Large screens

    // Register custom image size for form block images
    // Optimized for 3:4 aspect ratio portraits at ~600px rendered width
    add_image_size('form-image', 900, 1200, true);  // 3:4 aspect ratio, crisp for retina
    add_theme_support('custom-logo', [
      'height' => 100,
      'width' => 300,
      'flex-height' => true,
      'flex-width' => true,
    ]);
    add_theme_support('editor-styles');

    // Register navigation menus
    register_nav_menus([
      'primary' => __('Primary Menu', 'summit-law-theme'),
      'footer-firm' => __('Footer - Firm', 'summit-law-theme'),
      'footer-services' => __('Footer - Services', 'summit-law-theme'),
      'footer-insights' => __('Footer - Insights', 'summit-law-theme'),
      'footer-contact' => __('Footer - Get in Touch', 'summit-law-theme'),
      'footer-legal' => __('Footer - Legal Links', 'summit-law-theme'),
    ]);

  }
  add_action('after_setup_theme', 'summit_theme_setup');
}

/**
 * ACF JSON Save & Load Point
 */
add_filter('acf/settings/save_json', function() {
  return get_template_directory() . '/acf-json';
});

add_filter('acf/settings/load_json', function($paths) {
  unset($paths[0]);
  $paths[] = get_template_directory() . '/acf-json';
  return $paths;
});

/**
 * Disable Widgets
 */
add_filter('use_widgets_block_editor', '__return_false');
remove_theme_support('widgets-block-editor');

/**
 * Customizer Settings
 */
if (!function_exists('summit_customize_register')) {
  function summit_customize_register($wp_customize) {
    // Footer Contact Section
    $wp_customize->add_section('footer_contact', [
      'title' => __('Footer Contact Info', 'summit-law-theme'),
      'priority' => 30,
    ]);

    // Footer Address
    $wp_customize->add_setting('footer_address', [
      'default' => '',
      'sanitize_callback' => 'sanitize_textarea_field',
    ]);
    $wp_customize->add_control('footer_address', [
      'label' => __('Address', 'summit-law-theme'),
      'section' => 'footer_contact',
      'type' => 'textarea',
      'description' => __('Enter the physical address for the footer', 'summit-law-theme'),
    ]);

    // Footer Google Maps Link
    $wp_customize->add_setting('footer_maps_link', [
      'default' => '',
      'sanitize_callback' => 'esc_url_raw',
    ]);
    $wp_customize->add_control('footer_maps_link', [
      'label' => __('Google Maps Link', 'summit-law-theme'),
      'section' => 'footer_contact',
      'type' => 'url',
      'description' => __('Optional: Add a Google Maps link to make the address clickable', 'summit-law-theme'),
    ]);

    // Footer Bottom Section
    $wp_customize->add_section('footer_bottom', [
      'title' => __('Footer Bottom Info', 'summit-law-theme'),
      'priority' => 31,
    ]);

    // Copyright Text
    $wp_customize->add_setting('footer_copyright', [
      'default' => 'Summit Law. All rights reserved.',
      'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('footer_copyright', [
      'label' => __('Copyright Text', 'summit-law-theme'),
      'section' => 'footer_bottom',
      'type' => 'text',
      'description' => __('The copyright text (year will be added automatically)', 'summit-law-theme'),
    ]);

    // Maintained By Text
    $wp_customize->add_setting('footer_maintained_by', [
      'default' => 'Web Ok Solutions Inc.',
      'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('footer_maintained_by', [
      'label' => __('Maintained By', 'summit-law-theme'),
      'section' => 'footer_bottom',
      'type' => 'text',
      'description' => __('Company/person maintaining the site', 'summit-law-theme'),
    ]);

    // Maintained By Link
    $wp_customize->add_setting('footer_maintained_by_link', [
      'default' => 'https://webok.ca/',
      'sanitize_callback' => 'esc_url_raw',
    ]);
    $wp_customize->add_control('footer_maintained_by_link', [
      'label' => __('Maintained By Link', 'summit-law-theme'),
      'section' => 'footer_bottom',
      'type' => 'url',
      'description' => __('URL for the maintainer', 'summit-law-theme'),
    ]);
  }
  add_action('customize_register', 'summit_customize_register');
}

/**
 * Rename Posts to "Insights"
 */
function summit_rename_posts_to_insights( $args, $post_type ) {
  if ( 'post' === $post_type ) {
    $args['labels'] = [
      'name'                  => _x( 'Insights', 'Post type general name', 'summit-law-theme' ),
      'singular_name'         => _x( 'Insight', 'Post type singular name', 'summit-law-theme' ),
      'menu_name'             => _x( 'Insights', 'Admin Menu text', 'summit-law-theme' ),
      'add_new'               => __( 'Add New', 'summit-law-theme' ),
      'add_new_item'          => __( 'Add New Insight', 'summit-law-theme' ),
      'new_item'              => __( 'New Insight', 'summit-law-theme' ),
      'edit_item'             => __( 'Edit Insight', 'summit-law-theme' ),
      'view_item'             => __( 'View Insight', 'summit-law-theme' ),
      'all_items'             => __( 'All Insights', 'summit-law-theme' ),
      'search_items'          => __( 'Search Insights', 'summit-law-theme' ),
      'not_found'             => __( 'No insights found.', 'summit-law-theme' ),
      'not_found_in_trash'    => __( 'No insights found in Trash.', 'summit-law-theme' ),
      'archives'              => __( 'Insight Archives', 'summit-law-theme' ),
      'insert_into_item'      => __( 'Insert into insight', 'summit-law-theme' ),
      'uploaded_to_this_item' => __( 'Uploaded to this insight', 'summit-law-theme' ),
      'filter_items_list'     => __( 'Filter insights list', 'summit-law-theme' ),
      'items_list_navigation' => __( 'Insights list navigation', 'summit-law-theme' ),
      'items_list'            => __( 'Insights list', 'summit-law-theme' ),
    ];

    // Change menu icon to lightbulb
    $args['menu_icon'] = 'dashicons-lightbulb';

    // Set rewrite slug to 'insights' for posts only
    $args['rewrite'] = [
      'slug' => 'insights',
      'with_front' => false,
    ];

    // Remove default taxonomies (categories and tags) from Insights
    $args['taxonomies'] = [];
  }

  return $args;
}
add_filter( 'register_post_type_args', 'summit_rename_posts_to_insights', 10, 2 );

/**
 * Unregister default Categories and Tags from Insights (Posts)
 */
function summit_remove_default_taxonomies() {
  // Unregister category taxonomy from posts
  unregister_taxonomy_for_object_type( 'category', 'post' );

  // Unregister post_tag taxonomy from posts
  unregister_taxonomy_for_object_type( 'post_tag', 'post' );
}
add_action( 'init', 'summit_remove_default_taxonomies' );

/**
 * Remove Categories and Tags from admin menu
 */
function summit_remove_taxonomy_menus() {
  // Remove Categories submenu from Insights
  remove_submenu_page( 'edit.php', 'edit-tags.php?taxonomy=category' );

  // Remove Tags submenu from Insights
  remove_submenu_page( 'edit.php', 'edit-tags.php?taxonomy=post_tag' );
}
add_action( 'admin_menu', 'summit_remove_taxonomy_menus' );

/**
 * Add custom rewrite rules for insights
 */
function summit_add_insights_rewrite_rules() {
  // Add rewrite tag for post name
  add_rewrite_rule(
    '^insights/([^/]+)/?$',
    'index.php?name=$matches[1]',
    'top'
  );

  // Add rewrite tag for insights archive
  add_rewrite_rule(
    '^insights/?$',
    'index.php?post_type=post',
    'top'
  );
}
add_action( 'init', 'summit_add_insights_rewrite_rules' );

/**
 * Modify post permalinks to use /insights/ prefix
 */
function summit_change_post_permalink( $permalink, $post ) {
  if ( $post->post_type === 'post' ) {
    // Replace the base URL structure with /insights/
    $permalink = str_replace( home_url( '/' ), home_url( '/insights/' ), $permalink );

    // Remove any double slashes that might occur
    $permalink = str_replace( '/insights/insights/', '/insights/', $permalink );
  }
  return $permalink;
}
add_filter( 'post_link', 'summit_change_post_permalink', 10, 2 );

/**
 * Redirect non-insights post URLs to /insights/ URLs
 * This prevents duplicate content and consolidates SEO value
 */
function summit_redirect_to_insights() {
  // Only redirect single posts
  if ( is_single() && get_post_type() === 'post' ) {
    $current_url = home_url( $_SERVER['REQUEST_URI'] );
    $canonical_url = get_permalink();

    // If current URL doesn't match canonical (missing /insights/), redirect
    if ( $current_url !== $canonical_url && strpos( $current_url, '/insights/' ) === false ) {
      wp_redirect( $canonical_url, 301 );
      exit;
    }
  }
}
add_action( 'template_redirect', 'summit_redirect_to_insights' );

/**
 * Flush rewrite rules on theme activation
 * This ensures the /insights/ slug works correctly for posts
 */
function summit_flush_rewrite_rules() {
  flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'summit_flush_rewrite_rules' );

/**
 * Completely Disable Comments Site-Wide
 * Removes comments from all post types including posts, pages, and custom post types
 */

// Close comments on the front-end
add_filter( 'comments_open', '__return_false', 20, 2 );
add_filter( 'pings_open', '__return_false', 20, 2 );

// Hide existing comments
add_filter( 'comments_array', '__return_empty_array', 10, 2 );

// Remove comments page in menu
add_action( 'admin_menu', function() {
  remove_menu_page( 'edit-comments.php' );
} );

// Remove comments links from admin bar
add_action( 'admin_bar_menu', function( $wp_admin_bar ) {
  $wp_admin_bar->remove_node( 'comments' );
}, 999 );

// Remove comments metabox from dashboard
add_action( 'admin_init', function() {
  remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
} );

// Disable support for comments and trackbacks in post types
add_action( 'admin_init', function() {
  // Get all post types
  $post_types = get_post_types();

  foreach ( $post_types as $post_type ) {
    if ( post_type_supports( $post_type, 'comments' ) ) {
      remove_post_type_support( $post_type, 'comments' );
      remove_post_type_support( $post_type, 'trackbacks' );
    }
  }
} );

// Close comments on all existing posts
add_action( 'admin_init', function() {
  // Update all posts to have comments closed (runs once)
  if ( ! get_option( 'summit_comments_disabled' ) ) {
    global $wpdb;
    $wpdb->query( "UPDATE $wpdb->posts SET comment_status = 'closed', ping_status = 'closed'" );
    update_option( 'summit_comments_disabled', true );
  }
} );

// Remove comment-reply script
add_action( 'wp_enqueue_scripts', function() {
  wp_dequeue_script( 'comment-reply' );
}, 100 );

// Remove comments from admin bar
add_action( 'wp_before_admin_bar_render', function() {
  global $wp_admin_bar;
  $wp_admin_bar->remove_menu( 'comments' );
} );

/**
 * Add Block Template to Service Post Type
 * Locks the hero_banner block as required, but allows inserting additional blocks
 */
function summit_service_block_template( $args, $post_type ) {
	if ( $post_type !== 'service' ) {
		return $args;
	}

	// Ensure block editor is available
	$args['show_in_rest'] = true;

	// Define required blocks that will be pre-loaded
	$args['template'] = [
		[ 'acf/hero-banner', [] ],
	];

	// Lock setting: false means users can add, remove, and reorder blocks freely
	// The hero_banner will be pre-loaded but not locked in place
	$args['template_lock'] = false;

	return $args;
}
add_filter( 'register_post_type_args', 'summit_service_block_template', 10, 2 );

/**
 * Limit 'Areas' Taxonomy to Single Selection with Radio Buttons
 * Applies to Cases and Insights post types
 * Displays hierarchical structure with indentation
 */
function summit_areas_taxonomy_radio_metabox( $post, $box ) {
  $taxonomy = 'area';

  // Get current selected term
  $current = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );
  $current_id = ! empty( $current ) ? $current[0] : 0;

  echo '<div id="taxonomy-' . $taxonomy . '" class="categorydiv">';
  echo '<ul style="list-style: none; padding-left: 0;">';

  // Add "None" option
  echo '<li style="margin-bottom: 5px;">';
  echo '<label style="display: inline-block;">';
  echo '<input type="radio" name="tax_input[' . $taxonomy . '][]" value="0"' . checked( $current_id, 0, false ) . ' style="margin-right: 5px;"> ';
  echo '<em>None</em>';
  echo '</label>';
  echo '</li>';

  // Display hierarchical terms with indentation
  summit_display_area_terms_hierarchical( $taxonomy, 0, $current_id );

  echo '</ul>';
  echo '</div>';
}

/**
 * Recursively display area terms in hierarchical order with indentation
 *
 * @param string $taxonomy Taxonomy name
 * @param int    $parent_id Parent term ID (0 for top-level)
 * @param int    $current_id Currently selected term ID
 * @param int    $level Indentation level
 */
function summit_display_area_terms_hierarchical( $taxonomy, $parent_id = 0, $current_id = 0, $level = 0 ) {
  // Get terms at this level
  $terms = get_terms( array(
    'taxonomy' => $taxonomy,
    'hide_empty' => false,
    'parent' => $parent_id,
    'orderby' => 'name',
    'order' => 'ASC'
  ) );

  if ( empty( $terms ) || is_wp_error( $terms ) ) {
    return;
  }

  foreach ( $terms as $term ) {
    // Calculate indentation (20px per level)
    $indent = $level * 20;

    echo '<li style="margin-bottom: 5px; padding-left: ' . $indent . 'px;">';
    echo '<label style="display: inline-block;">';
    echo '<input type="radio" name="tax_input[' . $taxonomy . '][]" value="' . $term->term_id . '"' . checked( $current_id, $term->term_id, false ) . ' style="margin-right: 5px;"> ';

    // Add visual indicator for child items
    if ( $level > 0 ) {
      echo '<span style="color: #999;">— </span>';
    }

    echo esc_html( $term->name );
    echo '</label>';
    echo '</li>';

    // Recursively display child terms
    summit_display_area_terms_hierarchical( $taxonomy, $term->term_id, $current_id, $level + 1 );
  }
}

/**
 * Replace default Areas metabox with radio button version
 * Only for Case and Post (Insight) post types
 */
function summit_register_areas_radio_metabox() {
  // Remove default metabox for Cases
  remove_meta_box( 'tagsdiv-area', 'case', 'side' );
  remove_meta_box( 'areadiv', 'case', 'side' );

  // Remove default metabox for Insights (posts)
  remove_meta_box( 'tagsdiv-area', 'post', 'side' );
  remove_meta_box( 'areadiv', 'post', 'side' );

  // Add custom radio button metabox for Cases
  add_meta_box(
    'area-radio-metabox',
    'Areas',
    'summit_areas_taxonomy_radio_metabox',
    'case',
    'side',
    'default'
  );

  // Add custom radio button metabox for Insights
  add_meta_box(
    'area-radio-metabox',
    'Areas',
    'summit_areas_taxonomy_radio_metabox',
    'post',
    'side',
    'default'
  );
}
add_action( 'add_meta_boxes', 'summit_register_areas_radio_metabox' );

/**
 * Remove 'Areas' taxonomy from Gutenberg block editor sidebar
 * This prevents the checkbox interface from appearing alongside our radio buttons
 */
function summit_remove_area_from_gutenberg_sidebar() {
  // Unregister the taxonomy panel from REST API for Cases
  unregister_taxonomy_for_object_type( 'area', 'case' );

  // Unregister the taxonomy panel from REST API for Insights
  unregister_taxonomy_for_object_type( 'area', 'post' );

  // Re-register it only for saving purposes (keeps functionality, removes UI)
  register_taxonomy_for_object_type( 'area', 'case' );
  register_taxonomy_for_object_type( 'area', 'post' );
}
add_action( 'init', 'summit_remove_area_from_gutenberg_sidebar', 99 );

/**
 * Remove the Areas taxonomy from Gutenberg block editor
 * Uses the proper WordPress filter to prevent it from showing in the sidebar
 */
function summit_remove_area_taxonomy_from_rest( $args, $taxonomy ) {
  if ( 'area' === $taxonomy ) {
    // Hide from REST API response (removes from Gutenberg sidebar)
    $args['show_in_rest'] = false;
  }
  return $args;
}
add_filter( 'register_taxonomy_args', 'summit_remove_area_taxonomy_from_rest', 10, 2 );