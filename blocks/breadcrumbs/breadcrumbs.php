<?php
/**
 * Block template file: breadcrumbs.php
 *
 * Breadcrumbs Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'breadcrumbs-' . $block['id'];
if ( ! empty($block['anchor'] ) ) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$classes = 'block-breadcrumbs';
if ( ! empty( $block['className'] ) ) {
    $classes .= ' ' . $block['className'];
}
if ( ! empty( $block['align'] ) ) {
    $classes .= ' align' . $block['align'];
}

// Determine title to display - check for hero_banner's custom title first
$hero_custom_title_enabled = get_field( 'custom_title' ); // From hero_banner block
$hero_custom_title         = get_field( 'title' );        // From hero_banner block
$post_title                = get_the_title();
$is_auto_draft             = $post_title === 'Auto Draft' || empty( $post_title );

// Use hero's custom title if enabled and set, otherwise use post title
if ( $hero_custom_title_enabled && $hero_custom_title ) {
    $breadcrumb_title = $hero_custom_title;
} else {
    $breadcrumb_title = $post_title;
}
?>

<style type="text/css">
	<?php echo '#' . $id; ?> {
		/* Add styles that use ACF values here */
	}
</style>

<section id="<?php echo esc_attr( $id ); ?>" class="bg-white <?php echo esc_attr( $classes ); ?>">
	<?php
	// Get current post type and parent info (used by both mobile and desktop nav)
	$current_post_type = get_post_type();
	$parent_id = wp_get_post_parent_id( get_the_ID() );
	$is_service = $current_post_type === 'service';
	$is_child_service = $is_service && $parent_id;

	// Get Services archive page (if one exists)
	$services_page = get_page_by_path( 'services' );
	$services_url = $services_page ? get_permalink( $services_page->ID ) : home_url( '/services/' );

	// Determine mobile back link (immediate parent only)
	if ( $is_child_service ) {
		// Child service -> link to parent service
		$mobile_back_url = get_permalink( $parent_id );
		$mobile_back_text = get_the_title( $parent_id );
	} elseif ( $is_service ) {
		// Parent service -> link to services page
		$mobile_back_url = $services_url;
		$mobile_back_text = 'Services';
	} else {
		// Other pages -> link to home
		$mobile_back_url = home_url( '/' );
		$mobile_back_text = 'Summit Law LLP';
	}
	?>

	<div class="container mx-auto flex flex-row justify-between">
		<!-- Mobile: Condensed back link -->
		<nav aria-label="Back navigation" class="md:hidden py-4">
			<a href="<?php echo esc_url( $mobile_back_url ); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-40 hover:text-green-deep transition-colors">
				<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" class="flex-shrink-0 fill-green-accent1 rotate-180" aria-hidden="true">
					<path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"></path>
				</svg>
				<?php echo esc_html( $mobile_back_text ); ?>
			</a>
		</nav>

		<!-- Desktop: Full breadcrumb trail -->
		<nav aria-label="Breadcrumb" class="hidden md:block py-8">
			<ol class="flex items-center gap-2 text-sm font-semibold">
				<!-- Home -->
				<li>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="text-brand-40 hover:text-green-deep transition-colors">
						Summit Law LLP
					</a>
				</li>
				<li aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" class="flex-shrink-0 fill-green-accent1" aria-hidden="true">
						<path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"></path>
					</svg>
				</li>

				<?php if ( $is_service ) : ?>
					<!-- Services -->
					<li>
						<a href="<?php echo esc_url( $services_url ); ?>" class="text-brand-40 hover:text-green-deep transition-colors">
							Services
						</a>
					</li>
					<li aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" class="flex-shrink-0 fill-green-accent1" aria-hidden="true">
							<path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"></path>
						</svg>
					</li>

					<?php if ( $is_child_service ) : ?>
						<!-- Parent Service -->
						<li>
							<a href="<?php echo esc_url( get_permalink( $parent_id ) ); ?>" class="text-brand-40 hover:text-green-deep transition-colors">
								<?php echo esc_html( get_the_title( $parent_id ) ); ?>
							</a>
						</li>
						<li aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" class="flex-shrink-0 fill-green-accent1" aria-hidden="true">
								<path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"></path>
							</svg>
						</li>
					<?php endif; ?>
				<?php endif; ?>

				<!-- Current Page -->
				<li aria-current="page" class="text-green-deep">
					<?php if ( $is_preview && $is_auto_draft && ! $hero_custom_title_enabled ) : ?>
						<span class="breadcrumb-current-title text-brand-40" data-block-id="<?php echo esc_attr( $block['id'] ); ?>">Page Title</span>
					<?php else : ?>
						<span class="breadcrumb-current-title" data-block-id="<?php echo esc_attr( $block['id'] ); ?>"><?php echo esc_html( $breadcrumb_title ); ?></span>
					<?php endif; ?>
				</li>
			</ol>
		</nav>

		<?php if ( $is_preview && ! $hero_custom_title_enabled ) : ?>
		<script>
		(function() {
			const blockId = '<?php echo esc_js( $block['id'] ); ?>';
			const titleElement = document.querySelector('.breadcrumb-current-title[data-block-id="' + blockId + '"]');

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
					} else {
						titleElement.textContent = 'Page Title';
						titleElement.classList.add('text-brand-40');
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
		<div class="py-4 md:py-8 flex flex-row gap-x-4">
			<span class="text-base lowercase hidden lg:block">Share</span>
			<ul class="social-share flex flex-row items-center gap-2">
				<?php
				// Get current page URL and title for sharing
				$page_url = urlencode( get_permalink() );
				$page_title = urlencode( get_the_title() );

				// Define share links
				$share_links = array(
					'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $page_url,
					'x' => 'https://twitter.com/intent/tweet?url=' . $page_url . '&text=' . get_bloginfo( 'name' ) . ' - ' . $page_title . ' Page',
					'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . $page_url,
					'email' => 'mailto:?subject=' . get_bloginfo( 'name' ) . ' - ' . $page_title . '&body=Check out this page: ' . $page_url
				);

				foreach ( $share_links as $type => $url ) :
				?>
					<li class="social-share__item">
						<a href="<?php echo esc_url( $url ); ?>"
							class="social-share__link"
							target="_blank"
							rel="noopener noreferrer"
							aria-label="Share on <?php echo esc_attr( ucfirst($type) ); ?>">
							<?php echo summit_get_svg_icon( $type, ['class' => 'w-6 h-6 fill-green-deep'] ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</section>