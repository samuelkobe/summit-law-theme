<?php
/**
 * SVG Icons for Navigation
 *
 * @package Summit_Law_Theme
 */

if (!function_exists('summit_get_svg_icon')) {
  /**
   * Get SVG icon by name
   *
   * @param string $icon_name Name of the icon
   * @param array  $args      Additional arguments (class, aria-hidden, etc.)
   * @return string           SVG markup
   */
  function summit_get_svg_icon($icon_name, $args = []) {
    $defaults = [
      'class' => '',
      'aria-hidden' => 'true',
      'width' => '24',
      'height' => '24',
    ];

    $args = wp_parse_args($args, $defaults);

    $class = $args['class'] ? ' class="' . esc_attr($args['class']) . ' hover:fill-brand-black hover:shadow-lg transition-all duration-300"' : '';
    $aria_hidden = $args['aria-hidden'] ? ' aria-hidden="' . esc_attr($args['aria-hidden']) . '"' : '';
    $width = $args['width'] ? ' width="' . esc_attr($args['width']) . '"' : '';
    $height = $args['height'] ? ' height="' . esc_attr($args['height']) . '"' : '';

    $icons = [
      'chevron-down' => '<svg xmlns="http://www.w3.org/2000/svg"' . $width . $height . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"' . $class . $aria_hidden . '><polyline points="6 9 12 15 18 9"></polyline></svg>',

      'search' => '<svg xmlns="http://www.w3.org/2000/svg"' . $width . $height . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"' . $class . $aria_hidden . '><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>',

      'close' => '<svg xmlns="http://www.w3.org/2000/svg"' . $width . $height . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"' . $class . $aria_hidden . '><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>',

      'menu' => '<svg xmlns="http://www.w3.org/2000/svg"' . $width . $height . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"' . $class . $aria_hidden . '><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>',

      'linkedin' => '<svg xmlns="http://www.w3.org/2000/svg"' . $width . $height . ' viewBox="0 0 24 24" fill="currentColor"' . $class . $aria_hidden . '><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>',

      'facebook' => '<svg xmlns="http://www.w3.org/2000/svg"' . $width . $height . ' viewBox="0 0 24 24" fill="currentColor"' . $class . $aria_hidden . '><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-3 7h-1.924c-.615 0-1.076.252-1.076.889v1.111h3l-.238 3h-2.762v8h-3v-8h-2v-3h2v-1.923c0-2.022 1.064-3.077 3.461-3.077h2.539v3z"/></svg>',

      'x' => '<svg xmlns="http://www.w3.org/2000/svg"' . $width . $height . ' viewBox="0 0 24 24" fill="currentColor"' . $class . $aria_hidden . '><path d="M19,0H5C2.2,0,0,2.2,0,5v14c0,2.8,2.2,5,5,5h14c2.8,0,5-2.2,5-5V5c0-2.8-2.2-5-5-5ZM14.7,19.1l-3.9-5.5-4.7,5.5h-1.5l5.5-6.4-5.4-7.7h4.7l3.3,4.7,4-4.7h1.5l-4.8,5.7,6,8.5h-4.7Z"/><polygon points="6.9 6.1 15.3 17.9 17.1 17.9 8.7 6.1"/></svg>',

      'twitter' => '<svg xmlns="http://www.w3.org/2000/svg"' . $width . $height . ' viewBox="0 0 24 24" fill="currentColor"' . $class . $aria_hidden . '><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-.139 9.237c.209 4.617-3.234 9.765-9.33 9.765-1.854 0-3.579-.543-5.032-1.475 1.742.205 3.48-.278 4.86-1.359-1.437-.027-2.649-.976-3.066-2.28.515.098 1.021.069 1.482-.056-1.579-.317-2.668-1.739-2.633-3.26.442.246.949.394 1.486.411-1.461-.977-1.875-2.907-1.016-4.383 1.619 1.986 4.038 3.293 6.766 3.43-.479-2.053 1.08-4.03 3.199-4.03.943 0 1.797.398 2.395 1.037.748-.147 1.451-.42 2.086-.796-.246.767-.766 1.41-1.443 1.816.664-.08 1.297-.256 1.885-.517-.439.656-.996 1.234-1.639 1.697z"/></svg>',

      'instagram' => '<svg xmlns="http://www.w3.org/2000/svg"' . $width . $height . ' viewBox="0 0 24 24" fill="currentColor"' . $class . $aria_hidden . '><path d="M15.233 5.488c-.843-.038-1.097-.046-3.233-.046s-2.389.008-3.232.046c-2.17.099-3.181 1.127-3.279 3.279-.039.844-.048 1.097-.048 3.233s.009 2.389.047 3.233c.099 2.148 1.106 3.18 3.279 3.279.843.038 1.097.047 3.233.047 2.137 0 2.39-.008 3.233-.046 2.17-.099 3.18-1.129 3.279-3.279.038-.844.046-1.097.046-3.233s-.008-2.389-.046-3.232c-.099-2.153-1.111-3.182-3.279-3.281zm-3.233 10.62c-2.269 0-4.108-1.839-4.108-4.108 0-2.269 1.84-4.108 4.108-4.108s4.108 1.839 4.108 4.108c0 2.269-1.839 4.108-4.108 4.108zm4.271-7.418c-.53 0-.96-.43-.96-.96s.43-.96.96-.96.96.43.96.96-.43.96-.96.96zm-1.604 3.31c0 1.473-1.194 2.667-2.667 2.667s-2.667-1.194-2.667-2.667c0-1.473 1.194-2.667 2.667-2.667s2.667 1.194 2.667 2.667zm4.333-12h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm.952 15.298c-.132 2.909-1.751 4.521-4.653 4.654-.854.039-1.126.048-3.299.048s-2.444-.009-3.298-.048c-2.908-.133-4.52-1.748-4.654-4.654-.039-.853-.048-1.125-.048-3.298 0-2.172.009-2.445.048-3.298.134-2.908 1.748-4.521 4.654-4.653.854-.04 1.125-.049 3.298-.049s2.445.009 3.299.048c2.908.133 4.523 1.751 4.653 4.653.039.854.048 1.127.048 3.299 0 2.173-.009 2.445-.048 3.298z"/></svg>',

      'youtube' => '<svg xmlns="http://www.w3.org/2000/svg"' . $width . $height . ' viewBox="0 0 24 24" fill="currentColor"' . $class . $aria_hidden . '><path d="M10 9.333l5.333 2.662-5.333 2.672v-5.334zm14-4.333v14c0 2.761-2.238 5-5 5h-14c-2.761 0-5-2.239-5-5v-14c0-2.761 2.239-5 5-5h14c2.762 0 5 2.239 5 5zm-4 7c-.02-4.123-.323-5.7-2.923-5.877-2.403-.164-7.754-.163-10.153 0-2.598.177-2.904 1.747-2.924 5.877.02 4.123.323 5.7 2.923 5.877 2.399.163 7.75.164 10.153 0 2.598-.177 2.904-1.747 2.924-5.877z"/></svg>',

      'email' => '<svg xmlns="http://www.w3.org/2000/svg"' . $width . $height . ' viewBox="0 0 24 24" fill="currentColor"' . $class . $aria_hidden . '><path d="M18.1,7H5.9c-.2,0-.3.3-.1.4l5.7,4.7c.3.3.8.3,1.1,0l5.7-4.7c.2-.1,0-.4-.1-.4Z"/><path d="M18.8,8.9l-6.2,5c-.3.3-.8.3-1.1,0l-6.2-5c-.1-.1-.4,0-.4.2v7.8c0,.1.1.2.2.2h13.8c.1,0,.2-.1.2-.2v-7.8c0-.2-.2-.3-.4-.2Z"/><path d="M19,0H5C2.2,0,0,2.2,0,5v14c0,2.8,2.2,5,5,5h14c2.8,0,5-2.2,5-5V5c0-2.8-2.2-5-5-5ZM19.7,18.4H4.3c-.5,0-.9-.4-.9-.9V6.5c0-.5.4-.9.9-.9h15.3c.5,0,.9.4.9.9v11c0,.5-.4.9-.9.9Z"/></svg>',

      'email-alt' => '<svg xmlns="http://www.w3.org/2000/svg"' . $width . $height . ' viewBox="0 0 24 24" fill="#00000000" stroke="currentColor"' . $class . $aria_hidden . '><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>',

      'phone' => '<svg xmlns="http://www.w3.org/2000/svg"' . $width . $height . ' viewBox="0 0 24 24" fill="currentColor"' . $class . $aria_hidden . '><path d="M19,0H5C2.2,0,0,2.2,0,5v14c0,2.8,2.2,5,5,5h14c2.8,0,5-2.2,5-5V5c0-2.8-2.2-5-5-5ZM16.5,20.9C10.9,23.4,2.2,6.5,7.7,3.7l1.6-.8,2.7,5.2-1.6.8c-1.7.9,1.8,7.7,3.5,6.8,0,0,1.6-.8,1.6-.8l2.7,5.2s-1.5.7-1.6.8Z"/></svg>',
      
      'phone-alt' => '<svg xmlns="http://www.w3.org/2000/svg"' . $width . $height . ' viewBox="0 0 24 24"fill="#00000000" stroke="currentColor"' . $class . $aria_hidden . '><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>',

      'fax-alt' => '<svg xmlns="http://www.w3.org/2000/svg"' . $width . $height . ' viewBox="0 0 24 24" fill="currentColor"' . $class . $aria_hidden . '><path d="M8 13v1h2v-1h-2zm2-1v-1h-2v1h2zm1 1v1h2v-1h-2zm2-1v-1h-2v1h2zm1 1v1h2v-1h-2zm2-1v-1h-2v1h2zm3-5h-14v3h14v-3zm-3 11h-8v1h8v-1zm-2 2h-6v1h6v-1zm6-17v-3h-16v3h-3v17h3v4h11.73c1.248 0 3.437-2.604 4.041-4h3.229v-17h-3zm-15-2h14v2h-14v-2zm14 17.771c0 1.418-1.943 1.717-2.62 1.531.453.813.276 2.698-1.109 2.698h-10.271v-6h14v1.771zm2-3.771h-18v-10h18v10z"/></svg>',

      'vcard-alt' => '<svg xmlns="http://www.w3.org/2000/svg"' . $width . $height . ' viewBox="0 0 24 24" fill="#00000000" stroke="currentColor"' . $class . $aria_hidden . '><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path><rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>',
    ];

    if (!isset($icons[$icon_name])) {
      return '';
    }

    return $icons[$icon_name];
  }
}
