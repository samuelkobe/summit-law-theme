<?php
/**
 * Generic Archive Template
 *
 * Handles:
 * - Taxonomy archives (area, category, tag) with sidebar filters
 * - "All Areas" mode when accessed via /area/ without a specific term
 * - Date archives (year, month, day)
 * - Author archives
 * - Any other archive type
 */

get_header();

// Determine archive title and description
$archive_title       = '';
$archive_description = '';
$back_link           = '';
$back_text           = '';
$is_taxonomy_archive = false;
$is_all_areas_mode   = false;
$current_term        = null;

// Get result_type filter from URL (prefer result_type, fall back to type for flexibility)
$result_type_filter = '';
if ( isset( $_GET['result_type'] ) ) {
	$result_type_filter = sanitize_text_field( $_GET['result_type'] );
} elseif ( isset( $_GET['type'] ) ) {
	$result_type_filter = sanitize_text_field( $_GET['type'] );
}

// Check if we're in "All Areas" mode (accessed via /area/ without a specific term)
$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( $_SERVER['REQUEST_URI'] ) : '';
if ( preg_match( '#/area/?(\?|$)#', $request_uri ) && ! is_tax( 'area' ) ) {
	$is_all_areas_mode   = true;
	$is_taxonomy_archive = true;
	$archive_description = '';

	// Build title based on result type filter
	if ( $result_type_filter === 'post' ) {
		$archive_title = __( 'Insights: All Areas', 'summit-law-theme' );
	} elseif ( $result_type_filter === 'case' ) {
		$archive_title = __( 'Cases: All Areas', 'summit-law-theme' );
	} else {
		$archive_title = __( 'All Areas', 'summit-law-theme' );
	}
}

// Check for area taxonomy - use get_queried_object() as it's more reliable with query parameters
$queried_object = get_queried_object();

if ( ! $is_all_areas_mode && ( is_tax( 'area' ) || ( $queried_object instanceof WP_Term && $queried_object->taxonomy === 'area' ) ) ) {
	$is_taxonomy_archive = true;
	$current_term        = $queried_object;
	$area_name           = $current_term->name;
	$archive_description = $current_term->description;

	// Build title based on result type filter (will be updated after counts are calculated)
	$archive_title = $area_name;

	// Back link based on current filter
	if ( $result_type_filter === 'case' ) {
		$back_link = get_post_type_archive_link( 'case' );
		$back_text = __( 'All Cases', 'summit-law-theme' );
	} elseif ( $result_type_filter === 'post' ) {
		$back_link = home_url( '/insights/' );
		$back_text = __( 'All Insights', 'summit-law-theme' );
	} else {
		// No filter or "All Results" - don't show a back link since we're already showing everything
		$back_link = '';
		$back_text = '';
	}
} elseif ( ! $is_all_areas_mode ) {
	// Handle other archive types
	if ( is_category() ) {
		$archive_title       = single_cat_title( '', false );
		$archive_description = category_description();
		$back_link           = home_url( '/insights/' );
		$back_text           = __( 'All Insights', 'summit-law-theme' );
	} elseif ( is_tag() ) {
		$archive_title       = single_tag_title( '', false );
		$archive_description = tag_description();
		$back_link           = home_url( '/insights/' );
		$back_text           = __( 'All Insights', 'summit-law-theme' );
	} elseif ( is_author() ) {
		$archive_title = sprintf( __( 'Posts by %s', 'summit-law-theme' ), get_the_author() );
		$back_link     = home_url( '/insights/' );
		$back_text     = __( 'All Insights', 'summit-law-theme' );
	} elseif ( is_year() ) {
		$archive_title = get_the_date( 'Y' );
		$back_link     = home_url( '/insights/' );
		$back_text     = __( 'All Insights', 'summit-law-theme' );
	} elseif ( is_month() ) {
		$archive_title = get_the_date( 'F Y' );
		$back_link     = home_url( '/insights/' );
		$back_text     = __( 'All Insights', 'summit-law-theme' );
	} elseif ( is_day() ) {
		$archive_title = get_the_date( 'F j, Y' );
		$back_link     = home_url( '/insights/' );
		$back_text     = __( 'All Insights', 'summit-law-theme' );
	} else {
		$archive_title = __( 'Archives', 'summit-law-theme' );
		$back_link     = home_url( '/' );
		$back_text     = __( 'Home', 'summit-law-theme' );
	}
}

