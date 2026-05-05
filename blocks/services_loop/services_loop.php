<?php
/**
 * Block template file: services_loop.php
 *
 * Services Loop Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'services_loop-' . $block['id'];
if ( ! empty($block['anchor'] ) ) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$classes = 'block-services_loop';
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

<section id="<?php echo esc_attr( $id ); ?>" class="services-overview bg-white block-white antialiased <?php echo esc_attr( $classes ); ?>">
	<div class="container mx-auto px-6">
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

				<div class="service-type grid grid-cols-1 lg:grid-cols-12 gap-4 lg:gap-6 mb-12 lg:mb-24 2xl:mb-32 last:mb-0">

					<div class="col-span-1 lg:col-span-4">
						<h2 class="lg:transform lg:translate-y-6">
							<a class="group text-green-deep text-2xl md:text-3xl 2xl:text-4xl no-underline hover:underline" href="<?php echo esc_url( get_permalink( $service_type->ID ) ); ?>">
								<div class="inline-flex items-start gap-2">
									<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" class="flex-shrink-0 fill-green-accent1 hover:fill-green-accent1 relative top-2"><path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"/></svg>
									<span class="lg:w-48 font-museum decoration-2 lg:decoration-[3px] underline underline-offset-4 decoration-transparent group-hover:decoration-green-deep transition-colors duration-300"><?php echo esc_html( get_the_title( $service_type->ID ) ); ?></span>
								</div>
							</a>
						</h2>
					</div>

					<?php
					// Get child practice areas for this service type
					$practice_areas = get_posts( array(
						'post_type'      => 'service',
						'posts_per_page' => -1,
						'post_parent'    => $service_type->ID,
						'orderby'        => 'title',
						'order'          => 'ASC'
					) );

					if ( $practice_areas ) : ?>
						<ul class="practice-areas col-span-1 lg:col-span-7 text-lg leading-8 xl:text-xl xl:leading-9 2xl:text-xl 2xl:leading-10 font-normal list-none">
							<?php foreach ( $practice_areas as $area ) : ?>
								<li class="relative font-normal text-lg md:text-xl 2xl:text-2xl border-b-2 lg:border-b-[3px] border-b-brand-30 group">
									<a href="<?php echo esc_url( get_permalink( $area->ID ) ); ?>" class="block pr-12 after:content-[''] after:absolute after:right-4 after:md:hover:right-8 after:transition-all after:duration-300 after:top-1/2 after:-translate-y-1/2 after:w-4 after:h-4 2xl:after:w-5 2xl:after:h-5 after:bg-[url('data:image/svg+xml,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20width=%2220%22%20height=%2220%22%20viewBox=%220%200%2024%2024%22%20fill=%22%23d1d436%22%3E%3Cpath%20d=%22M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z%22/%3E%3C/svg%3E')] after:bg-contain after:bg-no-repeat group-hover:bg-gradient-to-r group-hover:from-white group-hover:to-brand-10/30 py-6 2xl:py-10 h-full">
										<span class="relative block group-hover:transform group-hover:translate-x-4 transition-transform duration-300"><?php echo esc_html( get_the_title( $area->ID ) ); ?></span>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>

			<?php endforeach;
		else : ?>
			<p><?php esc_html_e( 'No services found.', 'summit-law' ); ?></p>
		<?php endif; ?>
	</div>
</section>
