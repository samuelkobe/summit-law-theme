<?php
/**
 * Archive template for Team Members
 *
 * Displays all team members in a grid layout
 * URL: /team/
 */

get_header();
?>

<main id="main" class="site-main team-archive">

	<header class="page-header bg-green-deep lg:h-[40dvh] min-h[240px] lg:min-h-[400px] 2xl:min-h-[480px] flex items-center py-24 max-md:pt-12">
		<section class="banner-after-content h-full flex flex-col justify-center container mx-auto p-6 relative">
			<h1 class="h1 2xl:text-7xl text-green-accent1 mb-4 lg:mb-10">
				<?php post_type_archive_title(); ?>
			</h1>

			<?php // this needs to be replaced with dynamic content from ACF/WP admin ?>
			<p class="text-white text-lg lg:text-xl w-full md:w-3/4 2xl:w-2/3">
				<?php
				// Optional: Add ACF fields or custom description
				$post_type = get_post_type_object( 'team' );
				if ( $post_type && ! empty( $post_type->description ) ) : ?>
					<?php echo esc_html( $post_type->description ); ?>
				<?php else : ?>
					Trial-ready lawyers focused on insurance defence, commercial disputes, and practical resolution—including mediation.
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
						Team
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
						'x' => 'https://twitter.com/intent/tweet?url=' . $page_url . '&text=Meet the legal team at ' . get_bloginfo( 'name' ),
						'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . $page_url,
						'email' => 'mailto:?subject='. get_bloginfo( 'name' ) . ' - ' . $page_title . '&body=Meet the legal team at: ' . $page_url
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

	<section class="bg-white w-full min-h-[800px] py-24 max-md:pt-2">
		<div class="team-grid container mx-auto px-4 pb-16">







