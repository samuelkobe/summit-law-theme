<?php
/**
 * Single Service Template
 *
 * Handles both parent services (service types) and child services (practice areas)
 * Uses conditional logic to display different layouts
 */

get_header();

while ( have_posts() ) : the_post();

	// Check if this is a parent service (no parent) or child service (has parent)
	$parent_id = wp_get_post_parent_id( get_the_ID() );
	$is_parent = ( $parent_id === 0 );

	if ( $is_parent ) {
		// Load template for parent services (Insurance Litigation, Commercial, Mediation)
		get_template_part( 'parts/content', 'service-parent' );
	} else {
		// Load template for child services (Practice Areas)
		get_template_part( 'parts/content', 'service-area' );
	}

endwhile;

get_footer();
