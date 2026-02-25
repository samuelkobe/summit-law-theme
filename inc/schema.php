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
		'default'           => 'Ontario',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'summit_schema_area_served', array(
		'label'   => __( 'Area Served', 'summit-law' ),
		'section' => 'summit_schema_section',
		'type'    => 'text',
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
	$area_served = get_theme_mod( 'summit_schema_area_served', 'Ontario' );
	if ( $area_served ) {
		$schema['areaServed'] = $area_served;
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
	$area_served = get_theme_mod( 'summit_schema_area_served', 'Ontario' );
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
