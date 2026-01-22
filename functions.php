<?php
/**
 * Summit Law Theme Functions
 */

// Include required files
require_once get_template_directory() . '/inc/vite.php';
require_once get_template_directory() . '/inc/acf-blocks.php';
require_once get_template_directory() . '/inc/service-patterns.php';
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
    // Alternate Logo (in Site Identity section alongside main logo)
    $wp_customize->add_setting('alt_logo', [
      'default' => '',
      'sanitize_callback' => 'absint',
    ]);
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'alt_logo', [
      'label' => __('Alternate Logo', 'summit-law-theme'),
      'description' => __('Used for light backgrounds (e.g., when header scrolls on home page)', 'summit-law-theme'),
      'section' => 'title_tagline',
      'mime_type' => 'image',
      'priority' => 9, // Just after the main logo
    ]));

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

    // Designed and maintained By Text
    $wp_customize->add_setting('footer_maintained_by', [
      'default' => 'Web Ok Solutions Inc.',
      'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('footer_maintained_by', [
      'label' => __('Designed and maintained By', 'summit-law-theme'),
      'section' => 'footer_bottom',
      'type' => 'text',
      'description' => __('Company/person maintaining the site', 'summit-law-theme'),
    ]);

    // Designed and maintained By Link
    $wp_customize->add_setting('footer_maintained_by_link', [
      'default' => 'https://webok.ca/',
      'sanitize_callback' => 'esc_url_raw',
    ]);
    $wp_customize->add_control('footer_maintained_by_link', [
      'label' => __('Designed and maintained By Link', 'summit-law-theme'),
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
 * Enqueue Service Editor JavaScript
 *
 * Loads pattern-switching logic for the Service custom post type editor.
 * Only loads on service post type in the block editor.
 */
add_action( 'enqueue_block_editor_assets', function() {
	// Only load on service post type editor
	$screen = get_current_screen();
	if ( ! $screen || $screen->post_type !== 'service' ) {
		error_log( 'Service Editor: Not a service post type, skipping. Post type: ' . ( $screen ? $screen->post_type : 'no screen' ) );
		return;
	}

	error_log( 'Service Editor: Enqueue function called for service post type' );

	// Always try to load directly from src for now (simplest approach)
	$src_path = get_template_directory() . '/src/js/service-editor.js';

	if ( file_exists( $src_path ) ) {
		wp_enqueue_script(
			'summit-service-editor',
			get_template_directory_uri() . '/src/js/service-editor.js',
			[ 'wp-data', 'wp-blocks', 'wp-block-editor', 'wp-editor', 'wp-element', 'wp-plugins', 'wp-edit-post' ],
			filemtime( $src_path ),
			false // Load in footer
		);

		// Pass pattern content directly to JavaScript
		wp_localize_script( 'summit-service-editor', 'summitServicePatterns', [
			'parent' => [
				'name'    => 'summit-law/service-parent-pattern',
				'title'   => 'Service – Parent',
				'content' => '<!-- wp:acf/hero-banner /-->

<!-- wp:acf/breadcrumbs /-->

<!-- wp:acf/service-fields-parent /-->'
			],
			'child' => [
				'name'    => 'summit-law/service-child-pattern',
				'title'   => 'Service – Child',
				'content' => '<!-- wp:acf/hero-banner /-->

<!-- wp:acf/breadcrumbs /-->

<!-- wp:acf/content-group /-->

<!-- wp:acf/bullet-group /-->

<!-- wp:acf/bullet-group /-->

<!-- wp:acf/services-areas-loop /-->

<!-- wp:acf/accordion /-->

<!-- wp:acf/cta /-->'
			]
		] );

		error_log( 'Service Editor: Script enqueued from: ' . get_template_directory_uri() . '/src/js/service-editor.js' );
	} else {
		error_log( 'Service Editor: ERROR - Script file not found at: ' . $src_path );
	}
}, 10 );

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


// =============================================================================
// Insights Archive Filters
// =============================================================================

/**
 * Modify the main query on the Insights (blog) archive to handle filter parameters.
 *
 * @param WP_Query $query The main query object.
 */
function summit_modify_insights_query( $query ) {
  // Only modify front-end main query
  if ( is_admin() || ! $query->is_main_query() ) {
    return;
  }

  // Check if we're on the insights archive (by URL or by WordPress detection)
  $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( $_SERVER['REQUEST_URI'] ) : '';
  $is_insights_archive = $query->is_home() || preg_match( '#/insights/?(\?|$)#', $request_uri );

  if ( ! $is_insights_archive ) {
    return;
  }

  // Ensure we're querying posts (insights)
  $query->set( 'post_type', 'post' );

  // Handle area taxonomy filter
  if ( isset( $_GET['area'] ) && ! empty( $_GET['area'] ) ) {
    $query->set(
      'tax_query',
      array(
        array(
          'taxonomy' => 'area',
          'field'    => 'slug',
          'terms'    => sanitize_text_field( $_GET['area'] ),
        ),
      )
    );
  }
}
add_action( 'pre_get_posts', 'summit_modify_insights_query' );

/**
 * Modify the main query on the Cases archive to handle filter parameters.
 *
 * @param WP_Query $query The main query object.
 */
function summit_modify_cases_query( $query ) {
	// Only modify front-end main query
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	// Check if we're on the cases archive (by URL or by WordPress detection)
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( $_SERVER['REQUEST_URI'] ) : '';
	$is_cases_archive = $query->is_post_type_archive( 'case' ) || preg_match( '#/cases/?(\?|$)#', $request_uri );

	if ( ! $is_cases_archive ) {
		return;
	}

	// Ensure we're querying cases
	$query->set( 'post_type', 'case' );

	// Handle area taxonomy filter
	if ( isset( $_GET['area'] ) && ! empty( $_GET['area'] ) ) {
		$query->set(
			'tax_query',
			array(
				array(
					'taxonomy' => 'area',
					'field'    => 'slug',
					'terms'    => sanitize_text_field( $_GET['area'] ),
				),
			)
		);
	}
}
add_action( 'pre_get_posts', 'summit_modify_cases_query' );

/**
 * Handle post_type filter on taxonomy archives.
 *
 * Allows filtering taxonomy archives (like /area/defamation/) by post type
 * using a query parameter (e.g., ?post_type=case).
 *
 * @param WP_Query $query The main query object.
 */
function summit_modify_taxonomy_archive_query( $query ) {
	// Only modify main query on frontend taxonomy archives
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_tax( 'area' ) ) {
		return;
	}

	// Check for result_type filter in URL (prefer result_type, fall back to type for flexibility)
	$result_type_filter = '';
	if ( isset( $_GET['result_type'] ) ) {
		$result_type_filter = sanitize_text_field( $_GET['result_type'] );
	} elseif ( isset( $_GET['type'] ) ) {
		$result_type_filter = sanitize_text_field( $_GET['type'] );
	}

	// Valid post types for filtering
	$valid_types = array( 'post', 'case' );

	if ( $result_type_filter && in_array( $result_type_filter, $valid_types, true ) ) {
		// Filter to specific post type
		$query->set( 'post_type', $result_type_filter );
	} else {
		// Default: show both posts and cases
		$query->set( 'post_type', array( 'post', 'case' ) );
	}
}
add_action( 'pre_get_posts', 'summit_modify_taxonomy_archive_query' );

/**
 * Handle "All Areas" mode URL (/area/) to prevent 404.
 *
 * When accessing /area/ without a specific term slug, WordPress doesn't
 * recognize it as a valid URL and triggers a 404. This filter intercepts
 * that and sets up a proper archive query.
 *
 * @param WP_Query $query The main query object.
 */
function summit_handle_all_areas_url( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( $_SERVER['REQUEST_URI'] ) : '';

	// Check for /area/ without a specific term (All Areas mode)
	if ( preg_match( '#/area/?(\?|$)#', $request_uri ) && ! preg_match( '#/area/[^/\?]+#', $request_uri ) ) {
		// This is "All Areas" mode - set up a query for all posts and cases
		$query->is_404     = false;
		$query->is_archive = true;
		$query->is_home    = false;

		// Set up the query to fetch posts and cases
		$query->set( 'post_type', array( 'post', 'case' ) );
		$query->set( 'posts_per_page', 12 );

		// Apply result_type filter if set
		$result_type = '';
		if ( isset( $_GET['result_type'] ) ) {
			$result_type = sanitize_text_field( $_GET['result_type'] );
		} elseif ( isset( $_GET['type'] ) ) {
			$result_type = sanitize_text_field( $_GET['type'] );
		}

		if ( $result_type === 'post' ) {
			$query->set( 'post_type', 'post' );
		} elseif ( $result_type === 'case' ) {
			$query->set( 'post_type', 'case' );
		}
	}
}
add_action( 'pre_get_posts', 'summit_handle_all_areas_url', 1 );

/**
 * Force archive.php template for area taxonomy even with post_type parameter.
 *
 * When ?post_type=case is added to a taxonomy URL like /area/defamation/,
 * WordPress may try to load a different template. This filter ensures
 * the archive.php template is used when we detect an area term in the URL.
 *
 * @param string $template The path to the template file.
 * @return string Modified template path.
 */
function summit_force_archive_templates( $template ) {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( $_SERVER['REQUEST_URI'] ) : '';

	// Force archive-case.php for /cases/ URLs (even with ?area= parameter)
	if ( preg_match( '#/cases/?(\?|$)#', $request_uri ) ) {
		$case_template = locate_template( 'archive-case.php' );
		if ( $case_template ) {
			return $case_template;
		}
	}

	// Force home.php for /insights/ URLs (even with ?area= parameter)
	if ( preg_match( '#/insights/?(\?|$)#', $request_uri ) ) {
		$home_template = locate_template( 'home.php' );
		if ( $home_template ) {
			return $home_template;
		}
	}

	// Force archive.php for /area/ URLs (both with and without a specific term)
	// This handles "All Areas" mode when accessed via /area/ or /area/?result_type=case
	if ( preg_match( '#/area/?(\?|$)#', $request_uri ) && ! preg_match( '#/area/[^/\?]+#', $request_uri ) ) {
		// /area/ without a specific term - "All Areas" mode
		// Set up query flags to prevent 404
		global $wp_query;
		$wp_query->is_404    = false;
		$wp_query->is_archive = true;

		$archive_template = locate_template( 'archive.php' );
		if ( $archive_template ) {
			return $archive_template;
		}
	}

	// Force archive.php for /area/{term-slug} URLs
	if ( preg_match( '#/area/([^/\?]+)#', $request_uri, $matches ) ) {
		$term_slug = $matches[1];
		$term      = get_term_by( 'slug', $term_slug, 'area' );

		if ( $term && ! is_wp_error( $term ) ) {
			// Set up the queried object for the template
			global $wp_query;
			$wp_query->queried_object    = $term;
			$wp_query->queried_object_id = $term->term_id;
			$wp_query->is_tax            = true;
			$wp_query->is_archive        = true;

			// Return archive.php template
			$archive_template = locate_template( 'archive.php' );
			if ( $archive_template ) {
				return $archive_template;
			}
		}
	}

	return $template;
}
add_filter( 'template_include', 'summit_force_archive_templates', 99 );

// =============================================================================
// Enhanced Search Functionality
// =============================================================================

/**
 * Extend search to include custom post types and ACF fields.
 *
 * Makes search "smarter" by:
 * 1. Including page, post (insights), case, team, and service post types
 * 2. Searching ACF custom fields (title, content, meta fields)
 * 3. Supporting post type filtering via URL parameter
 * 4. Weighting results by relevance
 * 5. Detecting post type name searches (e.g., "team", "insights")
 *
 * @param WP_Query $query The main query object.
 */
function summit_modify_search_query( $query ) {
	// Only modify front-end main search queries
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
		return;
	}

	// Define searchable post types
	$searchable_types = array( 'page', 'post', 'case', 'team', 'service' );

	// Get search term for post type name detection
	$search_term = strtolower( trim( $query->get( 's' ) ) );

	// Map search terms to post types (singular and plural forms)
	$post_type_keywords = array(
		'team'       => 'team',
		'teams'      => 'team',
		'team member' => 'team',
		'team members' => 'team',
		'insight'    => 'post',
		'insights'   => 'post',
		'blog'       => 'post',
		'blogs'      => 'post',
		'post'       => 'post',
		'posts'      => 'post',
		'case'       => 'case',
		'cases'      => 'case',
		'service'    => 'service',
		'services'   => 'service',
		'page'       => 'page',
		'pages'      => 'page',
	);

	// Check if search term matches a post type keyword
	$matched_post_type = isset( $post_type_keywords[ $search_term ] ) ? $post_type_keywords[ $search_term ] : null;

	// Check for post type filter from URL
	if ( isset( $_GET['type'] ) && ! empty( $_GET['type'] ) ) {
		$requested_type = sanitize_text_field( $_GET['type'] );
		if ( in_array( $requested_type, $searchable_types, true ) ) {
			$query->set( 'post_type', $requested_type );
		} else {
			$query->set( 'post_type', $searchable_types );
		}
	} elseif ( $matched_post_type ) {
		// Search term matches a post type name - return all of that type
		$query->set( 'post_type', $matched_post_type );
		// Clear the search term so we get ALL posts of this type
		$query->set( 's', '' );
		// Store original search term for display purposes
		$query->set( 'summit_original_search', $search_term );
	} else {
		// Search all post types by default
		$query->set( 'post_type', $searchable_types );
	}

	// Set posts per page for search results
	$query->set( 'posts_per_page', 12 );
}
add_action( 'pre_get_posts', 'summit_modify_search_query' );

