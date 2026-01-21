<?php
/**
 * Blog/Insights Archive Template
 *
 * This template is specifically for the standard WordPress "post" post type (Insights).
 * Features:
 * - Hero banner with page title
 * - Year and Area dropdown filters
 * - Grid of insight cards with pagination
 */

get_header();

// Get the Posts page info
$posts_page_id = get_option( 'page_for_posts' );
$posts_page    = $posts_page_id ? get_post( $posts_page_id ) : null;

// Get current filter values
$current_year = isset( $_GET['year'] ) ? intval( $_GET['year'] ) : '';
$current_area = isset( $_GET['area'] ) ? sanitize_text_field( $_GET['area'] ) : '';
?>

<!-- Hero Banner -->
<header class="page-header bg-green-deep lg:h-[40dvh] min-h-[240px] lg:min-h-[400px] flex items-center py-24 max-md:pt-12">
	<section class="banner-after-content h-full flex flex-col justify-center container mx-auto p-6 relative">
		<h1 class="h1 2xl:text-7xl text-green-accent1 mb-4 lg:mb-10">
			<?php
			if ( $posts_page ) {
				echo esc_html( get_the_title( $posts_page_id ) );
			} else {
				esc_html_e( 'Insights', 'summit-law-theme' );
			}
			?>
		</h1>
		<?php if ( $posts_page && $posts_page->post_excerpt ) : ?>
			<p class="text-white text-lg lg:text-xl w-full md:w-3/4 2xl:w-2/3">
				<?php echo esc_html( $posts_page->post_excerpt ); ?>
			</p>
		<?php endif; ?>
	</section>
</header>

