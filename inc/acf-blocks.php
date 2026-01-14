<?php
/*------------------------------------*\
  ACF Blocks
\*------------------------------------*/
/** Add custom blocks via ACF to theme */

function register_acf_blocks() {
  $blocks = [
    get_template_directory() . '/blocks/accordion',
    get_template_directory() . '/blocks/affiliations',
    get_template_directory() . '/blocks/breadcrumbs',
    get_template_directory() . '/blocks/bullet_group',
    get_template_directory() . '/blocks/cta',
    get_template_directory() . '/blocks/form',
    get_template_directory() . '/blocks/content_banner',
    get_template_directory() . '/blocks/hero_banner',
    get_template_directory() . '/blocks/mini_banner',
    get_template_directory() . '/blocks/posts_loop',
    get_template_directory() . '/blocks/services_loop',
    get_template_directory() . '/blocks/team_loop',
    get_template_directory() . '/blocks/triple_cards'
  ];

  foreach ($blocks as $block_path) {
    register_block_type($block_path);
  }
}
add_action( 'init', 'register_acf_blocks' );