/**
 * Extend search to include ACF custom fields.
 *
 * This joins the postmeta table and searches in meta_value fields,
 * allowing searches to match content stored in ACF fields.
 *
 * @param string   $join  The JOIN clause of the query.
 * @param WP_Query $query The WP_Query instance.
 * @return string Modified JOIN clause.
 */
function summit_search_join_meta( $join, $query ) {
	global $wpdb;

	if ( ! is_admin() && $query->is_main_query() && $query->is_search() ) {
		$join .= " LEFT JOIN {$wpdb->postmeta} AS summit_meta ON ({$wpdb->posts}.ID = summit_meta.post_id) ";
	}

	return $join;
}
add_filter( 'posts_join', 'summit_search_join_meta', 10, 2 );

/**
 * Modify search WHERE clause to include ACF meta fields.
 *
 * Searches in:
 * - Post title
 * - Post content
 * - Post excerpt
 * - ACF meta values (for fields like team short_description, role, etc.)
 *
 * @param string   $where The WHERE clause of the query.
 * @param WP_Query $query The WP_Query instance.
 * @return string Modified WHERE clause.
 */
function summit_search_where_meta( $where, $query ) {
	global $wpdb;

	if ( ! is_admin() && $query->is_main_query() && $query->is_search() ) {
		$search_term = $query->get( 's' );

		if ( ! empty( $search_term ) ) {
			// Escape the search term for LIKE queries
			$like_term = '%' . $wpdb->esc_like( $search_term ) . '%';

			// Define ACF fields to search in
			$acf_fields = array(
				'short_description', // Team member short description
				'role',              // Team member role
				'email',             // Team member email
				'phone',             // Team member phone
				'affiliations',      // Team affiliations
				'education',         // Team education
			);

			// Build meta key conditions
			$meta_key_conditions = array();
			foreach ( $acf_fields as $field ) {
				$meta_key_conditions[] = $wpdb->prepare( "summit_meta.meta_key = %s", $field );
			}

			// Also allow searching any non-internal meta keys (those not starting with _)
			$meta_key_conditions[] = "summit_meta.meta_key NOT LIKE '\_%'";

			$meta_key_where = implode( ' OR ', $meta_key_conditions );

			// Modify WHERE to include meta search
			$where = preg_replace(
				"/\(\s*{$wpdb->posts}.post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
				"(
					{$wpdb->posts}.post_title LIKE $1
					OR {$wpdb->posts}.post_excerpt LIKE $1
					OR (
						summit_meta.meta_value LIKE $1
						AND ({$meta_key_where})
					)
				)",
				$where
			);
		}
	}

	return $where;
}
add_filter( 'posts_where', 'summit_search_where_meta', 10, 2 );

