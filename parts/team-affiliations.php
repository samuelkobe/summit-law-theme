<?php
/**
 * Team Member Advocacy Affiliations
 */

$role = get_field( 'role' );

if ( ! have_rows( 'affiliations' ) || ! $role || ! in_array( $role['value'], array( 'partner', 'associate' ) ) ) {
	return;
}
?>
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
