<?php
/**
 * Search Results Template
 *
 * Displays search results with:
 * - Hero banner with search query
 * - Left sidebar with post type filters
 * - Right column with results in card grid
 * - Responsive layout (mobile accordion filters)
 */

get_header();

// Get search query - use $_GET['s'] directly to preserve original search term
// even when the query was cleared for post type keyword searches
$search_query = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : get_search_query();

// Get current filter (post type)
$current_type = isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : '';

// Check if we detected a post type keyword search (search was cleared but we're showing a specific type)
global $wp_query;
$detected_post_type = $wp_query->get( 'summit_original_search' );

// Define searchable post types with labels
$post_types = array(
	''        => __( 'All Results', 'summit-law-theme' ),
	'page'    => __( 'Pages', 'summit-law-theme' ),
	'post'    => __( 'Insights', 'summit-law-theme' ),
	'case'    => __( 'Cases', 'summit-law-theme' ),
	'team'    => __( 'Team Members', 'summit-law-theme' ),
	'service' => __( 'Services', 'summit-law-theme' ),
);

// Map search terms to post types (for keyword detection in counts)
$post_type_keywords = array(
	'team'         => 'team',
	'teams'        => 'team',
	'team member'  => 'team',
	'team members' => 'team',
	'insight'      => 'post',
	'insights'     => 'post',
	'blog'         => 'post',
	'blogs'        => 'post',
	'post'         => 'post',
	'posts'        => 'post',
	'case'         => 'case',
	'cases'        => 'case',
	'service'      => 'service',
	'services'     => 'service',
	'page'         => 'page',
	'pages'        => 'page',
);

// Check if search term is a post type keyword
$search_term_lower   = strtolower( trim( $search_query ) );
$matched_post_type   = isset( $post_type_keywords[ $search_term_lower ] ) ? $post_type_keywords[ $search_term_lower ] : null;
$is_keyword_search   = ! empty( $matched_post_type );

// Get counts for each post type
// This ensures counts include ACF meta field matches (same as main search)
$type_counts = array();

if ( $is_keyword_search ) {
	// Keyword search: main query returns all of matched type, others are 0
	$type_counts[''] = $wp_query->found_posts;
	foreach ( array_keys( $post_types ) as $type ) {
		if ( empty( $type ) ) {
			continue;
		}
		$type_counts[ $type ] = ( $type === $matched_post_type ) ? $wp_query->found_posts : 0;
	}
} elseif ( empty( $search_query ) ) {
	// No search term - count all posts of each type (browse mode)
	// Get "All Results" count from main query
	if ( empty( $current_type ) ) {
		$type_counts[''] = $wp_query->found_posts;
	} else {
		// Type filter active - need separate count for all types
		$all_query = new WP_Query( array(
			'post_type'      => array( 'page', 'post', 'case', 'team', 'service' ),
			'posts_per_page' => 1,
			'post_status'    => 'publish',
		) );
		$type_counts[''] = $all_query->found_posts;
	}

	// Get counts for individual post types
	foreach ( array_keys( $post_types ) as $type ) {
		if ( empty( $type ) ) {
			continue;
		}
		$count_query = new WP_Query( array(
			'post_type'      => $type,
			'posts_per_page' => 1,
			'post_status'    => 'publish',
		) );
		$type_counts[ $type ] = $count_query->found_posts;
	}
} else {
	// Standard search with a search term: use helper function for accurate counts with meta search
	// Get "All Results" count
	if ( empty( $current_type ) ) {
		// No type filter - main query already has accurate count
		$type_counts[''] = $wp_query->found_posts;
	} else {
		// Type filter active - need separate count for all types
		$type_counts[''] = summit_count_search_results( $search_query );
	}

	// Get counts for individual post types
	foreach ( array_keys( $post_types ) as $type ) {
		if ( empty( $type ) ) {
			continue;
		}
		// Use the helper function for accurate counts including ACF meta matches
		$type_counts[ $type ] = summit_count_search_results( $search_query, $type );
	}
}
?>

<!-- Hero Banner -->
<header class="page-header bg-green-deep lg:h-[30dvh] min-h-[240px] lg:min-h-[320px] flex items-center pt-12 pb-20 lg:py-24">
	<section class="banner-after-content h-full flex flex-col justify-center container mx-auto p-6 relative text-left">
		<h1 class="h1 2xl:text-7xl text-green-accent1 mb-4 lg:mb-10">
			<?php esc_html_e( 'Site Index', 'summit-law-theme' ); ?>
		</h1>
		<?php if ( $search_query || $current_type ) : ?>
			<p class="text-white text-lg lg:text-xl w-full md:w-3/4 2xl:w-2/3">
				<?php
				if ( $search_query && $current_type && isset( $post_types[ $current_type ] ) ) {
					// Both search term and type filter
					printf(
						/* translators: 1: post type label, 2: search query */
						__( 'Showing %1$s for "%2$s"', 'summit-law-theme' ),
						'<span class="text-green-accent1 font-semibold">' . esc_html( $post_types[ $current_type ] ) . '</span>',
						'<span class="text-green-accent1 font-semibold">' . esc_html( $search_query ) . '</span>'
					);
				} elseif ( $current_type && isset( $post_types[ $current_type ] ) ) {
					// Type filter only
					printf(
						/* translators: %s: post type label */
						__( 'Showing all %s', 'summit-law-theme' ),
						'<span class="text-green-accent1 font-semibold">' . esc_html( $post_types[ $current_type ] ) . '</span>'
					);
				} else {
					// Search term only
					printf(
						/* translators: %s: search query */
						__( 'Showing results for "%s"', 'summit-law-theme' ),
						'<span class="text-green-accent1 font-semibold">' . esc_html( $search_query ) . '</span>'
					);
				}
				?>
			</p>
		<?php endif; ?>
	</section>