/**
 * Remove duplicate results from meta join search.
 *
 * @param string   $distinct The DISTINCT clause of the query.
 * @param WP_Query $query    The WP_Query instance.
 * @return string Modified DISTINCT clause.
 */
function summit_search_distinct( $distinct, $query ) {
	if ( ! is_admin() && $query->is_main_query() && $query->is_search() ) {
		return 'DISTINCT';
	}
	return $distinct;
}
add_filter( 'posts_distinct', 'summit_search_distinct', 10, 2 );

/**
 * Count search results including ACF meta field matches.
 *
 * This helper function performs a direct SQL count query with the same
 * JOIN and WHERE logic as the main search filters. Use this for accurate
 * counts in secondary queries (like sidebar type counts).
 *
 * @param string       $search_term The search term.
 * @param string|array $post_type   The post type(s) to search.
 * @return int The count of matching posts.
 */
function summit_count_search_results( $search_term, $post_type = '' ) {
	global $wpdb;

	if ( empty( $search_term ) ) {
		return 0;
	}

	// Build post type condition
	if ( is_array( $post_type ) ) {
		$post_types_sql = "'" . implode( "','", array_map( 'esc_sql', $post_type ) ) . "'";
		$post_type_where = "AND {$wpdb->posts}.post_type IN ({$post_types_sql})";
	} elseif ( ! empty( $post_type ) ) {
		$post_type_where = $wpdb->prepare( "AND {$wpdb->posts}.post_type = %s", $post_type );
	} else {
		$post_type_where = "AND {$wpdb->posts}.post_type IN ('page', 'post', 'case', 'team', 'service')";
	}

	// Escape the search term for LIKE queries
	$like_term = '%' . $wpdb->esc_like( $search_term ) . '%';

	// Define ACF fields to search in (same as summit_search_where_meta)
	$acf_fields = array(
		'short_description',
		'role',
		'email',
		'phone',
		'affiliations',
		'education',
	);

	// Build meta key conditions
	$meta_key_conditions = array();
	foreach ( $acf_fields as $field ) {
		$meta_key_conditions[] = $wpdb->prepare( "summit_meta.meta_key = %s", $field );
	}
	$meta_key_conditions[] = "summit_meta.meta_key NOT LIKE '\_%'";
	$meta_key_where = implode( ' OR ', $meta_key_conditions );

	// Build the full query
	$sql = $wpdb->prepare(
		"SELECT COUNT(DISTINCT {$wpdb->posts}.ID)
		FROM {$wpdb->posts}
		LEFT JOIN {$wpdb->postmeta} AS summit_meta ON ({$wpdb->posts}.ID = summit_meta.post_id)
		WHERE {$wpdb->posts}.post_status = 'publish'
		{$post_type_where}
		AND (
			{$wpdb->posts}.post_title LIKE %s
			OR {$wpdb->posts}.post_content LIKE %s
			OR {$wpdb->posts}.post_excerpt LIKE %s
			OR (
				summit_meta.meta_value LIKE %s
				AND ({$meta_key_where})
			)
		)",
		$like_term,
		$like_term,
		$like_term,
		$like_term
	);

	return (int) $wpdb->get_var( $sql );
}

