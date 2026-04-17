<?php
/**
 * Schema: Guide Page Type
 *
 * Single function that pulls all data dynamically from WordPress and outputs
 * the full JSON-LD @graph for a guide page in <head>.
 *
 * Post type handled: 'guide' (custom CPT for long-form educational content)
 *
 * How Guide differs from Article
 * ──────────────────────────────
 *  @type is still Article — guides are long-form editorial, not step-by-step
 *  how-tos.  (Switch to HowTo if your guides have discrete numbered steps,
 *  tools, and supplies — that is a different content pattern.)
 *
 *  FAQPage is treated as a strong expectation, not a nice-to-have.
 *  Guides answer multiple reader questions by nature; editors should fill in
 *  the FAQ meta box on every guide.
 *
 *  DefinedTerm is also expected. Guides introduce domain vocabulary that
 *  readers need to understand the content (e.g. BPO, offshoring, EOR).
 *  Each term the editor adds appears as its own node in the @graph and is
 *  cross-referenced from the Article node's 'about' property.
 *
 *  An optional 'teaches' field lists the skills or concepts the guide covers.
 *  Store as newline-separated values in the '_schema_guide_teaches' post meta.
 *  Example:  "Outsourcing strategy\nVendor selection\nCost modelling"
 *
 * Schema nodes emitted
 * ────────────────────
 *  Always:
 *    Organization    — publisher entity
 *    Person          — post author
 *    WebPage         — canonical URL
 *    Article         — guide metadata (headline, dates, image, section, teaches …)
 *    BreadcrumbList  — Home › Guides › {Post Title}
 *
 *  Opt-in (but strongly recommended for every guide):
 *    FAQPage         — added when the "Schema: FAQ Items" meta box has entries
 *    DefinedTerm[]   — one node per "Schema: Defined Terms" meta box entry;
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
 * Outputs the complete JSON-LD @graph for a guide page.
 *
 * @param WP_Post $post  The current post object (passed by the wp_head dispatcher).
 */
function oa_schema_output_guide( WP_Post $post ): void {

    $url        = get_permalink( $post );
    $author_url = get_author_posts_url( (int) $post->post_author );

    // Optional: skills / concepts this guide teaches.
    // Stored as a newline-separated list in '_schema_guide_teaches' post meta.
    $teaches_raw = get_post_meta( $post->ID, '_schema_guide_teaches', true );
    $teaches     = [];
    if ( $teaches_raw ) {
        $teaches = array_values(
            array_filter(
                array_map(
                    'sanitize_text_field',
                    explode( "\n", wp_unslash( $teaches_raw ) )
                )
            )
        );
    }

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

        // 4. Article — long-form educational guide.
        //    Key additions vs. the article type:
        //      'teaches'  — what skills/concepts the reader gains
        //      'about'    — cross-links to DefinedTerm nodes in this same @graph
        _oa_schema_clean( [
            '@type'               => 'Article',
            '@id'                 => $url . '#article',

            // Core identification.
            'headline'            => get_the_title( $post ),
            'description'         => _oa_schema_description( $post ),
            'url'                 => $url,
            'mainEntityOfPage'    => [ '@id' => $url . '#webpage' ],

            // Authorship & publisher.
            'author'              => [ '@id' => $author_url . '#person' ],
            'publisher'           => [ '@id' => OA_SCHEMA_ORG_URL . '/#organization' ],

            // Dates.
            'datePublished'       => get_the_date( 'c', $post ),
            'dateModified'        => get_the_modified_date( 'c', $post ),

            // Media.
            'image'               => _oa_schema_image( $post ),  // null → stripped

            // Taxonomy signals.
            'articleSection'      => _oa_schema_primary_category( $post ),  // '' → stripped
            'keywords'            => _oa_schema_keywords( $post ),            // '' → stripped

            // Educational intent — what this guide teaches.
            // Maps to schema.org/teaches; omitted when meta is blank.
            'teaches'             => $teaches ?: null,  // null → stripped

            // Cross-reference DefinedTerm nodes — core AEO signal for guides.
            // Google uses the entity graph to serve glossary-style answers.
            'about'               => _oa_schema_term_refs( $post ),  // [] → stripped

            'inLanguage'          => 'en-US',
            'isAccessibleForFree' => true,
        ] ),

        // 5. BreadcrumbList — Home › Guides › {Post Title}.
        _oa_schema_node_breadcrumb( $post ),

    ];

    // -------------------------------------------------------------------------
    // Opt-in nodes — strongly recommended for every guide.
    // -------------------------------------------------------------------------

    // 6. FAQPage — populate via the "Schema: FAQ Items" meta box.
    //    Guides naturally answer multiple reader questions; every guide should
    //    have at least 3–5 FAQ pairs for PAA and AI-generated answer coverage.
    $faq = _oa_schema_node_faq( $post );
    if ( null !== $faq ) {
        $graph[] = $faq;
    }

    // 7. DefinedTerm — populate via the "Schema: Defined Terms" meta box.
    //    Each term node is its own entity in the @graph and is cross-linked
    //    from the Article 'about' field above, forming a closed entity graph.
    foreach ( _oa_schema_node_defined_terms( $post ) as $term_node ) {
        $graph[] = $term_node;
    }

    // -------------------------------------------------------------------------
    // Output.
    // -------------------------------------------------------------------------
    _oa_schema_emit( $graph );
}
