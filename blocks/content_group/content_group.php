<?php
/**
 * Block template file: content_group.php
 *
 * Content Group Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'content_group-' . $block['id'];
if ( ! empty($block['anchor'] ) ) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$classes = 'block-content_group';
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

<?php
$title   = get_field( 'title' );
$content = get_field( 'content' );
?>

<section id="<?php echo esc_attr( $id ); ?>" class="bg-white <?php echo esc_attr( $classes ); ?>">

	<div class="md:container grid grid-cols-1 lg:grid-cols-12 gap-8 p-6 lg:px-6 lg:py-10">
		<div class="lg:col-span-4">
			<h2 class="h2 border-b-2 lg:border-b-[3px] border-brand-30 w-fit pb-[10px]">
				<?php if ( $title ) : ?>
					<?php echo esc_html( $title ); ?>
				<?php elseif ( $is_preview ) : ?>
					<span class="text-brand-40">Add a title in block settings.</span>
				<?php endif; ?>
			</h2>
		</div>
		<div class="lg:col-span-7">
			<p class="text-lg leading-8 xl:text-xl xl:leading-9 2xl:text-xl 2xl:leading-10 font-normal">
				<?php if ( $content ) : ?>
					<?php echo wp_kses_post( $content ); ?>
				<?php elseif ( $is_preview ) : ?>
					<span class="text-brand-40">Add content in block settings.</span>
				<?php endif; ?>
			</p>
		</div>
	</div>

</section>