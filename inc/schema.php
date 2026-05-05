<?php
/**
 * Schema.org JSON-LD Implementation
 *
 * Adds structured data for SEO via Customizer settings.
 * Outputs LegalService schema on homepage and Service schema on service pages.
 *
 * @package Summit_Law_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Schema Customizer Settings
 */
function summit_schema_customizer_settings( $wp_customize ) {

	// Add Schema Section
	$wp_customize->add_section( 'summit_schema_section', array(
		'title'       => __( 'Schema / SEO', 'summit-law' ),
		'description' => __( 'Configure structured data (JSON-LD) for improved search engine visibility.', 'summit-law' ),
		'priority'    => 35,
	) );

	// Enable Homepage Schema
	$wp_customize->add_setting( 'summit_schema_enable_homepage', array(
		'default'           => true,
		'sanitize_callback' => 'summit_sanitize_checkbox',
	) );
	$wp_customize->add_control( 'summit_schema_enable_homepage', array(
		'label'       => __( 'Enable Homepage Schema', 'summit-law' ),
		'description' => __( 'Output LegalService schema on the homepage.', 'summit-law' ),
		'section'     => 'summit_schema_section',
		'type'        => 'checkbox',
	) );

	// Enable Service Pages Schema
	$wp_customize->add_setting( 'summit_schema_enable_services', array(
		'default'           => true,
		'sanitize_callback' => 'summit_sanitize_checkbox',
	) );
	$wp_customize->add_control( 'summit_schema_enable_services', array(
		'label'       => __( 'Enable Service Pages Schema', 'summit-law' ),
		'description' => __( 'Output Service schema on service post type pages.', 'summit-law' ),
		'section'     => 'summit_schema_section',
		'type'        => 'checkbox',
	) );

	// Firm Name
	$wp_customize->add_setting( 'summit_schema_firm_name', array(
		'default'           => 'Summit Law LLP',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'summit_schema_firm_name', array(
		'label'   => __( 'Firm Name', 'summit-law' ),
		'section' => 'summit_schema_section',
		'type'    => 'text',
	) );

	// Firm URL
	$wp_customize->add_setting( 'summit_schema_firm_url', array(
		'default'           => home_url(),
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'summit_schema_firm_url', array(
		'label'   => __( 'Firm URL', 'summit-law' ),
		'section' => 'summit_schema_section',
		'type'    => 'url',
	) );

	// Logo URL
	$wp_customize->add_setting( 'summit_schema_logo', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'summit_schema_logo', array(
		'label'       => __( 'Firm Logo', 'summit-law' ),
		'description' => __( 'Used in schema markup. Google recommends at least 112x112px, ideally 1200px wide.', 'summit-law' ),
		'section'     => 'summit_schema_section',
	) ) );

	// Firm Image (optional)
	$wp_customize->add_setting( 'summit_schema_image', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'summit_schema_image', array(
		'label'       => __( 'Firm Image (Optional)', 'summit-law' ),
		'description' => __( 'Photo of the firm or office for schema.', 'summit-law' ),
		'section'     => 'summit_schema_section',
	) ) );

	// Phone Number
	$wp_customize->add_setting( 'summit_schema_phone', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'summit_schema_phone', array(
		'label'       => __( 'Phone Number', 'summit-law' ),
		'description' => __( 'Format: +1-613-555-0000', 'summit-law' ),
		'section'     => 'summit_schema_section',
		'type'        => 'text',
	) );

	// Street Address
	$wp_customize->add_setting( 'summit_schema_street', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'summit_schema_street', array(
		'label'   => __( 'Street Address', 'summit-law' ),
		'section' => 'summit_schema_section',
		'type'    => 'text',
	) );

	// City
	$wp_customize->add_setting( 'summit_schema_city', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'summit_schema_city', array(
		'label'   => __( 'City', 'summit-law' ),
		'section' => 'summit_schema_section',
		'type'    => 'text',
	) );

	// Province/Region
	$wp_customize->add_setting( 'summit_schema_region', array(
		'default'           => 'ON',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'summit_schema_region', array(
		'label'   => __( 'Province/Region', 'summit-law' ),
		'section' => 'summit_schema_section',
		'type'    => 'text',
	) );

	// Postal Code
	$wp_customize->add_setting( 'summit_schema_postal', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'summit_schema_postal', array(
		'label'   => __( 'Postal Code', 'summit-law' ),
		'section' => 'summit_schema_section',
		'type'    => 'text',
	) );

	// Country
	$wp_customize->add_setting( 'summit_schema_country', array(
		'default'           => 'CA',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'summit_schema_country', array(
		'label'   => __( 'Country Code', 'summit-law' ),
		'section' => 'summit_schema_section',
		'type'    => 'text',
	) );

	// Opening Hours
	$wp_customize->add_setting( 'summit_schema_hours', array(
		'default'           => 'Mo-Fr 09:00-17:00',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'summit_schema_hours', array(
		'label'       => __( 'Opening Hours', 'summit-law' ),
		'description' => __( 'Format: Mo-Fr 09:00-17:00', 'summit-law' ),
		'section'     => 'summit_schema_section',
		'type'        => 'text',
	) );

	// Price Range
	$wp_customize->add_setting( 'summit_schema_price_range', array(
		'default'           => '$$',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'summit_schema_price_range', array(
		'label'       => __( 'Price Range', 'summit-law' ),
		'description' => __( 'Use $ to $$$$ scale.', 'summit-law' ),
		'section'     => 'summit_schema_section',
		'type'        => 'text',
	) );

	// Firm Description (for homepage schema)
	$wp_customize->add_setting( 'summit_schema_description', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_textarea_field',
	) );
	$wp_customize->add_control( 'summit_schema_description', array(
		'label'       => __( 'Firm Description', 'summit-law' ),
		'description' => __( 'Brief description of the firm for schema (1-2 sentences).', 'summit-law' ),
		'section'     => 'summit_schema_section',
		'type'        => 'textarea',
	) );

	// Area Served
	$wp_customize->add_setting( 'summit_schema_area_served', array(
		'default'           => 'Ottawa and Eastern Ontario',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'summit_schema_area_served', array(
		'label'   => __( 'Area Served', 'summit-law' ),
		'section' => 'summit_schema_section',
		'type'    => 'text',
	) );

	// Social Links (sameAs)
	$wp_customize->add_setting( 'summit_schema_same_as', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_textarea_field',
	) );
	$wp_customize->add_control( 'summit_schema_same_as', array(
		'label'       => __( 'Social Links (sameAs)', 'summit-law' ),
		'description' => __( 'One URL per line. Include LinkedIn, Facebook, etc.', 'summit-law' ),
		'section'     => 'summit_schema_section',
		'type'        => 'textarea',
	) );

	// Geo Latitude
	$wp_customize->add_setting( 'summit_schema_geo_lat', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'summit_schema_geo_lat', array(
		'label'   => __( 'Latitude', 'summit-law' ),
		'section' => 'summit_schema_section',
		'type'    => 'text',
	) );

	// Geo Longitude
	$wp_customize->add_setting( 'summit_schema_geo_lng', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'summit_schema_geo_lng', array(
		'label'   => __( 'Longitude', 'summit-law' ),
		'section' => 'summit_schema_section',
		'type'    => 'text',
	) );

	// Enable Breadcrumb Schema
	$wp_customize->add_setting( 'summit_schema_enable_breadcrumbs', array(
		'default'           => true,
		'sanitize_callback' => 'summit_sanitize_checkbox',
	) );
	$wp_customize->add_control( 'summit_schema_enable_breadcrumbs', array(
		'label'       => __( 'Enable Breadcrumb Schema', 'summit-law' ),
		'description' => __( 'Output BreadcrumbList schema on all pages except homepage.', 'summit-law' ),
		'section'     => 'summit_schema_section',
		'type'        => 'checkbox',
	) );

	// Enable BlogPosting Schema
	$wp_customize->add_setting( 'summit_schema_enable_blogposting', array(
		'default'           => true,
		'sanitize_callback' => 'summit_sanitize_checkbox',
	) );
	$wp_customize->add_control( 'summit_schema_enable_blogposting', array(
		'label'       => __( 'Enable BlogPosting Schema', 'summit-law' ),
		'description' => __( 'Output BlogPosting schema on insight posts.', 'summit-law' ),
		'section'     => 'summit_schema_section',
		'type'        => 'checkbox',
	) );
}
add_action( 'customize_register', 'summit_schema_customizer_settings' );

/**
 * Sanitize checkbox values
 */
function summit_sanitize_checkbox( $checked ) {
	return ( ( isset( $checked ) && true == $checked ) ? true : false );
}

/**
 * Get firm provider schema array (reusable)
 */
function summit_get_provider_schema() {
	$provider = array(
		'@type' => 'LegalService',
		'name'  => get_theme_mod( 'summit_schema_firm_name', 'Summit Law LLP' ),
		'url'   => get_theme_mod( 'summit_schema_firm_url', home_url() ),
	);

	// Add address if any address fields are set
	$street  = get_theme_mod( 'summit_schema_street' );
	$city    = get_theme_mod( 'summit_schema_city' );
	$region  = get_theme_mod( 'summit_schema_region', 'ON' );
	$postal  = get_theme_mod( 'summit_schema_postal' );
	$country = get_theme_mod( 'summit_schema_country', 'CA' );

	if ( $street || $city ) {
		$provider['address'] = array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $street,
			'addressLocality' => $city,
			'addressRegion'   => $region,
			'postalCode'      => $postal,
			'addressCountry'  => $country,
		);
	}

	// Add phone if set
	$phone = get_theme_mod( 'summit_schema_phone' );
	if ( $phone ) {
		$provider['telephone'] = $phone;
	}

	// Add logo if set
	$logo = get_theme_mod( 'summit_schema_logo' );
	if ( $logo ) {
		$provider['logo'] = $logo;
	}

	// Add image if set
	$image = get_theme_mod( 'summit_schema_image' );
	if ( $image ) {
		$provider['image'] = $image;
	}

	// Add price range if set
	$price_range = get_theme_mod( 'summit_schema_price_range', '$$' );
	if ( $price_range ) {
		$provider['priceRange'] = $price_range;
	}

	// Add sameAs (social profiles)
	$same_as_raw = get_theme_mod( 'summit_schema_same_as', '' );
	if ( $same_as_raw ) {
		$same_as_urls = array_filter( array_map( 'trim', explode( "\n", $same_as_raw ) ) );
		if ( ! empty( $same_as_urls ) ) {
			$provider['sameAs'] = array_values( $same_as_urls );
		}
	}

	return $provider;
}

/**
 * Output Homepage LegalService Schema
 */
function summit_output_homepage_schema() {
	if ( ! is_front_page() ) {
		return;
	}

	if ( ! get_theme_mod( 'summit_schema_enable_homepage', true ) ) {
		return;
	}

	$schema = array(
		'@context' => 'https://schema.org',
		'@type'    => 'LegalService',
		'name'     => get_theme_mod( 'summit_schema_firm_name', 'Summit Law LLP' ),
		'url'      => get_theme_mod( 'summit_schema_firm_url', home_url() ),
	);

	// Add logo if set
	$logo = get_theme_mod( 'summit_schema_logo' );
	if ( $logo ) {
		$schema['logo'] = $logo;
	}

	// Add image if set
	$image = get_theme_mod( 'summit_schema_image' );
	if ( $image ) {
		$schema['image'] = $image;
	}

	// Add phone if set
	$phone = get_theme_mod( 'summit_schema_phone' );
	if ( $phone ) {
		$schema['telephone'] = $phone;
	}

	// Add description if set
	$description = get_theme_mod( 'summit_schema_description' );
	if ( $description ) {
		$schema['description'] = $description;
	}

	// Add price range
	$price_range = get_theme_mod( 'summit_schema_price_range', '$$' );
	if ( $price_range ) {
		$schema['priceRange'] = $price_range;
	}

	// Add address if any address fields are set
	$street  = get_theme_mod( 'summit_schema_street' );
	$city    = get_theme_mod( 'summit_schema_city' );
	$region  = get_theme_mod( 'summit_schema_region', 'ON' );
	$postal  = get_theme_mod( 'summit_schema_postal' );
	$country = get_theme_mod( 'summit_schema_country', 'CA' );

	if ( $street || $city ) {
		$schema['address'] = array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $street,
			'addressLocality' => $city,
			'addressRegion'   => $region,
			'postalCode'      => $postal,
			'addressCountry'  => $country,
		);
	}

	// Add opening hours
	$hours = get_theme_mod( 'summit_schema_hours', 'Mo-Fr 09:00-17:00' );
	if ( $hours ) {
		$schema['openingHours'] = $hours;
	}

	// Add area served
	$area_served = get_theme_mod( 'summit_schema_area_served', 'Ottawa and Eastern Ontario' );
	if ( $area_served ) {
		$schema['areaServed'] = $area_served;
	}

	// Add sameAs (social profiles)
	$same_as_raw = get_theme_mod( 'summit_schema_same_as', '' );
	if ( $same_as_raw ) {
		$same_as_urls = array_filter( array_map( 'trim', explode( "\n", $same_as_raw ) ) );
		if ( ! empty( $same_as_urls ) ) {
			$schema['sameAs'] = array_values( $same_as_urls );
		}
	}

	// Add geo coordinates if set
	$geo_lat = get_theme_mod( 'summit_schema_geo_lat', '' );
	$geo_lng = get_theme_mod( 'summit_schema_geo_lng', '' );
	if ( $geo_lat && $geo_lng ) {
		$schema['geo'] = array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => $geo_lat,
			'longitude' => $geo_lng,
		);
	}

	// Query parent services to add as offered services
	$parent_services = get_posts( array(
		'post_type'      => 'service',
		'post_parent'    => 0,
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	) );

	if ( $parent_services ) {
		$schema['hasOfferCatalog'] = array(
			'@type'           => 'OfferCatalog',
			'name'            => 'Legal Services',
			'itemListElement' => array(),
		);

		foreach ( $parent_services as $service ) {
			$schema['hasOfferCatalog']['itemListElement'][] = array(
				'@type'        => 'Offer',
				'itemOffered'  => array(
					'@type' => 'Service',
					'name'  => $service->post_title,
					'url'   => get_permalink( $service->ID ),
				),
			);
		}
	}

	summit_output_json_ld( $schema );
}
add_action( 'wp_head', 'summit_output_homepage_schema', 5 );

