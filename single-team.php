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

				<div class="overflow-hidden md:self-end md:col-start-1 md:col-span-6 lg:col-start-2 lg:col-span-5 2xl:col-start-2 2xl:col-span-4 px-4">
					<?php if ( has_post_thumbnail() ) : ?>
						<div class="aspect-[3/4] overflow-hidden opacity-100">
							<?php
								the_post_thumbnail( 'large', array(
									'class' => 'w-full h-full object-cover',
									'sizes' => '(min-width: 1024px) 33vw, (min-width: 768px) 50vw, 100vw'
								) );
							?>
						</div>
						<?php else : ?>
						<div class="hidden md:aspect-[3/4] md:flex md:items-center md:justify-center">
							<!-- Wordpress'site identity Logo -->
							<?php if ( function_exists( 'the_custom_logo' ) ) {
								the_custom_logo();
							} ?>
						</div>
					<?php endif; ?>
				</div>

				<?php $has_booking = get_field( 'bookable_services_toggle' ) == 1 && ! empty( get_field( 'service' ) ); ?>
				<div class="self-center bg-green-deep md:bg-transparent md:col-start-7 md:col-span-6 lg:col-start-7 lg:col-span-6 p-6 <?php echo $has_booking ? 'md:py-24' : ''; ?>">
					<h1 class="h1 text-4xl lg:text-5xl xl:text-[62.5px] text-white mb-1 xl:mb-2"><?php the_title(); // display name ?></h1>
					<h2 class="h2 text-2xl lg:text-3xl xl:text-[32px] text-green-accent1 mb-4">
						<?php if ( $role ) :
							echo esc_html( $role['label'] ); // display role label
						endif; ?>
					</h2>
					<p class="text-lg max-md:block max-xl:hidden lg:text-xl text-white lg:mb-12 2xl:max-w-[75%]"><?php echo wp_kses_post( get_field( 'short_description' ) ); ?></p>

					<?php
					// Check if legal assistant is enabled
					$has_legal_assistant = get_field( 'legal_assistant_toggle' ) == 1;
					?>

					<?php if ( get_field( 'bookable_services_toggle' ) == 1 && have_rows( 'service' ) ) :
						// Check for first service row label to use in CTA
						$has_bookable_service = false;
						$service_label = '';
						while ( have_rows( 'service' ) ) : the_row();
							if ( get_row_layout() == 'mediation_service' ) :
								$has_bookable_service = true;
								$service_label = 'Mediation session';
								break;
							endif;
						endwhile;
					?>
						<?php if ( $has_bookable_service ) : ?>
							<div class="my-12">
								<h3 class="text-white font-museum text-xl xl:text-2xl mb-3">Online Services</h3>
								<a href="#book-service" class="btn alt block text-sm lg:text-base cursor-pointer">Book a <?php echo esc_html( $service_label ); ?> with <?php echo esc_html( explode( ' ', get_the_title() )[0] ); ?></a>
							</div>
						<?php endif; ?>
					<?php endif; ?>

					<style>
						/* Hero accordion: collapsed by default below lg */
						.hero-accordion-content {
							max-height: 0;
							overflow: hidden;
							transition: max-height 0.3s ease, margin 0.3s ease;
							margin-top: 0;
						}
						.hero-accordion-content.active {
							margin-top: 0.75rem;
						}
						.hero-accordion-chevron {
							transition: transform 0.3s ease;
						}
						/* md and below, and lg+ : always show content, hide chevron */
						@media (max-width: 767px), (min-width: 1024px) {
							.hero-accordion-content {
								max-height: none !important;
								overflow: visible;
								margin-top: 0.75rem !important;
							}
							.hero-accordion-chevron {
								display: none;
							}
							.hero-accordion-toggle {
								pointer-events: none;
								cursor: default;
							}
						}
					</style>

					<div class="hero-accordions grid grid-cols-1 <?php echo $has_legal_assistant ? 'sm:grid-cols-2 md:grid-cols-1 lg:grid-cols-2' : ''; ?> gap-6 mt-6">

						<!-- Connect Section -->
						<div>
							<h3 class="text-white font-museum text-xl xl:text-2xl">
								<button class="hero-accordion-toggle flex items-center gap-2 w-full text-left cursor-pointer" aria-expanded="true">
									Connect
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" class="hero-accordion-chevron flex-shrink-0 fill-green-accent1" style="transform: rotate(-90deg);" aria-hidden="true"><path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"/></svg>
								</button>
							</h3>
							<address class="hero-accordion-content active not-italic space-y-3">
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
										<span>
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
								<h3 class="text-green-accent1 font-museum text-xl xl:text-2xl">
									<button class="hero-accordion-toggle flex items-center gap-2 w-full text-left cursor-pointer" aria-expanded="false">
										Legal Assistant
										<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" class="hero-accordion-chevron flex-shrink-0 fill-green-accent1" style="transform: rotate(90deg);" aria-hidden="true"><path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"/></svg>
									</button>
								</h3>
								<div class="hero-accordion-content">
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
							</div>
						<?php endif; ?>

					</div>

					<script>
					(function() {
						// Open Connect panel on load (between md and lg)
						var openPanels = document.querySelectorAll('.hero-accordion-content.active');
						for (var j = 0; j < openPanels.length; j++) {
							openPanels[j].style.maxHeight = openPanels[j].scrollHeight + 'px';
						}

						var buttons = document.querySelectorAll('.hero-accordion-toggle');

						for (var i = 0; i < buttons.length; i++) {
							buttons[i].addEventListener('click', function() {
								if (window.innerWidth < 768 || window.innerWidth >= 1024) return;

								var panel = this.closest('h3').parentElement.querySelector('.hero-accordion-content');
								var chevron = this.querySelector('.hero-accordion-chevron');
								var isExpanded = this.getAttribute('aria-expanded') === 'true';

								if (isExpanded) {
									panel.style.maxHeight = null;
									panel.classList.remove('active');
									chevron.style.transform = 'rotate(90deg)';
									this.setAttribute('aria-expanded', 'false');
								} else {
									panel.style.maxHeight = panel.scrollHeight + 'px';
									panel.classList.add('active');
									chevron.style.transform = 'rotate(-90deg)';
									this.setAttribute('aria-expanded', 'true');
								}
							});
						}
					})();
					</script>

				</div>
			</div>
		</section>

		<?php
		// Get Team page URL (similar to how Services works in breadcrumbs block)
		$team_page = get_page_by_path( 'team' );
		$team_url = $team_page ? get_permalink( $team_page->ID ) : home_url( '/team/' );
		?>
		<section class="bg-white">

			<div class="container mx-auto flex flex-col md:flex-row md:justify-between">
				<nav aria-label="Breadcrumb" class="hidden md:block py-8">
					<ol class="flex items-center gap-2 text-sm font-semibold">
						<li>
							<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="text-brand-40 hover:text-green-deep transition-colors">
								Summit Law LLP
							</a>
						</li>
						<li aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" class="flex-shrink-0 fill-brand-40">
								<path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"></path>
							</svg>
						</li>
						<li>
							<a href="<?php echo esc_url( $team_url ); ?>" class="text-brand-40 hover:text-green-deep transition-colors">
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
				<?php if ( have_rows('social_links') ) : ?>
					<div class="py-8 flex flex-row gap-x-4">
						<span class="text-base lowercase">Connect</span>
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
						</div>
					<?php endif; ?>
			</div>
			
		</section>

		<section class="bg-white min-h-[30dvh] pb-12 lg:pb-16">
			<div class="md:container grid grid-cols-1 lg:grid-cols-12 gap-8 p-6 lg:px-6 lg:py-8">
				<div class="lg:col-span-4">
					<h2 class="h2 border-b-2 lg:border-b-[3px] border-brand-30 w-fit pb-[6px] lg:pb-[10px]">Profile Summary</h2>
					<?php if ( get_field( 'show_languages_toggle' ) && get_field( 'languages' ) ) : ?>
						<div class="mt-8 lg:mt-12">
							<h3 class="h3 text-green-deep w-fit">Languages</h3>
							<p class="text-base leading-7 xl:text-lg xl:leading-8 2xl:text-lg 2xl:leading-9 font-normal"><?php echo wp_kses_post( get_field( 'languages' ) ); ?></p>
						</div>
					<?php endif; ?>

					<?php if ( get_field( 'show_education_toggle' ) ) : ?>
						<div class="mt-8 lg:mt-12">
							<h3 class="h3 text-green-deep w-fit mb-2">Education</h3>
							<ul class="text-sm lg:text-base font-normal list-none space-y-2">
								<?php while ( have_rows( 'education_list' ) ) : the_row(); ?>
									<li class="flex items-start before:top-2 lg:before:top-3 before:relative gap-5 before:content-[''] before:block before:w-[15px] before:h-[3px] before:bg-green-accent1 before:flex-shrink-0">
										<?php the_sub_field( 'education_item' ); ?>
									</li>
								<?php endwhile; ?>
							</ul>
						</div>
					<?php endif; ?>
					
				</div>
				<div class="lg:col-span-7">
					<p class="text-base xl:text-lg font-normal"><?php echo get_field( 'profile_summary' ); ?></p>
				</div>
			</div>

		<?php if ( have_rows( 'areas' ) && $role && in_array( $role['value'], array( 'partner', 'associate' ) ) ) : ?>

				<div class="md:container grid grid-cols-1 lg:grid-cols-12 gap-8 p-6 lg:px-6 lg:py-10">
					<div class="lg:col-span-4">
						<h2 class="h2 border-b-2 lg:border-b-[3px] border-brand-30 w-fit pb-[6px] lg:pb-[10px]">Focus Areas</h2>
					</div>
					<div class="lg:col-span-7">
							<ul class="text-base leading-7 xl:text-lg xl:leading-8 2xl:text-lg 2xl:leading-9 font-normal list-none space-y-2">
								<?php while ( have_rows( 'areas' ) ) : the_row(); ?>
									<li class="flex items-start before:top-4 xl:before:top-[18px] 2xl:before:top-5 before:relative gap-5 before:content-[''] before:block before:w-[15px] before:h-[3px] before:bg-green-accent1 before:flex-shrink-0">
										<?php the_sub_field( 'area_name' ); ?>
									</li>
								<?php endwhile; ?>
							</ul>
					</div>
				</div>

		<?php endif; ?>

		<?php if ( have_rows( 'affiliations' ) && $role && in_array( $role['value'], array( 'partner', 'associate' ) ) ) : ?>

				<div class="md:container grid grid-cols-1 lg:grid-cols-12 gap-8 p-6 lg:px-6 lg:py-10">
					<div class="lg:col-span-4">
						<h2 class="h2 border-b-2 lg:border-b-[3px] border-brand-30 w-fit pb-[6px] lg:pb-[10px]">Advocacy Affiliations</h2>
					</div>
					<div class="lg:col-span-7">
							<ul class="text-base leading-7 xl:text-lg xl:leading-8 2xl:text-lg 2xl:leading-9 font-normal list-none space-y-2">
								<?php while ( have_rows( 'affiliations' ) ) : the_row(); ?>
									<li class="flex items-start before:top-[14px] xl:before:top-[18px] 2xl:before:top-5 before:relative gap-4 lg:gap-5 before:content-[''] before:block before:w-[15px] before:h-[3px] before:bg-green-accent1 before:flex-shrink-0">
										<?php the_sub_field( 'affiliation_title' ); ?>
									</li>
								<?php endwhile; ?>
							</ul>
					</div>
				</div>

		<?php endif; ?>

		<?php $cases = get_field( 'cases' ); ?>
		<?php if ( $cases ) : ?>

				<div class="md:container grid grid-cols-1 gap-8 p-6 lg:px-6 lg:py-16">
					<div class="col-spam-1">
						<h2 class="h2 border-b-2 lg:border-b-[3px] border-brand-30 w-fit pb-[6px] lg:pb-[10px]">Selected Cases</h2>
					</div>
					<div class="col-span-1 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
						<?php foreach ( $cases as $post ) : ?>
							<?php setup_postdata ( $post ); ?>
							<div class="group col-span-1 mb-6">
								<div class="flex justify-between items-center mb-2 text-brand-black uppercase text-xs border-b-2 border-b-brand-30 pt-4 lg:pt-6 pb-2 lg:pb-3">
									<div>
										<?php
										// Create link to case archive filtered by month/year
										$year = get_the_date( 'Y' );
										$month = get_the_date( 'm' );
										$date_link = add_query_arg(
											array(
												'post_type' => 'case',
												'year' => $year,
												'monthnum' => $month
											),
											home_url( '/' )
										);
										?>
										<a href="<?php echo esc_url( $date_link ); ?>" class="hover:text-brand-black transition-colors duration-300 border-b-transparent border-b-[1px] hover:border-b-brand-black">
											<?php echo get_the_date( 'F Y' ); ?>
										</a>
									</div>
									<div>
									<?php
										$areas = get_the_terms( get_the_ID(), 'area' );
										if ( $areas && ! is_wp_error( $areas ) ) {
											$term_link = add_query_arg( 'post_type', 'case', get_term_link( $areas[0] ) );
											echo '<a href="' . esc_url( $term_link ) . '" class="hover:text-brand-black transition-colors duration-300 border-b-transparent border-b-[1px] hover:border-b-brand-black">';
											echo esc_html( $areas[0]->name );
											echo '</a>';
										}
										?>
									</div>
								</div>
								<a href="<?php the_permalink(); ?>" class="text-green-deep font-hanken font-normal text-lg lg:text-xl border-b-transparent border-b-2 group-hover:border-b-green-deep transition-colors duration-300">
									<?php the_title(); ?>
								</a>
								<div class="mt-1 lg:mt-2 text-sm lg:text-base text-brand-70">
									<?php
									$excerpt = get_the_excerpt();
									if ( $excerpt ) {
										echo wp_trim_words( $excerpt, 15, '...' );
									}
									?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
					<a class="btn outlined"
					   href="<?php echo esc_url( get_post_type_archive_link( 'case' ) ); ?>"
					   aria-label="View all cases"
					   title="Browse our complete collection of legal cases handled by our team">
						View All Cases
					</a>
				</div>

			<?php wp_reset_postdata(); ?>
		<?php endif; ?>

		<?php if ( get_field( 'bookable_services_toggle' ) == 1 && have_rows( 'service' ) ) :
			$has_service_to_book = false;
			$booking_shortcode = '';
			$booking_service_label = '';
			while ( have_rows( 'service' ) ) : the_row();
				if ( get_row_layout() == 'mediation_service' ) :
					$has_service_to_book = true;
					$booking_service_label = 'Mediation Session';
					$booking_shortcode = get_sub_field( 'mediation_shortcode' );
					break;
				endif;
			endwhile;
		?>
			<?php if ( $has_service_to_book && $booking_shortcode ) : ?>
				<div id="book-service" class="md:container grid grid-cols-1 lg:grid-cols-12 gap-8 p-6 lg:px-6 lg:py-16 scroll-mt-24">
					<div class="lg:col-span-4">
						<h2 class="h2 border-b-2 lg:border-b-[3px] border-brand-30 w-fit pb-[6px] lg:pb-[10px]">Book a <?php echo esc_html( $booking_service_label ); ?></h2>
						<p class="text-base lg:text-lg text-brand-50 pt-2 lg:pt-4">Schedule a <?php echo esc_html( strtolower( $booking_service_label ) ); ?> with <?php the_title(); ?>.</p>
					</div>
					<div class="lg:col-span-7">
						<?php echo do_shortcode( $booking_shortcode ); ?>
					</div>
				</div>
			<?php endif; ?>
		<?php endif; ?>

