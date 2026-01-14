<?php get_header(); ?>

<main>
  <div>
    
    <?php if (have_posts()) : ?>
      <?php while (have_posts()) : the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>   
            <?php the_content(); ?>
        </article>
      <?php endwhile; ?>

      <?php the_posts_pagination(); ?>
    <?php else : ?>
      <p>No content found.</p>
    <?php endif; ?>
  </div>
</main>

<?php get_footer(); ?>
