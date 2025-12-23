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
  }

  return $args;
}
add_filter( 'register_post_type_args', 'summit_rename_posts_to_insights', 10, 2 );

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