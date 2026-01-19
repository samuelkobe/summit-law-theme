<?php
/**
 * Helper Functions
 */

/**
 * Get the custom logo URL
 */
if (!function_exists('summit_get_logo_url')) {
  function summit_get_logo_url() {
    $custom_logo_id = get_theme_mod('custom_logo');
    $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
    return $logo ? $logo[0] : '';
  }
}

/**
 * Get the primary logo image tag (without the wrapping link)
 */
if (!function_exists('summit_get_primary_logo')) {
  function summit_get_primary_logo($class = '') {
    $custom_logo_id = get_theme_mod('custom_logo');
    if (!$custom_logo_id) {
      return '';
    }

    $alt_text = get_post_meta($custom_logo_id, '_wp_attachment_image_alt', true);
    if (!$alt_text) {
      $alt_text = get_bloginfo('name');
    }

    return wp_get_attachment_image($custom_logo_id, 'full', false, [
      'class' => 'custom-logo' . ($class ? ' ' . $class : ''),
      'alt' => $alt_text,
    ]);
  }
}

/**
 * Get the alternate logo URL
 */
if (!function_exists('summit_get_alt_logo_url')) {
  function summit_get_alt_logo_url() {
    $alt_logo_id = get_theme_mod('alt_logo');
    if (!$alt_logo_id) {
      return '';
    }
    $logo = wp_get_attachment_image_src($alt_logo_id, 'full');
    return $logo ? $logo[0] : '';
  }
}

/**
 * Get the alternate logo image tag
 */
if (!function_exists('summit_get_alt_logo')) {
  function summit_get_alt_logo($class = '') {
    $alt_logo_id = get_theme_mod('alt_logo');
    if (!$alt_logo_id) {
      return '';
    }

    $alt_text = get_post_meta($alt_logo_id, '_wp_attachment_image_alt', true);
    if (!$alt_text) {
      $alt_text = get_bloginfo('name') . ' Logo';
    }

    return wp_get_attachment_image($alt_logo_id, 'full', false, [
      'class' => $class,
      'alt' => $alt_text,
    ]);
  }
}

/**
 * Format phone number for tel: link
 */
if (!function_exists('summit_format_phone_link')) {
  function summit_format_phone_link($phone) {
    return str_replace([' ', '-', '(', ')'], '', $phone);
  }
}

/**
 * Truncate text to a specific length
 */
if (!function_exists('summit_truncate_text')) {
  function summit_truncate_text($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
      return $text;
    }
    return substr($text, 0, $length) . $suffix;
  }
}

/**
 * Get reading time estimate
 */
if (!function_exists('summit_get_reading_time')) {
  function summit_get_reading_time($post_id = null) {
    if (!$post_id) {
      $post_id = get_the_ID();
    }

    $content = get_post_field('post_content', $post_id);
    $word_count = str_word_count(strip_tags($content));
    $reading_time = ceil($word_count / 200); // Average reading speed: 200 words per minute

    return $reading_time . ' min read';
  }
}
