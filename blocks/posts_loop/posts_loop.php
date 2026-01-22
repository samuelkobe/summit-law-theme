<?php
/**
 * Block template file: mini_banner.php
 *
 * Mini Banner Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'mini_banner-' . $block['id'];
if ( ! empty($block['anchor'] ) ) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$classes = 'block-mini_banner';
if ( ! empty( $block['className'] ) ) {
    $classes .= ' ' . $block['className'];
}
if ( ! empty( $block['align'] ) ) {
    $classes .= ' align' . $block['align'];
}
?>

<style type="text/css">
	<?php echo '#' . $id; ?> {
		/* Add styles that use ACF values here */
	}
</style>

<section id="<?php echo esc_attr( $id ); ?>" class="bg-white py-12 lg:py-24 <?php echo esc_attr( $classes ); ?>">

	<div class="md:container grid grid-cols-1 gap-8 p-6 lg:px-6 md:mx-auto">

			<?php
		// Get the post type selection from ACF button group
		$post_type = get_field( 'post_type' ); // Expected values: 'insights', 'cases', 'both'
		$insights = get_field( 'insights' );
		$cases = get_field( 'cases' );
		$show_post_image = get_field( 'post_image' ); // Toggle to show/hide post images
		$loop_type = get_field( 'loop_type' ); // 1 = featured (relationship), 0 = recent (query)

		// For 'both' selection, we need to query posts dynamically
		if ( $post_type === 'both' ) :
			// Query both post types
			$args = array(
				'post_type' => array( 'post', 'case' ),
				'posts_per_page' => 8,
				'post_status' => 'publish',
				'orderby' => 'date',
				'order' => 'DESC'
			);
			$posts_query = new WP_Query( $args );

			if ( $posts_query->have_posts() ) : ?>

				<div class="col-span-1">
					<h2 class="h2 border-b-2 lg:border-b-[3px] border-brand-30 w-fit pb-[10px]">Recent Insights & Cases</h2>
					<?php if ( get_field( 'posts_subtitle' ) ) : ?>
						<p class="text-lg lg:text-xl text-brand-50 pt-2 lg:pt-4"><?php echo esc_html( get_field( 'posts_subtitle' ) ); ?></p>
					<?php endif; ?>
				</div>

				<div class="col-span-1 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
					<?php while ( $posts_query->have_posts() ) : $posts_query->the_post(); ?>
						<div class="group col-span-1 mb-6">
							<?php if ( $show_post_image == 1 && has_post_thumbnail() ) : ?>
								<a href="<?php the_permalink(); ?>" class="relative block mb-1 rounded-lg overflow-hidden group-hover:shadow-lg transition-shadow duration-1000">
									<span class="absolute top-3 left-3 z-10 bg-green-deep text-white uppercase text-xs font-semibold tracking-widest px-2.5 py-1 rounded-md">
										<?php echo get_post_type() === 'post' ? 'Insight' : 'Case'; ?>
									</span>
									<?php the_post_thumbnail( 'large', array( 'class' => 'w-full h-auto rounded-lg aspect-[13/8] overflow-hidden object-cover' ) ); ?>
								</a>
							<?php endif; ?>
							<div class="flex justify-between items-center mb-2 text-brand-black uppercase text-xs border-b-2 border-b-brand-30 pt-4 lg:pt-6 pb-2 lg:pb-3">
								<div>
									<?php
									// Create link to archive filtered by month/year
									$year = get_the_date( 'Y' );
									$month = get_the_date( 'm' );
									$date_link = add_query_arg(
										array(
											'post_type' => get_post_type(),
											'year' => $year,
											'monthnum' => $month
										),
										home_url( '/' )
									);
									?>
									<a href="<?php echo esc_url( $date_link ); ?>" class="hover:text-brand-black transition-colors duration-300 border-b-transparent border-b-[1px] hover:border-b-brand-black">
										<?php echo get_the_date( 'F Y' ); ?>
									</a>
								</div>
								<div>
								<?php
									$areas = get_the_terms( get_the_ID(), 'area' );
									if ( $areas && ! is_wp_error( $areas ) ) {
										$term_link = add_query_arg( 'post_type', get_post_type(), get_term_link( $areas[0] ) );
										echo '<a href="' . esc_url( $term_link ) . '" class="hover:text-brand-black transition-colors duration-300 border-b-transparent border-b-[1px] hover:border-b-brand-black">';
										echo esc_html( $areas[0]->name );
										echo '</a>';
									}
									?>
								</div>
							</div>
							<a href="<?php the_permalink(); ?>" class="text-green-deep font-hanken font-normal text-lg lg:text-xl underline decoration-2 decoration-transparent group-hover:decoration-green-deep underline-offset-2 transition-colors duration-300">
								<?php the_title(); ?>
							</a>
							<div class="mt-1 lg:mt-2 text-sm lg:text-base text-brand-70">
								<?php
								$excerpt = get_the_excerpt();
								if ( $excerpt ) {
									echo wp_trim_words( $excerpt, 15, '...' );
								}
								?>
							</div>
						</div>
					<?php endwhile; ?>
				</div>

				<div class="flex gap-4">
					<a class="btn outlined"
					   href="<?php echo esc_url( get_post_type_archive_link( 'post' ) ); ?>"
					   aria-label="View all insights articles"
					   title="Browse our complete collection of legal insights and articles">
						View All Insights
					</a>
					<a class="btn outlined"
					   href="<?php echo esc_url( get_post_type_archive_link( 'case' ) ); ?>"
					   aria-label="View all cases"
					   title="Browse our complete collection of cases">
						View All Cases
					</a>
				</div>

			<?php
			wp_reset_postdata();
			endif;

		// For 'insights' or 'cases' selection
		else :
			// Determine post type and labels
			$query_post_type = '';
			$heading = '';
			$button_text = '';
			$button_link = '';

			if ( $post_type === 'insights' ) {
				$query_post_type = 'post';
				$heading = $loop_type == 1 ? 'Featured Insights' : 'Recent Insights';
				$button_text = 'View All Insights';
				$button_link = get_post_type_archive_link( 'post' );
			} elseif ( $post_type === 'cases' ) {
				$query_post_type = 'case';
				$heading = $loop_type == 1 ? 'Featured Cases' : 'Recent Cases';
				$button_text = 'View All Cases';
				$button_link = get_post_type_archive_link( 'case' );
			}

			// If loop_type is 1 (featured), use relationship field posts
			if ( $loop_type == 1 ) :
				$posts_to_display = array();

				if ( $post_type === 'insights' && $insights ) {
					$posts_to_display = $insights;
				} elseif ( $post_type === 'cases' && $cases ) {
					$posts_to_display = $cases;
				}

				// Display featured posts if we have any
				if ( ! empty( $posts_to_display ) ) : ?>

					<div class="col-span-1">
						<h2 class="h2 border-b-2 lg:border-b-[3px] border-brand-30 w-fit pb-[10px]"><?php echo esc_html( $heading ); ?></h2>
						<?php if ( get_field( 'posts_subtitle' ) ) : ?>
							<p class="text-lg lg:text-xl text-brand-50 pt-2 lg:pt-4"><?php echo esc_html( get_field( 'posts_subtitle' ) ); ?></p>
						<?php endif; ?>
					</div>

					<div class="col-span-1 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
						<?php foreach ( $posts_to_display as $post ) : ?>
							<?php
							// Get the post ID from the relationship field
							$post_id = is_object( $post ) ? $post->ID : $post;
							?>
							<div class="group col-span-1 mb-6">
								<?php if ( $show_post_image == 1 && has_post_thumbnail( $post_id ) ) : ?>
									<a href="<?php echo get_permalink( $post_id ); ?>" class="relative block mb-1 rounded-lg overflow-hidden group-hover:shadow-lg transition-shadow duration-1000">
										<span class="absolute top-4 left-4 z-10 bg-green-deep text-white uppercase text-[9px] font-semibold tracking-widest px-2.5 py-1 rounded-md">
											<?php echo get_post_type( $post_id ) === 'post' ? 'Insight' : 'Case'; ?>
										</span>
										<?php echo get_the_post_thumbnail( $post_id, 'large', array( 'class' => 'w-full h-auto rounded-lg aspect-[13/8] overflow-hidden object-cover' ) ); ?>
									</a>
								<?php endif; ?>
								<div class="flex justify-between items-center mb-2 text-brand-black uppercase text-xs border-b-2 border-b-brand-30 pt-4 lg:pt-6 pb-2 lg:pb-3">
									<div>
										<?php
										// Create link to archive filtered by month/year
										$year = get_the_date( 'Y', $post_id );
										$month = get_the_date( 'm', $post_id );
										$date_link = add_query_arg(
											array(
												'post_type' => get_post_type( $post_id ),
												'year' => $year,
												'monthnum' => $month
											),
											home_url( '/' )
										);
										?>
										<a href="<?php echo esc_url( $date_link ); ?>" class="hover:text-brand-black transition-colors duration-300 border-b-transparent border-b-[1px] hover:border-b-brand-black">
											<?php echo get_the_date( 'F Y', $post_id ); ?>
										</a>
									</div>
									<div>
									<?php
										$areas = get_the_terms( $post_id, 'area' );
										if ( $areas && ! is_wp_error( $areas ) ) {
											$term_link = add_query_arg( 'post_type', get_post_type( $post_id ), get_term_link( $areas[0] ) );
											echo '<a href="' . esc_url( $term_link ) . '" class="hover:text-brand-black transition-colors duration-300 border-b-transparent border-b-[1px] hover:border-b-brand-black">';
											echo esc_html( $areas[0]->name );
											echo '</a>';
										}
										?>
									</div>
								</div>
								<a href="<?php echo get_permalink( $post_id ); ?>" class="text-green-deep font-hanken font-normal text-lg lg:text-xl underline decoration-2 decoration-transparent group-hover:decoration-green-deep underline-offset-2 transition-colors duration-300">
									<?php echo get_the_title( $post_id ); ?>
								</a>
								<div class="mt-1 lg:mt-2 text-sm lg:text-base text-brand-70">
									<?php
									$excerpt = get_the_excerpt( $post_id );
									if ( $excerpt ) {
										echo wp_trim_words( $excerpt, 15, '...' );
									}
									?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>

					<a class="btn outlined"
					   href="<?php echo esc_url( $button_link ); ?>"
					   aria-label="<?php echo esc_attr( $button_text ); ?>"
					   title="<?php echo esc_attr( $button_text ); ?>">
						<?php echo esc_html( $button_text ); ?>
					</a>

				<?php endif;

			// If loop_type is 0 (recent), query most recent posts
			else :
				if ( ! empty( $query_post_type ) ) :
					$args = array(
						'post_type' => $query_post_type,
						'posts_per_page' => 8,
						'post_status' => 'publish',
						'orderby' => 'date',
						'order' => 'DESC'
					);
					$posts_query = new WP_Query( $args );

					if ( $posts_query->have_posts() ) : ?>

						<div class="col-span-1">
							<h2 class="h2 border-b-2 lg:border-b-[3px] border-brand-30 w-fit pb-[10px]"><?php echo esc_html( $heading ); ?></h2>
							<?php if ( get_field( 'posts_subtitle' ) ) : ?>
								<p class="text-lg lg:text-xl text-brand-50 pt-2 lg:pt-4"><?php echo esc_html( get_field( 'posts_subtitle' ) ); ?></p>
							<?php endif; ?>
						</div>

						<div class="col-span-1 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
							<?php while ( $posts_query->have_posts() ) : $posts_query->the_post(); ?>
								<div class="group col-span-1 mb-6">
									<?php if ( $show_post_image == 1 && has_post_thumbnail() ) : ?>
										<a href="<?php the_permalink(); ?>" class="relative block mb-1 rounded-lg overflow-hidden group-hover:shadow-lg transition-shadow duration-1000">
											<span class="absolute top-4 left-4 z-10 bg-green-deep text-white uppercase text-[9px] font-semibold tracking-widest px-2.5 py-1 rounded-md">
												<?php echo get_post_type() === 'post' ? 'Insight' : 'Case'; ?>
											</span>
											<?php the_post_thumbnail( 'large', array( 'class' => 'w-full h-auto rounded-lg aspect-[13/8] overflow-hidden object-cover' ) ); ?>
										</a>
									<?php endif; ?>
									<div class="flex justify-between items-center mb-2 text-brand-black uppercase text-xs border-b-2 border-b-brand-30 pt-4 lg:pt-6 pb-2 lg:pb-3">
										<div>
											<?php
											// Create link to archive filtered by month/year
											$year = get_the_date( 'Y' );
											$month = get_the_date( 'm' );
											$date_link = add_query_arg(
												array(
													'post_type' => get_post_type(),
													'year' => $year,
													'monthnum' => $month
												),
												home_url( '/' )
											);
											?>
											<a href="<?php echo esc_url( $date_link ); ?>" class="hover:text-brand-black transition-colors duration-300 border-b-transparent border-b-[1px] hover:border-b-brand-black">
												<?php echo get_the_date( 'F Y' ); ?>
											</a>
										</div>
										<div>
										<?php
											$areas = get_the_terms( get_the_ID(), 'area' );
											if ( $areas && ! is_wp_error( $areas ) ) {
												$term_link = add_query_arg( 'post_type', get_post_type(), get_term_link( $areas[0] ) );
												echo '<a href="' . esc_url( $term_link ) . '" class="hover:text-brand-black transition-colors duration-300 border-b-transparent border-b-[1px] hover:border-b-brand-black">';
												echo esc_html( $areas[0]->name );
												echo '</a>';
											}
											?>
										</div>
									</div>
									<a href="<?php the_permalink(); ?>" class="text-green-deep font-hanken font-normal text-lg lg:text-xl underline decoration-2 decoration-transparent group-hover:decoration-green-deep underline-offset-2 transition-colors duration-300">
										<?php the_title(); ?>
									</a>
									<div class="mt-1 lg:mt-2 text-sm lg:text-base text-brand-70">
										<?php
										$excerpt = get_the_excerpt();
										if ( $excerpt ) {
											echo wp_trim_words( $excerpt, 15, '...' );
										}
										?>
									</div>
								</div>
							<?php endwhile; ?>
						</div>

						<a class="btn outlined"
						   href="<?php echo esc_url( $button_link ); ?>"
						   aria-label="<?php echo esc_attr( $button_text ); ?>"
						   title="<?php echo esc_attr( $button_text ); ?>">
							<?php echo esc_html( $button_text ); ?>
						</a>

					<?php
					wp_reset_postdata();
					endif;
				endif;
			endif;
		endif;
		?>

	</div>

</section>