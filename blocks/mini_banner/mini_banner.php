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

			<div class="flex flex-col justify-between gap-y-8 col-span-1">
				<?php
					$content = get_field( 'content' );
					$content_gap = !empty( $content ) ? 'gap-y-2' : 'gap-y-0'; 
				?>
				<div class="flex flex-col <?php echo esc_attr( $content_gap ); ?>">
					<h2 class="h2 xl:text-h2 w-fit"><?php the_field( 'title' ); ?></h2>
					<?php if ( !empty( $content ) ) : ?>
						<p class="font-normal text-base 2xl:text-lg lg:max-w-[75%] 2xl:max-w-[60%]"><?php echo esc_html( $content ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<div class="col-span-1">
				<?php $image = get_field( 'image' ); ?>
				<?php if ( $image ) : ?>
					<div class="relative w-full">
						<?php
						// Get the image ID for responsive images
						$image_id = $image['ID'];
						$image_alt = $image['alt'];

						// Get different image sizes
						$image_mobile = wp_get_attachment_image_src( $image_id, 'medium' ); // ~300px
						$image_tablet = wp_get_attachment_image_src( $image_id, 'large' ); // ~1024px
						$image_desktop = wp_get_attachment_image_src( $image_id, 'full' );
						?>
						<img
							class="w-full h-auto max-w-full max-h-[300px] lg:min-h-[360px] lg:max-h-[30dvh] 2xl:max-h-[40dvh] aspect-[1_/_10] object-cover rounded-lg shadow-lg relative z-0"
							src="<?php echo esc_url( $image_tablet[0] ); ?>"
							srcset="<?php echo esc_url( $image_mobile[0] ); ?> <?php echo $image_mobile[1]; ?>w,
									<?php echo esc_url( $image_tablet[0] ); ?> <?php echo $image_tablet[1]; ?>w,
									<?php echo esc_url( $image_desktop[0] ); ?> <?php echo $image_desktop[1]; ?>w"
							sizes="1920px"
							alt="<?php echo esc_attr( $image_alt ); ?>"
							loading="lazy"
						/>
						<svg class="w-12 md:w-16 2xl:w-24 flex-shrink-0 absolute bottom-8 2xl:bottom-12 right-8 md:-right-8 2xl:-right-12 z-1" xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 72 72">
							<path class="fill-green-accent1" d="M39.2,0L0,40.5v6.9L45.9,0h-6.7Z"/>
							<path class="fill-green-accent1" d="M0,0v3.5L3.4,0H0Z"/>
							<path class="fill-green-accent1" d="M53.4,0L0,55.1v6.9L60,0h-6.7Z"/>
							<path class="fill-green-accent1" d="M67.5,0L0,69.8v2.2h4.5L72,2.3V0h-4.5Z"/>
							<path class="fill-green-accent1" d="M10.9,0L0,11.2v6.9L17.5,0h-6.7Z"/>
							<path class="fill-green-accent1" d="M25,0L0,25.8v6.9L31.7,0h-6.7Z"/>
							<path class="fill-green-accent1" d="M68.7,72h3.3v-3.4l-3.3,3.4Z"/>
							<path class="fill-green-accent1" d="M54.5,72h6.7l10.8-11.1v-6.9l-17.5,18Z"/>
							<path class="fill-green-accent1" d="M12,72h6.7l53.3-55.1v-6.9L12,72Z"/>
							<path class="fill-green-accent1" d="M26.2,72h6.7l39.1-40.4v-6.9l-45.8,47.3Z"/>
							<path class="fill-green-accent1" d="M40.3,72h6.7l25-25.8v-6.9l-31.6,32.7Z"/>
						</svg>
					</div>
				<?php endif; ?>

				<?php if ( have_rows( 'lower_content_groups' ) ) : ?>
					<div class="grid col-span-1 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 3xl:gap-x-12 mt-8 ">
						<?php while ( have_rows( 'lower_content_groups' ) ) : the_row(); ?>
							<div class="flex flex-col gap-2">
								<h3 class="text-xl lg:text-2xl font-hanken font-normal text-green-deep"><?php the_sub_field( 'content_heading' ); ?></h3>
								<p class="text-sm xl:text-base font-hanken font-normal text-brand-black"><?php the_sub_field( 'content' ); ?></p>
							</div>
						<?php endwhile; ?>
					</div>
				<?php endif; ?>
			</div>

	</div>

</section>