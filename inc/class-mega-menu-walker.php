<?php
/**
 * Custom Walker for Mega Menu Navigation
 *
 * Extends Walker_Nav_Menu to create a 3-tier navigation structure:
 * - Tier 1 (depth 0): Top-level menu items (clickable links or mega menu toggles)
 * - Tier 2 (depth 1): Category headers in mega menu (with links)
 * - Tier 3 (depth 2): Links under categories
 *
 * @package Summit_Law_Theme
 */

class Summit_Mega_Menu_Walker extends Walker_Nav_Menu {

  /**
   * Menu type: desktop or mobile
   * @var string
   */
  private $menu_type;

  /**
   * Constructor
   *
   * @param string $menu_type Type of menu (desktop|mobile)
   */
  public function __construct($menu_type = 'desktop') {
    $this->menu_type = $menu_type;
  }

  /**
   * Starts the list before the elements are added.
   *
   * @param string   $output Used to append additional content (passed by reference).
   * @param int      $depth  Depth of menu item. Used for padding.
   * @param stdClass $args   An object of wp_nav_menu() arguments.
   */
  /**
   * Track parent item IDs for menu association
   */
  private $current_parent_id = null;

  public function start_lvl(&$output, $depth = 0, $args = null) {
    if ($this->menu_type === 'desktop') {
      if ($depth === 0) {
        // Start of mega menu container (tier 2 & 3)
        // Use the parent ID that was set in start_el
        $menu_id = $this->current_parent_id ? 'mega-menu-' . $this->current_parent_id : 'mega-menu';
        $output .= '<div class="mega-menu" id="' . esc_attr($menu_id) . '" data-mega-menu hidden>';
        $output .= '<div class="mega-menu-content">';
      } elseif ($depth === 1) {
        // Start of category links (tier 3)
        $output .= '<ul class="mega-menu-links">';
      }
    } else {
      // Mobile menu
      if ($depth === 0) {
        // Mobile submenu container
        $submenu_id = $this->current_parent_id ? 'mobile-submenu-' . $this->current_parent_id : 'mobile-submenu';
        $output .= '<div class="mobile-submenu" id="' . esc_attr($submenu_id) . '" data-mobile-submenu hidden>';
      } elseif ($depth === 1) {
        // Mobile category links
        $output .= '<ul class="mobile-submenu-links">';
      }
    }
  }

  /**
   * Ends the list of after the elements are added.
   *
   * @param string   $output Used to append additional content (passed by reference).
   * @param int      $depth  Depth of menu item. Used for padding.
   * @param stdClass $args   An object of wp_nav_menu() arguments.
   */
  public function end_lvl(&$output, $depth = 0, $args = null) {
    if ($this->menu_type === 'desktop') {
      if ($depth === 0) {
        // End of mega menu
        $output .= '</div>'; // Close mega-menu-content
        $output .= '</div>'; // Close mega-menu
      } elseif ($depth === 1) {
        // End of category links
        $output .= '</ul>';
      }
    } else {
      // Mobile menu
      if ($depth === 0) {
        // End mobile submenu
        $output .= '</div>';
      } elseif ($depth === 1) {
        // End mobile category links
        $output .= '</ul>';
      }
    }
  }

  /**
   * Starts the element output.
   *
   * @param string   $output Used to append additional content (passed by reference).
   * @param WP_Post  $item   Menu item data object.
   * @param int      $depth  Depth of menu item. Used for padding.
   * @param stdClass $args   An object of wp_nav_menu() arguments.
   * @param int      $id     Current item ID.
   */
  public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
    $classes = empty($item->classes) ? [] : (array) $item->classes;
    $classes[] = 'menu-item-' . $item->ID;

    // Check if item has children
    $has_children = in_array('menu-item-has-children', $classes);

