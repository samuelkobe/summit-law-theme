<?php get_header(); ?>

<main>
  <div>
    
    <?php if (have_posts()) : ?>
      <?php while (have_posts()) : the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
          <h2>
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
          </h2>
          <!-- test area -->
    
          <div class="min-h-[500px]">

          <?php
// TESTING - Remove after testing
$all_cases = get_posts( array( 'post_type' => 'case', 'numberposts' => -1 ) );
foreach ( $all_cases as $case ) {
    $areas = get_the_terms( $case->ID, 'area' );
    echo $areas ? $areas[0]->name : 'REMOVE ME AT LAUNCH';
    echo '<br>';
}
?>


            <?php the_content(); ?>
          </div>
        </article>
      <?php endwhile; ?>

      <?php the_posts_pagination(); ?>
    <?php else : ?>
      <p>No content found.</p>
    <?php endif; ?>
  </div>
</main>

<?php get_footer(); ?>
