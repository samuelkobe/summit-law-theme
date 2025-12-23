<?php
/**
 * Single Team Member Template
 *
 * Displays individual team member profile with details and related insights
 */

get_header(); ?>


<?php while ( have_posts() ) : the_post(); ?>


<?php // acf fields and variables
	$role = get_field( 'role' );
?>

<main id="main" class="site-main team-member-single">

	<article id="post-<?php the_ID(); ?>" <?php post_class( '' ); ?>>

		<section class="bg-gradient-to-b from-brand-black to-[#34312D]">
			<div class="grid md:grid-cols-12 md:container mx-auto !px-0">

				<div class="md:self-end md:col-start-1 md:col-span-6 lg:col-start-2 lg:col-span-5 xl:col-start-2 xl:col-span-4 px-4">
					<?php if ( has_post_thumbnail() ) : ?>
						<div class="aspect-[3/4] overflow-hidden opacity-95">
							<?php the_post_thumbnail( 'large', array( 'class' => 'w-full h-full object-cover' ) ); ?>
						</div>
					<?php endif; ?>
				</div>

				<div class="self-center bg-green-deep md:bg-transparent md:col-start-7 md:col-span-6 lg:col-start-7 lg:col-span-6 p-6">
					<h1 class="h1 text-4xl lg:text-5xl xl:text-[62.5px] text-white mb-1 xl:mb-2"><?php the_title(); // display name ?></h1>
					<h2 class="h2 text-2xl lg:text-3xl xl:text-[32px] text-green-accent1 mb-4">
						<?php if ( $role ) :
							echo esc_html( $role['label'] ); // display role label
						endif; ?>
					</h2>
					<p class="text-lg lg:text-xl text-white lg:mb-16 2xl:max-w-[75%]"><?php echo wp_kses_post( get_the_excerpt() ); ?></p>

					<?php
					// Check if legal assistant is enabled
					$has_legal_assistant = get_field( 'legal_assistant_toggle' ) == 1;
					?>

					<div class="grid grid-cols-1 <?php echo $has_legal_assistant ? 'sm:grid-cols-2 md:grid-cols-1 lg:grid-cols-2' : ''; ?> gap-6 mt-6">

						<!-- Connect Section -->
						<div>
							<h3 class="text-white font-museum text-xl xl:text-2xl mb-3">Connect</h3>
							<address class="not-italic space-y-3">
								<?php if ( get_field( 'email' ) ) : ?>
									<div class="flex items-center gap-2 text-white">
										<?php echo summit_get_svg_icon( 'email-alt', array( 'class' => 'w-5 h-5 flex-shrink-0' ) ); ?>
										<a href="mailto:<?php the_field( 'email' ); ?>" class="hover:text-green-accent1 transition-colors">
											<?php the_field( 'email' ); ?>
										</a>
									</div>
								<?php endif; ?>

								<?php if ( get_field( 'phone' ) ) : ?>
									<div class="flex items-center gap-2 text-white">
										<?php echo summit_get_svg_icon( 'phone-alt', array( 'class' => 'w-5 h-5 flex-shrink-0' ) ); ?>
										<a href="tel:<?php echo esc_attr( str_replace( array( ' ', '-', '(', ')' ), '', get_field( 'phone' ) ) ); ?>" class="hover:text-green-accent1 transition-colors">
											<?php the_field( 'phone' ); ?><?php if ( get_field( 'extension' ) ) : ?> ext. <?php the_field( 'extension' ); ?><?php endif; ?>
										</a>
									</div>
								<?php endif; ?>

								<?php if ( get_field( 'fax' ) ) : ?>
									<div class="flex items-center gap-2 text-white">
										<?php echo summit_get_svg_icon( 'fax-alt', array( 'class' => 'w-5 h-5 flex-shrink-0' ) ); ?>
										<span class="">
											<?php the_field( 'fax' ); ?>
										</span>
									</div>
								<?php endif; ?>

								<!-- vCard Download -->
								<div class="flex items-center gap-2 text-white">
									<?php echo summit_get_svg_icon( 'vcard-alt', array( 'class' => 'w-5 h-5 flex-shrink-0' ) ); ?>
									<a href="<?php echo esc_url( add_query_arg( 'summit_vcard', get_the_ID(), home_url( '/' ) ) ); ?>"
									   download="<?php echo sanitize_title( get_the_title() ); ?>.vcf"
									   class="hover:text-green-accent1 transition-colors">
										Download Contact
									</a>
								</div>
							</address>
						</div>

						<!-- Legal Assistant Section -->
						<?php if ( $has_legal_assistant && have_rows( 'assistant_information' ) ) : ?>
							<div>
								<h3 class="text-green-accent1 font-museum text-xl xl:text-2xl mb-3">Legal Assistant</h3>
								<?php while ( have_rows( 'assistant_information' ) ) : the_row(); ?>
									<?php $legal_assistant = get_sub_field( 'legal_assistant' ); ?>
									<?php if ( $legal_assistant ) : ?>
										<?php foreach ( $legal_assistant as $post ) : ?>
											<?php setup_postdata( $post ); ?>
											<div class="space-y-2">
												<a href="<?php the_permalink(); ?>" class="block text-white font-medium no-underline hover:text-green-accent1 transition-colors">
													<?php the_title(); ?>
												</a>

												<address class="not-italic space-y-3">
													<?php if ( get_field( 'email' ) ) : ?>
														<div class="flex items-center gap-2 text-white">
															<?php echo summit_get_svg_icon( 'email-alt', array( 'class' => 'w-5 h-5 flex-shrink-0' ) ); ?>
															<a href="mailto:<?php the_field( 'email' ); ?>" class="hover:text-green-accent1 transition-colors">
																<?php the_field( 'email' ); ?>
															</a>
														</div>
													<?php endif; ?>

													<?php if ( get_field( 'phone' ) ) : ?>
														<div class="flex items-center gap-2 text-white">
															<?php echo summit_get_svg_icon( 'phone-alt', array( 'class' => 'w-5 h-5 flex-shrink-0' ) ); ?>
															<a href="tel:<?php echo esc_attr( str_replace( array( ' ', '-', '(', ')' ), '', get_field( 'phone' ) ) ); ?>" class="hover:text-green-accent1 transition-colors">
																<?php the_field( 'phone' ); ?><?php if ( get_field( 'extension' ) ) : ?> ext. <?php the_field( 'extension' ); ?><?php endif; ?>
															</a>
														</div>
													<?php endif; ?>
												</address>
											</div>
										<?php endforeach; ?>
										<?php wp_reset_postdata(); ?>
									<?php endif; ?>
								<?php endwhile; ?>
							</div>
						<?php endif; ?>

					</div>

				</div>
			</div>
		</section>

		<section class="bg-white ">
																	
			<div class="container mx-auto flex flex-col md:flex-row md:justify-between">
				<nav aria-label="Breadcrumb" class="hidden md:block py-8">
					<ol class="flex items-center gap-2 text-sm font-semibold">
						<li>
							<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="text-brand-40 hover:text-green-deep transition-colors">
								Summit Law
							</a>
						</li>
						<li aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" class="flex-shrink-0 fill-brand-40">
								<path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"></path>
							</svg>
						</li>
						<li>
							<a href="<?php echo esc_url( get_post_type_archive_link( 'team' ) ); ?>" class="text-brand-40 hover:text-green-deep transition-colors">
								Team
							</a>
						</li>
						<li aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" class="flex-shrink-0 fill-green-accent1">
								<path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"></path>
							</svg>
						</li>
						<li aria-current="page" class="text-green-deep">
							<?php the_title(); ?>
						</li>
					</ol>
				</nav>
				<div class="py-8 flex flex-row gap-x-4">
					<span class="text-base">connect</span>
					<?php if ( have_rows('social_links') ) : ?>
							<ul class="team-social flex flex-row items-center gap-2">
									<?php while ( have_rows('social_links') ) : the_row(); 
											$type   = get_sub_field('social_type');
											$handle = trim( (string) get_sub_field('social_handle') );
	
											if ( ! $type || ! $handle ) {
													continue;
											}
	
											// Build base URL by type
											switch ( $type ) {
													case 'linkedin':
													default:
															// Allow full path or just handle
															$handle = ltrim($handle, '/');
															$url    = 'https://www.linkedin.com/' . $handle;
															break;
	
													case 'facebook':
															$handle = ltrim($handle, '/@');
															$url    = 'https://www.facebook.com/' . $handle;
															break;
	
													case 'x': // Twitter
															$handle = ltrim($handle, '@');
															$url    = 'https://twitter.com/' . $handle;
															break;
	
													case 'instagram':
															$handle = ltrim($handle, '/@');
															$url    = 'https://www.instagram.com/' . $handle;
															break;
	
													case 'youtube':
															// Allow full path or @handle
															$handle = ltrim($handle, '/');
															$url    = 'https://www.youtube.com/' . $handle;
															break;
	
													case 'email':
															$url = 'mailto:' . $handle;
															break;
	
													case 'phone':
															// Format: Strip spaces, dashes, parentheses for tel: link
															$clean_phone = preg_replace('/[\s\-\(\)]/', '', $handle);
															$url = 'tel:' . $clean_phone;
															break;
											}
	
											// Icon path
											$icon_path = get_template_directory_uri() . '/assets/icons/social-' . $type . '.svg';
									?>
											<li class="team-social__item">
													<a href="<?php echo esc_url( $url ); ?>" 
														class="team-social__link" 
														target="_blank" 
														rel="noopener noreferrer"
														aria-label="<?php echo esc_attr( ucfirst($type) ); ?>">
														<?php echo summit_get_svg_icon( $type, ['class' => 'w-6 h-6 fill-green-deep'] ); ?>
													</a>
											</li>
									<?php endwhile; ?>
							</ul>
					<?php endif; ?>
				</div>
			</div>
			
		</section>

				<!-- Bio / Main Content -->
				<?php if ( get_the_content() ) : ?>
					<div class="entry-content prose prose-lg max-w-none">
						<?php the_content(); ?>
					</div>
				<?php endif; ?>

				<!-- Practice Areas -->
				<?php
				$practice_areas = get_field( 'practice_areas' );
				if ( $practice_areas ) : ?>
					<div class="practice-areas mt-8">
						<h2 class="text-2xl font-bold text-green-deep mb-4">Practice Areas</h2>
						<ul class="grid grid-cols-1 md:grid-cols-2 gap-3">
							<?php foreach ( $practice_areas as $area ) : ?>
								<li>
									<a href="<?php echo esc_url( get_permalink( $area->ID ) ); ?>" class="flex items-center gap-2 text-green-deep hover:underline">
										<svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
											<path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
										</svg>
										<?php echo esc_html( get_the_title( $area->ID ) ); ?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

			</div>

		</div>

		<!-- Related Insights Section (Part 4 - will be populated when relationship field is added) -->
		<?php
		// Query insights related to this team member
		$related_insights_args = array(
			'post_type'      => 'post',
			'posts_per_page' => 4,
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'     => 'related_team_members',
					'value'   => '"' . get_the_ID() . '"',
					'compare' => 'LIKE'
				)
			),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$related_insights = new WP_Query( $related_insights_args );

		if ( $related_insights->have_posts() ) : ?>

			<section class="team-member-insights bg-gray-50 -mx-4 px-4 py-12 md:mx-0 md:px-12 md:rounded-lg">
				<h2 class="text-3xl font-bold text-green-deep mb-8">
					Insights by <?php the_title(); ?>
				</h2>

				<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
					<?php while ( $related_insights->have_posts() ) : $related_insights->the_post(); ?>

						<article class="insight-card bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">

							<?php if ( has_post_thumbnail() ) : ?>
								<a href="<?php the_permalink(); ?>" class="block aspect-[16/9] overflow-hidden">
									<?php the_post_thumbnail( 'medium', array( 'class' => 'w-full h-full object-cover hover:scale-105 transition-transform duration-300' ) ); ?>
								</a>
							<?php endif; ?>

							<div class="p-4">
								<h3 class="text-lg font-bold text-green-deep mb-2">
									<a href="<?php the_permalink(); ?>" class="hover:underline">
										<?php the_title(); ?>
									</a>
								</h3>

								<div class="text-sm text-gray-500 mb-3">
									<?php echo get_the_date(); ?>
								</div>

								<?php if ( get_the_excerpt() ) : ?>
									<div class="text-gray-700 text-sm mb-3 line-clamp-3">
										<?php echo wp_trim_words( get_the_excerpt(), 15 ); ?>
									</div>
								<?php endif; ?>

								<a href="<?php the_permalink(); ?>" class="text-green-deep font-medium text-sm hover:underline">
									Read More →
								</a>
							</div>

						</article>

					<?php endwhile; ?>
				</div>

				<div class="text-center mt-8">
					<a href="<?php echo get_post_type_archive_link( 'post' ); ?>" class="inline-block bg-green-deep text-white px-6 py-3 rounded-lg font-medium hover:bg-green-800 transition-colors">
						View All Insights
					</a>
				</div>
			</section>

			<?php wp_reset_postdata(); ?>

		<?php endif; ?>

	</article>

</main>

<?php
endwhile;

get_footer();
