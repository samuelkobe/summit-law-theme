<?php
/**
 * Vite Development Server & Production Build Integration
 */

/**
 * Check if Vite dev server is running
 *
 * Uses WP_ENVIRONMENT_TYPE to determine mode.
 * In Local by Flywheel, Docker can't reach localhost:5173,
 * so we rely on manifest file existence instead.
 */
function is_vite_dev_server_running() {
  // Method 1: Check for environment variable or constant
  if (defined('VITE_DEV_MODE') && VITE_DEV_MODE === true) {
    return true;
  }

  // Method 2: If no manifest file exists, assume dev mode
  $manifest_path = get_template_directory() . '/dist/.vite/manifest.json';
  if (!file_exists($manifest_path)) {
    // No production build exists, must be in dev mode
    return true;
  }

  // Method 3: Check if WP environment is set to 'development'
  if (function_exists('wp_get_environment_type') && wp_get_environment_type() === 'development') {
    // Could be dev mode, but only if no recent manifest
    $manifest_age = time() - filemtime($manifest_path);
    // If manifest is older than 1 hour, likely using dev server
    if ($manifest_age > 3600) {
      return true;
    }
  }

  return false;
}

/**
 * Get asset URL from Vite manifest (production) or dev server
 */
function get_vite_asset($entry = 'src/main.js') {
  $is_dev = is_vite_dev_server_running();

  if ($is_dev) {
    // Development mode - use Vite dev server
    return 'http://localhost:5173/' . $entry;
  }

  // Production mode - use manifest.json
  $manifest_path = get_template_directory() . '/dist/.vite/manifest.json';

  if (!file_exists($manifest_path)) {
    error_log('Vite manifest not found. Run `npm run build` first.');
    return '';
  }

  $manifest = json_decode(file_get_contents($manifest_path), true);

  if (isset($manifest[$entry])) {
    return get_template_directory_uri() . '/dist/' . $manifest[$entry]['file'];
  }

  return '';
}

/**
 * Enqueue Vite assets
 */
function summit_enqueue_vite_assets() {
  $is_dev = is_vite_dev_server_running();

  // For Docker/Local by Flywheel, use network IP instead of localhost
  // You can also define VITE_SERVER constant in wp-config.php
  $vite_server = defined('VITE_SERVER') ? VITE_SERVER : 'http://localhost:5173';

  if ($is_dev) {
    // Development mode - load Vite client and entry point
    wp_enqueue_script(
      'vite-client',
      $vite_server . '/@vite/client',
      [],
      null,
      false
    );
    wp_script_add_data('vite-client', 'type', 'module');

    wp_enqueue_script(
      'summit-vite-main',
      $vite_server . '/src/main.js',
      ['vite-client'],
      null,
      false
    );
    wp_script_add_data('summit-vite-main', 'type', 'module');
  } else {
    // Production mode - load built assets
    $manifest_path = get_template_directory() . '/dist/.vite/manifest.json';

    if (!file_exists($manifest_path)) {
      error_log('Vite manifest not found. Run `npm run build` first.');
      return;
    }

    $manifest = json_decode(file_get_contents($manifest_path), true);

    // Enqueue main JS
    if (isset($manifest['src/main.js'])) {
      wp_enqueue_script(
        'summit-main',
        get_template_directory_uri() . '/dist/' . $manifest['src/main.js']['file'],
        [],
        null,
        true
      );
      wp_script_add_data('summit-main', 'type', 'module');

      // Enqueue CSS if it exists in manifest
      if (isset($manifest['src/main.js']['css'])) {
        foreach ($manifest['src/main.js']['css'] as $css_file) {
          wp_enqueue_style(
            'summit-main-css',
            get_template_directory_uri() . '/dist/' . $css_file,
            [],
            null
          );
        }
      }
    }
  }

  // Localize script for AJAX
  if (wp_script_is('summit-main', 'enqueued') || wp_script_is('summit-vite-main', 'enqueued')) {
    $script_handle = $is_dev ? 'summit-vite-main' : 'summit-main';
    wp_localize_script($script_handle, 'summitAjax', [
      'ajaxurl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('summit-nonce'),
    ]);
  }
}
add_action('wp_enqueue_scripts', 'summit_enqueue_vite_assets');

/**
 * Add type="module" to Vite scripts
 */
function summit_add_type_module($tag, $handle, $src) {
  if (in_array($handle, ['vite-client', 'summit-vite-main', 'summit-main', 'vite-client-editor', 'summit-editor-vite'])) {
    $tag = str_replace(' src=', ' type="module" src=', $tag);
  }
  return $tag;
}
add_filter('script_loader_tag', 'summit_add_type_module', 10, 3);

/**
 * Make main CSS non-render-blocking
 *
 * Uses media="print" with onload swap technique to load CSS asynchronously.
 * Includes noscript fallback for users without JavaScript.
 */
