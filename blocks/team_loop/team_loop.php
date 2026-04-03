<?php
/**
 * Block template file: team_loop.php
 *
 * Team Loop Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'team_loop-' . $block['id'];
if ( ! empty($block['anchor'] ) ) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$classes = 'block-team_loop';
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

	/* Team booking modal */
	.team-booking-modal {
		position: fixed;
		inset: 0;
		z-index: 9999;
		display: flex;
		align-items: center;
		justify-content: center;
		opacity: 0;
		visibility: hidden;
		pointer-events: none;
		transition: opacity 0.2s ease, visibility 0.2s ease;
	}
	.team-booking-modal.active {
		opacity: 1;
		visibility: visible;
		pointer-events: auto;
	}
	.team-booking-modal__backdrop {
		position: absolute;
		inset: 0;
		background: rgba(0, 0, 0, 0.6);
		backdrop-filter: blur(4px);
	}
	.team-booking-modal__content {
		position: relative;
		background: #fff;
		border-radius: 0.75rem;
		padding: 2rem;
		max-width: 700px;
		width: 90%;
		max-height: 90vh;
		overflow-y: auto;
		box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
		z-index: 1;
	}
	.team-booking-modal__close {
		position: absolute;
		top: 0.75rem;
		right: 1rem;
		background: none;
		border: none;
		font-size: 1.75rem;
		line-height: 1;
		cursor: pointer;
		color: #666;
		transition: color 0.2s;
	}
	.team-booking-modal__close:hover {
		color: #000;
	}
	.team-booking-modal__content #amelia-container {
		margin: 0 !important;
	}

	/* Off-screen staging area — visible so Amelia Vue can mount, but not seen by user */
	.team-booking-staging {
		position: absolute;
		left: -9999px;
		top: -9999px;
		width: 600px;
		overflow: hidden;
	}
</style>


<section id="<?php echo esc_attr( $id ); ?>" class="bg-white block-white w-full min-h-[800px] lg:pt-16 max-md:pt-2 <?php echo esc_attr( $classes ); ?>">
	<div class="team-grid container mx-auto px-4 pb-16">

	<?php
		// Custom query to order by ACF Role field
		$role_order = array( 'partner', 'associate', 'clerk', 'assistant', 'admin' );

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
				$current_post = get_post();
				$role = get_field( 'role', $current_post->ID );
				$role_value = $role ? $role['value'] : 'other';

				if ( ! isset( $posts_by_role[ $role_value ] ) ) {
					$posts_by_role[ $role_value ] = array();
				}
				$posts_by_role[ $role_value ][] = $current_post;
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
				foreach ( $ordered_posts as $team_post ) :
					global $post;
					$post = $team_post;
					setup_postdata( $post );
					$is_left = ( $index % 2 === 0 ); // Even index = left card
					$xl_col_class = $is_left ? 'xl:col-start-2 xl:col-span-5' : 'xl:col-span-5';
					$index++;

					// Booking data — mirrors parts/team-booking.php logic
					$has_service_to_book = false;
					$booking_shortcode = '';
					$booking_label = '';
					if ( get_field( 'bookable_services_toggle', $post->ID ) == 1 ) {
						$service_rows = get_field( 'service', $post->ID );
						if ( ! empty( $service_rows ) ) {
							foreach ( $service_rows as $row ) {
								if ( $row['acf_fc_layout'] === 'mediation_service' ) {
									$has_service_to_book = true;
									$booking_label = 'Mediation Session';
									$booking_shortcode = $row['mediation_shortcode'] ?? '';
									break;
								}
							}
						}
					}
					$first_name = explode( ' ', get_the_title() )[0];
				?>

				<article class="team-member-card <?php echo esc_attr( $xl_col_class ); ?>">

					<a href="<?php the_permalink(); ?>" class="group block">
						<?php if ( has_post_thumbnail() ) : ?>
							<div class="bg-brand-black w-full overflow-hidden mb-2 lg:mb-4 group-hover:shadow-lg transition-shadow duration-1000">
								<?php the_post_thumbnail( 'large', array( 'class' => 'aspect-image sm:aspect-video object-contain' ) ); ?>
							<?php else : ?>
								<div class="bg-green-deep w-full overflow-hidden mb-2 lg:mb-4 group-hover:shadow-lg transition-shadow duration-1000">
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
								<?php $role = get_field( 'role', $post->ID );?>
								<?php if ( $role ) :
									echo esc_html( $role['label'] ); // display role label
								endif; ?>
							</p>
							<p class="text-brand-black mt-2 lg:mt-4 text-base 2xl:text-lg lg:min-h-[4.5rem] 2xl:min-h-[6rem]"><?php echo wp_kses_post( get_field( 'short_description', $post->ID ) ); ?></p>
						</div>
					</a>
					<address class="not-italic flex flex-col sm:flex-row gap-y-1 gap-x-6 mt-2 lg:mt-4">
						<?php if ( get_field( 'email', $post->ID ) ) : ?>
							<div class="flex items-center gap-2 text-green-deep">
								<?php echo summit_get_svg_icon( 'email-alt', array( 'class' => 'w-5 h-5 flex-shrink-0  stroke-green-deep' ) ); ?>
								<a href="mailto:<?php echo esc_attr( get_field( 'email', $post->ID ) ); ?>" class="underline decoration-2 decoration-transparent hover:decoration-green-deep underline-offset-2 transition-colors duration-300">
									<?php echo esc_html( get_field( 'email', $post->ID ) ); ?>
								</a>
							</div>
						<?php endif; ?>

						<?php if ( get_field( 'phone', $post->ID ) ) : ?>
							<div class="flex items-center gap-2 text-green-deep">
								<?php echo summit_get_svg_icon( 'phone-alt', array( 'class' => 'w-5 h-5 flex-shrink-0 stroke-green-deep' ) ); ?>
								<a href="tel:<?php echo esc_attr( str_replace( array( ' ', '-', '(', ')' ), '', get_field( 'phone', $post->ID ) ) ); ?>" class="underline decoration-2 decoration-transparent hover:decoration-green-deep underline-offset-2 transition-colors duration-300">
									<?php echo esc_html( get_field( 'phone', $post->ID ) ); ?><?php if ( get_field( 'extension', $post->ID ) ) : ?> ext. <?php echo esc_html( get_field( 'extension', $post->ID ) ); ?><?php endif; ?>
								</a>
							</div>
						<?php endif; ?>
					</address>

					<?php if ( $has_service_to_book && $booking_shortcode ) : ?>
						<div class="mt-3 lg:mt-5">
							<button class="btn team-booking-trigger"
								data-modal="booking-modal-<?php echo esc_attr( $post->ID ); ?>"
								data-staging="booking-staging-<?php echo esc_attr( $post->ID ); ?>"
								aria-label="Book a <?php echo esc_attr( $booking_label ); ?> with <?php echo esc_attr( get_the_title() ); ?>">
								Book a <?php echo esc_html( $booking_label ); ?>
							</button>
						</div>
					<?php endif; ?>

					<div class="mt-2 lg:mt-4">
						<a href="<?php the_permalink(); ?>" class="text-brand-40 hover:text-brand-black text-sm lg:text-base font-bold underline decoration-2 decoration-transparent hover:decoration-brand-black transition-colors duration-300">View full profile</a>
					</div>
				</article>
				<?php endforeach; wp_reset_postdata(); ?>

			</div>

		<?php else : ?>

			<p class="text-center text-gray-600"><?php esc_html_e( 'No team members found.', 'summit-law-theme' ); ?></p>

		<?php endif; ?>

	</div>
