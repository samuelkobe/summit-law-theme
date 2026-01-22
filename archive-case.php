<?php
/**
 * Archive template for Cases
 *
 * Features:
 * - Hero banner with page title
 * - Two-column layout with sidebar filters (Area, Year)
 * - Grid of case cards with pagination
 */

get_header();

// Get current filter values
$current_area = isset( $_GET['area'] ) ? sanitize_text_field( $_GET['area'] ) : '';

// Get all areas for the filter (only those with cases)
$all_areas = get_terms( array(
	'taxonomy'   => 'area',
	'hide_empty' => false,
	'orderby'    => 'name',
	'order'      => 'ASC',
) );

// Calculate counts for each area (only cases, not posts)
$area_counts = array();
if ( $all_areas && ! is_wp_error( $all_areas ) ) {
	foreach ( $all_areas as $area ) {
		$count = new WP_Query( array(
			'post_type'      => 'case',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'tax_query'      => array(
				array(
					'taxonomy' => 'area',
					'field'    => 'term_id',
					'terms'    => $area->term_id,
				),
			),
		) );
		$area_counts[ $area->term_id ] = $count->found_posts;
	}
	// Filter out areas with no cases
	$all_areas = array_filter( $all_areas, function( $area ) use ( $area_counts ) {
		return $area_counts[ $area->term_id ] > 0;
	} );
}

// Get archive page title - explicitly use "Cases" since we're in archive-case.php
// post_type_archive_title() may not work correctly with query parameters
$archive_title = __( 'Cases', 'summit-law-theme' );

// Get current area term if filtering
$current_area_term = null;
$current_area_name = '';
if ( $current_area ) {
	$current_area_term = get_term_by( 'slug', $current_area, 'area' );
	if ( $current_area_term && ! is_wp_error( $current_area_term ) ) {
		$current_area_name = $current_area_term->name;
	}
}

// Build display title - include area name if filtering
$display_title = $archive_title;
if ( $current_area_name ) {
	$display_title = $archive_title . ': ' . $current_area_name;
}
?>

<!-- Hero Banner -->
<header class="page-header bg-green-deep lg:h-[30dvh] min-h-[240px] lg:min-h-[320px] flex items-center pt-12 pb-20 lg:py-24">
	<section class="banner-after-content h-full flex flex-col justify-center container mx-auto p-6 relative text-left">
		<h1 class="h1 2xl:text-7xl text-green-accent1 mb-4 lg:mb-10">
			<?php echo esc_html( $display_title ); ?>
		</h1>
	</section>
</header>

