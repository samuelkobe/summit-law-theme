<?php
/**
 * Generic Archive Template
 *
 * Handles:
 * - Taxonomy archives (area, category, tag)
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

if ( is_tax( 'area' ) ) {
	$term                = get_queried_object();
	$archive_title       = $term->name;
	$archive_description = $term->description;
	// Determine if filtering cases or posts based on query
	$post_type = get_query_var( 'post_type' );
	if ( $post_type === 'case' ) {
		$back_link = get_post_type_archive_link( 'case' );
		$back_text = __( 'All Cases', 'summit-law-theme' );
	} else {
		$back_link = home_url( '/insights/' );
		$back_text = __( 'All Insights', 'summit-law-theme' );
	}
} elseif ( is_category() ) {
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
?>

<!-- Hero Banner -->
<header class="page-header bg-green-deep lg:h-[40dvh] min-h-[240px] lg:min-h-[400px] flex items-center py-24 max-md:pt-12">
	<section class="banner-after-content h-full flex flex-col justify-center container mx-auto p-6 relative">
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

<main id="main" class="site-main">

	<section class="bg-white py-12 lg:py-24">
		<div class="md:container grid grid-cols-1 gap-8 p-6 lg:px-6 md:mx-auto">

			<!-- Results Header -->
			<div class="col-span-1 flex flex-wrap items-center justify-between gap-4">
				<h2 class="h2 border-b-2 lg:border-b-[3px] border-brand-30 w-fit pb-[10px]">
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
								<a href="<?php the_permalink(); ?>" class="block mb-1 rounded-lg overflow-hidden group-hover:shadow-lg transition-shadow duration-1000">
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
	</section>

</main>

<?php
get_footer();
