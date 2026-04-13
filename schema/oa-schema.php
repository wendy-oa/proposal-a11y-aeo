<?php
/**
 * Outsource Accelerator — Dynamic Schema Markup
 * 
 * HOW IT WORKS:
 * One PHP file. Three schema templates (Article, NewsArticle, HowTo/Guide).
 * WordPress pulls real data from each post automatically.
 * Outputs valid JSON-LD into <head> on every page load.
 * Covers all 5,000+ article, news, and guide pages with zero manual work.
 *
 * SETUP: Add this line to your functions.php:
 *   require_once get_template_directory() . '/inc/oa-schema.php';
 *
 * REQUIREMENTS:
 * - Post types: 'post' (articles/news), 'guide' (or your custom post type slug)
 * - Categories: articles under 'article' or 'news' category
 * - Featured images set per post (recommended for rich results)
 * - Author profiles filled in (display name, description)
 */


// ─────────────────────────────────────────────
// 1. BOOTSTRAP — hooks into <head> automatically
// ─────────────────────────────────────────────

add_action( 'wp_head', 'oa_output_schema_markup', 5 );

function oa_output_schema_markup() {

    // Only run on singular content pages
    if ( ! is_singular() ) return;

    $post_id   = get_the_ID();
    $post_type = get_post_type( $post_id );

    // Detect which schema to render
    if ( oa_is_news_article( $post_id ) ) {
        $schema = oa_schema_news_article( $post_id );

    } elseif ( oa_is_guide( $post_id, $post_type ) ) {
        $schema = oa_schema_guide( $post_id );

    } elseif ( $post_type === 'post' ) {
        $schema = oa_schema_article( $post_id );

    } else {
        return; // Not a content type we handle
    }

    // Always add BreadcrumbList on top of content schema
    $breadcrumb = oa_schema_breadcrumb( $post_id );

    echo "\n<!-- OA Schema Markup -->\n";
    echo '<script type="application/ld+json">' . "\n";
    echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
    echo "\n" . '</script>' . "\n";

    echo '<script type="application/ld+json">' . "\n";
    echo wp_json_encode( $breadcrumb, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
    echo "\n" . '</script>' . "\n";

    // Always output Organization schema once in <head>
    static $org_done = false;
    if ( ! $org_done ) {
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode( oa_schema_organization(), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
        echo "\n" . '</script>' . "\n";
        $org_done = true;
    }
    echo "<!-- /OA Schema Markup -->\n";
}


// ─────────────────────────────────────────────
// 2. PAGE TYPE DETECTORS
// ─────────────────────────────────────────────

/**
 * Detect news articles by category slug or post tag.
 * Update 'news' to match OA's actual news category slug.
 */
function oa_is_news_article( $post_id ) {
    return has_category( 'news', $post_id ) || has_term( 'news', 'category', $post_id );
}

/**
 * Detect guide pages by custom post type or category.
 * Update 'guide' to match OA's actual CPT slug or category.
 */
function oa_is_guide( $post_id, $post_type ) {
    return $post_type === 'guide'
        || has_category( 'guide', $post_id )
        || has_category( 'guides', $post_id );
}


// ─────────────────────────────────────────────
// 3. SCHEMA TEMPLATE: ARTICLE
// Covers: all standard blog/editorial articles
// ─────────────────────────────────────────────

function oa_schema_article( $post_id ) {
    return [
        '@context'         => 'https://schema.org',
        '@type'            => 'Article',
        'headline'         => oa_get_title( $post_id ),
        'description'      => oa_get_description( $post_id ),
        'url'              => get_permalink( $post_id ),
        'datePublished'    => oa_get_date( $post_id, 'published' ),
        'dateModified'     => oa_get_date( $post_id, 'modified' ),
        'author'           => oa_get_author( $post_id ),
        'publisher'        => oa_get_publisher(),
        'image'            => oa_get_image( $post_id ),
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id'   => get_permalink( $post_id ),
        ],
        'keywords'         => oa_get_keywords( $post_id ),
        'articleSection'   => oa_get_categories( $post_id ),
        'inLanguage'       => 'en',
        'isAccessibleForFree' => true,
    ];
}


// ─────────────────────────────────────────────
// 4. SCHEMA TEMPLATE: NEWS ARTICLE
// Covers: all news category posts
// ─────────────────────────────────────────────

function oa_schema_news_article( $post_id ) {
    return [
        '@context'         => 'https://schema.org',
        '@type'            => 'NewsArticle',
        'headline'         => oa_get_title( $post_id ),
        'description'      => oa_get_description( $post_id ),
        'url'              => get_permalink( $post_id ),
        'datePublished'    => oa_get_date( $post_id, 'published' ),
        'dateModified'     => oa_get_date( $post_id, 'modified' ),
        'author'           => oa_get_author( $post_id ),
        'publisher'        => oa_get_publisher(),
        'image'            => oa_get_image( $post_id ),
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id'   => get_permalink( $post_id ),
        ],
        'keywords'         => oa_get_keywords( $post_id ),
        'articleSection'   => 'News',
        'inLanguage'       => 'en',
        'isAccessibleForFree' => true,
        'dateline'         => get_bloginfo( 'name' ),
    ];
}


