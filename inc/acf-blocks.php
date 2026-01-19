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
    get_template_directory() . '/blocks/content_group',
    get_template_directory() . '/blocks/hero_banner',
    get_template_directory() . '/blocks/hero_home_page',
    get_template_directory() . '/blocks/mini_banner',
    get_template_directory() . '/blocks/posts_loop',
    get_template_directory() . '/blocks/service_fields_child',
    get_template_directory() . '/blocks/service_fields_parent',
    get_template_directory() . '/blocks/services_areas_loop',
    get_template_directory() . '/blocks/services_loop',
    get_template_directory() . '/blocks/team_loop',
    get_template_directory() . '/blocks/triple_cards',
  ];

  foreach ($blocks as $block_path) {
    register_block_type($block_path);
  }
}
add_action( 'init', 'register_acf_blocks' );

/**
 * Restrict certain blocks to specific post types.
 *
 * ACF doesn't pass through the postTypes property from block.json,
 * so we need to filter allowed blocks manually.
 */
function summit_restrict_blocks_by_post_type( $allowed_block_types, $editor_context ) {
  // Only apply restrictions when editing a post
  if ( empty( $editor_context->post ) ) {
    return $allowed_block_types;
  }

  $post_type = $editor_context->post->post_type;

  // Define blocks that should be restricted to specific post types
  $restricted_blocks = [
    'acf/service-fields-child' => [ 'service' ],
    'acf/service-fields-parent' => [ 'service' ],
    'acf/services-areas-loop' => [ 'service' ],
    // Add more blocks here as needed:
    // 'acf/some-block' => [ 'post', 'page' ],
  ];

  // If all blocks are allowed (true), get the full list first
  if ( $allowed_block_types === true ) {
    $allowed_block_types = array_keys( WP_Block_Type_Registry::get_instance()->get_all_registered() );
  }

  // Remove restricted blocks that don't belong to this post type
  foreach ( $restricted_blocks as $block_name => $allowed_post_types ) {
    if ( ! in_array( $post_type, $allowed_post_types, true ) ) {
      $allowed_block_types = array_filter( $allowed_block_types, function( $block ) use ( $block_name ) {
        return $block !== $block_name;
      });
    }
  }

  return array_values( $allowed_block_types );
}
add_filter( 'allowed_block_types_all', 'summit_restrict_blocks_by_post_type', 10, 2 );