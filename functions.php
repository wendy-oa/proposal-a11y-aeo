<?php
/**
 * Theme Functions
 *
 * This file bootstraps the OA Schema system.  Drop the four lines below
 * into your existing functions.php (or keep this file as-is if starting
 * from scratch).
 *
 * Load order matters:
 *   1. config.php    — defines all constants; must come first.
 *   2. class-oa-schema.php — references the constants; must come second.
 *   3. meta-boxes.php   — uses OA_SCHEMA_POST_TYPE_MAP and meta-key constants.
 *   4. author-meta.php  — uses user-meta-key constants.
 *
 * @package OA_Schema
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ---------------------------------------------------------------------------
// 1. Schema system
// ---------------------------------------------------------------------------

require_once get_template_directory() . '/inc/schema/config.php';
require_once get_template_directory() . '/inc/schema/class-oa-schema.php';
require_once get_template_directory() . '/inc/schema/meta-boxes.php';
require_once get_template_directory() . '/inc/schema/author-meta.php';

/**
 * Output the JSON-LD block into <head> for every singular post / page
 * whose post type is listed in OA_SCHEMA_POST_TYPE_MAP.
 *
 * Priority 5 ensures the block appears early in <head>,
 * before most theme or plugin scripts.
 */
add_action( 'wp_head', [ 'OA_Schema', 'output' ], 5 );

// ---------------------------------------------------------------------------
// Add your other theme functions below this line.
// ---------------------------------------------------------------------------
