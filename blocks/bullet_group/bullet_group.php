<?php
/**
 * Block template file: bullet_group.php
 *
 * Bullet Group Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'bullet_group-' . $block['id'];
if ( ! empty($block['anchor'] ) ) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$classes = 'block-bullet_group';
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

<section id="<?php echo esc_attr( $id ); ?>" class="bg-white <?php echo esc_attr( $classes ); ?>">

	<?php if ( have_rows( 'bullet_points' ) ) : ?>

		<div class="md:container grid grid-cols-1 lg:grid-cols-12 gap-8 p-6 lg:px-6 lg:py-10">
			<div class="lg:col-span-4">
				<h2 class="h2 border-b-[3px] border-brand-30 w-fit pb-[10px]"><?php the_field( 'title' ); ?></h2>
			</div>
			<div class="lg:col-span-7">
					<ul class="text-lg leading-8 xl:text-xl xl:leading-9 2xl:text-xl 2xl:leading-10 font-normal list-none space-y-2">
						<?php while ( have_rows( 'bullet_points' ) ) : the_row(); ?>
							<li class="flex items-start before:top-4 xl:before:top-[18px] 2xl:before:top-5 before:relative gap-5 before:content-[''] before:block before:w-[15px] before:h-[3px] before:bg-green-accent1 before:flex-shrink-0">
								<?php the_sub_field( 'bullet_content' ); ?>
							</li>
						<?php endwhile; ?>
					</ul>
			</div>
		</div>

	<?php endif; ?>

</section>