</section>

<?php
// Modals + staging areas (outside grid to avoid stacking context issues)
// Shortcode rendered once per bookable member in visible off-screen staging so Amelia Vue can mount
if ( ! empty( $ordered_posts ) ) :
	foreach ( $ordered_posts as $modal_post ) :
		if ( get_field( 'bookable_services_toggle', $modal_post->ID ) != 1 ) {
			continue;
		}
		$modal_service_rows = get_field( 'service', $modal_post->ID );
		if ( empty( $modal_service_rows ) ) {
			continue;
		}
		$modal_shortcode = '';
		$modal_label = '';
		foreach ( $modal_service_rows as $modal_row ) {
			if ( $modal_row['acf_fc_layout'] === 'mediation_service' ) {
				$modal_label = 'Mediation Session';
				$modal_shortcode = $modal_row['mediation_shortcode'] ?? '';
				break;
			}
		}
		if ( ! $modal_shortcode ) {
			continue;
		}
?>
	<div id="booking-modal-<?php echo esc_attr( $modal_post->ID ); ?>" class="team-booking-modal" aria-hidden="true" role="dialog" aria-label="Book a <?php echo esc_attr( $modal_label ); ?>">
		<div class="team-booking-modal__backdrop"></div>
		<div class="team-booking-modal__content">
			<button class="team-booking-modal__close" aria-label="Close booking modal">&times;</button>
			<h3 class="h3 font-museum text-green-deep mb-2">Book a <?php echo esc_html( $modal_label ); ?></h3>
			<p class="text-brand-50 text-base mb-4">Schedule a <?php echo esc_html( strtolower( $modal_label ) ); ?> with <?php echo esc_html( $modal_post->post_title ); ?>.</p>
			<div class="team-booking-modal__slot"></div>
		</div>
	</div>
	<div id="booking-staging-<?php echo esc_attr( $modal_post->ID ); ?>" class="team-booking-staging">
		<?php echo do_shortcode( $modal_shortcode ); ?>
	</div>
<?php
	endforeach;
endif;
?>

<script>
(function() {
	// Open modal — move staged shortcode into modal slot
	document.querySelectorAll('.team-booking-trigger').forEach(function(trigger) {
		trigger.addEventListener('click', function(e) {
			e.preventDefault();
			var modal = document.getElementById(this.getAttribute('data-modal'));
			var staging = document.getElementById(this.getAttribute('data-staging'));
			if (modal && staging) {
				modal.querySelector('.team-booking-modal__slot').appendChild(staging);
				staging.style.position = 'static';
				staging.style.left = 'auto';
				staging.style.top = 'auto';
				staging.style.width = 'auto';
				modal.classList.add('active');
				modal.setAttribute('aria-hidden', 'false');
				document.body.style.overflow = 'hidden';
			}
		});
	});

	// Close modal — move shortcode back to body-level staging
	function closeModal(modal) {
		var staging = modal.querySelector('.team-booking-staging');
		if (staging) {
			staging.style.position = '';
			staging.style.left = '';
			staging.style.top = '';
			staging.style.width = '';
			document.body.appendChild(staging);
		}
		modal.classList.remove('active');
		modal.setAttribute('aria-hidden', 'true');
		document.body.style.overflow = '';
	}

	document.querySelectorAll('.team-booking-modal__close').forEach(function(btn) {
		btn.addEventListener('click', function() {
			closeModal(this.closest('.team-booking-modal'));
		});
	});

	document.querySelectorAll('.team-booking-modal__backdrop').forEach(function(backdrop) {
		backdrop.addEventListener('click', function() {
			closeModal(this.closest('.team-booking-modal'));
		});
	});

	document.addEventListener('keydown', function(e) {
		if (e.key === 'Escape') {
			var activeModal = document.querySelector('.team-booking-modal.active');
			if (activeModal) closeModal(activeModal);
		}
	});
})();
</script>
