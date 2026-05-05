<?php
/**
 * Template part for displaying parent services (Service Types)
 *
 * Used for: Insurance Litigation, Commercial Litigation, Mediation
 * URL example: /services/insurance-litigation/
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'service-parent page-content' ); ?>>

	<?php the_content(); ?>

	<div class="bg-brand-10 py-12 lg:py-24 xl:pb-32 px-6">
		<!-- Other Services section -->
		<section class="services-overview antialiased">
			<div class="container mx-auto px-6">
				<h2 class="h2 h2-line-after">Other Services</h2>
				<?php
				// Get only parent services (top-level), excluding the current service
				$current_service_id = get_the_ID();
				$other_service_types = get_posts( array(
					'post_type'      => 'service',
					'posts_per_page' => -1,
					'post_parent'    => 0,
					'exclude'        => array( $current_service_id ),
					'orderby'        => 'menu_order',
					'order'          => 'ASC'
				) );

				if ( $other_service_types ) : ?>
					<div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8 2xl:gap-12 mt-6 lg:mt-10">
						<?php foreach ( $other_service_types as $service_type ) : ?>
							<div class="col-span-1 flex flex-col gap-4 lg:gap-6">
								<h3 class="text-green-deep text-2xl">
									<a href="<?php echo esc_url( get_permalink( $service_type->ID ) ); ?>" class="no-underline group hover:underline decoration-transparent hover:decoration-green-deep underline-offset-4 decoration-2.5px transition-colors duration-300">
										<div class="inline-flex items-start gap-2">
											<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" class="flex-shrink-0 fill-green-accent1 hover:fill-green-accent1 relative top-[6px]"><path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"/></svg>
											<span class="font-hanken decoration-[2px] underline underline-offset-4 decoration-transparent group-hover:decoration-green-deep transition-colors duration-300"><?php echo esc_html( get_the_title( $service_type->ID ) ); ?></span>
										</div>
									</a>
								</h3>

								<?php
								// Get child practice areas for this service type
								$other_practice_areas = get_posts( array(
									'post_type'      => 'service',
									'posts_per_page' => -1,
									'post_parent'    => $service_type->ID,
									'orderby'        => 'title',
									'order'          => 'ASC'
								) );

								if ( $other_practice_areas ) : ?>
									<ul class="flex flex-col gap-2 lg:gap-3">
										<?php foreach ( $other_practice_areas as $area ) : ?>
											<li>
												<a class="font-hanken no-underline decoration-[2px] hover:underline underline-offset-4 decoration-transparent hover:decoration-green-deep transition-colors duration-300" href="<?php echo esc_url( get_permalink( $area->ID ) ); ?>">
													<?php echo esc_html( get_the_title( $area->ID ) ); ?>
												</a>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</section>
		<!-- End Other Services section -->
	</div>

</article>
