<?php
/**
 * OA_Schema — Dynamic JSON-LD Schema Builder
 *
 * Outputs a single <script type="application/ld+json"> block containing a
 * schema.org @graph with up to six node types, all wired together by @id:
 *
 *   1. Organization    — publisher (static; same on every page)
 *   2. Person          — post author (dynamic per author)
 *   3. WebPage         — the current URL (dynamic per post)
 *   4. Article / NewsArticle — article metadata (dynamic per post)
 *   5. BreadcrumbList  — path from home to this post (dynamic per post)
 *   6. FAQPage         — FAQ Q&A pairs (opt-in via post meta)
 *   7. DefinedTerm[]   — glossary entries (opt-in via post meta)
 *
 * Nodes 6 and 7 are only emitted when the editor has populated the
 * corresponding meta fields via the meta boxes registered in meta-boxes.php.
 *
 * Usage (in functions.php):
 *   require_once get_template_directory() . '/inc/schema/config.php';
 *   require_once get_template_directory() . '/inc/schema/class-oa-schema.php';
 *   add_action( 'wp_head', [ 'OA_Schema', 'output' ] );
 *
 * @package OA_Schema
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OA_Schema {

    // =========================================================================
    // Public entry point
    // =========================================================================

    /**
     * Build and echo the full JSON-LD block for a singular post / page.
     * Hooked to wp_head; silently does nothing on non-singular contexts.
     */
    public static function output(): void {
        if ( ! is_singular() ) {
            return;
        }

        $post = get_queried_object();
        if ( ! $post instanceof WP_Post ) {
            return;
        }

        // Nodes that always appear.
        $graph = [
            self::build_organization(),
            self::build_person( $post ),
            self::build_webpage( $post ),
            self::build_article( $post ),
            self::build_breadcrumb( $post ),
        ];

        // FAQPage — only when the post has FAQ meta.
        $faq = self::build_faq( $post );
        if ( null !== $faq ) {
            $graph[] = $faq;
        }

        // DefinedTerm — returns 0-n nodes; each is a separate @graph entry.
        foreach ( self::build_defined_terms( $post ) as $term_node ) {
            $graph[] = $term_node;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@graph'   => $graph,
        ];

        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;

        echo "\n<script type=\"application/ld+json\">\n";
        echo wp_json_encode( $schema, $flags );
        echo "\n</script>\n";
    }

    // =========================================================================
    // Node builders — private
    // =========================================================================

    // -------------------------------------------------------------------------
    // 1. Organization
    // -------------------------------------------------------------------------

    /**
     * Returns the Organisation node.
     * Static — same data on every page of the site.
     *
     * @return array<string, mixed>
     */
    private static function build_organization(): array {
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

    // -------------------------------------------------------------------------
    // 2. Person (Author)
    // -------------------------------------------------------------------------

    /**
     * Returns the Person node for the post's author.
     * Pulls standard WP user fields plus custom meta set in author-meta.php.
     *
     * @param  WP_Post $post
     * @return array<string, mixed>
     */
    private static function build_person( WP_Post $post ): array {
        $author_id  = (int) $post->post_author;
        $author_url = get_author_posts_url( $author_id );

        // Custom user-meta (populated via author profile fields in the WP admin).
        $job_title  = get_user_meta( $author_id, OA_SCHEMA_UMETA_JOB_TITLE, true );
        $social_raw = get_user_meta( $author_id, OA_SCHEMA_UMETA_SOCIAL_PROFILES, true );
        $social     = is_array( $social_raw ) ? array_values( array_filter( $social_raw ) ) : [];
        $photo_id   = (int) get_user_meta( $author_id, OA_SCHEMA_UMETA_PHOTO_ID, true );
        $photo_url  = $photo_id ? wp_get_attachment_image_url( $photo_id, 'medium' ) : '';

        $node = [
            '@type'    => 'Person',
            '@id'      => $author_url . '#person',
            'name'     => get_the_author_meta( 'display_name', $author_id ),
            'url'      => $author_url,
            'worksFor' => [ '@id' => OA_SCHEMA_ORG_URL . '/#organization' ],
        ];

        // Optional fields — only add when values exist.
        $bio = get_the_author_meta( 'description', $author_id );
        if ( $bio )       $node['description'] = $bio;
        if ( $job_title ) $node['jobTitle']     = $job_title;
        if ( $photo_url ) $node['image']        = [ '@type' => 'ImageObject', 'url' => $photo_url ];
        if ( $social )    $node['sameAs']       = $social;

        return $node;
    }

    // -------------------------------------------------------------------------
    // 3. WebPage
    // -------------------------------------------------------------------------

    /**
     * Returns the WebPage node for the current post URL.
     *
     * @param  WP_Post $post
     * @return array<string, mixed>
     */
    private static function build_webpage( WP_Post $post ): array {
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

    // -------------------------------------------------------------------------
    // 4. Article / NewsArticle
    // -------------------------------------------------------------------------

    /**
     * Returns the Article (or NewsArticle) node.
     * The schema subtype is resolved from OA_SCHEMA_POST_TYPE_MAP.
     *
     * @param  WP_Post $post
     * @return array<string, mixed>
     */
    private static function build_article( WP_Post $post ): array {
        $url         = get_permalink( $post );
        $author_id   = (int) $post->post_author;
        $author_url  = get_author_posts_url( $author_id );

        // Resolve schema subtype from config map; fallback to Article.
        $schema_type = OA_SCHEMA_POST_TYPE_MAP[ $post->post_type ] ?? 'Article';

        // Description: prefer the excerpt, else trim the raw content.
        $description = has_excerpt( $post )
            ? wp_strip_all_tags( get_the_excerpt( $post ) )
            : wp_trim_words( wp_strip_all_tags( $post->post_content ), 35, '…' );

        $node = [
            '@type'               => $schema_type,
            '@id'                 => $url . '#article',
            'headline'            => get_the_title( $post ),
            'description'         => $description,
            'url'                 => $url,
            'mainEntityOfPage'    => [ '@id' => $url . '#webpage' ],
            'author'              => [ '@id' => $author_url . '#person' ],
            'publisher'           => [ '@id' => OA_SCHEMA_ORG_URL . '/#organization' ],
            'datePublished'       => get_the_date( 'c', $post ),
            'dateModified'        => get_the_modified_date( 'c', $post ),
            'inLanguage'          => 'en-US',
            'isAccessibleForFree' => true,
        ];

        // Featured image.
        $image_url = get_the_post_thumbnail_url( $post->ID, 'full' );
        if ( $image_url ) {
            $node['image'] = [
                '@type'  => 'ImageObject',
                'url'    => $image_url,
                'width'  => 1200,
                'height' => 630,
            ];
        }

        // Article section from primary category.
        $categories = get_the_category( $post->ID );
        if ( ! empty( $categories ) ) {
            $node['articleSection'] = $categories[0]->name;
        }

        // Keywords from tags.
        $tags = get_the_tags( $post->ID );
        if ( $tags ) {
            $node['keywords'] = implode( ', ', wp_list_pluck( $tags, 'name' ) );
        }

        // Cross-reference any DefinedTerm nodes via 'about'.
        $raw_terms = get_post_meta( $post->ID, OA_SCHEMA_META_TERMS, true );
        if ( is_array( $raw_terms ) && ! empty( $raw_terms ) ) {
            $node['about'] = array_map(
                static fn( int $i ): array => [ '@id' => $url . '#term-' . $i ],
                array_keys( $raw_terms )
            );
        }

        return $node;
    }

    // -------------------------------------------------------------------------
    // 5. BreadcrumbList
    // -------------------------------------------------------------------------

    /**
     * Builds a BreadcrumbList from the post hierarchy.
     *
     * Tier 1 : Home
     * Tier 2 : Post-type archive URL  (if registered)
     *          OR primary category    (fallback)
     * Tier 3 : Current post
     *
     * @param  WP_Post $post
     * @return array<string, mixed>
     */
    private static function build_breadcrumb( WP_Post $post ): array {
        $url   = get_permalink( $post );
        $items = [
            [
                '@type'    => 'ListItem',
                'position' => 1,
                'name'     => 'Home',
                'item'     => home_url( '/' ),
            ],
        ];

        // Mid-level crumb: prefer the CPT archive, fall back to primary category.
        $mid_name = '';
        $mid_url  = '';

        $archive_url = get_post_type_archive_link( $post->post_type );
        if ( $archive_url ) {
            $pt_obj   = get_post_type_object( $post->post_type );
            $mid_name = $pt_obj ? $pt_obj->labels->name : ucfirst( $post->post_type );
            $mid_url  = $archive_url;
        } else {
            $categories = get_the_category( $post->ID );
            if ( ! empty( $categories ) ) {
                $mid_name = $categories[0]->name;
                $mid_url  = get_category_link( $categories[0]->term_id );
            }
        }

        if ( $mid_name && $mid_url ) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => 2,
                'name'     => $mid_name,
                'item'     => $mid_url,
            ];
        }

        // Leaf crumb: the post itself.
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

    // -------------------------------------------------------------------------
    // 6. FAQPage (opt-in)
    // -------------------------------------------------------------------------

    /**
     * Returns the FAQPage node, or null if the post has no FAQ meta.
     *
     * Data is stored under OA_SCHEMA_META_FAQ as an array of associative
     * arrays: [ ['question' => '…', 'answer' => '…'], … ]
     *
     * @param  WP_Post  $post
     * @return array<string, mixed>|null
     */
    private static function build_faq( WP_Post $post ): ?array {
        $raw = get_post_meta( $post->ID, OA_SCHEMA_META_FAQ, true );
        if ( ! is_array( $raw ) || empty( $raw ) ) {
            return null;
        }

        $entities = [];
        foreach ( $raw as $item ) {
            $q = sanitize_text_field( $item['question'] ?? '' );
            $a = wp_kses_post( $item['answer']   ?? '' );
            if ( $q && $a ) {
                $entities[] = [
                    '@type'          => 'Question',
                    'name'           => $q,
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => $a,
                    ],
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

    // -------------------------------------------------------------------------
    // 7. DefinedTerm (opt-in, multiple nodes)
    // -------------------------------------------------------------------------

    /**
     * Returns 0-n DefinedTerm nodes.
     *
     * Data is stored under OA_SCHEMA_META_TERMS as an array:
     * [ ['name' => '…', 'description' => '…'], … ]
     *
     * Each term gets its own @id so the Article's 'about' array can reference
     * them precisely.
     *
     * @param  WP_Post            $post
     * @return array<int, array<string, mixed>>
     */
    private static function build_defined_terms( WP_Post $post ): array {
        $raw = get_post_meta( $post->ID, OA_SCHEMA_META_TERMS, true );
        if ( ! is_array( $raw ) || empty( $raw ) ) {
            return [];
        }

        $url   = get_permalink( $post );
        $nodes = [];

        foreach ( $raw as $index => $term ) {
            $name = sanitize_text_field( $term['name']        ?? '' );
            $desc = wp_kses_post(        $term['description'] ?? '' );
            if ( ! $name || ! $desc ) {
                continue;
            }

            $nodes[] = [
                '@type'            => 'DefinedTerm',
                '@id'              => $url . '#term-' . $index,
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
}