/**
 * Order search results by relevance or role hierarchy.
 *
 * For team results, orders by role hierarchy (partner > associate > clerk > assistant).
 * For other searches, prioritizes:
 * 1. Exact title matches
 * 2. Title contains search term
 * 3. Content/excerpt matches
 * 4. Meta field matches
 *
 * @param string   $orderby The ORDER BY clause of the query.
 * @param WP_Query $query   The WP_Query instance.
 * @return string Modified ORDER BY clause.
 */
function summit_search_orderby( $orderby, $query ) {
	global $wpdb;

	if ( ! is_admin() && $query->is_main_query() && $query->is_search() ) {
		$post_type   = $query->get( 'post_type' );
		$search_term = $query->get( 's' );

		// Check if filtering/searching for team only
		$is_team_only = ( $post_type === 'team' ) ||
						( isset( $_GET['type'] ) && $_GET['type'] === 'team' );

		if ( $is_team_only ) {
			// Order team members by role hierarchy, then alphabetically by title
			// This requires a subquery to get the role meta value
			$orderby = "(
				SELECT CASE summit_role.meta_value
					WHEN 'partner' THEN 1
					WHEN 'associate' THEN 2
					WHEN 'clerk' THEN 3
					WHEN 'assistant' THEN 4
					ELSE 5
				END
				FROM {$wpdb->postmeta} AS summit_role
				WHERE summit_role.post_id = {$wpdb->posts}.ID
				AND summit_role.meta_key = 'role'
				LIMIT 1
			) ASC,
			{$wpdb->posts}.post_title ASC";
		} elseif ( ! empty( $search_term ) ) {
			$like_term  = '%' . $wpdb->esc_like( $search_term ) . '%';
			$exact_term = $wpdb->esc_like( $search_term );

			// Custom relevance scoring
			$orderby = $wpdb->prepare(
				"(CASE
					WHEN {$wpdb->posts}.post_title = %s THEN 1
					WHEN {$wpdb->posts}.post_title LIKE %s THEN 2
					WHEN {$wpdb->posts}.post_excerpt LIKE %s THEN 3
					WHEN {$wpdb->posts}.post_content LIKE %s THEN 4
					ELSE 5
				END) ASC,
				{$wpdb->posts}.post_date DESC",
				$exact_term,
				$like_term,
				$like_term,
				$like_term
			);
		}
	}

	return $orderby;
}
add_filter( 'posts_orderby', 'summit_search_orderby', 10, 2 );

