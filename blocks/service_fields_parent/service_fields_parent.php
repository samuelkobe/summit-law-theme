<?php
/**
 * Block template file: service_fields_parent.php
 *
 * Service fields for a parent service Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'service_fields_parent-' . $block['id'];
if ( ! empty($block['anchor'] ) ) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$classes = 'block-service_fields_parent';
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

<!-- <div class="block bg-red-800 text-white text-4xl p-4 font-normal text-center">I am the <span class="font-black uppercase">parent service</span> field block (remove this notice before launch).</div> -->

<div id="<?php echo esc_attr( $id ); ?>" class="bg-white block-white grid grid-cols-1 gap-y-12 lg:gap-y-24 xl:gap-y-32 2xl:gap-y-48 xl:pb-32 2xl:pb-48 <?php echo esc_attr( $classes ); ?>">
	
	<!-- Service Areas section -->
	<section class="services-overview bg-white antialiased">
		<div class="container mx-auto px-6">

			<div class="service-type grid grid-cols-1 lg:grid-cols-12 gap-4 lg:gap-6">

				<div class="col-span-1 lg:col-span-4">
					<h2 class="h2 border-b-2 lg:border-b-[3px] border-brand-30 w-fit pb-[6px] lg:pb-[10px]">Areas</h2>
				</div>

				<?php
				// Get current service ID (the parent service this block is on)
				$current_service_id = get_the_ID();

				// Get child practice areas for this service type
				$practice_areas = get_posts( array(
					'post_type'      => 'service',
					'posts_per_page' => -1,
					'post_parent'    => $current_service_id,
					'orderby'        => 'title',
					'order'          => 'ASC'
				) );

				if ( $practice_areas ) : ?>
					<ul class="practice-areas col-span-1 lg:col-span-7 text-lg leading-8 xl:text-xl xl:leading-9 2xl:text-xl 2xl:leading-10 font-normal list-none">
						<?php foreach ( $practice_areas as $area ) : ?>
							<li class="relative font-normal text-lg md:text-xl 2xl:text-2xl border-b-2 border-b-brand-30 group">
								<a href="<?php echo esc_url( get_permalink( $area->ID ) ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Learn more about %s', 'summit-law-theme' ), get_the_title( $area->ID ) ) ); ?>" class="block pr-12 after:content-[''] after:absolute after:right-4 after:md:hover:right-8 after:transition-all after:duration-300 after:top-1/2 after:-translate-y-1/2 after:w-4 after:h-4 2xl:after:w-5 2xl:after:h-5 after:bg-[url('data:image/svg+xml,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20width=%2220%22%20height=%2220%22%20viewBox=%220%200%2024%2024%22%20fill=%22%23d1d436%22%3E%3Cpath%20d=%22M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z%22/%3E%3C/svg%3E')] after:bg-contain after:bg-no-repeat group-hover:bg-brand-10/40 py-6 2xl:py-10 h-full">
									<span class="relative block group-hover:transform group-hover:translate-x-4 transition-transform duration-300"><?php echo esc_html( get_the_title( $area->ID ) ); ?></span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php elseif ( $is_preview ) : ?>
					<ul class="practice-areas col-span-1 lg:col-span-7 text-lg leading-8 xl:text-xl xl:leading-9 2xl:text-xl 2xl:leading-10 font-normal list-none">
						<li class="relative font-normal text-lg md:text-xl 2xl:text-2xl border-b-2 border-b-brand-30 py-6 2xl:py-10">
							<span class="text-brand-40">Child service areas will appear here once you create services with this page as the parent.</span>
						</li>
					</ul>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<!-- End Service Areas section -->

	<?php if ( get_field( 'info_toggle' ) == 1 ) : ?>
	<!-- Info section -->
	<?php
	$info_title            = get_field( 'title' );
	$has_info_bullet_points = have_rows( 'bullet_points' );
	?>
	<section class="services-overview bg-white antialiased">
		<div class="container mx-auto px-6">
			<?php if ( $has_info_bullet_points || $is_preview ) : ?>
				<div class="grid grid-cols-1 lg:grid-cols-12">
					<div class="col-span-1 lg:col-span-12">
						<h2 class="h2 h2-line-after alt-line-after">
							<?php if ( $info_title ) : ?>
								<?php echo esc_html( $info_title ); ?>
							<?php elseif ( $is_preview ) : ?>
								<span class="text-brand-40">Add a title in block settings.</span>
							<?php endif; ?>
						</h2>
					</div>
					<div class="col-span-1 lg:col-span-9 mt-6 lg:mt-10">
						<?php if ( $has_info_bullet_points ) : ?>
							<ul class="text-xl leading-8 xl:text-2xl xl:leading-9 font-normal list-none space-y-2 lg:space-y-4 2xl:space-y-8">
								<?php while ( have_rows( 'bullet_points' ) ) : the_row(); ?>
									<li class="flex items-start before:top-4 xl:before:top-[18px] before:relative gap-5 before:content-[''] before:block before:w-[15px] before:h-[3px] before:bg-green-accent1 before:flex-shrink-0">
										<?php the_sub_field( 'bullet_content' ); ?>
									</li>
								<?php endwhile; ?>
							</ul>
						<?php elseif ( $is_preview ) : ?>
							<ul class="text-xl leading-8 xl:text-2xl xl:leading-9 font-normal list-none space-y-2 lg:space-y-4 2xl:space-y-8">
								<li class="flex items-start before:top-4 xl:before:top-[18px] before:relative gap-5 before:content-[''] before:block before:w-[15px] before:h-[3px] before:bg-green-accent1 before:flex-shrink-0">
									<span class="text-brand-40">Add bullet points in block settings.</span>
								</li>
							</ul>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<!-- End Info section -->
	<?php endif; ?>
			
	<?php if ( get_field( 'show_team_members_toggle' ) == 1 ) : ?>
	<!-- Team section -->
	<section class="services-overview bg-white antialiased">
		<div class="container mx-auto px-6">
			<div class="grid grid-cols-2 lg:grid-cols-12 gap-x-8 2xl:gap-x-12">
				<h2 class="h2 col-span-2 lg:col-span-12"><?php the_title(); ?> Team</h2>
				<?php $team = get_field( 'team' ); ?>
				<?php if ( $team ) : ?>
					<?php foreach ( $team as $team_member ) : ?>
						<?php $role = get_field( 'role', $team_member->ID ); ?>
						<div class="col-span-2 sm:col-span-1 lg:col-span-3 mt-6 lg:mt-10 px-6 lg:px-0">
							<a href="<?php echo esc_url( get_permalink( $team_member->ID ) ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'View %s profile', 'summit-law-theme' ), get_the_title( $team_member->ID ) ) ); ?>" class="no-underline group hover:underline decoration-transparent hover:decoration-green-deep underline-offset-4 decoration-2.5px transition-colors duration-300">
								<?php
								// Get thumbnail with responsive sizes
								$thumbnail_id = get_post_thumbnail_id( $team_member->ID );
								if ( $thumbnail_id ) :
									$image_alt = get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true );
									if ( ! $image_alt ) {
										$image_alt = get_the_title( $team_member->ID );
									}
									$image_small = wp_get_attachment_image_src( $thumbnail_id, 'medium' );
									$image_medium = wp_get_attachment_image_src( $thumbnail_id, 'medium_large' );
									$image_large = wp_get_attachment_image_src( $thumbnail_id, 'large' );
								?>
									<img
										class="w-full h-auto aspect-square bg-brand-black object-cover rounded-lg mb-2 lg:mb-4 group-hover:shadow-xl transition-shadow duration-300"
										src="<?php echo esc_url( $image_medium[0] ); ?>"
										srcset="<?php echo esc_url( $image_small[0] ); ?> <?php echo esc_attr( $image_small[1] ); ?>w,
												<?php echo esc_url( $image_medium[0] ); ?> <?php echo esc_attr( $image_medium[1] ); ?>w,
												<?php echo esc_url( $image_large[0] ); ?> <?php echo esc_attr( $image_large[1] ); ?>w"
										sizes="(max-width: 640px) 50vw, (max-width: 1024px) 25vw, 600px"
										alt="<?php echo esc_attr( $image_alt ); ?>"
										loading="lazy"
									/>
								<?php endif; ?>
								<h3 class="text-green-deep text-2xl">
									<span class="group no-underline hover:underline">
										<div class="inline-flex items-start gap-2">
											<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" class="flex-shrink-0 fill-green-accent1 hover:fill-green-accent1 relative top-[6px]" aria-hidden="true"><path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"/></svg>
											<span class="lg:w-48 font-museum decoration-[2.5px] underline underline-offset-4 decoration-transparent group-hover:decoration-green-deep transition-colors duration-300"><?php echo esc_html( get_the_title( $team_member->ID ) ); ?></span>
										</div>
									</span>
								</h3>
							</a>
							<span class="text-xl text-brand-40 mt-1 lg:mt-2 mb-4 lg:mb-0 flex"><?php echo esc_html( $role['label'] ); ?></span>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<!-- End Team section -->
	<?php endif; ?>
</div>