// ─────────────────────────────────────────────
// 5. SCHEMA TEMPLATE: GUIDE (HowTo + FAQPage)
// Covers: all guide/resource pages
// ─────────────────────────────────────────────

function oa_schema_guide( $post_id ) {

    // Build HowTo steps from H2 headings in post content
    $steps   = oa_extract_headings_as_steps( $post_id );

    // Build FAQPage from any FAQ block in the content
    // Looks for a pattern: <strong>Q:</strong> ... <strong>A:</strong>
    // Update oa_extract_faqs() if OA uses a different FAQ format
    $faqs    = oa_extract_faqs( $post_id );

    $schemas = [];

    // HowTo schema — only if steps were found
    if ( ! empty( $steps ) ) {
        $schemas[] = [
            '@context'    => 'https://schema.org',
            '@type'       => 'HowTo',
            'name'        => oa_get_title( $post_id ),
            'description' => oa_get_description( $post_id ),
            'url'         => get_permalink( $post_id ),
            'image'       => oa_get_image( $post_id ),
            'author'      => oa_get_author( $post_id ),
            'publisher'   => oa_get_publisher(),
            'datePublished' => oa_get_date( $post_id, 'published' ),
            'dateModified'  => oa_get_date( $post_id, 'modified' ),
            'step'        => $steps,
            'inLanguage'  => 'en',
        ];
    }

    // FAQPage schema — only if FAQs were found
    if ( ! empty( $faqs ) ) {
        $schemas[] = [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => $faqs,
        ];
    }

    // Fallback to Article schema if no steps or FAQs detected
    if ( empty( $schemas ) ) {
        return oa_schema_article( $post_id );
    }

    // Return array if multiple schemas, single if one
    return count( $schemas ) === 1 ? $schemas[0] : $schemas;
}


// ─────────────────────────────────────────────
// 6. SCHEMA TEMPLATE: BREADCRUMB
// Applies to all page types automatically
// ─────────────────────────────────────────────

function oa_schema_breadcrumb( $post_id ) {
    $items = [];

    // Home
    $items[] = [
        '@type'    => 'ListItem',
        'position' => 1,
        'name'     => 'Home',
        'item'     => home_url( '/' ),
    ];

    // Primary category (if exists)
    $categories = get_the_category( $post_id );
    if ( ! empty( $categories ) ) {
        $cat = $categories[0];
        $items[] = [
            '@type'    => 'ListItem',
            'position' => 2,
            'name'     => $cat->name,
            'item'     => get_category_link( $cat->term_id ),
        ];
        // Current page
        $items[] = [
            '@type'    => 'ListItem',
            'position' => 3,
            'name'     => get_the_title( $post_id ),
            'item'     => get_permalink( $post_id ),
        ];
    } else {
        // No category — just add current page as position 2
        $items[] = [
            '@type'    => 'ListItem',
            'position' => 2,
            'name'     => get_the_title( $post_id ),
            'item'     => get_permalink( $post_id ),
        ];
    }

    return [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $items,
    ];
}


// ─────────────────────────────────────────────
// 7. SCHEMA TEMPLATE: ORGANIZATION
// Output once per page — identifies OA as publisher
// ─────────────────────────────────────────────

function oa_schema_organization() {
    return [
        '@context'    => 'https://schema.org',
        '@type'       => 'Organization',
        'name'        => 'Outsource Accelerator',
        'url'         => 'https://www.outsourceaccelerator.com',
        'logo'        => [
            '@type' => 'ImageObject',
            'url'   => 'https://www.outsourceaccelerator.com/wp-content/themes/oa/images/logo.png',
            // ↑ Update this path to OA's actual logo URL
        ],
        'sameAs'      => [
            'https://www.linkedin.com/company/outsource-accelerator',
            'https://twitter.com/OutsourceAcc',
            // ↑ Add all official OA social profile URLs here
        ],
        'description' => 'Outsource Accelerator is the world\'s leading outsourcing marketplace and advisory. We provide independent information, advisory, and expert implementation of BPO for businesses globally.',
    ];
}


// ─────────────────────────────────────────────
// 8. DATA HELPERS
// These pull real WordPress data for each post
// ─────────────────────────────────────────────

/** Post title — falls back to site name */
function oa_get_title( $post_id ) {
    return get_the_title( $post_id ) ?: get_bloginfo( 'name' );
}

/** Meta description — checks Yoast/RankMath meta first, falls back to excerpt */
function oa_get_description( $post_id ) {
    // RankMath
    $desc = get_post_meta( $post_id, 'rank_math_description', true );
    // Yoast fallback
    if ( ! $desc ) $desc = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
    // Manual excerpt fallback
    if ( ! $desc ) $desc = get_the_excerpt( $post_id );
    // Auto-generate from content as last resort
    if ( ! $desc ) {
        $content = get_post_field( 'post_content', $post_id );
        $desc    = wp_trim_words( wp_strip_all_tags( $content ), 30, '...' );
    }
    return $desc;
}

