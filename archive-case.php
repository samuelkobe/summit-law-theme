<?php
/**
 * Archive template for Cases
 *
 * Displays all cases in a grid layout
 * URL: /cases/
 */

get_header();
?>

<main id="main" class="site-main team-archive">

	<header class="page-header container mx-auto px-4 py-12">
		<h1 class="text-4xl md:text-5xl font-bold text-green-deep mb-4">
			<?php post_type_archive_title(); ?>
		</h1>
		<?php
		// Optional: Add ACF fields or custom description
		$post_type = get_post_type_object( 'case' );
		if ( $post_type && ! empty( $post_type->description ) ) {
			echo '<p class="text-lg text-gray-600">' . esc_html( $post_type->description ) . '</p>';
		}
		?>
	</header>

	<div class="team-grid container mx-auto px-4 pb-16">
		<?php if ( have_posts() ) : ?>

			<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
				<?php while ( have_posts() ) : the_post(); ?>

					<article class="team-member-card bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">

						<?php if ( has_post_thumbnail() ) : ?>
							<a href="<?php the_permalink(); ?>" class="block aspect-[3/4] overflow-hidden">
								<?php the_post_thumbnail( 'large', array( 'class' => 'w-full h-full object-cover hover:scale-105 transition-transform duration-300' ) ); ?>
							</a>
						<?php endif; ?>

						<div class="p-6">
							<h2 class="text-2xl font-bold text-green-deep mb-2">
								<a href="<?php the_permalink(); ?>" class="hover:underline">
									<?php the_title(); ?>
								</a>
							</h2>

							<?php if ( get_field( 'role' ) ) : ?>
								<p class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-4">
									<?php the_field( 'role' ); ?>
								</p>
							<?php endif; ?>

							<?php if ( get_the_excerpt() ) : ?>
								<div class="text-gray-700 mb-4">
									<?php the_excerpt(); ?>
								</div>
							<?php endif; ?>

							<a href="<?php the_permalink(); ?>" class="inline-block text-green-deep font-medium hover:underline">
								View Profile →
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

			<p class="text-center text-gray-600"><?php esc_html_e( 'No team members found.', 'summit-law-theme' ); ?></p>

		<?php endif; ?>
	</div>

</main>

<?php
get_footer();
