<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php
// Determine if we're on the home page
$is_home = is_front_page();
$page_type = $is_home ? 'home' : 'default';
?>

<header data-header data-page-type="<?php echo esc_attr($page_type); ?>" class="<?php echo $is_home ? 'header--dark' : 'header--light'; ?>">
  <div class="container max-lg:py-4">
    <div class="header-inner<?php echo !$is_home ? '' : ' lg:grid lg:grid-cols-12'; ?>">

      <!-- Logo -->
      <?php
      $primary_logo = summit_get_primary_logo();
      $alt_logo = summit_get_alt_logo('logo-alt');
      $has_primary_logo = !empty($primary_logo);
      $has_alt_logo = !empty($alt_logo);
      ?>
      <div class="header-logo<?php echo !$is_home ? ' logo--small' : ' lg:col-span-6'; ?>" data-logo>
        <a href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php bloginfo('name'); ?> Home">
          <?php if ($has_primary_logo || $has_alt_logo) : ?>
            <?php if ($has_primary_logo) : ?>
              <span class="logo-primary"><?php echo $primary_logo; ?></span>
            <?php endif; ?>
            <?php if ($has_alt_logo) : ?>
              <span class="logo-alternate"><?php echo $alt_logo; ?></span>
            <?php endif; ?>
          <?php else : ?>
            <span><?php bloginfo('name'); ?></span>
          <?php endif; ?>
        </a>
      </div>

      <!-- Desktop: Navigation + Search grouped on right -->
      <div class="header-right<?php echo !$is_home ? '' : ' lg:col-span-6 lg:justify-self-end lg:self-start'; ?>">
        <!-- Primary Navigation (Desktop) -->
        <nav class="primary-nav" aria-label="Primary Navigation">
          <?php
          if (has_nav_menu('primary')) {
            wp_nav_menu([
              'theme_location' => 'primary',
              'container' => false,
              'menu_class' => 'nav-menu',
              'walker' => new Summit_Mega_Menu_Walker('desktop'),
              'fallback_cb' => false,
            ]);
          }
          ?>
        </nav>

        <!-- Search Icon (Desktop) -->
        <button
          class="search-toggle"
          aria-label="Open search"
          aria-expanded="false"
          aria-controls="search-overlay"
          data-search-toggle>
          <?php echo summit_get_svg_icon('search'); ?>
        </button>
      </div>

      <!-- Hamburger Menu (Mobile) -->
      <button
        class="mobile-menu-toggle"
        aria-label="Open menu"
        aria-expanded="false"
        aria-controls="mobile-menu"
        data-mobile-toggle>
        <span class="hamburger">
          <span></span>
          <span></span>
          <span></span>
        </span>
      </button>

    </div>
  </div>

  <!-- Search Overlay (Desktop) -->
  <div
    class="search-overlay"
    id="search-overlay"
    data-search-overlay
    hidden>
    <div class="container">
      <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
        <label for="search-field" class="sr-only">Search</label>
        <input
          type="search"
          id="search-field"
          name="s"
          placeholder="Search..."
          data-search-input>
        <button type="submit" aria-label="Submit search">
          <?php echo summit_get_svg_icon('search'); ?>
        </button>
      </form>
      <button
        class="search-close"
        aria-label="Close search"
        data-search-close>
        <?php echo summit_get_svg_icon('close'); ?>
      </button>
    </div>
  </div>

  <!-- Mobile Menu Drawer -->
  <div
  class="mobile-menu container max-lg:!px-0"
    id="mobile-menu"
    data-mobile-menu
    hidden>
    <nav aria-label="Mobile Navigation">
      <?php
      if (has_nav_menu('primary')) {
        wp_nav_menu([
          'theme_location' => 'primary',
          'container' => false,
          'menu_class' => 'mobile-nav-menu',
          'walker' => new Summit_Mega_Menu_Walker('mobile'),
          'fallback_cb' => false,
        ]);
      }
      ?>

      <!-- Search Field (Mobile) -->
      <div class="mobile-search">
        <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
          <label for="mobile-search-field" class="sr-only">Search</label>
          <input
            type="search"
            id="mobile-search-field"
            name="s"
            placeholder="Search..."
            data-mobile-search-input>
          <button type="submit" aria-label="Submit search">
            <?php echo summit_get_svg_icon('search'); ?>
          </button>
        </form>
      </div>
    </nav>
  </div>
</header>
