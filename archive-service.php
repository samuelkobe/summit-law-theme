<?php
/**
 * Archive template for Services
 *
 * Displays all top-level service types with their practice areas
 * URL: /services/
 */

get_header();
?>

<main id="main" class="site-main services-archive">

	<header class="page-header">
		<h1><?php post_type_archive_title(); ?></h1>
		<?php
		// Optional: Add ACF fields or custom description
		$post_type = get_post_type_object( 'service' );
		if ( $post_type && ! empty( $post_type->description ) ) {
			echo '<p class="archive-description">' . esc_html( $post_type->description ) . '</p>';
		}
		?>
	</header>

	<div class="services-overview">
		<?php
		// Get only parent services (the 3 main types)
		$service_types = get_posts( array(
			'post_type'      => 'service',
			'posts_per_page' => -1,
			'post_parent'    => 0, // Only top-level items
			'orderby'        => 'menu_order',
			'order'          => 'ASC'
		) );

		if ( $service_types ) :
			foreach ( $service_types as $service_type ) : ?>

				<section class="service-type">
					<h2>
						<a href="<?php echo esc_url( get_permalink( $service_type->ID ) ); ?>">
							<?php echo esc_html( get_the_title( $service_type->ID ) ); ?>
						</a>
					</h2>

					<?php if ( get_the_excerpt( $service_type->ID ) ) : ?>
						<div class="service-type__excerpt">
							<?php echo wp_kses_post( get_the_excerpt( $service_type->ID ) ); ?>
						</div>
					<?php endif; ?>

					<?php
					// Get child practice areas for this service type
					$practice_areas = get_posts( array(
						'post_type'      => 'service',
						'posts_per_page' => -1,
						'post_parent'    => $service_type->ID,
						'orderby'        => 'menu_order',
						'order'          => 'ASC'
					) );

					if ( $practice_areas ) : ?>
						<ul class="practice-areas">
							<?php foreach ( $practice_areas as $area ) : ?>
								<li>
									<a href="<?php echo esc_url( get_permalink( $area->ID ) ); ?>">
										<?php echo esc_html( get_the_title( $area->ID ) ); ?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</section>

			<?php endforeach;
		else : ?>
			<p><?php esc_html_e( 'No services found.', 'summit-law' ); ?></p>
		<?php endif; ?>
	</div>

</main>

<?php
get_footer();
