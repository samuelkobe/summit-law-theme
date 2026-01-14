<?php
/**
 * Archive template for Services
 *
 * Displays all top-level service types with their practice areas
 * URL: /services/
 */

get_header();
?>

<main id="main" class="site-main services-archive">

	<header class="page-header bg-green-deep lg:h-[40dvh] min-h[240px] lg:min-h-[400px] 2xl:min-h-[480px] flex items-center py-24 max-md:pt-12">
		<section class="banner-after-content h-full flex flex-col justify-center container mx-auto p-6 relative">
			<h1 class="h1 2xl:text-7xl text-green-accent1 mb-4 lg:mb-10">
				<?php post_type_archive_title(); ?>
			</h1>

			<?php // this need to be replaced with dynamic content from ACF/WP admin ?>
			<p class="text-white text-lg lg:text-xl w-full md:w-3/4 2xl:w-2/3">
				<?php
				// Optional: Add ACF fields or custom description
				$post_type = get_post_type_object( 'service' );
				if ( $post_type && ! empty( $post_type->description ) ) : ?>
					<?php echo 'ARCHIVE: ' . esc_html( $post_type->description ); ?>
				<?php else : ?>
					ARCHIVE: Focused advocacy in insurance defence, commercial litigation, and mediation across multiple provinces—delivering clarity, strategy, and results.
				<?php endif; ?>
			</p>
		</section>
	</header>

	<section class="bg-white">			
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
					<li aria-current="page" class="text-green-deep">
						Services
					</li>
				</ol>
			</nav>
			<div class="py-8 flex flex-row gap-x-4">
				<span class="text-base lowercase">Share</span>
				<ul class="team-social flex flex-row items-center gap-2">
					<?php
					// Get current archive page URL and title for sharing
					$page_url = urlencode( get_post_type_archive_link( 'team' ) );
					$page_title = urlencode( post_type_archive_title( '', false ) );

					// this needs to be replaced with dynamic content from ACF/WP admin
					// Define share links
					$share_links = array(
						'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $page_url,
						'x' => 'https://twitter.com/intent/tweet?url=' . $page_url . '&text=See the services offered by ' . get_bloginfo( 'name' ),
						'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . $page_url,
						'email' => 'mailto:?subject='. get_bloginfo( 'name' ) . ' - ' . $page_title . '&body=See the services offered at: ' . $page_url
					);

					foreach ( $share_links as $type => $url ) :
					?>
						<li class="team-social__item">
							<a href="<?php echo esc_url( $url ); ?>"
								class="team-social__link"
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

	<div class="services-overview">
		<?php
		// Get only parent services (the 3 main types)
		$service_types = get_posts( array(
			'post_type'      => 'service',
			'posts_per_page' => -1,
			'post_parent'    => 0, // Only top-level items
			'orderby'        => 'menu_order',
			'order'          => 'ASC'
		) );

		if ( $service_types ) :
			foreach ( $service_types as $service_type ) : ?>

				<section class="service-type">
					<h2>
						<a href="<?php echo esc_url( get_permalink( $service_type->ID ) ); ?>">
							<?php echo esc_html( get_the_title( $service_type->ID ) ); ?>
						</a>
					</h2>

					<?php if ( get_the_excerpt( $service_type->ID ) ) : ?>
						<div class="service-type__excerpt">
							<?php echo wp_kses_post( get_the_excerpt( $service_type->ID ) ); ?>
						</div>
					<?php endif; ?>

					<?php
					// Get child practice areas for this service type
					$practice_areas = get_posts( array(
						'post_type'      => 'service',
						'posts_per_page' => -1,
						'post_parent'    => $service_type->ID,
						'orderby'        => 'menu_order',
						'order'          => 'ASC'
					) );

					if ( $practice_areas ) : ?>
						<ul class="practice-areas">
							<?php foreach ( $practice_areas as $area ) : ?>
								<li>
									<a href="<?php echo esc_url( get_permalink( $area->ID ) ); ?>">
										<?php echo esc_html( get_the_title( $area->ID ) ); ?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</section>

			<?php endforeach;
		else : ?>
			<p><?php esc_html_e( 'No services found.', 'summit-law' ); ?></p>
		<?php endif; ?>
	</div>

</main>

<?php
get_footer();
