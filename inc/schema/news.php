<?php
/**
 * Schema: News Article Page Type
 *
 * Single function that pulls all data dynamically from WordPress and outputs
 * the full JSON-LD @graph for a news article page in <head>.
 *
 * Post type handled: 'news' (custom CPT for the press room / news section)
 *
 * How NewsArticle differs from Article
 * ─────────────────────────────────────
 *  @type is NewsArticle — the stronger signal for Google News, Google Discover,
 *  and the "Top Stories" carousel.  Google requires:
 *    • datePublished and dateModified in ISO 8601
 *    • image with width ≥ 1200 px
 *    • author with a name
 *  All three are satisfied automatically by this function.
 *
 *  DefinedTerm is intentionally excluded — news content is timely reporting,
 *  not glossary/educational content.  Adding glossary nodes to a NewsArticle
 *  dilutes the freshness signal and can confuse structured-data parsers.
 *
 *  An optional 'dateline' field is supported via post meta (_schema_news_dateline)
 *  so editors can specify the reporting location (e.g. "Manila, Philippines").
 *
 * Schema nodes emitted
 * ────────────────────
 *  Always:
 *    Organization    — publisher entity
 *    Person          — post author
 *    WebPage         — canonical URL
 *    NewsArticle     — news-specific article metadata
 *    BreadcrumbList  — Home › News › {Post Title}
 *
 *  Opt-in:
 *    FAQPage         — rarely used for news, but supported if FAQ meta exists
 *
 * Hooked via functions.php dispatcher — do not call directly from templates.
 *
 * @package OA_Schema
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Outputs the complete JSON-LD @graph for a news article page.
 *
 * @param WP_Post $post  The current post object (passed by the wp_head dispatcher).
 */
function oa_schema_output_news( WP_Post $post ): void {

    $url        = get_permalink( $post );
    $author_url = get_author_posts_url( (int) $post->post_author );

    // Optional: reporting location stored by the editor as plain text.
    // Example value: "Manila, Philippines"
    // Meta key: _schema_news_dateline  (register in meta-boxes.php if needed)
    $dateline = sanitize_text_field( get_post_meta( $post->ID, '_schema_news_dateline', true ) );

    // -------------------------------------------------------------------------
    // Build the @graph.
    // -------------------------------------------------------------------------

    $graph = [

        // 1. Organization — publisher.
        _oa_schema_node_organization(),

        // 2. Person — author.
        _oa_schema_node_person( $post ),

        // 3. WebPage — canonical URL node.
        _oa_schema_node_webpage( $post ),

        // 4. NewsArticle — time-sensitive reporting content.
        //    Key difference from Article: @type = NewsArticle and the optional
        //    'dateline' field.  The 'about' / DefinedTerm cross-reference is
        //    intentionally absent.
        _oa_schema_clean( [
            '@type'               => 'NewsArticle',
            '@id'                 => $url . '#article',

            // Core identification.
            'headline'            => get_the_title( $post ),
            'description'         => _oa_schema_description( $post ),
            'url'                 => $url,
            'mainEntityOfPage'    => [ '@id' => $url . '#webpage' ],

            // Authorship & publisher.
            'author'              => [ '@id' => $author_url . '#person' ],
            'publisher'           => [ '@id' => OA_SCHEMA_ORG_URL . '/#organization' ],

            // Dates — ISO 8601 required by Google News.
            'datePublished'       => get_the_date( 'c', $post ),
            'dateModified'        => get_the_modified_date( 'c', $post ),

            // Image — ≥ 1200 px wide required for Top Stories eligibility.
            'image'               => _oa_schema_image( $post ),  // null → stripped

            // Section & keywords from WP taxonomy.
            'articleSection'      => _oa_schema_primary_category( $post ),  // '' → stripped
            'keywords'            => _oa_schema_keywords( $post ),            // '' → stripped

            // Reporting location — omitted when the meta field is blank.
            'dateline'            => $dateline ?: null,  // null → stripped

            'inLanguage'          => 'en-US',
            'isAccessibleForFree' => true,
        ] ),

        // 5. BreadcrumbList — Home › News › {Post Title}.
        _oa_schema_node_breadcrumb( $post ),

    ];

    // -------------------------------------------------------------------------
    // Opt-in nodes.
    // -------------------------------------------------------------------------

    // 6. FAQPage — uncommon for news but supported if the editor adds FAQ meta.
    $faq = _oa_schema_node_faq( $post );
    if ( null !== $faq ) {
        $graph[] = $faq;
    }

    // Note: DefinedTerm nodes are intentionally excluded for news content.

    // -------------------------------------------------------------------------
    // Output.
    // -------------------------------------------------------------------------
    _oa_schema_emit( $graph );
}
