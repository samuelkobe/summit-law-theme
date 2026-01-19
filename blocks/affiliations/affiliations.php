<?php
/**
 * Block template file: affiliations.php
 *
 * Affiliations Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'affiliations-' . $block['id'];
if ( ! empty($block['anchor'] ) ) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$classes = 'block-affiliations';
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

<section id="<?php echo esc_attr( $id ); ?>" class="py-16 lg:py-24 <?php echo esc_attr( $classes ); ?>">

	<div class="container mx-auto">

		<h2 class="h2 h2-line-after"><?php the_field( 'title' ); ?></h2>

		<?php if ( have_rows( 'logos_links' ) ) : ?>
			<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-16 max-sm:px-6 lg:gap-24 2xl:gap-32 items-center pt-12 pb-8 lg:py-16">
				<?php while ( have_rows( 'logos_links' ) ) : the_row(); ?>
					<?php $logo = get_sub_field( 'logo' ); ?>
					<?php if ( $logo ) : ?>
						<div class="col-span-1">
							<a class="" href="<?php the_sub_field( 'link' ); ?>" target="_blank" rel="noopener noreferrer">
								<img class="aspect-image object-contain max-w-full" src="<?php echo esc_url( $logo['url'] ); ?>" alt="<?php echo esc_attr( $logo['alt'] ); ?>" />
							</a>
						</div>
					<?php endif; ?>
				<?php endwhile; ?>
			</div>
		<?php endif; ?>

	</div>

</section>