/**
 * Output Service Page Schema
 */
function summit_output_service_schema() {
	if ( ! is_singular( 'service' ) ) {
		return;
	}

	if ( ! get_theme_mod( 'summit_schema_enable_services', true ) ) {
		return;
	}

	global $post;

	$is_parent = ( $post->post_parent == 0 );

	// Get service description - try excerpt first, then ACF field if exists, then generate from content
	$description = '';
	if ( has_excerpt( $post->ID ) ) {
		$description = get_the_excerpt( $post->ID );
	} elseif ( function_exists( 'get_field' ) && get_field( 'schema_description', $post->ID ) ) {
		$description = get_field( 'schema_description', $post->ID );
	} else {
		// Generate from content
		$content     = wp_strip_all_tags( $post->post_content );
		$description = wp_trim_words( $content, 30, '...' );
	}

	$schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Service',
		'serviceType' => $post->post_title,
		'name'        => $post->post_title,
		'url'         => get_permalink( $post->ID ),
		'provider'    => summit_get_provider_schema(),
	);

	if ( $description ) {
		$schema['description'] = $description;
	}

	// Add area served
	$area_served = get_theme_mod( 'summit_schema_area_served', 'Ottawa and Eastern Ontario' );
	if ( $area_served ) {
		$schema['areaServed'] = $area_served;
	}

	// If parent service, add child services to offer catalog
	if ( $is_parent ) {
		$child_services = get_posts( array(
			'post_type'      => 'service',
			'post_parent'    => $post->ID,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		) );

		if ( $child_services ) {
			$schema['hasOfferCatalog'] = array(
				'@type'           => 'OfferCatalog',
				'name'            => $post->post_title . ' Services',
				'itemListElement' => array(),
			);

			foreach ( $child_services as $child ) {
				$schema['hasOfferCatalog']['itemListElement'][] = array(
					'@type'       => 'Offer',
					'itemOffered' => array(
						'@type' => 'Service',
						'name'  => $child->post_title,
						'url'   => get_permalink( $child->ID ),
					),
				);
			}
		}
	}

	summit_output_json_ld( $schema );
}
add_action( 'wp_head', 'summit_output_service_schema', 5 );

