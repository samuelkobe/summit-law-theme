<?php
/**
 * Block template file: hero_banner.php
 *
 * Hero Banner Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'hero_banner-' . $block['id'];
if ( ! empty($block['anchor'] ) ) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$classes = 'block-hero_banner';
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


<header id="<?php echo esc_attr( $id ); ?>" class="page-header bg-green-deep lg:h-[30dvh] min-h-[240px] lg:min-h-[400px] flex items-center pt-12 pb-20 lg:py-24 <?php echo esc_attr( $classes ); ?>">
		<section class="banner-after-content h-full flex flex-col justify-center container mx-auto p-6 relative">
			<?php
			$custom_title_enabled = get_field( 'custom_title' );
			$custom_title         = get_field( 'title' );
			$post_title           = get_the_title();
			$is_auto_draft        = $post_title === 'Auto Draft' || empty( $post_title );
			?>
			<h1 class="h1 2xl:text-7xl !text-green-accent1 mb-4 lg:mb-10">
				<?php if ( $custom_title_enabled == 1 ) : ?>
					<?php if ( $custom_title ) : ?>
						<?php echo esc_html( $custom_title ); ?>
					<?php else : ?>
						<span class="text-brand-40">Add a page title in the block settings.</span>
					<?php endif; ?>
				<?php elseif ( $is_preview && $is_auto_draft ) : ?>
					<span class="hero-banner-title-placeholder text-brand-40" data-block-id="<?php echo esc_attr( $block['id'] ); ?>">Add title above, or in the block settings for a custom title.</span>
				<?php else : ?>
					<span class="hero-banner-title" data-block-id="<?php echo esc_attr( $block['id'] ); ?>"><?php the_title(); ?></span>
				<?php endif; ?>
			</h1>

			<?php if ( $is_preview && ! $custom_title_enabled ) : ?>
			<script>
			(function() {
				const blockId = '<?php echo esc_js( $block['id'] ); ?>';
				const titleElement = document.querySelector('[data-block-id="' + blockId + '"]');

				if (!titleElement || !window.wp || !window.wp.data) return;

				const { subscribe, select } = window.wp.data;
				let lastTitle = '';

				const updateTitle = () => {
					const currentTitle = select('core/editor')?.getEditedPostAttribute('title') || '';

					if (currentTitle !== lastTitle) {
						lastTitle = currentTitle;

						if (currentTitle && currentTitle !== 'Auto Draft') {
							titleElement.textContent = currentTitle;
							titleElement.classList.remove('text-brand-40');
							titleElement.classList.add('text-green-accent1');
						} else {
							titleElement.textContent = 'Add title above, or in the block settings for a custom title.';
							titleElement.classList.add('text-brand-40');
							titleElement.classList.remove('text-green-accent1');
						}
					}
				};

				// Initial check
				updateTitle();

				// Subscribe to changes
				subscribe(updateTitle);
			})();
			</script>
			<?php endif; ?>

			<?php // this needs to be replaced with dynamic content from ACF/WP admin ?>
			<p class="text-white text-lg lg:text-xl w-full md:w-3/4 2xl:w-2/3">
				<?php the_field( 'page_description' ); ?>
				<?php if ( ! get_field( 'page_description' ) ) : ?>
					<span class="text-brand-40">Add a page description in the block settings.</span>
				<?php endif; ?>
			</p>

			<!-- <?//php if ( is_page( 'services' ) ) : ?>
				<div class="w-full grid grid-cols-12 gap-4 mt-8 lg:mt-12">
					<div class="col-span-5 sm:col-span-4 md:col-span-3 2xl:col-span-2 bg-white rounded-lg p-2 lg:p-3">
						SEARCH HERE
					</div>
				</div>
			<?//php endif; ?> -->

		</section>
	</header>