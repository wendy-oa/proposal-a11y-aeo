<?php
/**
 * Schema Helper Functions
 *
 * Shared utility and node-builder functions called by every page-type
 * output function.  All are prefixed _oa_schema_ to signal they are
 * internal helpers — call them from the page-type files, not directly
 * from templates or functions.php.
 *
 * Functions in this file
 * ──────────────────────
 * Utilities
 *   _oa_schema_emit( array $graph ): void
 *   _oa_schema_clean( array $node ): array
 *
 * Field helpers (return scalar / array values, NOT full nodes)
 *   _oa_schema_description( WP_Post $post ): string
 *   _oa_schema_image( WP_Post $post ): ?array
 *   _oa_schema_primary_category( WP_Post $post ): string
 *   _oa_schema_keywords( WP_Post $post ): string
 *   _oa_schema_term_refs( WP_Post $post ): array
 *
 * Node builders (return a complete schema node ready for the @graph)
 *   _oa_schema_node_organization(): array
 *   _oa_schema_node_person( WP_Post $post ): array
 *   _oa_schema_node_webpage( WP_Post $post ): array
 *   _oa_schema_node_breadcrumb( WP_Post $post ): array
 *   _oa_schema_node_faq( WP_Post $post ): ?array
 *   _oa_schema_node_defined_terms( WP_Post $post ): array
 *
 * @package OA_Schema
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// =============================================================================
// Utilities
// =============================================================================

/**
 * Wraps a graph array in a schema.org @context and echoes the <script> block.
 * Every page-type function ends by calling this.
 *
 * @param array<int, array<string, mixed>> $graph  Ordered list of schema nodes.
 */
function _oa_schema_emit( array $graph ): void {
    $payload = [
        '@context' => 'https://schema.org',
        '@graph'   => array_values( $graph ),
    ];

    $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;

    echo "\n<script type=\"application/ld+json\">\n";
    echo wp_json_encode( $payload, $flags );
    echo "\n</script>\n";
}

/**
 * Removes null, empty-string, and empty-array values from a flat schema node.
 * Call this when building Article / NewsArticle nodes that have optional fields.
 *
 * @param  array<string, mixed> $node
 * @return array<string, mixed>
 */
function _oa_schema_clean( array $node ): array {
    return array_filter(
        $node,
        static fn( $v ): bool => $v !== null && $v !== '' && $v !== []
    );
}

// =============================================================================
// Field helpers
// =============================================================================

/**
 * Returns the post description used in the 'description' property.
 * Priority: excerpt → first 35 words of raw content.
 *
 * @param  WP_Post $post
 * @return string
 */
function _oa_schema_description( WP_Post $post ): string {
    return has_excerpt( $post )
        ? wp_strip_all_tags( get_the_excerpt( $post ) )
        : wp_trim_words( wp_strip_all_tags( $post->post_content ), 35, '…' );
}

/**
 * Returns an ImageObject array for the post's featured image, or null if none.
 * Width/height are set to the OG-standard 1200×630 as a safe default; update
 * if your theme registers a custom 'schema-image' size with known dimensions.
 *
 * @param  WP_Post $post
 * @return array{@type:string,url:string,width:int,height:int}|null
 */
function _oa_schema_image( WP_Post $post ): ?array {
    $url = get_the_post_thumbnail_url( $post->ID, 'full' );
    if ( ! $url ) {
        return null;
    }
    return [
        '@type'  => 'ImageObject',
        'url'    => $url,
        'width'  => 1200,
        'height' => 630,
    ];
}

/**
 * Returns the name of the post's first assigned category, or empty string.
 * Used for the 'articleSection' property.
 *
 * @param  WP_Post $post
 * @return string
 */
function _oa_schema_primary_category( WP_Post $post ): string {
    $cats = get_the_category( $post->ID );
    return ! empty( $cats ) ? $cats[0]->name : '';
}

/**
 * Returns a comma-joined list of tag names for the 'keywords' property,
 * or an empty string if the post has no tags.
 *
 * @param  WP_Post $post
 * @return string
 */
function _oa_schema_keywords( WP_Post $post ): string {
    $tags = get_the_tags( $post->ID );
    return $tags ? implode( ', ', wp_list_pluck( $tags, 'name' ) ) : '';
}

