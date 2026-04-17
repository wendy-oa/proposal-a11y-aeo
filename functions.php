<?php
/**
 * Theme Functions — OA Schema Bootstrap
 *
 * Loads the schema system and registers the wp_head dispatcher that routes
 * each post type to its dedicated output function.
 *
 * Load order (order matters — each file depends on the one above):
 *   1. config.php      — defines all OA_SCHEMA_* constants
 *   2. helpers.php     — shared node-builder functions (_oa_schema_*)
 *   3. article.php     — oa_schema_output_article()
 *   4. news.php        — oa_schema_output_news()
 *   5. guide.php       — oa_schema_output_guide()
 *   6. meta-boxes.php  — FAQ Items + Defined Terms admin meta boxes
 *   7. author-meta.php — extended author profile fields
 *
 * To add a new post type:
 *   1. Create inc/schema/{post-type}.php with an oa_schema_output_{post_type}() function.
 *   2. require_once it below.
 *   3. Add a new 'case' to the match expression in the dispatcher.
 *   4. Add the post type to OA_SCHEMA_POST_TYPE_MAP in config.php.
 *
 * @package OA_Schema
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// =============================================================================
// Schema system — load in dependency order.
// =============================================================================

require_once get_template_directory() . '/inc/schema/config.php';      // constants
require_once get_template_directory() . '/inc/schema/helpers.php';     // shared node builders

require_once get_template_directory() . '/inc/schema/article.php';     // oa_schema_output_article()
require_once get_template_directory() . '/inc/schema/news.php';        // oa_schema_output_news()
require_once get_template_directory() . '/inc/schema/guide.php';       // oa_schema_output_guide()

require_once get_template_directory() . '/inc/schema/meta-boxes.php';  // FAQ + DefinedTerm meta boxes
require_once get_template_directory() . '/inc/schema/author-meta.php'; // author profile fields

// =============================================================================
// wp_head dispatcher
// =============================================================================

/**
 * Routes the current singular post to its page-type schema function.
 *
 * Priority 5 — runs early in <head>, before most plugin/theme scripts,
 * so validators and crawlers encounter the schema before other markup.
 *
 * Silently does nothing on:
 *   - Archives, taxonomy pages, search results, the homepage
 *   - Singular post types not listed in the match expression
 */
add_action( 'wp_head', static function (): void {

    if ( ! is_singular() ) {
        return;
    }

    $post = get_queried_object();
    if ( ! $post instanceof WP_Post ) {
        return;
    }

    // Add a new 'case' here when you add a new post type.
    // Each case calls the single dedicated function for that page type.
    match ( $post->post_type ) {
        'post',
        'article' => oa_schema_output_article( $post ),
        'news'    => oa_schema_output_news( $post ),
        'guide'   => oa_schema_output_guide( $post ),
        default   => null,  // unknown post types: silently omit schema
    };

}, 5 );

// =============================================================================
// Add your other theme functions below this line.
// =============================================================================