<main id="main" class="site-main">

	<section class="bg-white py-12 lg:py-24">
		<div class="md:container grid grid-cols-1 gap-8 p-6 lg:px-6 md:mx-auto">

			<!-- Filters Row -->
			<div class="col-span-1 flex flex-wrap items-center justify-between gap-4">
				<h2 class="h2 border-b-2 lg:border-b-[3px] border-brand-30 w-fit pb-[10px]">
					<?php
					if ( $current_year || $current_area ) {
						esc_html_e( 'Filtered Results', 'summit-law-theme' );
					} else {
						esc_html_e( 'All Insights', 'summit-law-theme' );
					}
					?>
				</h2>

				<div class="filter-bar flex flex-wrap gap-4">
					<!-- Year Filter -->
					<div class="filter-group">
						<label for="filter-year" class="sr-only"><?php esc_attr_e( 'Filter by Year', 'summit-law-theme' ); ?></label>
						<select id="filter-year" class="filter-select" onchange="applyInsightFilters()">
							<option value=""><?php esc_html_e( 'All Years', 'summit-law-theme' ); ?></option>
							<?php
							global $wpdb;
							$years = $wpdb->get_col(
								"SELECT DISTINCT YEAR(post_date) FROM $wpdb->posts
								WHERE post_type = 'post' AND post_status = 'publish'
								ORDER BY post_date DESC"
							);
							foreach ( $years as $year ) :
							?>
								<option value="<?php echo esc_attr( $year ); ?>" <?php selected( $current_year, $year ); ?>>
									<?php echo esc_html( $year ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<!-- Area Filter -->
					<div class="filter-group">
						<label for="filter-area" class="sr-only"><?php esc_attr_e( 'Filter by Area', 'summit-law-theme' ); ?></label>
						<select id="filter-area" class="filter-select" onchange="applyInsightFilters()">
							<option value=""><?php esc_html_e( 'All Areas', 'summit-law-theme' ); ?></option>
							<?php
							$areas = get_terms(
								array(
									'taxonomy'   => 'area',
									'hide_empty' => true,
								)
							);
							if ( ! is_wp_error( $areas ) && ! empty( $areas ) ) :
								foreach ( $areas as $area ) :
								?>
									<option value="<?php echo esc_attr( $area->slug ); ?>" <?php selected( $current_area, $area->slug ); ?>>
										<?php echo esc_html( $area->name ); ?>
									</option>
								<?php
								endforeach;
							endif;
							?>
						</select>
					</div>

					<?php if ( $current_year || $current_area ) : ?>
						<a href="<?php echo esc_url( home_url( '/insights/' ) ); ?>" class="text-green-deep hover:underline text-sm flex items-center gap-1">
							<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
								<path d="M19,6.4L17.6,5,12,10.6,6.4,5,5,6.4,10.6,12,5,17.6,6.4,19,12,13.4,17.6,19,19,17.6,13.4,12,19,6.4Z"/>
							</svg>
							<?php esc_html_e( 'Clear filters', 'summit-law-theme' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( have_posts() ) : ?>

				<div class="col-span-1 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
					<?php while ( have_posts() ) : the_post(); ?>
						<div class="group col-span-1 mb-6">
							<?php if ( has_post_thumbnail() ) : ?>
								<a href="<?php the_permalink(); ?>" class="block mb-1 rounded-lg overflow-hidden group-hover:shadow-lg transition-shadow duration-1000">
									<?php the_post_thumbnail( 'large', array( 'class' => 'w-full h-auto rounded-lg aspect-[13/8] overflow-hidden object-cover' ) ); ?>
								</a>
							<?php endif; ?>
							<div class="flex justify-between items-center mb-2 text-brand-black uppercase text-xs border-b-2 border-b-brand-30 pt-4 lg:pt-6 pb-2 lg:pb-3">
								<div>
									<?php
									// Create link to archive filtered by month/year
									$year  = get_the_date( 'Y' );
									$month = get_the_date( 'm' );
									$date_link = add_query_arg(
										array(
											'year'     => $year,
											'monthnum' => $month,
										),
										home_url( '/insights/' )
									);
									?>
									<a href="<?php echo esc_url( $date_link ); ?>" class="hover:text-brand-black transition-colors duration-300 border-b-transparent border-b-[1px] hover:border-b-brand-black">
										<?php echo esc_html( get_the_date( 'F Y' ) ); ?>
									</a>
								</div>
								<div>
								<?php
									$post_areas = get_the_terms( get_the_ID(), 'area' );
									if ( $post_areas && ! is_wp_error( $post_areas ) ) {
										echo '<a href="' . esc_url( get_term_link( $post_areas[0] ) ) . '" class="hover:text-brand-black transition-colors duration-300 border-b-transparent border-b-[1px] hover:border-b-brand-black">';
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
				// Pagination
				the_posts_pagination(
					array(
						'mid_size'  => 2,
						'prev_text' => __( '&larr; Previous', 'summit-law-theme' ),
						'next_text' => __( 'Next &rarr;', 'summit-law-theme' ),
						'class'     => 'mt-12',
					)
				);
				?>

			<?php else : ?>

				<div class="col-span-1 text-center py-12">
					<p class="text-brand-50 text-lg">
						<?php esc_html_e( 'No insights found matching your criteria.', 'summit-law-theme' ); ?>
					</p>
					<?php if ( $current_year || $current_area ) : ?>
						<a href="<?php echo esc_url( home_url( '/insights/' ) ); ?>" class="btn alt mt-6 inline-block">
							<?php esc_html_e( 'View All Insights', 'summit-law-theme' ); ?>
						</a>
					<?php endif; ?>
				</div>

			<?php endif; ?>

		</div>
	</section>

</main>

<script>
function applyInsightFilters() {
	const year = document.getElementById('filter-year').value;
	const area = document.getElementById('filter-area').value;
	let url = new URL(window.location.href);

	// Clear existing filter params
	url.searchParams.delete('year');
	url.searchParams.delete('area');
	url.searchParams.delete('paged');

	// Add new filter params if set
	if (year) url.searchParams.set('year', year);
	if (area) url.searchParams.set('area', area);

	window.location.href = url.toString();
}
</script>

<?php
get_footer();
