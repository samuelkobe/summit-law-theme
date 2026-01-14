<?php
/**
 * Block template file: form.php
 *
 * Form Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'form-' . $block['id'];
if ( ! empty($block['anchor'] ) ) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$classes = 'block-form';
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

<section id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $classes ); ?> bg-green-deep py-16 lg:py-24">
	<div class="container mx-auto grid grid-cols-1 lg:grid-cols-12 gap-4 px-4 lg:px-0">
		<div class="col-span-1 lg:col-start-2 lg:col-span-4 flex flex-col items-start justify-cente p-4">
			<h2 class="font-museum font-normal antialiased leading-tight tracking-[-0.02em] text-[clamp(28px,_3vw_+_28px,_44px)] xl:text-[clamp(32px,_3vw_+_28px,_50px)] text-green-accent1 2xl:max-w-[75%] mb-4 lg:mb-8"><?php the_field( 'heading' ); ?></h1>
			<p class="text-lg xl:text-xl 2xl:text-xl font-normal text-white 2xl:max-w-[75%] mb-6 lg:mb-12"><?php the_field( 'content' ); ?></p>
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
					$image_form = wp_get_attachment_image_src( $image_id, 'form-image' ); // 900x1200px
					$image_desktop = wp_get_attachment_image_src( $image_id, 'full' );
					?>
					<img
						class="w-full h-auto max-w-full object-cover aspect-[7/8] lg:aspect-[3/4] rounded-lg shadow-lg relative z-0"
						src="<?php echo esc_url( $image_tablet[0] ); ?>"
						srcset="<?php echo esc_url( $image_mobile[0] ); ?> <?php echo $image_mobile[1]; ?>w,
								<?php echo esc_url( $image_tablet[0] ); ?> <?php echo $image_tablet[1]; ?>w,
								<?php echo esc_url( $image_form[0] ); ?> <?php echo $image_form[1]; ?>w,
								<?php echo esc_url( $image_desktop[0] ); ?> <?php echo $image_desktop[1]; ?>w"
						sizes="(max-width: 640px) 90vw, (max-width: 1024px) 45vw, 800px"
						alt="<?php echo esc_attr( $image_alt ); ?>"
						loading="lazy"
					/>
					<svg class="w-12 md:w-16 2xl:w-24 flex-shrink-0 absolute bottom-8 2xl:bottom-12 left-8 md:-left-8 2xl:-left-12 z-1" xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 72 72">
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
		</div>
		<div class="text-white text-base lg:text-xl col-span-1 lg:col-start-7 lg:col-span-5 wysiwyg p-4">
			<?php the_field( 'shortcode' ); ?>
		</div>
	</div>
</section>