</header>

<main id="main" class="site-main search-results">

	<section class="bg-white py-12 lg:py-24">
		<div class="container mx-auto px-6">

			<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">

				<!-- Left Sidebar: Filters -->
				<aside class="lg:col-span-4 xl:col-span-3">

					<!-- Mobile Filter Toggle -->
					<button
						class="search-filter-toggle lg:hidden w-full flex items-center justify-between p-4 bg-brand-10 rounded-lg mb-4"
						aria-expanded="false"
						aria-controls="search-filters"
						data-search-filter-toggle>
						<span class="font-semibold text-brand-black">
							<?php esc_html_e( 'Filter Results', 'summit-law-theme' ); ?>
						</span>
						<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="search-filter-chevron transition-transform duration-300">
							<path d="M12,17.5c-.4,0-.8-.1-1.1-.4L1.4,7.6c-.6-.6-.6-1.5,0-2.1.6-.6,1.5-.6,2.1,0l8.5,8.5,8.5-8.5c.6-.6,1.5-.6,2.1,0,.6.6.6,1.5,0,2.1l-9.5,9.5c-.3.3-.7.4-1.1.4Z"/>
						</svg>
					</button>

					<!-- Filter Panel -->
					<div
						id="search-filters"
						class="search-filters max-lg:hidden lg:block"
						data-search-filters>

						<!-- Search Form -->
						<div class="mb-8">
							<h2 class="text-sm uppercase tracking-wider text-brand-40 mb-3">
								<?php esc_html_e( 'Refine Search', 'summit-law-theme' ); ?>
							</h2>
							<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="search-refine-form">
								<div class="relative">
									<input
										type="search"
										name="s"
										value="<?php echo esc_attr( $search_query ); ?>"
										placeholder="<?php esc_attr_e( 'Search...', 'summit-law-theme' ); ?>"
										class="w-full px-4 py-3 pr-12 bg-brand-10 border-2 border-transparent rounded-lg text-brand-black focus:border-green-deep focus:outline-none transition-colors duration-300">
									<?php if ( $current_type ) : ?>
										<input type="hidden" name="type" value="<?php echo esc_attr( $current_type ); ?>">
									<?php endif; ?>
									<button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-brand-40 hover:text-green-deep transition-colors duration-300">
										<?php echo summit_get_svg_icon( 'search', array( 'class' => 'w-5 h-5' ) ); ?>
									</button>
								</div>
							</form>
						</div>

						<!-- Post Type Filter -->
						<div class="mb-8">
							<h2 class="text-sm uppercase tracking-wider text-brand-40 mb-3">
								<?php esc_html_e( 'Result Type', 'summit-law-theme' ); ?>
							</h2>
							<ul class="space-y-2">
								<?php foreach ( $post_types as $type => $label ) :
									$count = isset( $type_counts[ $type ] ) ? $type_counts[ $type ] : 0;
									$is_active = ( $current_type === $type );
									$filter_url = add_query_arg(
										array(
											's'    => $search_query,
											'type' => $type ? $type : false,
										),
										home_url( '/' )
									);
									// Remove type param if empty
									if ( empty( $type ) ) {
										$filter_url = remove_query_arg( 'type', $filter_url );
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

						<!-- Clear Filters -->
						<?php if ( $current_type || $search_query ) : ?>
							<a
								href="<?php echo esc_url( home_url( '/?s=' ) ); ?>"
								class="inline-flex items-center gap-2 text-green-deep hover:text-brand-black transition-colors duration-300">
								<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
									<path d="M19,6.4L17.6,5,12,10.6,6.4,5,5,6.4,10.6,12,5,17.6,6.4,19,12,13.4,17.6,19,19,17.6,13.4,12,19,6.4Z"/>
								</svg>
								<?php esc_html_e( 'Clear search', 'summit-law-theme' ); ?>
							</a>
						<?php endif; ?>

					</div>
				</aside>

				<!-- Right Column: Results -->
				<div class="lg:col-span-8 xl:col-span-9">

					<!-- Results Header -->
					<div class="flex flex-wrap items-center justify-between gap-4 mb-8">
						<h2 class="h3 border-b-2 lg:border-b-[3px] border-brand-30 w-fit pb-[10px]">
							<?php
							global $wp_query;
							$found = $wp_query->found_posts;
							if ( $current_type && isset( $post_types[ $current_type ] ) ) {
								printf(
									/* translators: 1: number of results, 2: post type label */
									esc_html( _n( '%1$s %2$s Found', '%1$s %2$s Found', $found, 'summit-law-theme' ) ),
									number_format_i18n( $found ),
									esc_html( $post_types[ $current_type ] )
								);
							} else {
								printf(
									esc_html( _n( '%s Result', '%s Results', $found, 'summit-law-theme' ) ),
									number_format_i18n( $found )
								);
							}
							?>
						</h2>
					</div>

					<?php if ( have_posts() ) : ?>

						<div class="search-results-grid grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
							<?php while ( have_posts() ) : the_post();
								$post_type = get_post_type();
								$type_label = isset( $post_types[ $post_type ] ) ? $post_types[ $post_type ] : ucfirst( $post_type );
							?>

								<article class="search-result-card group col-span-1 mb-6">

									<a href="<?php the_permalink(); ?>" class="block mb-1 rounded-lg overflow-hidden group-hover:shadow-lg transition-shadow duration-1000">
										<?php if ( has_post_thumbnail() ) : ?>
											<?php if ( $post_type === 'team' ) : ?>
												<!-- Team members: brand-black background for transparent images -->
												<div class="bg-brand-black w-full aspect-[13/8]">
													<?php the_post_thumbnail( 'large', array( 'class' => 'w-full h-full object-contain' ) ); ?>
												</div>
											<?php else : ?>
												<!-- Other post types: standard cover image -->
												<?php the_post_thumbnail( 'large', array( 'class' => 'w-full h-auto aspect-[13/8] object-cover' ) ); ?>
											<?php endif; ?>
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
											<?php if ( $post_type === 'post' || $post_type === 'case' ) : ?>
												<?php echo esc_html( get_the_date( 'F Y' ) ); ?>
											<?php elseif ( $post_type === 'team' ) :
												$role = get_field( 'role' );
												if ( $role ) :
													echo esc_html( $role['label'] );
												endif;
											endif; ?>
										</div>
									</div>

									<a href="<?php the_permalink(); ?>" class="text-green-deep font-hanken font-normal text-lg lg:text-xl underline decoration-2 decoration-transparent group-hover:decoration-green-deep underline-offset-2 transition-colors duration-300">
										<?php the_title(); ?>
									</a>

									<div class="mt-1 lg:mt-2 text-sm lg:text-base text-brand-50">
										<?php
										// Get appropriate excerpt based on post type
										if ( $post_type === 'team' ) {
											$excerpt = get_field( 'short_description' );
											if ( $excerpt ) {
												echo esc_html( wp_trim_words( wp_strip_all_tags( $excerpt ), 15, '...' ) );
											}
										} else {
											$excerpt = get_the_excerpt();
											if ( $excerpt ) {
												echo esc_html( wp_trim_words( $excerpt, 15, '...' ) );
											}
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

						the_posts_pagination( array(
							'mid_size'  => 2,
							'prev_text' => $chevron_left . ' ' . __( 'Previous', 'summit-law-theme' ),
							'next_text' => __( 'Next', 'summit-law-theme' ) . ' ' . $chevron_right,
							'class'     => 'mt-12',
						) );
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
								printf(
									/* translators: %s: search query */
									esc_html__( 'We couldn\'t find any results for "%s". Try adjusting your search terms or browse our content below.', 'summit-law-theme' ),
									esc_html( $search_query )
								);
								?>
							</p>
							<div class="flex flex-wrap justify-center gap-4">
								<a href="<?php echo esc_url( home_url( '/insights/' ) ); ?>" class="btn">
									<?php esc_html_e( 'Browse Insights', 'summit-law-theme' ); ?>
								</a>
								<a href="<?php echo esc_url( home_url( '/team/' ) ); ?>" class="btn alt">
									<?php esc_html_e( 'Meet Our Team', 'summit-law-theme' ); ?>
								</a>
							</div>
						</div>

					<?php endif; ?>

				</div>

			</div>

		</div>
	</section>

</main>

<script>
// Mobile filter toggle
document.addEventListener('DOMContentLoaded', function() {
	const toggle = document.querySelector('[data-search-filter-toggle]');
	const filters = document.querySelector('[data-search-filters]');

	if (toggle && filters) {
		toggle.addEventListener('click', function() {
			const expanded = this.getAttribute('aria-expanded') === 'true';
			this.setAttribute('aria-expanded', !expanded);
			filters.classList.toggle('max-lg:hidden');

			// Rotate chevron
			const chevron = this.querySelector('.search-filter-chevron');
			if (chevron) {
				chevron.style.transform = expanded ? 'rotate(0deg)' : 'rotate(180deg)';
			}
		});
	}
});
</script>

<?php
get_footer();
