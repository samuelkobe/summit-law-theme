<?php
/**
 * Block template file: cta.php
 *
 * Cta Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'cta-' . $block['id'];
if ( ! empty($block['anchor'] ) ) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$classes = 'block-cta';
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

<div id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $classes ); ?> bg-brand-10 py-24">
	<div class="container mx-auto flex flex-col items-center justify-center text-center px-4 lg:px-0">
		<h2 class="h2 mb-4"><?php the_field( 'call_to_action_title' ); ?></h2>
		<p class="text-brand-black text-lg lg:text-xl mb-7 lg:mb-11 w-fit text-center"><?php the_field( 'call_to_action_content' ); ?></p>
		<?php $call_to_action_linked_page = get_field( 'call_to_action_linked_page' ); ?>
		<?php if ( $call_to_action_linked_page ) : ?>
			<a class="btn" href="<?php echo esc_url( $call_to_action_linked_page['url'] ); ?>" target="<?php echo esc_attr( $call_to_action_linked_page['target'] ); ?>"><?php echo esc_html( $call_to_action_linked_page['title'] ); ?></a>
		<?php endif; ?>
	</div>
</div>