<?php $insights = get_field( 'insights' ); ?>
		<?php if ( $insights ) : ?>

				<div class="md:container grid grid-cols-1 gap-8 p-6 lg:px-6 lg:py-16">
					<div class="col-spam-1">
						<h2 class="h2 border-b-2 lg:border-b-[3px] border-brand-30 w-fit pb-[6px] lg:pb-[10px]">Related Insights</h2>
						<?php if ( get_field( 'insights_subtitle' ) ) : ?>
							<p class="text-lg lg:text-xl text-brand-50 pt-2 lg:pt-4"><?php echo esc_html( get_field( 'insights_subtitle' ) ); ?></p>
						<?php endif; ?>
					</div>
					<div class="col-span-1 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
						<?php foreach ( $insights as $post ) : ?>
							<?php setup_postdata ( $post ); ?>
							<div class="group col-span-1 mb-6">
								<?php if ( has_post_thumbnail() ) : ?>
									<a href="<?php the_permalink(); ?>" class="block mb-1 rounded-lg overflow-hidden group-hover:shadow-lg transition-shadow duration-1000" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
										<?php the_post_thumbnail( 'large', array( 'class' => 'w-full h-auto rounded-lg aspect-[13/8] overflow-hidden object-cover' ) ); ?>
									</a>
								<?php endif; ?>
								<div class="flex justify-between items-center mb-2 text-brand-black uppercase text-xs border-b-2 border-b-brand-30 pt-4 lg:pt-6 pb-2 lg:pb-3">
									<div>
										<?php
										// Create link to case archive filtered by month/year
										$year = get_the_date( 'Y' );
										$month = get_the_date( 'm' );
										$date_link = add_query_arg(
											array(
												'post_type' => 'case',
												'year' => $year,
												'monthnum' => $month
											),
											home_url( '/' )
										);
										?>
										<a href="<?php echo esc_url( $date_link ); ?>" class="hover:text-brand-black transition-colors duration-300 border-b-transparent border-b-[1px] hover:border-b-brand-black">
											<?php echo get_the_date( 'F Y' ); ?>
										</a>
									</div>
									<div>
									<?php
										$areas = get_the_terms( get_the_ID(), 'area' );
										if ( $areas && ! is_wp_error( $areas ) ) {
											$term_link = add_query_arg( 'post_type', 'case', get_term_link( $areas[0] ) );
											echo '<a href="' . esc_url( $term_link ) . '" class="hover:text-brand-black transition-colors duration-300 border-b-transparent border-b-[1px] hover:border-b-brand-black">';
											echo esc_html( $areas[0]->name );
											echo '</a>';
										}
										?>
									</div>
								</div>
								<a href="<?php the_permalink(); ?>" class="text-green-deep font-hanken font-normal text-lg lg:text-xl underline decoration-2 decoration-transparent group-hover:decoration-green-deep underline-offset-2 transition-colors duration-300">
									<?php the_title(); ?>
								</a>
								<div class="mt-1 lg:mt-2 text-sm lg:text-base text-brand-70">
									<?php
									$excerpt = get_the_excerpt();
									if ( $excerpt ) {
										echo wp_trim_words( $excerpt, 15, '...' );
									}
									?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>

					<a class="btn outlined"
					   href="<?php echo esc_url( get_post_type_archive_link( 'post' ) ); ?>"
					   aria-label="View all insights articles"
					   title="Browse our complete collection of legal insights and articles">
						View All Insights
					</a>

				</div>
			<?php wp_reset_postdata(); ?>
		<?php endif; ?>

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
								<a href="<?php the_permalink(); ?>" class="block aspect-[16/9] overflow-hidden" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
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
