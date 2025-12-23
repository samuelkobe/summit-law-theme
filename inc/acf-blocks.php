<?php
/*------------------------------------*\
  ACF Blocks
\*------------------------------------*/
/** Add custom blocks via ACF to theme */

function register_acf_blocks() {
  $blocks = [
    get_template_directory() . '/blocks/starter_template',
    get_template_directory() . '/blocks/cta',
    get_template_directory() . '/blocks/triple_cards',
  ];

  foreach ($blocks as $block_path) {
    register_block_type($block_path);
  }
}
add_action( 'init', 'register_acf_blocks' );