/** Published or modified date in ISO 8601 format */
function oa_get_date( $post_id, $type = 'published' ) {
    $field = $type === 'modified' ? 'post_modified' : 'post_date';
    return get_post_field( $field, $post_id )
        ? date( 'c', strtotime( get_post_field( $field, $post_id ) ) )
        : '';
}

/** Author object with name and URL */
function oa_get_author( $post_id ) {
    $author_id = get_post_field( 'post_author', $post_id );
    return [
        '@type' => 'Person',
        'name'  => get_the_author_meta( 'display_name', $author_id ),
        'url'   => get_author_posts_url( $author_id ),
    ];
}

/** Publisher — always Outsource Accelerator */
function oa_get_publisher() {
    return [
        '@type' => 'Organization',
        'name'  => 'Outsource Accelerator',
        'logo'  => [
            '@type' => 'ImageObject',
            'url'   => 'https://www.outsourceaccelerator.com/wp-content/themes/oa/images/logo.png',
            // ↑ Update to actual logo URL
        ],
    ];
}

/** Featured image as ImageObject */
function oa_get_image( $post_id ) {
    if ( ! has_post_thumbnail( $post_id ) ) return null;

    $img_id  = get_post_thumbnail_id( $post_id );
    $img_src = wp_get_attachment_image_src( $img_id, 'full' );
    $meta    = wp_get_attachment_metadata( $img_id );

    return [
        '@type'  => 'ImageObject',
        'url'    => $img_src[0] ?? '',
        'width'  => $meta['width'] ?? $img_src[1] ?? '',
        'height' => $meta['height'] ?? $img_src[2] ?? '',
    ];
}

/** Tags as comma-separated keywords string */
function oa_get_keywords( $post_id ) {
    $tags = get_the_tags( $post_id );
    if ( ! $tags ) return '';
    return implode( ', ', wp_list_pluck( $tags, 'name' ) );
}

/** Primary category name */
function oa_get_categories( $post_id ) {
    $cats = get_the_category( $post_id );
    return ! empty( $cats ) ? $cats[0]->name : '';
}


// ─────────────────────────────────────────────
// 9. CONTENT PARSERS (for Guide schema)
// ─────────────────────────────────────────────

/**
 * Extract H2 headings from post content as HowTo steps.
 * Each H2 becomes one step. Text after the H2 until the next
 * H2 becomes the step directions.
 */
function oa_extract_headings_as_steps( $post_id ) {
    $content = get_post_field( 'post_content', $post_id );
    $content = apply_filters( 'the_content', $content );

    preg_match_all( '/<h2[^>]*>(.*?)<\/h2>(.*?)(?=<h2|$)/si', $content, $matches );

    if ( empty( $matches[1] ) ) return [];

    $steps = [];
    foreach ( $matches[1] as $i => $heading ) {
        $text      = wp_strip_all_tags( $matches[2][ $i ] ?? '' );
        $steps[]   = [
            '@type'     => 'HowToStep',
            'name'      => wp_strip_all_tags( $heading ),
            'text'      => wp_trim_words( $text, 50, '...' ),
            'url'       => get_permalink( $post_id ) . '#step-' . ( $i + 1 ),
        ];
    }
    return $steps;
}

/**
 * Extract FAQ pairs from post content.
 *
 * Supports two common patterns devs might use:
 *
 * Pattern A — FAQ block with data attributes (recommended for devs):
 *   <div class="oa-faq" data-question="..." data-answer="..."></div>
 *
 * Pattern B — Strong tag Q&A pattern:
 *   <strong>Q: What is BPO?</strong>
 *   <p>A: BPO stands for...</p>
 *
 * Ask your devs to use Pattern A going forward — it's the cleanest.
 */
function oa_extract_faqs( $post_id ) {
    $content = get_post_field( 'post_content', $post_id );
    $faqs    = [];

    // Pattern A: data attributes (preferred)
    preg_match_all(
        '/data-question=["\']([^"\']+)["\'][^>]*data-answer=["\']([^"\']+)["\']/',
        $content,
        $matchesA
    );

    if ( ! empty( $matchesA[1] ) ) {
        foreach ( $matchesA[1] as $i => $question ) {
            $faqs[] = [
                '@type'          => 'Question',
                'name'           => sanitize_text_field( $question ),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => sanitize_text_field( $matchesA[2][ $i ] ),
                ],
            ];
        }
        return $faqs;
    }

    // Pattern B: Strong Q&A fallback
    preg_match_all(
        '/<strong>Q:\s*(.*?)<\/strong>.*?<p>A:\s*(.*?)<\/p>/si',
        $content,
        $matchesB
    );

    if ( ! empty( $matchesB[1] ) ) {
        foreach ( $matchesB[1] as $i => $question ) {
            $faqs[] = [
                '@type'          => 'Question',
                'name'           => wp_strip_all_tags( $question ),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => wp_strip_all_tags( $matchesB[2][ $i ] ),
                ],
            ];
        }
    }

    return $faqs;
}