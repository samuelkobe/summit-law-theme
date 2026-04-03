<?php
/**
 * Team Member Hero Section
 *
 * Photo, name, role, description, booking CTA, connect/assistant accordion
 */

$role = get_field( 'role' );
?>
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
					<?php if ( function_exists( 'the_custom_logo' ) ) {
						the_custom_logo();
					} ?>
				</div>
			<?php endif; ?>
		</div>

		<?php $has_booking = get_field( 'bookable_services_toggle' ) == 1 && ! empty( get_field( 'service' ) ); ?>
		<div class="self-center bg-green-deep md:bg-transparent md:col-start-7 md:col-span-6 lg:col-start-7 lg:col-span-6 p-6 <?php echo $has_booking ? 'md:py-24' : ''; ?>">
			<h1 class="h1 text-4xl lg:text-5xl xl:text-[62.5px] text-white mb-1 xl:mb-2"><?php the_title(); ?></h1>
			<h2 class="h2 text-2xl lg:text-3xl xl:text-[32px] text-green-accent1 mb-4">
				<?php if ( $role ) :
					echo esc_html( $role['label'] );
				endif; ?>
			</h2>
			<p class="text-lg max-md:block max-xl:hidden lg:text-xl text-white lg:mb-12 2xl:max-w-[75%]"><?php echo wp_kses_post( get_field( 'short_description' ) ); ?></p>

			<?php
			$has_legal_assistant = get_field( 'legal_assistant_toggle' ) == 1;
			?>

			<?php if ( get_field( 'bookable_services_toggle' ) == 1 && have_rows( 'service' ) ) :
				$has_bookable_service = false;
				$service_label = '';
				while ( have_rows( 'service' ) ) : the_row();
					if ( get_row_layout() == 'mediation_service' ) :
						$has_bookable_service = true;
						$service_label = 'Mediation Session';
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
