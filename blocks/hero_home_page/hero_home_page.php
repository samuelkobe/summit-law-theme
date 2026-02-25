<?php
/**
 * Block template file: hero_home_page.php
 *
 * Hero Home Page Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'hero_home_page-' . $block['id'];
if ( ! empty($block['anchor'] ) ) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$classes = 'block-hero_home_page';
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


<header id="<?php echo esc_attr( $id ); ?>" class="page-header bg-green-deep lg:h-[80dvh] xl:h-[100dvh] min-h-[240px] lg:min-h-[800px] xl:min-h-[1024px] flex items-center pt-12 pb-20 lg:py-24 <?php echo esc_attr( $classes ); ?>">
		<section class="home-before-after-content animate-line h-full flex flex-col justify-center container mx-auto p-6 relative">

			<?php // this needs to be replaced with dynamic content from ACF/WP admin ?>

			<div class="hero-content-grid grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 2xl:gap-24 items-end xl:mt-[20dvh]" data-hero-content>
				<div class="col-span-1">

					<?php
					$slogan     = get_field( 'slogan' );
					$post_title = get_the_title();
					$is_auto_draft = $post_title === 'Auto Draft' || empty( $post_title );
					?>
					<h1 class="font-normal leading-tight tracking-[-0.02em] text-h2 3xl:text-h1 antialiased font-hanken text-green-accent1 mb-4 3xl:mb-10">
						<?php if ( $slogan ) : ?>
							<?php echo strip_tags( $slogan, '<em><strong><br>' ); ?>
						<?php elseif ( $is_preview && $is_auto_draft ) : ?>
							<span class="hero-banner-title-placeholder text-brand-40" data-block-id="<?php echo esc_attr( $block['id'] ); ?>">Add a slogan in block settings, or a page title above.</span>
						<?php else : ?>
							<span class="hero-banner-title" data-block-id="<?php echo esc_attr( $block['id'] ); ?>"><?php the_title(); ?></span>
						<?php endif; ?>
					</h1>

					<?php if ( $is_preview && ! $slogan ) : ?>
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
									titleElement.textContent = 'Add a slogan in block settings, or a page title above.';
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

					<p class="text-white text-lg 3xl:text-xl">
						<?php the_field( 'content' ); ?>
						<?php if ( ! get_field( 'content' ) ) : ?>
							<span class="text-brand-40">Add hero content in the block settings.</span>
						<?php endif; ?>
					</p>
				</div>
				<div class="col-span-1 p-6 max-lg:pl-0 lg:pl-12 2xl:pl-24 !pb-0 !pr-0 md:mt-12 lg:mt-0">
					<?php
					$gallery_images = get_field( 'gallery' );
					if ( $gallery_images && is_array( $gallery_images ) ) :
						// Get a random image from the gallery
						$random_index = array_rand( $gallery_images );
						$random_image = $gallery_images[ $random_index ];
						// Use large size for better quality, fallback to full
						$image_size = isset( $random_image['sizes']['large'] ) ? 'large' : 'full';
						$image_url = $random_image['sizes'][ $image_size ] ?? $random_image['url'];
						?>
						<div class="hero-gallery-image relative">
							<img
								src="<?php echo esc_url( $image_url ); ?>"
								alt="<?php echo esc_attr( $random_image['alt'] ); ?>"
								class="w-full h-auto aspect-video object-cover shadow-lg overflow-hidden"
							/>
							<svg xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 72 72" class="w-12 md:w-16 xl:w-[72px] lg:group-hover:-translate-y-4 transition-transform duration-500 delay-200 flex-shrink-0 absolute top-8 md:-top-8 left-8 md:left-16 z-10">
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
					<?php elseif ( $is_preview ) : ?>
						<div class="hero-gallery-placeholder bg-green-accent1/20 aspect-[4/3] flex items-center justify-center">
							<span class="text-brand-40 text-center px-4">Add gallery images in block settings.</span>
						</div>
					<?php endif; ?>
				</div>
			</div>

		</section>
	</header>