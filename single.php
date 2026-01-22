<?php
/**
 * Single Post (Insight) Template
 *
 * Displays individual insight posts with:
 * - Featured image banner with dark overlay
 * - Post title, date, author, and area taxonomy
 * - Block editor content
 */

get_header();
?>

<?php while ( have_posts() ) : the_post(); ?>

<?php
// Get the area taxonomy terms
$areas = get_the_terms( get_the_ID(), 'area' );
?>

<!-- Featured Image Banner with Overlay -->
<header class="insight-banner relative bg-green-deep min-h-[300px] lg:min-h-[400px] flex items-end">
	<?php if ( has_post_thumbnail() ) : ?>
		<div class="absolute inset-0">
			<?php the_post_thumbnail( 'full', array( 'class' => 'w-full h-full object-cover' ) ); ?>
			<div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/40 to-black/20"></div>
		</div>
	<?php else : ?>
		<div class="absolute inset-0 bg-green-deep"></div>
	<?php endif; ?>

	<div class="container mx-auto p-6 relative z-10 pb-12 lg:pb-16">
		<h1 class="h1 text-white mb-4"><?php the_title(); ?></h1>

		<!-- Meta: Date, Author, Area -->
		<div class="flex flex-wrap items-center gap-4 text-white/80 text-sm">
			<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
				<?php echo esc_html( get_the_date( 'F j, Y' ) ); ?>
			</time>
			<span class="text-white/50">|</span>
			<span>Posted by <?php the_author(); ?></span>
			<?php if ( $areas && ! is_wp_error( $areas ) ) : ?>
				<span class="text-white/50">|</span>
				<a href="<?php echo esc_url( get_term_link( $areas[0] ) ); ?>" class="text-green-accent1 hover:underline transition-colors">
					<?php echo esc_html( $areas[0]->name ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</header>

<!-- Breadcrumbs -->
<section class="bg-white">
	<div class="container mx-auto flex flex-row justify-between">
		<!-- Mobile: Condensed back link -->
		<nav aria-label="Back navigation" class="md:hidden py-4">
			<a href="<?php echo esc_url( home_url( '/insights/' ) ); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-40 hover:text-green-deep transition-colors">
				<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" class="flex-shrink-0 fill-green-accent1 rotate-180" aria-hidden="true">
					<path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"></path>
				</svg>
				<?php esc_html_e( 'Insights', 'summit-law-theme' ); ?>
			</a>
		</nav>

		<!-- Desktop: Full breadcrumb trail -->
		<nav aria-label="Breadcrumb" class="hidden md:block py-8">
			<ol class="flex items-center gap-2 text-sm font-semibold">
				<!-- Home -->
				<li>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="text-brand-40 hover:text-green-deep transition-colors">
						<?php esc_html_e( 'Summit Law LLP', 'summit-law-theme' ); ?>
					</a>
				</li>
				<li aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" class="flex-shrink-0 fill-green-accent1" aria-hidden="true">
						<path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"></path>
					</svg>
				</li>
				<!-- Insights -->
				<li>
					<a href="<?php echo esc_url( home_url( '/insights/' ) ); ?>" class="text-brand-40 hover:text-green-deep transition-colors">
						<?php esc_html_e( 'Insights', 'summit-law-theme' ); ?>
					</a>
				</li>
				<li aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" class="flex-shrink-0 fill-green-accent1" aria-hidden="true">
						<path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"></path>
					</svg>
				</li>
				<!-- Current Post -->
				<li aria-current="page" class="text-green-deep">
					<?php the_title(); ?>
				</li>
			</ol>
		</nav>

		<!-- Share Links -->
		<div class="py-4 md:py-8 flex flex-row gap-x-4">
			<span class="text-base lowercase hidden lg:block"><?php esc_html_e( 'Share', 'summit-law-theme' ); ?></span>
			<ul class="social-share flex flex-row items-center gap-2">
				<?php
				// Get current page URL and title for sharing
				$page_url   = urlencode( get_permalink() );
				$page_title = urlencode( get_the_title() );

				// Define share links
				$share_links = array(
					'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $page_url,
					'x'        => 'https://twitter.com/intent/tweet?url=' . $page_url . '&text=' . get_bloginfo( 'name' ) . ' - ' . $page_title,
					'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . $page_url,
					'email'    => 'mailto:?subject=' . get_bloginfo( 'name' ) . ' - ' . $page_title . '&body=Check out this page: ' . $page_url,
				);

				foreach ( $share_links as $type => $url ) :
				?>
					<li class="social-share__item">
						<a href="<?php echo esc_url( $url ); ?>"
							class="social-share__link"
							target="_blank"
							rel="noopener noreferrer"
							aria-label="<?php echo esc_attr( sprintf( __( 'Share on %s', 'summit-law-theme' ), ucfirst( $type ) ) ); ?>">
							<?php echo summit_get_svg_icon( $type, array( 'class' => 'w-6 h-6 fill-green-deep' ) ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</section>

<!-- Content Area -->
<main id="main" class="site-main bg-white">
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'container mx-auto py-12 lg:py-16' ); ?>>

		<div class="entry-content max-w-3xl mx-auto px-6 lg:px-0">
			<?php the_content(); ?>
		</div>

		<!-- Post Footer -->
		<footer class="max-w-3xl mx-auto mt-12 pt-8 border-t border-brand-30 px-6 lg:px-0">
			<div class="flex flex-wrap items-center justify-between gap-4">
				<?php if ( $areas && ! is_wp_error( $areas ) ) : ?>
					<div class="flex items-center gap-2 text-sm">
						<span class="text-brand-40">Area:</span>
						<a href="<?php echo esc_url( get_term_link( $areas[0] ) ); ?>" class="text-green-deep hover:underline transition-colors">
							<?php echo esc_html( $areas[0]->name ); ?>
						</a>
					</div>
				<?php endif; ?>

				<!-- Back to Insights link -->
				<a href="<?php echo esc_url( home_url( '/insights/' ) ); ?>" class="text-green-deep hover:underline text-sm flex items-center gap-2 transition-colors">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="rotate-180">
						<path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"/>
					</svg>
					Back to Insights
				</a>
			</div>
		</footer>

	</article>
</main>

<?php endwhile; ?>

<?php get_footer(); ?>
