<?php
/**
 * Block template file: service_fields_child.php
 *
 * Service fields for a child service Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'service_fields_child-' . $block['id'];
if ( ! empty($block['anchor'] ) ) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$classes = 'block-service_fields_child';
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
		<span class="block bg-blue-800 text-white text-4xl p-2 font-bold">I am the child service field block (REMOVE).</span>
	</div>
</section>