/**
 * Output BlogPosting Schema on Insight (post) Singles
 */
function summit_output_blogposting_schema() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	if ( ! get_theme_mod( 'summit_schema_enable_blogposting', true ) ) {
		return;
	}

	global $post;

	$schema = array(
		'@context'      => 'https://schema.org',
		'@type'         => 'BlogPosting',
		'headline'      => get_the_title(),
		'url'           => get_permalink(),
		'datePublished' => get_the_date( 'c' ),
		'dateModified'  => get_the_modified_date( 'c' ),
	);

	// Author
	$author_name   = get_the_author();
	$schema['author'] = array(
		'@type' => 'Person',
		'name'  => $author_name,
	);

	// Try to link author to their team profile page
	$team_post = get_posts( array(
		'post_type'      => 'team',
		'title'          => $author_name,
		'posts_per_page' => 1,
		'post_status'    => 'publish',
	) );
	if ( $team_post ) {
		$schema['author']['url'] = get_permalink( $team_post[0]->ID );
	}

	// Publisher (the firm)
	$firm_name = get_theme_mod( 'summit_schema_firm_name', 'Summit Law LLP' );
	$logo      = get_theme_mod( 'summit_schema_logo' );

	$publisher = array(
		'@type' => 'Organization',
		'name'  => $firm_name,
		'url'   => get_theme_mod( 'summit_schema_firm_url', home_url() ),
	);
	if ( $logo ) {
		$publisher['logo'] = array(
			'@type' => 'ImageObject',
			'url'   => $logo,
		);
	}
	$schema['publisher'] = $publisher;

	// Featured image
	if ( has_post_thumbnail() ) {
		$thumb_id  = get_post_thumbnail_id();
		$thumb_url = wp_get_attachment_image_url( $thumb_id, 'full' );
		if ( $thumb_url ) {
			$schema['image'] = $thumb_url;
		}
	}

	// Description from excerpt or content
	if ( has_excerpt() ) {
		$schema['description'] = get_the_excerpt();
	} else {
		$content              = wp_strip_all_tags( $post->post_content );
		$schema['description'] = wp_trim_words( $content, 30, '...' );
	}

	// mainEntityOfPage
	$schema['mainEntityOfPage'] = array(
		'@type' => 'WebPage',
		'@id'   => get_permalink(),
	);

	summit_output_json_ld( $schema );
}
add_action( 'wp_head', 'summit_output_blogposting_schema', 5 );

