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

					<!-- <div>
						Conditional: Book a meeting link (future functionality)
					</div> -->
					
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