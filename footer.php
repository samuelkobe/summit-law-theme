<footer role="contentinfo">
  <section class="bg-green-deep py-6 lg:py-16">
    <div class="footer-grid">
      <div class="mb-0 lg:mb-8 xl:mb-0">
        <!-- Logo and Address -->
        <div class="footer-logo mb-8 sm:mb-4 w-full transition-transform duration-300 ease-in-out hover:scale-105">
          <?php if (has_custom_logo()) : ?>
            <?php the_custom_logo(); ?>
          <?php endif; ?>
        </div>
        <?php if (get_theme_mod('footer_address')) : ?>
          <?php $maps_link = get_theme_mod('footer_maps_link'); ?>
          <h3 class="text-green-accent1 text-xl mt-0 sm:mt-6">Address</h3>
          <address>
            <?php if ($maps_link) : ?>
              <a href="<?php echo esc_url($maps_link); ?>" target="_blank" rel="noopener noreferrer">
                <?php echo nl2br(esc_html(get_theme_mod('footer_address'))); ?>
              </a>
            <?php else : ?>
              <?php echo nl2br(esc_html(get_theme_mod('footer_address'))); ?>
            <?php endif; ?>
          </address>
        <?php endif; ?>
      </div>

      <?php
      // Footer menu locations to display
      $footer_menus = ['footer-services', 'footer-firm', 'footer-insights', 'footer-contact'];
      $locations = get_nav_menu_locations();

      foreach ($footer_menus as $menu_location) :
        $menu_id = isset($locations[$menu_location]) ? $locations[$menu_location] : null;

        if ($menu_id) :
          // Get ACF group field
          if (have_rows('menu_group_settings', 'term_' . $menu_id)) :
            while (have_rows('menu_group_settings', 'term_' . $menu_id)) : the_row();
              $menu_title = get_sub_field('title');
              $page_link_toggle = get_sub_field('page_link_toggle');
              $link_type = get_sub_field('link_type'); // true (1) = custom_link, false (0) = page_link
              $page_link = get_sub_field('page_link');
              $custom_link = get_sub_field('custom_link');

              // Determine which link to use based on link_type
              $title_link = null;
              if ($page_link_toggle === 'yes') {
                if ($link_type && $custom_link) {
                  // link_type is true/1: use custom_link
                  $title_link = $custom_link;
                } elseif (!$link_type && $page_link) {
                  // link_type is false/0: use page_link
                  $title_link = $page_link;
                }
              }

              // Use "Menu" as default title if no custom title provided
              $display_title = $menu_title ? $menu_title : 'Menu';
            ?>
              <div class="pt-3 lg:py-0">
                <?php if ($menu_title && $title_link) : ?>
                  <h3 class="text-xl"><a href="<?php echo esc_url($title_link); ?>" class="footer-menu-title-link fill-green-accent1 hover:fill-green-accent2"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><path d="M17.9,10.5L9,1.7c-.8-.8-2.1-.8-2.9,0-.8.8-.8,2.1,0,2.9l7.4,7.4-7.4,7.4c-.8.8-.8,2.1,0,2.9s.9.6,1.5.6,1.1-.2,1.5-.6l8.9-8.9c.4-.4.6-.9.6-1.5s-.2-1.1-.6-1.5Z"/></svg><?php echo esc_html($display_title); ?></a></h3>
                <?php else : ?>
                  <h3 class="text-green-accent1 text-xl"><?php echo esc_html($display_title); ?></h3>
                <?php endif; ?>
                <div class="mt-2 lg:mt-6">
                  <?php
                  wp_nav_menu([
                    'theme_location' => $menu_location,
                    'menu_class' => 'footer-menu',
                    'container' => false,
                    'fallback_cb' => false,
                    'walker' => new Summit_Icon_Walker($menu_id),
                  ]);
                  ?>
                </div>
              </div>
            <?php
            endwhile;
          endif;
        endif;
      endforeach;
      ?>
    </div>
  </section>

  <section class="footer-bottom">
    <div class="container mx-auto !p-4 flex flex-col lg:flex-row justify-between items-center gap-4">
      <?php
      wp_nav_menu([
        'theme_location' => 'footer-legal',
        'menu_class' => 'footer-legal-menu',
        'container' => false,
        'fallback_cb' => false,
        'depth' => 1,
      ]);
      ?>
      <div>
        <p class="subpixel-antialiased">&copy; <?php echo date('Y'); ?> <?php echo esc_html(get_theme_mod('footer_copyright', 'Summit Law. All rights reserved.')); ?></p>
      </div>
      <?php if (get_theme_mod('footer_maintained_by')) : ?>
        <div class="text-brand-40 text-sm">
          Designed and maintained by
          <?php if (get_theme_mod('footer_maintained_by_link')) : ?>
            <a class="underline" href="<?php echo esc_url(get_theme_mod('footer_maintained_by_link')); ?>" target="_blank" rel="noopener noreferrer">
              <?php echo esc_html(get_theme_mod('footer_maintained_by', 'Web Ok Solutions Inc.')); ?>
            </a>
          <?php else : ?>
            <?php echo esc_html(get_theme_mod('footer_maintained_by', 'Web Ok Solutions Inc.')); ?>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
</footer>

<?php wp_footer(); ?>
</body>
</html>