/**
 * Customize document title for search results page.
 *
 * Uses SEOPress filter to override title for search pages,
 * avoiding issues with empty search terms being wrapped in quotes.
 *
 * @param string $title The document title.
 * @return string Modified title.
 */
function summit_search_document_title( $title ) {
	// Only handle search pages - check for 's' parameter in URL
	if ( ! is_search() && ! isset( $_GET['s'] ) ) {
		return $title;
	}

	$search_term = isset( $_GET['s'] ) ? trim( sanitize_text_field( $_GET['s'] ) ) : '';
	$type_filter = isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : '';
	$site_name   = get_bloginfo( 'name' );

	$type_labels = array(
		'page'    => __( 'Pages', 'summit-law-theme' ),
		'post'    => __( 'Insights', 'summit-law-theme' ),
		'case'    => __( 'Cases', 'summit-law-theme' ),
		'team'    => __( 'Team Members', 'summit-law-theme' ),
		'service' => __( 'Services', 'summit-law-theme' ),
	);

	$has_search_term = strlen( $search_term ) > 0;
	$has_type_filter = ! empty( $type_filter ) && isset( $type_labels[ $type_filter ] );

	// Build the page title portion
	if ( $has_search_term && $has_type_filter ) {
		$page_title = sprintf( __( 'Site Index: "%1$s" in %2$s', 'summit-law-theme' ), $search_term, $type_labels[ $type_filter ] );
	} elseif ( $has_search_term ) {
		$page_title = sprintf( __( 'Site Index: "%s"', 'summit-law-theme' ), $search_term );
	} elseif ( $has_type_filter ) {
		$page_title = sprintf( __( 'Site Index: %s', 'summit-law-theme' ), $type_labels[ $type_filter ] );
	} else {
		$page_title = __( 'Site Index', 'summit-law-theme' );
	}

	return $page_title . ' - ' . $site_name;
}
// Hook into SEOPress title filter (used when SEOPress is active)
add_filter( 'seopress_titles_title', 'summit_search_document_title' );