<!-- Breadcrumbs -->
<section class="bg-white">
	<div class="container mx-auto flex flex-row justify-between">
		<!-- Mobile: Condensed back link -->
		<nav aria-label="Back navigation" class="md:hidden py-4">
			<?php if ( $current_area_name ) : ?>
				<!-- When filtering by area, back link goes to Cases -->
				<a href="<?php echo esc_url( get_post_type_archive_link( 'case' ) ); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-40 hover:text-green-deep transition-colors">
					<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" class="flex-shrink-0 fill-green-accent1 rotate-180" aria-hidden="true">
						<path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"></path>
					</svg>
					<?php echo esc_html( $archive_title ); ?>
				</a>
			<?php else : ?>
				<!-- When not filtering, back link goes to Home -->
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-40 hover:text-green-deep transition-colors">
					<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" class="flex-shrink-0 fill-green-accent1 rotate-180" aria-hidden="true">
						<path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"></path>
					</svg>
					<?php esc_html_e( 'Summit Law LLP', 'summit-law-theme' ); ?>
				</a>
			<?php endif; ?>
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

				<?php if ( $current_area_name ) : ?>
					<!-- Cases link when filtering by area -->
					<li>
						<a href="<?php echo esc_url( get_post_type_archive_link( 'case' ) ); ?>" class="text-brand-40 hover:text-green-deep transition-colors">
							<?php echo esc_html( $archive_title ); ?>
						</a>
					</li>
					<li aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" class="flex-shrink-0 fill-green-accent1" aria-hidden="true">
							<path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"></path>
						</svg>
					</li>
					<!-- Current Area -->
					<li aria-current="page" class="text-green-deep">
						<?php echo esc_html( $current_area_name ); ?>
					</li>
				<?php else : ?>
					<!-- Cases as current page when not filtering -->
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
				$page_url   = urlencode( get_post_type_archive_link( 'case' ) );
				$page_title = urlencode( $archive_title );

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

			<!-- Two-column layout with sidebar -->
			<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">

				<!-- Left Sidebar: Filters -->
				<aside class="lg:col-span-4 xl:col-span-3">

					<!-- Mobile Filter Toggle -->
					<button
						class="lg:hidden w-full flex items-center justify-between p-4 bg-brand-10 rounded-lg mb-4"
						aria-expanded="false"
						aria-controls="cases-filters"
						data-filter-toggle>
						<span class="font-semibold text-brand-black">
							<?php esc_html_e( 'Filter Cases', 'summit-law-theme' ); ?>
						</span>
						<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="filter-chevron transition-transform duration-300">
							<path d="M12,17.5c-.4,0-.8-.1-1.1-.4L1.4,7.6c-.6-.6-.6-1.5,0-2.1.6-.6,1.5-.6,2.1,0l8.5,8.5,8.5-8.5c.6-.6,1.5-.6,2.1,0,.6.6.6,1.5,0,2.1l-9.5,9.5c-.3.3-.7.4-1.1.4Z"/>
						</svg>
					</button>

					<!-- Filter Panel -->
					<div
						id="cases-filters"
						class="max-lg:hidden lg:block"
						data-filters>

						<!-- Browse Areas -->
						<?php if ( $all_areas && ! is_wp_error( $all_areas ) ) : ?>
							<div class="mb-8">
								<h2 class="text-sm uppercase tracking-wider text-brand-40 mb-3">
									<label for="filter-area">
										<?php esc_html_e( 'Browse Areas', 'summit-law-theme' ); ?>
									</label>
								</h2>
								<select
									id="filter-area"
									class="filter-select w-full"
									onchange="applyCaseFilters()">
									<option value=""><?php esc_html_e( 'All Areas', 'summit-law-theme' ); ?></option>
									<?php foreach ( $all_areas as $area ) : ?>
										<option value="<?php echo esc_attr( $area->slug ); ?>" <?php selected( $current_area, $area->slug ); ?>>
											<?php echo esc_html( $area->name ); ?> (<?php echo esc_html( $area_counts[ $area->term_id ] ); ?>)
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						<?php endif; ?>

						<!-- Clear Filters -->
						<?php if ( $current_area ) : ?>
							<a
								href="<?php echo esc_url( get_post_type_archive_link( 'case' ) ); ?>"
								class="inline-flex items-center gap-2 text-green-deep hover:text-brand-black transition-colors duration-300">
								<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
									<path d="M19,6.4L17.6,5,12,10.6,6.4,5,5,6.4,10.6,12,5,17.6,6.4,19,12,13.4,17.6,19,19,17.6,13.4,12,19,6.4Z"/>
								</svg>
								<?php esc_html_e( 'Clear filters', 'summit-law-theme' ); ?>
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
							printf(
								esc_html( _n( '%s Case', '%s Cases', $found, 'summit-law-theme' ) ),
								number_format_i18n( $found )
							);
							?>
						</h2>
					</div>

					<?php if ( have_posts() ) : ?>

						<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
							<?php while ( have_posts() ) : the_post(); ?>

								<article class="group col-span-1 mb-6">

									<a href="<?php the_permalink(); ?>" class="block mb-1 rounded-lg overflow-hidden group-hover:shadow-lg transition-shadow duration-1000">
										<?php if ( has_post_thumbnail() ) : ?>
											<?php the_post_thumbnail( 'large', array( 'class' => 'w-full h-auto aspect-[13/8] object-cover' ) ); ?>
										<?php else : ?>
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
											<?php echo esc_html( get_the_date( 'F Y' ) ); ?>
										</div>
										<div>
											<?php
											$post_areas = get_the_terms( get_the_ID(), 'area' );
											if ( $post_areas && ! is_wp_error( $post_areas ) ) {
												echo esc_html( $post_areas[0]->name );
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
								<?php esc_html_e( 'No cases found', 'summit-law-theme' ); ?>
							</h3>
							<p class="text-brand-50 mb-6 max-w-md mx-auto">
								<?php esc_html_e( 'No cases found matching your criteria. Try adjusting your filters.', 'summit-law-theme' ); ?>
							</p>
							<?php if ( $current_area ) : ?>
								<a href="<?php echo esc_url( get_post_type_archive_link( 'case' ) ); ?>" class="btn">
									<?php esc_html_e( 'View All Cases', 'summit-law-theme' ); ?>
								</a>
							<?php endif; ?>
						</div>

					<?php endif; ?>

				</div>

			</div>

		</div>
	</section>

</main>

<!-- Mobile filter toggle script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
	const toggle = document.querySelector('[data-filter-toggle]');
	const filters = document.querySelector('[data-filters]');

	if (toggle && filters) {
		toggle.addEventListener('click', function() {
			const expanded = this.getAttribute('aria-expanded') === 'true';
			this.setAttribute('aria-expanded', !expanded);
			filters.classList.toggle('max-lg:hidden');

			// Rotate chevron
			const chevron = this.querySelector('.filter-chevron');
			if (chevron) {
				chevron.style.transform = expanded ? 'rotate(0deg)' : 'rotate(180deg)';
			}
		});
	}
});

function applyCaseFilters() {
	const area = document.getElementById('filter-area').value;
	let url = new URL(window.location.href);

	// Clear existing filter params
	url.searchParams.delete('area');
	url.searchParams.delete('paged');

	// Add area filter if set
	if (area) url.searchParams.set('area', area);

	window.location.href = url.toString();
}
</script>

<?php
get_footer();
