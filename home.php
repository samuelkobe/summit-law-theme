<?php
/**
 * Blog/Insights Archive Template (home.php)
 *
 * This template displays the main blog posts archive
 * Used when Settings > Reading > Posts page is set
 * URL: /insights/ (or whatever page is set as Posts page)
 */

get_header();
?>

<main id="main" class="site-main insights-archive">

	<header class="page-header container mx-auto px-4 py-12">
		<h1 class="text-4xl md:text-5xl font-bold text-green-deep mb-4">
			<?php
			// If a page is set as Posts page, use its title
			if ( is_home() && ! is_front_page() ) {
				single_post_title();
			} else {
				echo esc_html__( 'Insights', 'summit-law-theme' );
			}
			?>
		</h1>
		<?php
		// Display page content if this is a page set as Posts page
		if ( is_home() && ! is_front_page() ) {
			$posts_page_id = get_option( 'page_for_posts' );
			if ( $posts_page_id ) {
				$posts_page = get_post( $posts_page_id );
				if ( $posts_page && ! empty( $posts_page->post_content ) ) {
					echo '<div class="archive-description text-lg text-gray-600 mb-8">' . wp_kses_post( apply_filters( 'the_content', $posts_page->post_content ) ) . '</div>';
				}
			}
		}
		?>
	</header>

	<div class="insights-grid container mx-auto px-4 pb-16">
		<?php if ( have_posts() ) : ?>

			<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
				<?php while ( have_posts() ) : the_post(); ?>

					<article id="post-<?php the_ID(); ?>" <?php post_class( 'insight-card bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300' ); ?>>

						<?php if ( has_post_thumbnail() ) : ?>
							<a href="<?php the_permalink(); ?>" class="block aspect-[16/9] overflow-hidden">
								<?php the_post_thumbnail( 'large', array( 'class' => 'w-full h-full object-cover hover:scale-105 transition-transform duration-300' ) ); ?>
							</a>
						<?php endif; ?>

						<div class="p-6">
							<header class="entry-header mb-4">
								<h2 class="text-2xl font-bold text-green-deep mb-2">
									<a href="<?php the_permalink(); ?>" class="hover:underline">
										<?php the_title(); ?>
									</a>
								</h2>

								<div class="entry-meta text-sm text-gray-500 flex items-center gap-4">
									<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
										<?php echo get_the_date(); ?>
									</time>

									<?php
									$categories = get_the_category();
									if ( ! empty( $categories ) ) :
										?>
										<span class="category">
											<a href="<?php echo esc_url( get_category_link( $categories[0]->term_id ) ); ?>" class="text-green-deep hover:underline">
												<?php echo esc_html( $categories[0]->name ); ?>
											</a>
										</span>
									<?php endif; ?>
								</div>
							</header>

							<?php if ( get_the_excerpt() ) : ?>
								<div class="entry-summary text-gray-700 mb-4">
									<?php the_excerpt(); ?>
								</div>
							<?php endif; ?>

							<a href="<?php the_permalink(); ?>" class="inline-block text-green-deep font-medium hover:underline">
								Read More →
							</a>
						</div>

					</article>

				<?php endwhile; ?>
			</div>

			<?php
			// Pagination
			the_posts_pagination( array(
				'mid_size'  => 2,
				'prev_text' => __( '← Previous', 'summit-law-theme' ),
				'next_text' => __( 'Next →', 'summit-law-theme' ),
				'class'     => 'mt-12',
			) );
			?>

		<?php else : ?>

			<div class="text-center py-12">
				<p class="text-gray-600 text-lg"><?php esc_html_e( 'No insights found.', 'summit-law-theme' ); ?></p>
			</div>

		<?php endif; ?>
	</div>

</main>

<?php
get_footer();
