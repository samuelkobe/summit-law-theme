<?php
/**
 * Template part for displaying child services (Practice Areas)
 *
 * Used for individual practice areas under each service type
 * URL example: /services/insurance-litigation/personal-injury/
 */

$parent_id = wp_get_post_parent_id( get_the_ID() );
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'service-area page-content' ); ?>>

	<?php the_content(); ?>

</article>
