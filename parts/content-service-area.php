<?php
/**
 * Template part for displaying child services (Practice Areas)
 *
 * Used for individual practice areas under each service type
 * URL example: /services/insurance-litigation/personal-injury/
 */

$parent_id = wp_get_post_parent_id( get_the_ID() );
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'service-area' ); ?>>

	<header class="entry-header">
		<?php if ( $parent_id ) : ?>
			<div class="breadcrumb">
				<a href="<?php echo esc_url( get_post_type_archive_link( 'service' ) ); ?>">Services</a>
				<span class="separator">/</span>
				<a href="<?php echo esc_url( get_permalink( $parent_id ) ); ?>">
					<?php echo esc_html( get_the_title( $parent_id ) ); ?>
				</a>
				<span class="separator">/</span>
				<span class="current"><?php the_title(); ?></span>
			</div>
		<?php endif; ?>

		<h1 class="entry-title">CHILD<span class="uppercase text-sm text-red-700">(remove later) </span><?php the_title(); ?></h1>
	</header>

	<?php if ( has_post_thumbnail() ) : ?>
		<div class="service-hero">
			<?php the_post_thumbnail( 'large' ); ?>
		</div>
	<?php endif; ?>

	<div>
		<?php the_content(); ?>
	</div>

	<?php
	// Display related practice areas (siblings)
	if ( $parent_id ) :
		$siblings = get_posts( array(
			'post_type'      => 'service',
			'posts_per_page' => -1,
			'post_parent'    => $parent_id,
			'post__not_in'   => array( get_the_ID() ), // Exclude current post
			'orderby'        => 'menu_order',
			'order'          => 'ASC'
		) );

		if ( $siblings ) : ?>
			<aside class="related-services">
				<h2>Related Practice Areas</h2>
				<ul>
					<?php foreach ( $siblings as $sibling ) : ?>
						<li>
							<a href="<?php echo esc_url( get_permalink( $sibling->ID ) ); ?>">
								<?php echo esc_html( get_the_title( $sibling->ID ) ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</aside>
		<?php endif;
	endif;
	?>

</article>