function summit_async_css($tag, $handle, $href, $media) {
  // Only apply to main CSS on frontend (not admin)
  if ($handle !== 'summit-main-css' || is_admin()) {
    return $tag;
  }

  // Replace with async loading pattern
  $async_tag = sprintf(
    '<link rel="preload" href="%s" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">' . "\n" .
    '<noscript><link rel="stylesheet" href="%s"></noscript>' . "\n",
    esc_url($href),
    esc_url($href)
  );

  return $async_tag;
}
add_filter('style_loader_tag', 'summit_async_css', 10, 4);

/**
 * Enqueue Vite assets in the block editor
 *
 * For simplicity, we always load the built CSS file (not dev server)
 * Run `npm run build` to update editor styles during development
 */
function summit_enqueue_editor_assets() {
  // Only load in admin/editor context, not on frontend
  if (!is_admin()) {
    return;
  }

  // Always use the built CSS file for the editor
  // This is simpler and more reliable than trying to use Vite dev server in iframe
  $css_file = get_template_directory() . '/dist/css/main.css';

  if (file_exists($css_file)) {
    wp_enqueue_style(
      'summit-editor-css',
      get_template_directory_uri() . '/dist/css/main.css',
      [],
      filemtime($css_file)
    );

    // Add inline CSS for editor customizations
    $editor_overrides = '
      /* Disable pointer events on links inside ACF blocks in the editor */
      [data-type^="acf/"] a {
        pointer-events: none;
        cursor: default;
      }

      /* Increase editor width for better content editing */
      .wp-block {
        max-width: 1400px;
      }

      /* Increase width for wide-aligned blocks */
      .wp-block[data-align="wide"] {
        max-width: 1600px;
      }

      /* Full-width blocks remain 100% */
      .wp-block[data-align="full"] {
        max-width: none;
      }

      /* Override WordPress block editor default image styles for CORE blocks only */
      /* ACF blocks should NOT get these overrides - they use their own Tailwind styling */
      .block-editor__container [data-type="core/image"] img {
        max-width: none !important;
        height: auto !important;
      }
    ';

    wp_add_inline_style('summit-editor-css', $editor_overrides);
  }
}
add_action('enqueue_block_editor_assets', 'summit_enqueue_editor_assets');

/**
 * Remove old enqueue function to avoid conflicts
 */
remove_action('wp_enqueue_scripts', 'summit_enqueue_assets');

/**
 * Add preload hints for critical fonts
 *
 * Preloading fonts improves LCP by allowing the browser to discover
 * font files before CSS is parsed. Only runs when dist/fonts exists.
 */
function summit_add_font_preloads() {
  // Check if fonts directory exists (more reliable than dev server check)
  $fonts_dir = get_template_directory() . '/dist/fonts';
  if (!is_dir($fonts_dir)) {
    return;
  }

  $fonts = [
    'HankenGrotesk-VariableFont_wght.woff2',
    'PPMuseum-Regular.woff2',
  ];

  foreach ($fonts as $font) {
    $font_path = $fonts_dir . '/' . $font;
    if (file_exists($font_path)) {
      $font_url = get_template_directory_uri() . '/dist/fonts/' . $font;
      echo '<link rel="preload" href="' . esc_url($font_url) . '" as="font" type="font/woff2" crossorigin="anonymous">' . "\n";
    }
  }
}
add_action('wp_head', 'summit_add_font_preloads', 1);

/**
 * Add critical inline CSS
 *
 * Prevents FOUC when main CSS loads asynchronously.
 * Includes font-face declarations and essential layout styles.
 */
function summit_add_critical_css() {
  // Only add on frontend
  if (is_admin()) {
    return;
  }

  $fonts_uri = get_template_directory_uri() . '/dist/fonts';
  ?>
  <style id="summit-critical-css">
    /* Critical font-face declarations */
    @font-face {
      font-family: "Hanken Grotesk";
      src: url("<?php echo esc_url($fonts_uri); ?>/HankenGrotesk-VariableFont_wght.woff2") format("woff2");
      font-weight: 100 900;
      font-style: normal;
      font-display: swap;
    }
    @font-face {
      font-family: "PP Museum";
      src: url("<?php echo esc_url($fonts_uri); ?>/PPMuseum-Regular.woff2") format("woff2");
      font-weight: 400;
      font-style: normal;
      font-display: swap;
    }
    /* Critical layout styles */
    *, *::before, *::after { box-sizing: border-box; }
    html { line-height: 1.5; -webkit-text-size-adjust: 100%; }
    body {
      margin: 0;
      font-family: "Hanken Grotesk", system-ui, -apple-system, sans-serif;
      background-color: #fff;
      color: #1a1a1a;
    }
    img, video { max-width: 100%; height: auto; }
    /* Prevent layout shift for header */
    [data-header] { min-height: 80px; }
    /* Hide content until CSS loads to prevent FOUC */
    .no-js body { visibility: visible; }
  </style>
  <?php
}
add_action('wp_head', 'summit_add_critical_css', 2);