/**
 * Fallback for when SEOPress is not active.
 * Uses pre_get_document_title with maximum priority.
 */
function summit_search_pre_document_title_fallback( $title ) {
	// Only run if SEOPress is not active
	if ( function_exists( 'seopress_init' ) ) {
		return $title;
	}

	// Only handle search pages
	if ( ! is_search() && ! isset( $_GET['s'] ) ) {
		return $title;
	}

	// summit_search_document_title already includes site name
	return summit_search_document_title( '' );
}
add_filter( 'pre_get_document_title', 'summit_search_pre_document_title_fallback', 999 );

/**
 * Set document title for area archive pages (/area/ and /area/{term}/).
 *
 * Handles:
 * - All Areas mode (/area/)
 * - Specific area pages (/area/{term}/)
 * - Result type filters (?result_type=post or ?result_type=case)
 * - No Results indication when filtering returns zero results
 *
 * @param string $title The document title.
 * @return string Modified title.
 */
function summit_area_archive_document_title( $title ) {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( $_SERVER['REQUEST_URI'] ) : '';
	$site_name   = get_bloginfo( 'name' );

	// Check for result type filter
	$result_type = '';
	if ( isset( $_GET['result_type'] ) ) {
		$result_type = sanitize_text_field( $_GET['result_type'] );
	} elseif ( isset( $_GET['type'] ) ) {
		$result_type = sanitize_text_field( $_GET['type'] );
	}

	// Check for /area/ without a specific term (All Areas mode)
	if ( preg_match( '#/area/?(\?|$)#', $request_uri ) && ! preg_match( '#/area/[^/\?]+#', $request_uri ) ) {
		// Count posts for "No Results" check
		if ( $result_type ) {
			$count_query = new WP_Query( array(
				'post_type'      => $result_type === 'post' ? 'post' : 'case',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			) );
			$has_results = $count_query->found_posts > 0;

			if ( $result_type === 'post' ) {
				$page_title = $has_results
					? __( 'Insights: All Areas', 'summit-law-theme' )
					: __( 'Insights: All Areas - No Results', 'summit-law-theme' );
			} else {
				$page_title = $has_results
					? __( 'Cases: All Areas', 'summit-law-theme' )
					: __( 'Cases: All Areas - No Results', 'summit-law-theme' );
			}
		} else {
			$page_title = __( 'All Areas', 'summit-law-theme' );
		}

		return $page_title . ' - ' . $site_name;
	}

	// Check for /area/{term-slug} (specific area page)
	if ( preg_match( '#/area/([^/\?]+)#', $request_uri, $matches ) ) {
		$term_slug = $matches[1];
		$term      = get_term_by( 'slug', $term_slug, 'area' );

		if ( $term && ! is_wp_error( $term ) ) {
			$area_name = $term->name;

			if ( $result_type ) {
				// Count posts/cases in this specific area
				$count_query = new WP_Query( array(
					'post_type'      => $result_type === 'post' ? 'post' : 'case',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'tax_query'      => array(
						array(
							'taxonomy' => 'area',
							'field'    => 'term_id',
							'terms'    => $term->term_id,
						),
					),
				) );
				$has_results = $count_query->found_posts > 0;

				if ( $result_type === 'post' ) {
					$page_title = $has_results
						? sprintf( __( 'Insights: %s', 'summit-law-theme' ), $area_name )
						: sprintf( __( 'Insights: %s - No Results', 'summit-law-theme' ), $area_name );
				} else {
					$page_title = $has_results
						? sprintf( __( 'Cases: %s', 'summit-law-theme' ), $area_name )
						: sprintf( __( 'Cases: %s - No Results', 'summit-law-theme' ), $area_name );
				}
			} else {
				// No result type filter - just show area name
				$page_title = $area_name;
			}

			return $page_title . ' - ' . $site_name;
		}
	}

	return $title;
}
add_filter( 'pre_get_document_title', 'summit_area_archive_document_title', 998 );
add_filter( 'seopress_titles_title', 'summit_area_archive_document_title' );

