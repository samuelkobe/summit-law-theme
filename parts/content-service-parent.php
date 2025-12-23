<?php
/**
 * Template part for displaying parent services (Service Types)
 *
 * Used for: Insurance Litigation, Commercial Litigation, Mediation
 * URL example: /services/insurance-litigation/
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'service-parent' ); ?>>

	<header class="entry-header">
		<h1 class="entry-title"><?php the_title(); ?></h1>
	</header>

	<?php if ( has_post_thumbnail() ) : ?>
		<div class="service-hero">
			<?php the_post_thumbnail( 'large' ); ?>
		</div>
	<?php endif; ?>

	<div class="entry-content">
		<?php the_content(); ?>
	</div>

	<?php
	// Display child practice areas
	$practice_areas = get_posts( array(
		'post_type'      => 'service',
		'posts_per_page' => -1,
		'post_parent'    => get_the_ID(),
		'orderby'        => 'menu_order',
		'order'          => 'ASC'
	) );

	if ( $practice_areas ) : ?>
		<section class="practice-areas-section">
			<h2>Practice Areas</h2>
			<div class="practice-areas-grid">
				<?php foreach ( $practice_areas as $area ) : ?>
					<article class="practice-area-card">
						<?php if ( has_post_thumbnail( $area->ID ) ) : ?>
							<div class="practice-area-card__image">
								<a href="<?php echo esc_url( get_permalink( $area->ID ) ); ?>">
									<?php echo get_the_post_thumbnail( $area->ID, 'medium' ); ?>
								</a>
							</div>
						<?php endif; ?>

						<div class="practice-area-card__content">
							<h3>
								<a href="<?php echo esc_url( get_permalink( $area->ID ) ); ?>">
									<?php echo esc_html( get_the_title( $area->ID ) ); ?>
								</a>
							</h3>

							<?php if ( get_the_excerpt( $area->ID ) ) : ?>
								<p><?php echo esc_html( get_the_excerpt( $area->ID ) ); ?></p>
							<?php endif; ?>

							<a href="<?php echo esc_url( get_permalink( $area->ID ) ); ?>" class="read-more">
								Learn More
							</a>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

</article>
