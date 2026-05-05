<?php
/**
 * Single Team Member Template
 *
 * Displays individual team member profile with details and related insights.
 * Partials are located in /parts/team-*.php
 */

get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>

<?php $role = get_field( 'role' ); ?>

<main id="main" class="site-main team-member-single">

	<article id="post-<?php the_ID(); ?>" <?php post_class( '' ); ?>>

		<?php get_template_part( 'parts/team', 'hero' ); ?>

		<?php get_template_part( 'parts/team', 'breadcrumbs' ); ?>

		<section class="bg-white min-h-[30dvh] pb-12 lg:pb-16">

			<?php get_template_part( 'parts/team', 'profile' ); ?>

			<?php get_template_part( 'parts/team', 'focus-areas' ); ?>

			<?php get_template_part( 'parts/team', 'affiliations' ); ?>

			<?php get_template_part( 'parts/team', 'cases' ); ?>

			<?php get_template_part( 'parts/team', 'insights' ); ?>
			
			<?php get_template_part( 'parts/team', 'booking' ); ?>

		</section>

	</article>

</main>

<?php endwhile; ?>

<?php get_footer(); ?>
