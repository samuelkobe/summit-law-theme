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
?>

<style type="text/css">
	<?php echo '#' . $id; ?> {
		/* Add styles that use ACF values here */
	}
</style>

<section id="<?php echo esc_attr( $id ); ?>" class="bg-white <?php echo esc_attr( $classes ); ?>">			
	<div class="container mx-auto flex flex-col md:flex-row md:justify-between">
		<nav aria-label="Breadcrumb" class="hidden md:block py-8">
			<ol class="flex items-center gap-2 text-sm font-semibold">
				<li>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="text-brand-40 hover:text-green-deep transition-colors">
						Summit Law
					</a>
				</li>
				<li aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" class="flex-shrink-0 fill-green-accent1">
						<path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"></path>
					</svg>
				</li>
				<?php
				// Check if this is a child service (practice area) with a parent
				$parent_id = wp_get_post_parent_id( get_the_ID() );
				if ( $parent_id && get_post_type() === 'service' ) : ?>
					<li>
						<a href="<?php echo esc_url( get_permalink( $parent_id ) ); ?>" class="text-brand-40 hover:text-green-deep transition-colors">
							<?php echo esc_html( get_the_title( $parent_id ) ); ?>
						</a>
					</li>
					<li aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" class="flex-shrink-0 fill-green-accent1">
							<path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"></path>
						</svg>
					</li>
				<?php endif; ?>
				<li aria-current="page" class="text-green-deep">
					<?php echo esc_html( get_the_title() ); ?>
				</li>
			</ol>
		</nav>
		<div class="py-8 flex flex-row gap-x-4">
			<span class="text-base lowercase">Share</span>
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