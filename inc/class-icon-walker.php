<?php
/**
 * Custom Walker for Menu Icons
 *
 * Extends Walker_Nav_Menu to inject SVG icons before menu item links
 * based on CSS class matching with ACF icon keynames.
 *
 * @package Summit_Law_Theme
 */

class Summit_Icon_Walker extends Walker_Nav_Menu {

  /**
   * Menu ID for ACF field retrieval
   * @var int
   */
  private $menu_id;

  /**
   * Array of icons keyed by CSS class
   * @var array
   */
  private $icons_data = [];

  /**
   * Constructor
   *
   * @param int $menu_id WordPress menu ID
   */
  public function __construct($menu_id = null) {
    $this->menu_id = $menu_id;

    if ($menu_id) {
      $this->icons_data = $this->get_menu_icons();
    }
  }

  /**
   * Retrieve icons from ACF fields
   *
   * @return array Associative array of keyname => svg_code
   */
  private function get_menu_icons() {
    $icons = [];

    // Check if ACF function exists
    if (!function_exists('have_rows')) {
      return $icons;
    }

    // Check if icons are enabled for this menu
    if (!have_rows('menu_icons_settings', 'term_' . $this->menu_id)) {
      return $icons;
    }

    while (have_rows('menu_icons_settings', 'term_' . $this->menu_id)) {
      the_row();

      // Check if icons toggle is enabled
      $icons_toggle = get_sub_field('icons_toggle');

      if (!$icons_toggle) {
        return $icons;
      }

      // Get icons from repeater
      if (have_rows('icons')) {
        while (have_rows('icons')) {
          the_row();

          $keyname = get_sub_field('keyname');
          $svg = get_sub_field('svg_code');

          if ($keyname && $svg) {
            // Sanitize keyname to match WordPress class format
            $clean_keyname = sanitize_html_class($keyname);
            $icons[$clean_keyname] = $svg;
          }
        }
      }
    }

    return $icons;
  }

  /**
   * Sanitize SVG code
   *
   * @param string $svg Raw SVG code
   * @return string Sanitized SVG
   */
  private function sanitize_svg($svg) {
    $allowed_tags = [
      'svg' => [
        'xmlns' => [],
        'width' => [],
        'height' => [],
        'viewbox' => [],
        'viewBox' => [],
        'fill' => [],
        'class' => [],
        'aria-hidden' => [],
        'role' => [],
      ],
      'path' => [
        'd' => [],
        'fill' => [],
        'stroke' => [],
        'stroke-width' => [],
        'class' => [],
      ],
      'circle' => [
        'cx' => [],
        'cy' => [],
        'r' => [],
        'fill' => [],
        'stroke' => [],
        'stroke-width' => [],
        'class' => [],
      ],
      'rect' => [
        'd' => [],
        'x' => [],
        'y' => [],
        'width' => [],
        'height' => [],
        'fill' => [],
        'stroke' => [],
        'stroke-width' => [],
        'class' => [],
      ],
      'line' => [
        'x1' => [],
        'y1' => [],
        'x2' => [],
        'y2' => [],
        'stroke' => [],
        'stroke-width' => [],
        'class' => [],
      ],
      'polyline' => [
        'points' => [],
        'fill' => [],
        'stroke' => [],
        'stroke-width' => [],
        'class' => [],
      ],
      'polygon' => [
        'points' => [],
        'fill' => [],
        'stroke' => [],
        'stroke-width' => [],
        'class' => [],
      ],
      'g' => [
        'fill' => [],
        'stroke' => [],
        'class' => [],
      ],
      'defs' => [],
      'style' => [],
    ];

    return wp_kses($svg, $allowed_tags);
  }

  /**
   * Find matching icon for menu item
   *
   * @param array $classes Menu item CSS classes
   * @return string|null SVG code or null if no match
   */
  private function find_matching_icon($classes) {
    if (empty($this->icons_data) || empty($classes)) {
      return null;
    }

    // Check each class for a match
    foreach ($classes as $class) {
      $clean_class = sanitize_html_class($class);

      if (isset($this->icons_data[$clean_class])) {
        return $this->icons_data[$clean_class];
      }
    }

    return null;
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
    $indent = ($depth) ? str_repeat("\t", $depth) : '';

    $classes = empty($item->classes) ? [] : (array) $item->classes;
    $classes[] = 'menu-item-' . $item->ID;

    /**
     * Filters the CSS classes applied to a menu item's list item element.
     *
     * @param string[] $classes Array of the CSS classes that are applied to the menu item's `<li>` element.
     * @param WP_Post  $item    The current menu item.
     * @param stdClass $args    An object of wp_nav_menu() arguments.
     * @param int      $depth   Depth of menu item. Used for padding.
     */
    $class_names = implode(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
    $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

    $output .= $indent . '<li' . $class_names . '>';

    $atts = [];
    $atts['title']  = !empty($item->attr_title) ? $item->attr_title : '';
    $atts['target'] = !empty($item->target) ? $item->target : '';
    $atts['rel']    = !empty($item->xfn) ? $item->xfn : '';
    $atts['href']   = !empty($item->url) ? $item->url : '';

    /**
     * Filters the HTML attributes applied to a menu item's anchor element.
     *
     * @param array $atts {
     *     The HTML attributes applied to the menu item's `<a>` element, empty strings are ignored.
     *
     *     @type string $title  Title attribute.
     *     @type string $target Target attribute.
     *     @type string $rel    The rel attribute.
     *     @type string $href   The href attribute.
     * }
     * @param WP_Post  $item  The current menu item.
     * @param stdClass $args  An object of wp_nav_menu() arguments.
     * @param int      $depth Depth of menu item. Used for padding.
     */
    $atts = apply_filters('nav_menu_link_attributes', $atts, $item, $args, $depth);

    $attributes = '';
    foreach ($atts as $attr => $value) {
      if (!empty($value)) {
        $value = ('href' === $attr) ? esc_url($value) : esc_attr($value);
        $attributes .= ' ' . $attr . '="' . $value . '"';
      }
    }

    // Find matching icon
    $icon_svg = $this->find_matching_icon($item->classes);

    /** This filter is documented in wp-includes/post-template.php */
    $title = apply_filters('the_title', $item->title, $item->ID);

    /**
     * Filters a menu item's title.
     *
     * @param string   $title The menu item's title.
     * @param WP_Post  $item  The current menu item.
     * @param stdClass $args  An object of wp_nav_menu() arguments.
     * @param int      $depth Depth of menu item. Used for padding.
     */
    $title = apply_filters('nav_menu_item_title', $title, $item, $args, $depth);

    // Build link output with optional icon
    $item_output = $args->before;
    $item_output .= '<a' . $attributes . '>';

    // Inject SVG icon before link text if match found
    if ($icon_svg) {
      $item_output .= $this->sanitize_svg($icon_svg);
    }

    $item_output .= $args->link_before . $title . $args->link_after;
    $item_output .= '</a>';
    $item_output .= $args->after;

    /**
     * Filters a menu item's starting output.
     *
     * @param string   $item_output The menu item's starting HTML output.
     * @param WP_Post  $item        Menu item data object.
     * @param int      $depth       Depth of menu item. Used for padding.
     * @param stdClass $args        An object of wp_nav_menu() arguments.
     */
    $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
  }
}