// For taxonomy archives, get counts for each post type
$type_counts = array();
if ( $is_taxonomy_archive ) {
	if ( $is_all_areas_mode ) {
		// Count all posts and cases (no area filter)
		$posts_query = new WP_Query( array(
			'post_type'      => 'post',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );
		$type_counts['post'] = $posts_query->found_posts;

		$cases_query = new WP_Query( array(
			'post_type'      => 'case',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );
		$type_counts['case'] = $cases_query->found_posts;
	} elseif ( $current_term ) {
		// Count posts (insights) in this term
		$posts_query = new WP_Query( array(
			'post_type'      => 'post',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'tax_query'      => array(
				array(
					'taxonomy' => $current_term->taxonomy,
					'field'    => 'term_id',
					'terms'    => $current_term->term_id,
				),
			),
		) );
		$type_counts['post'] = $posts_query->found_posts;

		// Count cases in this term
		$cases_query = new WP_Query( array(
			'post_type'      => 'case',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'tax_query'      => array(
				array(
					'taxonomy' => $current_term->taxonomy,
					'field'    => 'term_id',
					'terms'    => $current_term->term_id,
				),
			),
		) );
		$type_counts['case'] = $cases_query->found_posts;
	}

	// Total count
	$type_counts['all'] = $type_counts['post'] + $type_counts['case'];

	// Update title for specific area pages with result type filter
	if ( ! $is_all_areas_mode && $current_term && $result_type_filter ) {
		$area_name = $current_term->name;

		if ( $result_type_filter === 'post' ) {
			if ( $type_counts['post'] === 0 ) {
				$archive_title = sprintf( __( 'Insights: %s - No Results', 'summit-law-theme' ), $area_name );
			} else {
				$archive_title = sprintf( __( 'Insights: %s', 'summit-law-theme' ), $area_name );
			}
		} elseif ( $result_type_filter === 'case' ) {
			if ( $type_counts['case'] === 0 ) {
				$archive_title = sprintf( __( 'Cases: %s - No Results', 'summit-law-theme' ), $area_name );
			} else {
				$archive_title = sprintf( __( 'Cases: %s', 'summit-law-theme' ), $area_name );
			}
		}
	}

	// Update title for All Areas mode with no results
	if ( $is_all_areas_mode && $result_type_filter ) {
		if ( $result_type_filter === 'post' && $type_counts['post'] === 0 ) {
			$archive_title = __( 'Insights: All Areas - No Results', 'summit-law-theme' );
		} elseif ( $result_type_filter === 'case' && $type_counts['case'] === 0 ) {
			$archive_title = __( 'Cases: All Areas - No Results', 'summit-law-theme' );
		}
	}
}

// Define filter options for taxonomy archives
$filter_types = array(
	''     => __( 'All Results', 'summit-law-theme' ),
	'post' => __( 'Insights', 'summit-law-theme' ),
	'case' => __( 'Cases', 'summit-law-theme' ),
);

// For "All Areas" mode, run a custom query
if ( $is_all_areas_mode ) {
	$paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;

	$query_args = array(
		'post_type'      => array( 'post', 'case' ),
		'posts_per_page' => 12,
		'paged'          => $paged,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	// Filter by result_type if set
	if ( $result_type_filter === 'post' ) {
		$query_args['post_type'] = 'post';
	} elseif ( $result_type_filter === 'case' ) {
		$query_args['post_type'] = 'case';
	}

	$custom_query = new WP_Query( $query_args );
}
?>

<!-- Hero Banner -->
<header class="page-header bg-green-deep lg:h-[30dvh] min-h-[240px] lg:min-h-[320px] flex items-center pt-12 pb-20 lg:py-24">
	<section class="banner-after-content h-full flex flex-col justify-center container mx-auto p-6 relative text-left">
		<h1 class="h1 2xl:text-7xl text-green-accent1 mb-4 lg:mb-10">
			<?php echo esc_html( $archive_title ); ?>
		</h1>
		<?php if ( $archive_description ) : ?>
			<p class="text-white text-lg lg:text-xl w-full md:w-3/4 2xl:w-2/3">
				<?php echo wp_kses_post( $archive_description ); ?>
			</p>
		<?php endif; ?>
	</section>
</header>

<!-- Breadcrumbs -->
<section class="bg-white">
	<div class="container mx-auto flex flex-row justify-between">
		<!-- Mobile: Condensed back link -->
		<nav aria-label="Back navigation" class="md:hidden py-4">
			<?php
			// Determine mobile back link based on context
			if ( $is_taxonomy_archive ) {
				if ( $result_type_filter === 'case' ) {
					// Filtering by Cases -> back to Cases archive
					$mobile_back_url  = get_post_type_archive_link( 'case' );
					$mobile_back_text = __( 'Cases', 'summit-law-theme' );
				} elseif ( $result_type_filter === 'post' ) {
					// Filtering by Insights -> back to Insights archive
					$mobile_back_url  = home_url( '/insights/' );
					$mobile_back_text = __( 'Insights', 'summit-law-theme' );
				} elseif ( ! $is_all_areas_mode && $current_term ) {
					// Specific area with All Results -> back to All Areas
					$mobile_back_url  = home_url( '/area/' );
					$mobile_back_text = __( 'All Areas', 'summit-law-theme' );
				} else {
					// All Areas mode -> back to Home
					$mobile_back_url  = home_url( '/' );
					$mobile_back_text = __( 'Summit Law LLP', 'summit-law-theme' );
				}
			} else {
				// Non-taxonomy archives -> back to Home
				$mobile_back_url  = home_url( '/' );
				$mobile_back_text = __( 'Summit Law LLP', 'summit-law-theme' );
			}
			?>
			<a href="<?php echo esc_url( $mobile_back_url ); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-40 hover:text-green-deep transition-colors">
				<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" class="flex-shrink-0 fill-green-accent1 rotate-180" aria-hidden="true">
					<path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"></path>
				</svg>
				<?php echo esc_html( $mobile_back_text ); ?>
			</a>
		</nav>

		<!-- Desktop: Full breadcrumb trail -->
		<nav aria-label="Breadcrumb" class="hidden md:block py-8">
			<ol class="flex items-center gap-2 text-sm font-semibold">
				<!-- Home -->
				<li>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="text-brand-40 hover:text-green-deep transition-colors">
						<?php esc_html_e( 'Summit Law LLP', 'summit-law-theme' ); ?>
					</a>
				</li>
				<li aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" class="flex-shrink-0 fill-green-accent1" aria-hidden="true">
						<path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"></path>
					</svg>
				</li>

				<?php if ( $is_taxonomy_archive ) : ?>
					<?php if ( $result_type_filter ) : ?>
						<!-- Result type (Insights or Cases) as link -->
						<li>
							<?php if ( $result_type_filter === 'post' ) : ?>
								<a href="<?php echo esc_url( home_url( '/insights/' ) ); ?>" class="text-brand-40 hover:text-green-deep transition-colors">
									<?php esc_html_e( 'Insights', 'summit-law-theme' ); ?>
								</a>
							<?php else : ?>
								<a href="<?php echo esc_url( get_post_type_archive_link( 'case' ) ); ?>" class="text-brand-40 hover:text-green-deep transition-colors">
									<?php esc_html_e( 'Cases', 'summit-law-theme' ); ?>
								</a>
							<?php endif; ?>
						</li>
						<li aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" class="flex-shrink-0 fill-green-accent1" aria-hidden="true">
								<path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"></path>
							</svg>
						</li>
						<!-- Current: Area name (All Areas or specific area) -->
						<li aria-current="page" class="text-green-deep">
							<?php echo $is_all_areas_mode ? esc_html__( 'All Areas', 'summit-law-theme' ) : esc_html( $current_term->name ); ?>
						</li>
					<?php elseif ( ! $is_all_areas_mode && $current_term ) : ?>
						<!-- No result type filter, specific area: show All Areas as link -->
						<li>
							<a href="<?php echo esc_url( home_url( '/area/' ) ); ?>" class="text-brand-40 hover:text-green-deep transition-colors">
								<?php esc_html_e( 'All Areas', 'summit-law-theme' ); ?>
							</a>
						</li>
						<li aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" class="flex-shrink-0 fill-green-accent1" aria-hidden="true">
								<path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"></path>
							</svg>
						</li>
						<!-- Current: Specific area name -->
						<li aria-current="page" class="text-green-deep">
							<?php echo esc_html( $current_term->name ); ?>
						</li>
					<?php else : ?>
						<!-- All Areas mode with no filter: just show All Areas as current -->
						<li aria-current="page" class="text-green-deep">
							<?php esc_html_e( 'All Areas', 'summit-law-theme' ); ?>
						</li>
					<?php endif; ?>
				<?php else : ?>
					<!-- Non-taxonomy archive: show archive title as current -->
					<li aria-current="page" class="text-green-deep">
						<?php echo esc_html( $archive_title ); ?>
					</li>
				<?php endif; ?>
			</ol>
		</nav>

		<!-- Share Links -->
		<div class="py-4 md:py-8 flex flex-row gap-x-4">
			<span class="text-base lowercase hidden lg:block"><?php esc_html_e( 'Share', 'summit-law-theme' ); ?></span>
			<ul class="social-share flex flex-row items-center gap-2">
				<?php
				// Get current page URL and title for sharing
				$share_url   = $is_all_areas_mode ? home_url( '/area/' ) : ( $current_term ? get_term_link( $current_term ) : get_permalink() );
				$page_url    = urlencode( $share_url );
				$page_title  = urlencode( $archive_title );

				// Define share links
				$share_links = array(
					'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $page_url,
					'x'        => 'https://twitter.com/intent/tweet?url=' . $page_url . '&text=' . get_bloginfo( 'name' ) . ' - ' . $page_title,
					'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . $page_url,
					'email'    => 'mailto:?subject=' . get_bloginfo( 'name' ) . ' - ' . $page_title . '&body=Check out this page: ' . $page_url,
				);

				foreach ( $share_links as $type => $url ) :
				?>
					<li class="social-share__item">
						<a href="<?php echo esc_url( $url ); ?>"
							class="social-share__link"
							target="_blank"
							rel="noopener noreferrer"
							aria-label="<?php echo esc_attr( sprintf( __( 'Share on %s', 'summit-law-theme' ), ucfirst( $type ) ) ); ?>">
							<?php echo summit_get_svg_icon( $type, array( 'class' => 'w-6 h-6 fill-green-deep' ) ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</section>

<main id="main" class="site-main">

	<section class="bg-white py-12 lg:pb-24 lg:pt-16">
		<div class="container mx-auto px-6">

			<?php if ( $is_taxonomy_archive ) : ?>
				<!-- Two-column layout with sidebar for taxonomy archives -->
				<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">

					<!-- Left Sidebar: Filters -->
					<aside class="lg:col-span-4 xl:col-span-3">

						<!-- Mobile Filter Toggle -->
						<button
							class="search-filter-toggle lg:hidden w-full flex items-center justify-between p-4 bg-brand-10 rounded-lg mb-4"
							aria-expanded="false"
							aria-controls="archive-filters"
							data-archive-filter-toggle>
							<span class="font-semibold text-brand-black">
								<?php esc_html_e( 'Filter Results', 'summit-law-theme' ); ?>
							</span>
							<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="archive-filter-chevron transition-transform duration-300">
								<path d="M12,17.5c-.4,0-.8-.1-1.1-.4L1.4,7.6c-.6-.6-.6-1.5,0-2.1.6-.6,1.5-.6,2.1,0l8.5,8.5,8.5-8.5c.6-.6,1.5-.6,2.1,0,.6.6.6,1.5,0,2.1l-9.5,9.5c-.3.3-.7.4-1.1.4Z"/>
							</svg>
						</button>

						<!-- Filter Panel -->
						<div
							id="archive-filters"
							class="search-filters max-lg:hidden lg:block"
							data-archive-filters>

							<!-- Browse Areas -->
							<?php
							$all_areas = get_terms( array(
								'taxonomy'   => 'area',
								'hide_empty' => true,
								'orderby'    => 'name',
								'order'      => 'ASC',
							) );

							if ( $all_areas && ! is_wp_error( $all_areas ) ) :
							?>
								<div class="mb-8">
									<h2 class="text-sm uppercase tracking-wider text-brand-40 mb-3">
										<label for="areas-select">
											<?php esc_html_e( 'Browse Areas', 'summit-law-theme' ); ?>
										</label>
									</h2>
									<select
										id="areas-select"
										class="filter-select w-full"
										onchange="if(this.value) window.location.href=this.value;">
										<?php
										// "All Areas" links to /area/ with result_type preserved
										$all_areas_url = home_url( '/area/' );
										if ( $result_type_filter ) {
											$all_areas_url = add_query_arg( 'result_type', $result_type_filter, $all_areas_url );
										}
										?>
										<option value="<?php echo esc_url( $all_areas_url ); ?>" <?php selected( $is_all_areas_mode ); ?>>
											<?php esc_html_e( 'All Areas', 'summit-law-theme' ); ?>
										</option>
										<?php foreach ( $all_areas as $area ) :
											$area_url = get_term_link( $area );
											// Preserve result_type filter when switching areas
											if ( $result_type_filter ) {
												$area_url = add_query_arg( 'result_type', $result_type_filter, $area_url );
											}
											$is_current = ( $current_term && $area->term_id === $current_term->term_id );
										?>
											<option value="<?php echo esc_url( $area_url ); ?>" <?php selected( $is_current ); ?>>
												<?php echo esc_html( $area->name ); ?> (<?php echo esc_html( $area->count ); ?>)
											</option>
										<?php endforeach; ?>
									</select>
								</div>
							<?php endif; ?>

							<!-- Result Type Filter -->
							<div class="mb-8">
								<h2 class="text-sm uppercase tracking-wider text-brand-40 mb-3">
									<?php esc_html_e( 'Result Type', 'summit-law-theme' ); ?>
								</h2>
								<ul class="space-y-2">
									<?php foreach ( $filter_types as $type => $label ) :
										$count = '';
										if ( empty( $type ) ) {
											$count = isset( $type_counts['all'] ) ? $type_counts['all'] : 0;
										} else {
											$count = isset( $type_counts[ $type ] ) ? $type_counts[ $type ] : 0;
										}

										$is_active = ( $result_type_filter === $type );

										// Build filter URL - preserve current area or all areas mode
										if ( $is_all_areas_mode ) {
											$filter_url = home_url( '/area/' );
										} else {
											$filter_url = get_term_link( $current_term );
										}
										if ( ! empty( $type ) ) {
											$filter_url = add_query_arg( 'result_type', $type, $filter_url );
										}
									?>
										<li>
											<a
												href="<?php echo esc_url( $filter_url ); ?>"
												class="flex items-center justify-between px-4 py-3 rounded-lg transition-all duration-300 <?php echo $is_active ? 'bg-green-deep text-white' : 'bg-brand-10 text-brand-black hover:bg-brand-20'; ?>">
												<span class="font-medium"><?php echo esc_html( $label ); ?></span>
												<span class="<?php echo $is_active ? 'bg-white/20 text-white' : 'bg-brand-20 text-brand-40'; ?> text-sm px-2 py-0.5 rounded-full">
													<?php echo esc_html( $count ); ?>
												</span>
											</a>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>

							<!-- Clear Filter -->
							<?php if ( $result_type_filter ) : ?>
								<a
									href="<?php echo $is_all_areas_mode ? esc_url( home_url( '/area/' ) ) : esc_url( get_term_link( $current_term ) ); ?>"
									class="inline-flex items-center gap-2 text-green-deep hover:text-brand-black transition-colors duration-300">
									<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
										<path d="M19,6.4L17.6,5,12,10.6,6.4,5,5,6.4,10.6,12,5,17.6,6.4,19,12,13.4,17.6,19,19,17.6,13.4,12,19,6.4Z"/>
									</svg>
									<?php esc_html_e( 'Clear filter', 'summit-law-theme' ); ?>
								</a>
							<?php endif; ?>

						</div>
					</aside>

					<!-- Right Column: Results -->
					<div class="lg:col-span-8 xl:col-span-9">

						<!-- Results Header -->
						<div class="flex flex-wrap items-center justify-between gap-4 mb-8">
							<h2 class="h3 border-b-2 lg:border-b-[3px] border-brand-30 w-fit pb-[6px] lg:pb-[10px]">
								<?php
								if ( $is_all_areas_mode ) {
									$found = $custom_query->found_posts;
								} else {
									global $wp_query;
									$found = $wp_query->found_posts;
								}

								if ( $result_type_filter && isset( $filter_types[ $result_type_filter ] ) ) {
									printf(
										/* translators: 1: number of results, 2: post type label */
										esc_html( _n( '%1$s %2$s Found', '%1$s %2$s Found', $found, 'summit-law-theme' ) ),
										number_format_i18n( $found ),
										esc_html( $filter_types[ $result_type_filter ] )
									);
								} else {
									printf(
										esc_html( _n( '%s Result', '%s Results', $found, 'summit-law-theme' ) ),
										number_format_i18n( $found )
									);
								}
								?>
							</h2>

							<?php if ( $back_link ) : ?>
								<a href="<?php echo esc_url( $back_link ); ?>" class="text-green-deep hover:underline text-sm flex items-center gap-1">
									<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" class="rotate-180">
										<path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"/>
									</svg>
									<?php echo esc_html( $back_text ); ?>
								</a>
							<?php endif; ?>
						</div>

						<?php
						// Use custom query for all areas mode, otherwise use default
						$the_query = $is_all_areas_mode ? $custom_query : $wp_query;

						if ( $the_query->have_posts() ) : ?>

							<div class="search-results-grid grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
								<?php while ( $the_query->have_posts() ) : $the_query->the_post();
									$current_post_type = get_post_type();
									$type_label = isset( $filter_types[ $current_post_type ] ) ? $filter_types[ $current_post_type ] : ucfirst( $current_post_type );
								?>

									<article class="search-result-card group col-span-1 mb-6">

										<a href="<?php the_permalink(); ?>" class="block mb-1 rounded-lg overflow-hidden group-hover:shadow-lg transition-shadow duration-1000" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
											<?php if ( has_post_thumbnail() ) : ?>
												<?php the_post_thumbnail( 'large', array( 'class' => 'w-full h-auto aspect-[13/8] object-cover' ) ); ?>
											<?php else : ?>
												<!-- Fallback: site logo with green-deep background -->
												<div class="bg-green-deep w-full aspect-[13/8] flex items-center justify-center p-8">
													<?php
													$custom_logo_id = get_theme_mod( 'custom_logo' );
													if ( $custom_logo_id ) {
														echo wp_get_attachment_image( $custom_logo_id, 'medium', false, array( 'class' => 'max-w-full max-h-full object-contain opacity-80' ) );
													}
													?>
												</div>
											<?php endif; ?>
										</a>

										<div class="flex justify-between items-center mb-2 text-brand-black uppercase text-xs border-b-2 border-b-brand-30 pt-4 lg:pt-6 pb-2 lg:pb-3">
											<div>
												<!-- Post Type Badge -->
												<span class="text-green-deep font-semibold">
													<?php echo esc_html( rtrim( $type_label, 's' ) ); ?>
												</span>
											</div>
											<div>
												<?php echo esc_html( get_the_date( 'F Y' ) ); ?>
											</div>
										</div>

										<a href="<?php the_permalink(); ?>" class="text-green-deep font-hanken font-normal text-lg lg:text-xl underline decoration-2 decoration-transparent group-hover:decoration-green-deep underline-offset-2 transition-colors duration-300">
											<?php the_title(); ?>
										</a>

										<div class="mt-1 lg:mt-2 text-sm lg:text-base text-brand-50">
											<?php
											$excerpt = get_the_excerpt();
											if ( $excerpt ) {
												echo esc_html( wp_trim_words( $excerpt, 15, '...' ) );
											}
											?>
										</div>

									</article>

								<?php endwhile; ?>
							</div>

							<?php
							// Pagination with SVG chevrons
							$chevron_left  = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor" class="inline-block rotate-180"><path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"/></svg>';
							$chevron_right = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor" class="inline-block"><path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"/></svg>';

							if ( $is_all_areas_mode ) {
								// Custom pagination for custom query
								$big = 999999999;
								echo '<nav class="navigation pagination mt-12" aria-label="Posts pagination">';
								echo '<div class="nav-links">';
								echo paginate_links( array(
									'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
									'format'    => '?paged=%#%',
									'current'   => max( 1, get_query_var( 'paged' ) ),
									'total'     => $custom_query->max_num_pages,
									'prev_text' => $chevron_left . ' ' . __( 'Previous', 'summit-law-theme' ),
									'next_text' => __( 'Next', 'summit-law-theme' ) . ' ' . $chevron_right,
								) );
								echo '</div>';
								echo '</nav>';
							} else {
								the_posts_pagination( array(
									'mid_size'  => 2,
									'prev_text' => $chevron_left . ' ' . __( 'Previous', 'summit-law-theme' ),
									'next_text' => __( 'Next', 'summit-law-theme' ) . ' ' . $chevron_right,
									'class'     => 'mt-12',
								) );
							}

							// Reset post data if using custom query
							if ( $is_all_areas_mode ) {
								wp_reset_postdata();
							}
							?>

						<?php else : ?>

							<div class="text-center py-12 bg-brand-10 rounded-lg">
								<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mx-auto mb-4 text-brand-30">
									<circle cx="11" cy="11" r="8"/>
									<path d="M21 21l-4.35-4.35"/>
								</svg>
								<h3 class="h4 text-brand-black mb-2">
									<?php esc_html_e( 'No results found', 'summit-law-theme' ); ?>
								</h3>
								<p class="text-brand-50 mb-6 max-w-md mx-auto">
									<?php
									if ( $is_all_areas_mode ) {
										if ( $result_type_filter ) {
											printf(
												esc_html__( 'No %s found. Try viewing all results.', 'summit-law-theme' ),
												esc_html( strtolower( $filter_types[ $result_type_filter ] ) )
											);
										} else {
											esc_html_e( 'No content found.', 'summit-law-theme' );
										}
									} else {
										printf(
											/* translators: %s: area name */
											esc_html__( 'No %1$s found in %2$s. Try browsing all results or selecting a different area.', 'summit-law-theme' ),
											$result_type_filter ? esc_html( strtolower( $filter_types[ $result_type_filter ] ) ) : esc_html__( 'content', 'summit-law-theme' ),
											$current_term ? esc_html( $current_term->name ) : ''
										);
									}
									?>
								</p>
								<?php if ( $result_type_filter ) : ?>
									<a href="<?php echo $is_all_areas_mode ? esc_url( home_url( '/area/' ) ) : esc_url( get_term_link( $current_term ) ); ?>" class="btn">
										<?php esc_html_e( 'View All Results', 'summit-law-theme' ); ?>
									</a>
								<?php endif; ?>
							</div>

						<?php endif; ?>

					</div>

				</div>

			<?php else : ?>
				<!-- Standard single-column layout for non-taxonomy archives -->
				<div class="grid grid-cols-1 gap-8">

					<!-- Results Header -->
					<div class="col-span-1 flex flex-wrap items-center justify-between gap-4">
						<h2 class="h2 border-b-2 lg:border-b-[3px] border-brand-30 w-fit pb-[6px] lg:pb-[10px]">
							<?php
							global $wp_query;
							printf(
								esc_html( _n( '%s Result', '%s Results', $wp_query->found_posts, 'summit-law-theme' ) ),
								number_format_i18n( $wp_query->found_posts )
							);
							?>
						</h2>

						<?php if ( $back_link ) : ?>
							<a href="<?php echo esc_url( $back_link ); ?>" class="text-green-deep hover:underline text-sm flex items-center gap-1">
								<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" class="rotate-180">
									<path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"/>
								</svg>
								<?php echo esc_html( $back_text ); ?>
							</a>
						<?php endif; ?>
					</div>

					<?php if ( have_posts() ) : ?>

						<div class="col-span-1 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
							<?php while ( have_posts() ) : the_post(); ?>
								<div class="group col-span-1 mb-6">
									<?php if ( has_post_thumbnail() ) : ?>
										<a href="<?php the_permalink(); ?>" class="block mb-1 rounded-lg overflow-hidden group-hover:shadow-lg transition-shadow duration-1000" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
											<?php the_post_thumbnail( 'large', array( 'class' => 'w-full h-auto rounded-lg aspect-[13/8] overflow-hidden object-cover' ) ); ?>
										</a>
									<?php endif; ?>
									<div class="flex justify-between items-center mb-2 text-brand-black uppercase text-xs border-b-2 border-b-brand-30 pt-4 lg:pt-6 pb-2 lg:pb-3">
										<div>
											<?php
											$year  = get_the_date( 'Y' );
											$month = get_the_date( 'm' );
											// Build date link based on post type
											$current_post_type = get_post_type();
											if ( $current_post_type === 'case' ) {
												$date_link = add_query_arg(
													array(
														'year'     => $year,
														'monthnum' => $month,
													),
													get_post_type_archive_link( 'case' )
												);
											} else {
												$date_link = add_query_arg(
													array(
														'year'     => $year,
														'monthnum' => $month,
													),
													home_url( '/insights/' )
												);
											}
											?>
											<a href="<?php echo esc_url( $date_link ); ?>" class="hover:text-brand-black transition-colors duration-300 border-b-transparent border-b-[1px] hover:border-b-brand-black">
												<?php echo esc_html( get_the_date( 'F Y' ) ); ?>
											</a>
										</div>
										<div>
											<?php
											$post_areas = get_the_terms( get_the_ID(), 'area' );
											if ( $post_areas && ! is_wp_error( $post_areas ) ) {
												$term_link = get_term_link( $post_areas[0] );
												if ( $current_post_type === 'case' ) {
													$term_link = add_query_arg( 'post_type', 'case', $term_link );
												}
												echo '<a href="' . esc_url( $term_link ) . '" class="hover:text-brand-black transition-colors duration-300 border-b-transparent border-b-[1px] hover:border-b-brand-black">';
												echo esc_html( $post_areas[0]->name );
												echo '</a>';
											}
											?>
										</div>
									</div>
									<a href="<?php the_permalink(); ?>" class="text-green-deep font-hanken font-normal text-lg lg:text-xl underline decoration-2 decoration-transparent group-hover:decoration-green-deep underline-offset-2 transition-colors duration-300">
										<?php the_title(); ?>
									</a>
									<div class="mt-1 lg:mt-2 text-sm lg:text-base text-brand-50">
										<?php
										$excerpt = get_the_excerpt();
										if ( $excerpt ) {
											echo esc_html( wp_trim_words( $excerpt, 15, '...' ) );
										}
										?>
									</div>
								</div>
							<?php endwhile; ?>
						</div>

						<?php
						// Pagination with SVG chevrons
						$chevron_left  = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor" class="inline-block rotate-180"><path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"/></svg>';
						$chevron_right = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor" class="inline-block"><path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"/></svg>';

						the_posts_pagination(
							array(
								'mid_size'  => 2,
								'prev_text' => $chevron_left . ' ' . __( 'Previous', 'summit-law-theme' ),
								'next_text' => __( 'Next', 'summit-law-theme' ) . ' ' . $chevron_right,
								'class'     => 'mt-12',
							)
						);
						?>

					<?php else : ?>

						<div class="col-span-1 text-center py-12">
							<p class="text-brand-50 text-lg">
								<?php esc_html_e( 'No posts found.', 'summit-law-theme' ); ?>
							</p>
							<?php if ( $back_link ) : ?>
								<a href="<?php echo esc_url( $back_link ); ?>" class="btn alt mt-6 inline-block">
									<?php echo esc_html( $back_text ); ?>
								</a>
							<?php endif; ?>
						</div>

					<?php endif; ?>

				</div>

			<?php endif; ?>

		</div>
	</section>

</main>

<!-- Mobile filter toggle script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
	const toggle = document.querySelector('[data-archive-filter-toggle]');
	const filters = document.querySelector('[data-archive-filters]');

	if (toggle && filters) {
		toggle.addEventListener('click', function() {
			const expanded = this.getAttribute('aria-expanded') === 'true';
			this.setAttribute('aria-expanded', !expanded);
			filters.classList.toggle('max-lg:hidden');

			// Rotate chevron
			const chevron = this.querySelector('.archive-filter-chevron');
			if (chevron) {
				chevron.style.transform = expanded ? 'rotate(0deg)' : 'rotate(180deg)';
			}
		});
	}
});
</script>

<?php
get_footer();
