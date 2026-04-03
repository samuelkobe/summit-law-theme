<?php
/**
 * Team Member Selected Cases
 */

$cases = get_field( 'cases' );

if ( ! $cases ) {
	return;
}
?>
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