    if ($this->menu_type === 'desktop') {
      $this->render_desktop_item($output, $item, $depth, $args, $classes, $has_children);
    } else {
      $this->render_mobile_item($output, $item, $depth, $args, $classes, $has_children);
    }
  }

  /**
   * Render desktop menu item
   */
  private function render_desktop_item(&$output, $item, $depth, $args, $classes, $has_children) {
    if ($depth === 0) {
      // Top-level item (Tier 1)
      // Store parent ID for mega menu association
      if ($has_children) {
        $this->current_parent_id = $item->ID;
      }

      $class_names = implode(' ', array_filter($classes));
      $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

      $output .= '<li' . $class_names . '>';

      if ($has_children) {
        // Parent item with mega menu - link + toggle button
        $menu_id = 'mega-menu-' . $item->ID;

        // Link to parent page
        $output .= '<a href="' . esc_url($item->url) . '">';
        $output .= esc_html($item->title);
        $output .= '</a>';

        // Chevron button for mega menu toggle
        $output .= '<button aria-expanded="false" aria-controls="' . esc_attr($menu_id) . '" data-mega-toggle aria-label="' . esc_attr('Open ' . $item->title . ' menu') . '" title="' . esc_attr('Open ' . $item->title . ' menu') . '">';
        $output .= summit_get_svg_icon('chevron-down', ['class' => 'chevron']);
        $output .= '</button>';
      } else {
        // Regular link
        $atts = [];
        $atts['href'] = !empty($item->url) ? $item->url : '';

        $attributes = '';
        foreach ($atts as $attr => $value) {
          if (!empty($value)) {
            $value = ('href' === $attr) ? esc_url($value) : esc_attr($value);
            $attributes .= ' ' . $attr . '="' . $value . '"';
          }
        }

        $output .= '<a' . $attributes . '>';
        $output .= esc_html($item->title);
        $output .= '</a>';
      }

    } elseif ($depth === 1) {
      // Category item (Tier 2) - in mega menu
      $output .= '<div class="mega-menu-column">';

      $atts = [];
      $atts['href'] = !empty($item->url) ? $item->url : '';

      $attributes = '';
      foreach ($atts as $attr => $value) {
        if (!empty($value)) {
          $value = ('href' === $attr) ? esc_url($value) : esc_attr($value);
          $attributes .= ' ' . $attr . '="' . $value . '"';
        }
      }

      $output .= '<a' . $attributes . '>';
      $output .= esc_html($item->title);
      $output .= '</a>';

    } elseif ($depth === 2) {
      // Link item (Tier 3) - under category
      $output .= '<li>';

      $atts = [];
      $atts['href'] = !empty($item->url) ? $item->url : '';

      $attributes = '';
      foreach ($atts as $attr => $value) {
        if (!empty($value)) {
          $value = ('href' === $attr) ? esc_url($value) : esc_attr($value);
          $attributes .= ' ' . $attr . '="' . $value . '"';
        }
      }

      $output .= '<a' . $attributes . '>';
      $output .= esc_html($item->title);
      $output .= '</a>';
    }
  }

  /**
   * Render mobile menu item
   */
  private function render_mobile_item(&$output, $item, $depth, $args, $classes, $has_children) {
    if ($depth === 0) {
      // Top-level mobile item
      // Store parent ID for submenu association
      if ($has_children) {
        $this->current_parent_id = $item->ID;
      }

      $class_names = implode(' ', array_filter($classes));
      $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

      $output .= '<li' . $class_names . '>';

      if ($has_children) {
        // Parent item with submenu - link + toggle button
        $submenu_id = 'mobile-submenu-' . $item->ID;

        // Link to parent page
        $output .= '<a href="' . esc_url($item->url) . '">';
        $output .= esc_html($item->title);
        $output .= '</a>';

        // Chevron button for submenu toggle
        $output .= '<button aria-expanded="false" aria-controls="' . esc_attr($submenu_id) . '" data-mobile-submenu-toggle aria-label="' . esc_attr('Open ' . $item->title . ' submenu') . '" title="' . esc_attr('Open ' . $item->title . ' submenu') . '">';
        $output .= summit_get_svg_icon('chevron-down', ['class' => 'chevron']);
        $output .= '</button>';
      } else {
        // Regular link
        $atts = [];
        $atts['href'] = !empty($item->url) ? $item->url : '';

        $attributes = '';
        foreach ($atts as $attr => $value) {
          if (!empty($value)) {
            $value = ('href' === $attr) ? esc_url($value) : esc_attr($value);
            $attributes .= ' ' . $attr . '="' . $value . '"';
          }
        }

        $output .= '<a' . $attributes . '>';
        $output .= esc_html($item->title);
        $output .= '</a>';
      }

    } elseif ($depth === 1) {
      // Mobile category
      $output .= '<div class="mobile-submenu-column">';

      $atts = [];
      $atts['href'] = !empty($item->url) ? $item->url : '';

      $attributes = '';
      foreach ($atts as $attr => $value) {
        if (!empty($value)) {
          $value = ('href' === $attr) ? esc_url($value) : esc_attr($value);
          $attributes .= ' ' . $attr . '="' . $value . '"';
        }
      }

      $output .= '<a' . $attributes . '>';
      $output .= esc_html($item->title);
      $output .= '</a>';

    } elseif ($depth === 2) {
      // Mobile link under category
      $output .= '<li>';

      $atts = [];
      $atts['href'] = !empty($item->url) ? $item->url : '';

      $attributes = '';
      foreach ($atts as $attr => $value) {
        if (!empty($value)) {
          $value = ('href' === $attr) ? esc_url($value) : esc_attr($value);
          $attributes .= ' ' . $attr . '="' . $value . '"';
        }
      }

      $output .= '<a' . $attributes . '>';
      $output .= esc_html($item->title);
      $output .= '</a>';
    }
  }

  /**
   * Ends the element output, if needed.
   *
   * @param string   $output Used to append additional content (passed by reference).
   * @param WP_Post  $item   Page data object. Not used.
   * @param int      $depth  Depth of page. Not Used.
   * @param stdClass $args   An object of wp_nav_menu() arguments.
   */
  public function end_el(&$output, $item, $depth = 0, $args = null) {
    if ($this->menu_type === 'desktop' && $depth === 1) {
      // Close mega-menu-column div
      $output .= '</div>';
    } elseif ($this->menu_type === 'mobile' && $depth === 1) {
      // Close mobile-submenu-column div
      $output .= '</div>';
    } else {
      $output .= '</li>';
    }
  }
}