/**
 * Returns an array of @id references pointing to this post's DefinedTerm nodes.
 * Used in the Article node's 'about' property to cross-link the @graph.
 * Returns an empty array when the post has no defined-term meta.
 *
 * @param  WP_Post $post
 * @return list<array{@id: string}>
 */
function _oa_schema_term_refs( WP_Post $post ): array {
    $raw = get_post_meta( $post->ID, OA_SCHEMA_META_TERMS, true );
    if ( ! is_array( $raw ) || empty( $raw ) ) {
        return [];
    }
    $url = get_permalink( $post );
    return array_map(
        static fn( int $i ): array => [ '@id' => $url . '#term-' . $i ],
        array_keys( $raw )
    );
}

// =============================================================================
// Node builders
// =============================================================================

/**
 * Organization node.
 * Static — identical on every page of the site.
 * Values come from the constants defined in config.php.
 *
 * @return array<string, mixed>
 */
function _oa_schema_node_organization(): array {
    return [
        '@type'       => 'Organization',
        '@id'         => OA_SCHEMA_ORG_URL . '/#organization',
        'name'        => OA_SCHEMA_ORG_NAME,
        'url'         => OA_SCHEMA_ORG_URL,
        'logo'        => [
            '@type'  => 'ImageObject',
            'url'    => OA_SCHEMA_ORG_LOGO_URL,
            'width'  => OA_SCHEMA_ORG_LOGO_W,
            'height' => OA_SCHEMA_ORG_LOGO_H,
        ],
        'description' => OA_SCHEMA_ORG_DESCRIPTION,
        'sameAs'      => OA_SCHEMA_ORG_SAME_AS,
    ];
}

/**
 * Person node for the post's author.
 * Pulls from standard WP user fields + custom meta set via author-meta.php.
 * Optional fields (jobTitle, image, sameAs, description) are omitted when empty.
 *
 * @param  WP_Post $post
 * @return array<string, mixed>
 */
function _oa_schema_node_person( WP_Post $post ): array {
    $author_id  = (int) $post->post_author;
    $author_url = get_author_posts_url( $author_id );

    $job_title  = get_user_meta( $author_id, OA_SCHEMA_UMETA_JOB_TITLE, true );
    $social_raw = get_user_meta( $author_id, OA_SCHEMA_UMETA_SOCIAL_PROFILES, true );
    $social     = is_array( $social_raw ) ? array_values( array_filter( $social_raw ) ) : [];
    $photo_id   = (int) get_user_meta( $author_id, OA_SCHEMA_UMETA_PHOTO_ID, true );
    $photo_url  = $photo_id ? wp_get_attachment_image_url( $photo_id, 'medium' ) : '';

    return _oa_schema_clean( [
        '@type'       => 'Person',
        '@id'         => $author_url . '#person',
        'name'        => get_the_author_meta( 'display_name', $author_id ),
        'url'         => $author_url,
        'description' => get_the_author_meta( 'description', $author_id ) ?: null,
        'jobTitle'    => $job_title ?: null,
        'image'       => $photo_url ? [ '@type' => 'ImageObject', 'url' => $photo_url ] : null,
        'sameAs'      => $social ?: null,
        'worksFor'    => [ '@id' => OA_SCHEMA_ORG_URL . '/#organization' ],
    ] );
}

/**
 * WebPage node.
 * Ties the canonical URL to the breadcrumb trail via @id references.
 *
 * @param  WP_Post $post
 * @return array<string, mixed>
 */
function _oa_schema_node_webpage( WP_Post $post ): array {
    $url = get_permalink( $post );
    return [
        '@type'      => 'WebPage',
        '@id'        => $url . '#webpage',
        'url'        => $url,
        'name'       => get_the_title( $post ),
        'isPartOf'   => [ '@id' => OA_SCHEMA_ORG_URL . '/#organization' ],
        'inLanguage' => 'en-US',
        'breadcrumb' => [ '@id' => $url . '#breadcrumb' ],
    ];
}

/**
 * BreadcrumbList node — three tiers: Home › Archive/Category › Post.
 *
 * Tier 2 resolution order:
 *   1. CPT archive URL (if the post type has has_archive => true)
 *   2. Primary category link (fallback for standard posts or CPTs without archives)
 *
 * @param  WP_Post $post
 * @return array<string, mixed>
 */
