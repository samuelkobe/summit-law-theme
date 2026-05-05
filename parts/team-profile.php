<?php
/**
 * Team Member Profile Summary
 *
 * Profile summary, languages, education
 */
?>
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
