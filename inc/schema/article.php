<?php
/**
 * Schema: Article Page Type
 *
 * Single function that pulls all data dynamically from WordPress and outputs
 * the full JSON-LD @graph for an article page in <head>.
 *
 * Post types handled: 'post' (standard WP posts), 'article' (custom CPT)
 *
 * Schema nodes emitted
 * ────────────────────
 *  Always:
 *    Organization    — publisher entity (static across the site)
 *    Person          — post author (dynamic per author)
 *    WebPage         — canonical URL for this page
 *    Article         — article metadata (headline, dates, image, section …)
 *    BreadcrumbList  — Home › Articles › {Post Title}
 *
 *  Opt-in (populated via the Schema meta boxes on the post edit screen):
 *    FAQPage         — added when the editor fills in FAQ Items meta
 *    DefinedTerm[]   — one node per entry in Defined Terms meta;
 *                      cross-linked to the Article node via 'about'
 *
 * Hooked via functions.php dispatcher — do not call directly from templates.
 *
 * @package OA_Schema
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Outputs the complete JSON-LD @graph for an article page.
 *
 * @param WP_Post $post  The current post object (passed by the wp_head dispatcher).
 */
function oa_schema_output_article( WP_Post $post ): void {

    $url        = get_permalink( $post );
    $author_url = get_author_posts_url( (int) $post->post_author );

    // -------------------------------------------------------------------------
    // Build the @graph — nodes are ordered for readability in the source.
    // -------------------------------------------------------------------------

    $graph = [

        // 1. Organization — publisher; same on every page.
        _oa_schema_node_organization(),

        // 2. Person — author; sourced from WP user profile + custom meta.
        _oa_schema_node_person( $post ),

        // 3. WebPage — links this URL to the breadcrumb trail.
        _oa_schema_node_webpage( $post ),

        // 4. Article — the primary entity for editorial long-form content.
        //    _oa_schema_clean() removes any null / empty optional fields so
        //    the output stays valid even when image, category, or tags are absent.
        _oa_schema_clean( [
            '@type'               => 'Article',
            '@id'                 => $url . '#article',

            // Core identification.
            'headline'            => get_the_title( $post ),
            'description'         => _oa_schema_description( $post ),
            'url'                 => $url,
            'mainEntityOfPage'    => [ '@id' => $url . '#webpage' ],

            // Authorship & publisher — resolved via @id cross-references.
            'author'              => [ '@id' => $author_url . '#person' ],
            'publisher'           => [ '@id' => OA_SCHEMA_ORG_URL . '/#organization' ],

            // Dates — WordPress stores and returns these in site timezone.
            'datePublished'       => get_the_date( 'c', $post ),
            'dateModified'        => get_the_modified_date( 'c', $post ),

            // Media.
            'image'               => _oa_schema_image( $post ),  // null → stripped

            // Taxonomy signals.
            'articleSection'      => _oa_schema_primary_category( $post ),  // '' → stripped
            'keywords'            => _oa_schema_keywords( $post ),            // '' → stripped

            // Cross-reference any DefinedTerm nodes added in the meta box.
            // Google uses this to connect the article to the glossary entities.
            'about'               => _oa_schema_term_refs( $post ),            // [] → stripped

            // Access & language.
            'inLanguage'          => 'en-US',
            'isAccessibleForFree' => true,
        ] ),

        // 5. BreadcrumbList — Home › Articles › {Post Title}.
        _oa_schema_node_breadcrumb( $post ),

    ];

    // -------------------------------------------------------------------------
    // Opt-in nodes — appended only when the editor has supplied the data.
    // -------------------------------------------------------------------------

    // 6. FAQPage — emitted when the "Schema: FAQ Items" meta box has entries.
    $faq = _oa_schema_node_faq( $post );
    if ( null !== $faq ) {
        $graph[] = $faq;
    }

    // 7. DefinedTerm — one node per "Schema: Defined Terms" meta box entry.
    //    These are also cross-referenced above via the Article 'about' field.
    foreach ( _oa_schema_node_defined_terms( $post ) as $term_node ) {
        $graph[] = $term_node;
    }

    // -------------------------------------------------------------------------
    // Output.
    // -------------------------------------------------------------------------
    _oa_schema_emit( $graph );
}
