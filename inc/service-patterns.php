<?php
/**
 * Service Block Patterns
 *
 * Register block patterns for parent and child service posts.
 * These patterns automatically load based on the hierarchical parent selection.
 *
 * @package Summit_Law_Theme
 */

/**
 * Register Services pattern category
 */
add_action( 'init', function() {
	register_block_pattern_category(
		'services',
		[
			'label'       => __( 'Services', 'summit-law-theme' ),
			'description' => __( 'Patterns for service post types', 'summit-law-theme' ),
		]
	);
	error_log( 'Service Patterns: Services category registered' );
} );

/**
 * Register Parent Service Pattern
 *
 * Used for top-level services (Insurance Litigation, Commercial Litigation, Mediation)
 */
add_action( 'init', function() {
	if ( ! function_exists( 'register_block_pattern' ) ) {
		error_log( 'Service Patterns: ERROR - register_block_pattern function not available' );
		return;
	}

	register_block_pattern(
		'summit-law/service-parent-pattern',
		[
			'title'       => __( 'Service – Parent', 'summit-law-theme' ),
			'description' => __( 'Pattern used for top-level services. Automatically loaded when creating a new service without a parent.', 'summit-law-theme' ),
			'categories'  => [ 'services' ],
			'keywords'    => [ 'service', 'parent', 'main' ],
			'content'     => '<!-- wp:acf/hero-banner /-->

<!-- wp:acf/breadcrumbs /-->

<!-- wp:acf/service-fields-parent /-->',
		]
	);

	error_log( 'Service Patterns: Parent pattern registered - summit-law/service-parent-pattern' );
} );

/**
 * Register Child Service Pattern
 *
 * Used for practice areas under a parent service
 */
add_action( 'init', function() {
	if ( ! function_exists( 'register_block_pattern' ) ) {
		error_log( 'Service Patterns: ERROR - register_block_pattern function not available for child pattern' );
		return;
	}

	register_block_pattern(
		'summit-law/service-child-pattern',
		[
			'title'       => __( 'Service – Child (Practice Area)', 'summit-law-theme' ),
			'description' => __( 'Pattern used for child services (practice areas). Automatically loaded when creating a new service with a parent selected.', 'summit-law-theme' ),
			'categories'  => [ 'services' ],
			'keywords'    => [ 'service', 'child', 'area', 'practice' ],
			'content'     => '<!-- wp:acf/hero-banner /-->

<!-- wp:acf/breadcrumbs /-->

<!-- wp:acf/content-group /-->

<!-- wp:acf/bullet-group /-->

<!-- wp:acf/bullet-group /-->

<!-- wp:acf/services-areas-loop /-->

<!-- wp:acf/accordion /-->

<!-- wp:acf/cta /-->',
		]
	);

	error_log( 'Service Patterns: Child pattern registered - summit-law/service-child-pattern' );
} );

/**
 * Ensure patterns are available in block editor settings
 *
 * WordPress patterns need to be explicitly added to editor settings to be accessible via JavaScript
 */
add_filter( 'block_editor_settings_all', function( $settings ) {
	error_log( 'Service Patterns: block_editor_settings_all filter called' );

	// Get all registered patterns
	$all_patterns = WP_Block_Patterns_Registry::get_instance()->get_all_registered();
	error_log( 'Service Patterns: Total registered patterns: ' . count( $all_patterns ) );

	// Find our service patterns
	$service_patterns = array_filter( $all_patterns, function( $pattern ) {
		return strpos( $pattern['name'], 'summit-law/service-' ) === 0;
	} );

	error_log( 'Service Patterns: Found ' . count( $service_patterns ) . ' service patterns' );
	error_log( 'Service Patterns: Pattern names: ' . implode( ', ', array_column( $service_patterns, 'name' ) ) );

	// Ensure __experimentalBlockPatterns exists and add our patterns
	if ( ! isset( $settings['__experimentalBlockPatterns'] ) ) {
		$settings['__experimentalBlockPatterns'] = [];
		error_log( 'Service Patterns: Created __experimentalBlockPatterns array' );
	}

	// Add service patterns to the editor
	foreach ( $service_patterns as $pattern ) {
		$settings['__experimentalBlockPatterns'][] = $pattern;
		error_log( 'Service Patterns: Added pattern to editor: ' . $pattern['name'] );
	}

	error_log( 'Service Patterns: Total patterns in __experimentalBlockPatterns: ' . count( $settings['__experimentalBlockPatterns'] ) );

	return $settings;
}, 10, 1 );