/**
 * Set document title for Insights archive page (/insights/).
 *
 * Handles:
 * - Base Insights archive
 * - Area filter (?area={slug})
 *
 * @param string $title The current title.
 * @return string Modified title.
 */
function summit_insights_archive_document_title( $title ) {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( $_SERVER['REQUEST_URI'] ) : '';
	$site_name   = get_bloginfo( 'name' );

	// Only handle /insights/ URLs
	if ( ! preg_match( '#/insights/?(\?|$)#', $request_uri ) ) {
		return $title;
	}

	// Get the posts page title (if set) or default to "Insights"
	$posts_page_id = get_option( 'page_for_posts' );
	$base_title    = $posts_page_id ? get_the_title( $posts_page_id ) : __( 'Insights', 'summit-law-theme' );

	// Check for area filter
	$area_slug = isset( $_GET['area'] ) ? sanitize_text_field( $_GET['area'] ) : '';

	if ( $area_slug ) {
		$area_term = get_term_by( 'slug', $area_slug, 'area' );
		if ( $area_term && ! is_wp_error( $area_term ) ) {
			return sprintf( '%s: %s - %s', $base_title, $area_term->name, $site_name );
		}
	}

	return $base_title . ' - ' . $site_name;
}
add_filter( 'pre_get_document_title', 'summit_insights_archive_document_title', 997 );
add_filter( 'seopress_titles_title', 'summit_insights_archive_document_title' );

/**
 * Set document title for Cases archive page (/cases/).
 *
 * Handles:
 * - Base Cases archive
 * - Area filter (?area={slug})
 *
 * @param string $title The current title.
 * @return string Modified title.
 */
function summit_cases_archive_document_title( $title ) {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( $_SERVER['REQUEST_URI'] ) : '';
	$site_name   = get_bloginfo( 'name' );

	// Only handle /cases/ URLs (use URL matching for reliability with query params)
	if ( ! preg_match( '#/cases/?(\?|$)#', $request_uri ) ) {
		return $title;
	}

	$base_title = __( 'Cases', 'summit-law-theme' );

	// Check for area filter
	$area_slug = isset( $_GET['area'] ) ? sanitize_text_field( $_GET['area'] ) : '';

	if ( $area_slug ) {
		$area_term = get_term_by( 'slug', $area_slug, 'area' );
		if ( $area_term && ! is_wp_error( $area_term ) ) {
			return sprintf( '%s: %s - %s', $base_title, $area_term->name, $site_name );
		}
	}

	return $base_title . ' - ' . $site_name;
}
add_filter( 'pre_get_document_title', 'summit_cases_archive_document_title', 996 );
add_filter( 'seopress_titles_title', 'summit_cases_archive_document_title' );