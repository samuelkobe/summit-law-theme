<?php
/**
 * Block template file: triple_cards.php
 *
 * Triple Cards Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'triple-cards-' . $block['id'];
if ( ! empty($block['anchor'] ) ) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$classes = 'block-triple-cards';
if ( ! empty( $block['className'] ) ) {
    $classes .= ' ' . $block['className'];
}
if ( ! empty( $block['align'] ) ) {
    $classes .= ' align' . $block['align'];
}

// Get the selected content type
$selected_type = get_field( 'triple_card_content_type_selector' );

// Map of content type values to their field names
$content_fields = array(
	'all'      => 'triple_card_content_all',
	'services' => 'triple_card_content_services',
	'pages'    => 'triple_card_content_pages',
	'articles' => 'triple_card_content_articles',
);

// Determine which field to use based on selection
$field_name = isset( $content_fields[ $selected_type['value'] ] )
	? $content_fields[ $selected_type['value'] ]
	: null;

// Get the selected posts
$selected_posts = $field_name ? get_field( $field_name ) : null;

// Inline styles for the block (if needed) ?>
<style type="text/css">
	<?php echo '#' . $id; ?> {
		/* Add styles that use ACF values here */
	}
</style>

<div id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $classes ); ?> bg-white py-24">
	<div class="container mx-auto">
		<h2 class="h2 border-b-2 lg:border-b-[3px] border-b-brand-30 w-fit pb-2 lg:pb-3 mb-4 lg:mb-7"><?php echo  esc_html(get_field( 'triple_card_title' )); ?></h2>
		<p class="text-green-deep text-lg lg:text-xl mb-7 lg:mb-11 max-w-none lg:max-w-[75%]"><?php echo esc_html(get_field( 'triple_card_content' )); ?></p>

		<? // Display the posts
		if ( $selected_posts ) : ?>
			<div class="triple-cards-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 lg:gap-12">
				<?php foreach ( $selected_posts as $selected_post ) : ?>
					<article class="triple-card">
						<a class="group text-green-deep text-2xl xl:text-3xl no-underline hover:underline" href="<?php echo esc_url( get_permalink( $selected_post->ID ) ); ?>">
							<?php if ( has_post_thumbnail( $selected_post->ID ) ) : ?>
								<div class="triple-card__wrapper mb-4 md:w-[80%] relative">
									<div class="triple-card__image aspect-[4/5] overflow-hidden relative shadow-md lg:group-hover:shadow-xl lg:group-hover:-translate-y-1 lg:group-hover:scale-[1.025] transition-all duration-500">
										<?php echo get_the_post_thumbnail( $selected_post->ID, 'full', array( 'class' => 'w-full h-full object-cover' ) ); ?>
									</div>
									<svg xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 72 72" class="w-12 md:w-16 xl:w-[72px] lg:group-hover:-translate-y-4 transition-transform duration-500 delay-200 flex-shrink-0 absolute bottom-8 right-8 md:-right-8 z-10">
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
							<div class="inline-flex items-start gap-2">
								<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" class="flex-shrink-0 fill-green-accent1 hover:fill-green-accent1 relative top-2"><path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"/></svg>
								<span class="md:w-48 font-museum decoration-[2.5px] underline underline-offset-4 decoration-transparent group-hover:decoration-green-deep transition-colors duration-300"><?php echo esc_html( get_the_title( $selected_post->ID ) ); ?></span>
							</div>
						</a>

					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</div>