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