<?php
		// Custom query to order by ACF Role field
		$role_order = array( 'partner', 'associate', 'clerk', 'assistant' );

		// Get all team members
		$team_args = array(
			'post_type' => 'team',
			'posts_per_page' => -1,
			'orderby' => 'title',
			'order' => 'ASC'
		);

		$team_query = new WP_Query( $team_args );

		// Group posts by role
		$posts_by_role = array();
		if ( $team_query->have_posts() ) {
			while ( $team_query->have_posts() ) {
				$team_query->the_post();
				$role = get_field( 'role' );
				$role_value = $role ? $role['value'] : 'other';

				if ( ! isset( $posts_by_role[ $role_value ] ) ) {
					$posts_by_role[ $role_value ] = array();
				}
				$posts_by_role[ $role_value ][] = get_post();
			}
			wp_reset_postdata();
		}

		// Reorder posts based on role hierarchy
		$ordered_posts = array();
		foreach ( $role_order as $role ) {
			if ( isset( $posts_by_role[ $role ] ) ) {
				$ordered_posts = array_merge( $ordered_posts, $posts_by_role[ $role ] );
			}
		}

		// Add any remaining posts not in the defined roles
		foreach ( $posts_by_role as $role => $posts ) {
			if ( ! in_array( $role, $role_order ) ) {
				$ordered_posts = array_merge( $ordered_posts, $posts );
			}
		}
		?>

		<?php if ( ! empty( $ordered_posts ) ) : ?>

			<div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-12 gap-x-8 gap-y-12 lg:gap-y-16 2xl:gap-y-24">
				<?php
				$index = 0;
				foreach ( $ordered_posts as $post ) :
					setup_postdata( $post );
					$is_left = ( $index % 2 === 0 ); // Even index = left card
					$xl_col_class = $is_left ? 'xl:col-start-2 xl:col-span-5' : 'xl:col-span-5';
					$index++;
				?>

				<article class="team-member-card <?php echo esc_attr( $xl_col_class ); ?>">

					<a href="<?php the_permalink(); ?>" class="group block">
						<?php if ( has_post_thumbnail() ) : ?>
							<div class="bg-brand-black rounded-lg w-full overflow-hidden mb-2 lg:mb-4 group-hover:shadow-lg transition-shadow duration-1000">
								<?php the_post_thumbnail( 'large', array( 'class' => 'aspect-image sm:aspect-video object-contain' ) ); ?>
							<?php else : ?>
								<div class="bg-green-deep rounded-lg w-full overflow-hidden mb-2 lg:mb-4 group-hover:shadow-lg transition-shadow duration-1000">
								<div class="aspect-image sm:aspect-video object-contain flex items-center justify-center p-20">
									<?php
									$custom_logo_id = get_theme_mod( 'custom_logo' );
									if ( $custom_logo_id ) {
										echo wp_get_attachment_image( $custom_logo_id, 'full', false, array( 'class' => 'max-w-full max-h-full object-contain' ) );
									}
									?>
								</div>
							<?php endif; ?>
						</div>
						<div>
							<div>
								<h2 class="h2-line-after h2 font-museum capitalize text-green-deep underline decoration-2 decoration-transparent group-hover:decoration-green-deep underline-offset-4 transition-colors duration-300"><?php the_title(); // display name ?></h2>
							</div>
							
							<p class="text-lg lg:text-xl text-brand-40">
								<?php $role = get_field( 'role' );?>
								<?php if ( $role ) :
									echo esc_html( $role['label'] ); // display role label
								endif; ?>
							</p>
							<p class="text-brand-black mt-2 lg:mt-4 text-base 2xl:text-lg lg:min-h-[4.5rem] 2xl:min-h-[6rem]"><?php echo wp_kses_post( get_field( 'short_description' ) ); ?></p>
						</div>
					</a>
					<address class="not-italic flex flex-row gap-x-6 mt-2 lg:mt-4">
						<?php if ( get_field( 'email' ) ) : ?>
							<div class="flex items-center gap-2 text-green-deep">
								<?php echo summit_get_svg_icon( 'email-alt', array( 'class' => 'w-5 h-5 flex-shrink-0  stroke-green-deep' ) ); ?>
								<a href="mailto:<?php the_field( 'email' ); ?>" class="underline decoration-2 decoration-transparent hover:decoration-green-deep underline-offset-2 transition-colors duration-300">
									<?php the_field( 'email' ); ?>
								</a>
							</div>
						<?php endif; ?>

						<?php if ( get_field( 'phone' ) ) : ?>
							<div class="flex items-center gap-2 text-green-deep">
								<?php echo summit_get_svg_icon( 'phone-alt', array( 'class' => 'w-5 h-5 flex-shrink-0 stroke-green-deep' ) ); ?>
								<a href="tel:<?php echo esc_attr( str_replace( array( ' ', '-', '(', ')' ), '', get_field( 'phone' ) ) ); ?>" class="underline decoration-2 decoration-transparent hover:decoration-green-deep underline-offset-2 transition-colors duration-300">
									<?php the_field( 'phone' ); ?><?php if ( get_field( 'extension' ) ) : ?> ext. <?php the_field( 'extension' ); ?><?php endif; ?>
								</a>
							</div>
						<?php endif; ?>
					</address>

					<!-- <div>
						Conditional: Book a meeting link (future functionality)
					</div> -->
					
					<div class="mt-2 lg:mt-4">
						<a href="<?php the_permalink(); ?>" class="text-brand-40 hover:text-brand-black text-sm lg:text-base font-bold underline decoration-2 decoration-transparent hover:decoration-brand-black transition-colors duration-300">View full profile</a>
					</div>
				</article>
				<?php endforeach; wp_reset_postdata(); ?>
				
			</div>

						<?php
			// Pagination
			the_posts_pagination( array(
				'mid_size'  => 2,
				'prev_text' => __( '← Previous', 'summit-law-theme' ),
				'next_text' => __( 'Next →', 'summit-law-theme' ),
				'class'     => 'mt-12',
			) );
			?>

		<?php else : ?>

			<p class="text-center text-gray-600"><?php esc_html_e( 'No team members found.', 'summit-law-theme' ); ?></p>

		<?php endif; ?>

		</div>
	</section>

</main>

<?php
get_footer();
