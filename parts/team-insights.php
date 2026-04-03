<?php
/**
 * Team Member Related Insights
 */

$insights = get_field( 'insights' );

if ( ! $insights ) {
	return;
}
?>
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
