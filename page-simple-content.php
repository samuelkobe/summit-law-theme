<?php
/**
 * Template Name: Sitemap Page
 * Template Post Type: page
 *
 * A minimal page template for legal/policy pages like:
 * - Sitemap
 *
 * Features:
 * - Page title as h1
 * - Breadcrumbs (Home > Page Title)
 * - Narrow content width (max-w-3xl)
 * - No featured image/hero
 */

get_header();
?>

<?php while ( have_posts() ) : the_post(); ?>

<!-- Breadcrumbs -->
<section class="bg-white border-b border-brand-20">
	<div class="container mx-auto flex flex-row justify-between">
		<!-- Mobile: Condensed back link -->
		<nav aria-label="Back navigation" class="md:hidden py-4">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-40 hover:text-green-deep transition-colors">
				<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" class="flex-shrink-0 fill-green-accent1 rotate-180" aria-hidden="true">
					<path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"></path>
				</svg>
				<?php esc_html_e( 'Home', 'summit-law-theme' ); ?>
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
				<!-- Current Page -->
				<li aria-current="page" class="text-green-deep">
					<?php the_title(); ?>
				</li>
			</ol>
		</nav>
	</div>
</section>

<!-- Content Area -->
<main id="main" class="site-main bg-white">
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'container mx-auto py-12 lg:py-16' ); ?>>

		<!-- Page Title -->
		<header class="max-w-7xl mx-auto mb-8 lg:mb-12">
			<h1 class="h1 text-green-deep"><?php the_title(); ?></h1>
		</header>

		<!-- Page Content -->
		<div class="entry-content max-w-7xl mx-auto">
			<?php the_content(); ?>
		</div>

	</article>
</main>

<?php endwhile; ?>

<?php get_footer(); ?>