function _oa_schema_node_breadcrumb( WP_Post $post ): array {
    $url   = get_permalink( $post );
    $items = [
        [
            '@type'    => 'ListItem',
            'position' => 1,
            'name'     => 'Home',
            'item'     => home_url( '/' ),
        ],
    ];

    // Tier 2: CPT archive preferred; category as fallback.
    $archive = get_post_type_archive_link( $post->post_type );
    if ( $archive ) {
        $pt_obj  = get_post_type_object( $post->post_type );
        $items[] = [
            '@type'    => 'ListItem',
            'position' => 2,
            'name'     => $pt_obj ? $pt_obj->labels->name : ucfirst( $post->post_type ),
            'item'     => $archive,
        ];
    } else {
        $cats = get_the_category( $post->ID );
        if ( ! empty( $cats ) ) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => 2,
                'name'     => $cats[0]->name,
                'item'     => get_category_link( $cats[0]->term_id ),
            ];
        }
    }

    // Tier 3: the post itself — position adjusts automatically.
    $items[] = [
        '@type'    => 'ListItem',
        'position' => count( $items ) + 1,
        'name'     => get_the_title( $post ),
        'item'     => $url,
    ];

    return [
        '@type'           => 'BreadcrumbList',
        '@id'             => $url . '#breadcrumb',
        'itemListElement' => $items,
    ];
}

/**
 * FAQPage node — built from the OA_SCHEMA_META_FAQ post meta key.
 * Returns null (node silently omitted) when no FAQ data exists.
 *
 * Data shape stored in meta:
 *   [ ['question' => '…', 'answer' => '…'], … ]
 *
 * @param  WP_Post $post
 * @return array<string, mixed>|null
 */
function _oa_schema_node_faq( WP_Post $post ): ?array {
    $raw = get_post_meta( $post->ID, OA_SCHEMA_META_FAQ, true );
    if ( ! is_array( $raw ) || empty( $raw ) ) {
        return null;
    }

    $entities = [];
    foreach ( $raw as $item ) {
        $q = sanitize_text_field( $item['question'] ?? '' );
        $a = wp_kses_post( $item['answer'] ?? '' );
        if ( $q && $a ) {
            $entities[] = [
                '@type'          => 'Question',
                'name'           => $q,
                'acceptedAnswer' => [ '@type' => 'Answer', 'text' => $a ],
            ];
        }
    }

    if ( empty( $entities ) ) {
        return null;
    }

    return [
        '@type'      => 'FAQPage',
        '@id'        => get_permalink( $post ) . '#faqpage',
        'mainEntity' => $entities,
    ];
}

/**
 * DefinedTerm nodes — one per entry in the OA_SCHEMA_META_TERMS post meta key.
 * Returns an empty array when no terms exist (nothing is added to the graph).
 *
 * Data shape stored in meta:
 *   [ ['name' => '…', 'description' => '…'], … ]
 *
 * Each term's @id is referenced by the Article node's 'about' property so
 * Google can resolve the full entity graph from a single <script> block.
 *
 * @param  WP_Post $post
 * @return list<array<string, mixed>>
 */
function _oa_schema_node_defined_terms( WP_Post $post ): array {
    $raw = get_post_meta( $post->ID, OA_SCHEMA_META_TERMS, true );
    if ( ! is_array( $raw ) || empty( $raw ) ) {
        return [];
    }

    $url   = get_permalink( $post );
    $nodes = [];

    foreach ( $raw as $i => $term ) {
        $name = sanitize_text_field( $term['name']        ?? '' );
        $desc = wp_kses_post(        $term['description'] ?? '' );
        if ( ! $name || ! $desc ) {
            continue;
        }
        $nodes[] = [
            '@type'            => 'DefinedTerm',
            '@id'              => $url . '#term-' . $i,
            'name'             => $name,
            'description'      => $desc,
            'inDefinedTermSet' => [
                '@type' => 'DefinedTermSet',
                'name'  => OA_SCHEMA_TERMSET_NAME,
                'url'   => OA_SCHEMA_TERMSET_URL,
            ],
        ];
    }

    return $nodes;
}