/**
 * Output ContactPage Schema on Contact Page
 */
function summit_output_contact_page_schema() {
	if ( ! is_page( 'contact' ) ) {
		return;
	}

	$firm_name   = get_theme_mod( 'summit_schema_firm_name', 'Summit Law LLP' );
	$phone       = get_theme_mod( 'summit_schema_phone' );
	$main_entity = summit_get_provider_schema();

	// Add contactPoint with telephone
	if ( $phone ) {
		$main_entity['contactPoint'] = array(
			'@type'       => 'ContactPoint',
			'telephone'   => $phone,
			'contactType' => 'customer service',
		);
	}

	// Add opening hours to the main entity
	$hours = get_theme_mod( 'summit_schema_hours', 'Mo-Fr 09:00-17:00' );
	if ( $hours ) {
		$main_entity['openingHours'] = $hours;
	}

	$schema = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'ContactPage',
		'name'       => 'Contact ' . $firm_name,
		'url'        => get_permalink(),
		'mainEntity' => $main_entity,
	);

	summit_output_json_ld( $schema );
}
add_action( 'wp_head', 'summit_output_contact_page_schema', 5 );

/**
 * Output ItemList Schema on Services Archive
 */
function summit_output_services_archive_schema() {
	if ( ! is_post_type_archive( 'service' ) ) {
		return;
	}

	$parent_services = get_posts( array(
		'post_type'      => 'service',
		'post_parent'    => 0,
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'title',
		'order'          => 'ASC',
	) );

	if ( ! $parent_services ) {
		return;
	}

	$items    = array();
	$position = 1;
	foreach ( $parent_services as $service ) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $position,
			'url'      => get_permalink( $service->ID ),
			'name'     => $service->post_title,
		);
		$position++;
	}

	$schema = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'ItemList',
		'name'            => 'Legal Services',
		'itemListElement' => $items,
	);

	summit_output_json_ld( $schema );
}
add_action( 'wp_head', 'summit_output_services_archive_schema', 5 );

/**
 * Output BreadcrumbList Schema (all pages except homepage)
 */
function summit_output_breadcrumb_schema() {
	if ( is_front_page() ) {
		return;
	}

	if ( ! get_theme_mod( 'summit_schema_enable_breadcrumbs', true ) ) {
		return;
	}

	$crumbs = array();

	// Position 1 is always Home
	$crumbs[] = array(
		'name' => get_theme_mod( 'summit_schema_firm_name', 'Summit Law LLP' ),
		'url'  => home_url( '/' ),
	);

	if ( is_singular( 'service' ) ) {
		// Services > [Parent] > [Child]
		global $post;
		$crumbs[] = array( 'name' => 'Services', 'url' => home_url( '/services/' ) );
		if ( $post->post_parent ) {
			$parent   = get_post( $post->post_parent );
			$crumbs[] = array( 'name' => $parent->post_title, 'url' => get_permalink( $parent->ID ) );
		}
		$crumbs[] = array( 'name' => get_the_title(), 'url' => get_permalink() );

	} elseif ( is_post_type_archive( 'service' ) ) {
		$crumbs[] = array( 'name' => 'Services', 'url' => home_url( '/services/' ) );

	} elseif ( is_singular( 'post' ) ) {
		// Insights > [Post Title]
		$crumbs[] = array( 'name' => 'Insights', 'url' => home_url( '/insights/' ) );
		$crumbs[] = array( 'name' => get_the_title(), 'url' => get_permalink() );

	} elseif ( is_home() ) {
		// Insights archive page
		$crumbs[] = array( 'name' => 'Insights', 'url' => home_url( '/insights/' ) );

	} elseif ( is_singular( 'case' ) ) {
		$crumbs[] = array( 'name' => 'Cases', 'url' => get_post_type_archive_link( 'case' ) );
		$crumbs[] = array( 'name' => get_the_title(), 'url' => get_permalink() );

	} elseif ( is_post_type_archive( 'case' ) ) {
		$crumbs[] = array( 'name' => 'Cases', 'url' => get_post_type_archive_link( 'case' ) );

	} elseif ( is_singular( 'team' ) ) {
		$crumbs[] = array( 'name' => 'Team', 'url' => home_url( '/team/' ) );
		$crumbs[] = array( 'name' => get_the_title(), 'url' => get_permalink() );

	} elseif ( is_post_type_archive( 'team' ) ) {
		$crumbs[] = array( 'name' => 'Team', 'url' => home_url( '/team/' ) );

	} elseif ( is_page() ) {
		// Regular pages — handle nested pages via ancestors
		global $post;
		$ancestors = array_reverse( get_post_ancestors( $post->ID ) );
		foreach ( $ancestors as $ancestor_id ) {
			$crumbs[] = array(
				'name' => get_the_title( $ancestor_id ),
				'url'  => get_permalink( $ancestor_id ),
			);
		}
		$crumbs[] = array( 'name' => get_the_title(), 'url' => get_permalink() );

	} elseif ( is_search() ) {
		$crumbs[] = array( 'name' => 'Search Results', 'url' => get_search_link() );

	} elseif ( is_404() ) {
		$crumbs[] = array( 'name' => 'Page Not Found', 'url' => '' );
	}

	// Build the schema
	$items = array();
	foreach ( $crumbs as $position => $crumb ) {
		$item = array(
			'@type'    => 'ListItem',
			'position' => $position + 1,
			'name'     => $crumb['name'],
		);
		if ( ! empty( $crumb['url'] ) ) {
			$item['item'] = $crumb['url'];
		}
		$items[] = $item;
	}

	$schema = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $items,
	);

	summit_output_json_ld( $schema );
}
add_action( 'wp_head', 'summit_output_breadcrumb_schema', 5 );

/**
 * Output JSON-LD script tag
 *
 * @param array $schema The schema array to output
 */
function summit_output_json_ld( $schema ) {
	if ( empty( $schema ) ) {
		return;
	}

	$json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );

	if ( $json ) {
		echo "\n" . '<script type="application/ld+json">' . "\n";
		echo $json;
		echo "\n" . '</script>' . "\n";
	}
}
