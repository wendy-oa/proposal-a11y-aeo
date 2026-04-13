<?php
/**
 * Schema Configuration
 *
 * Site-wide constants used by OA_Schema.
 * Update once here; every article/news/guide page inherits the values.
 *
 * @package OA_Schema
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ---------------------------------------------------------------------------
// Organization
// ---------------------------------------------------------------------------

/** Legal / display name of the publisher. */
define( 'OA_SCHEMA_ORG_NAME', 'Outsource Accelerator' );

/** Canonical home URL — no trailing slash. */
define( 'OA_SCHEMA_ORG_URL', 'https://www.outsourceaccelerator.com' );

/** Absolute URL of the organisation logo stored in the WP Media Library. */
define( 'OA_SCHEMA_ORG_LOGO_URL', 'https://www.outsourceaccelerator.com/wp-content/uploads/outsource-accelerator-logo.png' );

/** Logo dimensions in pixels. */
define( 'OA_SCHEMA_ORG_LOGO_W', 280 );
define( 'OA_SCHEMA_ORG_LOGO_H', 60 );

/** Short organisation description shown in Knowledge Panel. */
define(
    'OA_SCHEMA_ORG_DESCRIPTION',
    'Outsource Accelerator is the world\'s leading Business Process Outsourcing (BPO) '
    . 'marketplace and advisory — the trusted, independent resource for businesses of '
    . 'all sizes to explore, initiate, and embed outsourcing into their operations.'
);

/**
 * Verified social / sameAs profiles for the organisation.
 * Add or remove entries as needed; order does not matter.
 */
define( 'OA_SCHEMA_ORG_SAME_AS', [
    'https://www.linkedin.com/company/outsourceaccelerator',
    'https://www.facebook.com/outsourceaccelerator/',
    'https://www.instagram.com/outsourceaccel/',
    'https://podcast.outsourceaccelerator.com/',
] );

// ---------------------------------------------------------------------------
// DefinedTermSet (used by DefinedTerm nodes)
// ---------------------------------------------------------------------------

/** Label for the site-wide glossary DefinedTermSet. */
define( 'OA_SCHEMA_TERMSET_NAME', 'Outsource Accelerator Glossary' );

/** URL of the glossary / articles index. */
define( 'OA_SCHEMA_TERMSET_URL', OA_SCHEMA_ORG_URL . '/articles/' );

// ---------------------------------------------------------------------------
// Post-type → schema Article subtype map
// ---------------------------------------------------------------------------

/**
 * Maps WordPress post types to the correct schema.org Article subtype.
 *
 * Recognised subtypes: Article | NewsArticle | TechArticle | HowTo
 * Add every custom post type your theme registers.
 *
 * @var array<string, string>
 */
define( 'OA_SCHEMA_POST_TYPE_MAP', [
    'post'    => 'Article',       // standard WP posts used as articles
    'article' => 'Article',       // CPT: long-form editorial content
    'news'    => 'NewsArticle',   // CPT: news / press room
    'guide'   => 'Article',       // CPT: long-form guides
] );

// ---------------------------------------------------------------------------
// Custom post-meta keys (centralised to avoid typos across files)
// ---------------------------------------------------------------------------

/** Post-meta key storing FAQ items array: [ ['question'=>'', 'answer'=>''], … ] */
define( 'OA_SCHEMA_META_FAQ',   '_schema_faq_items' );

/** Post-meta key storing DefinedTerm items array: [ ['name'=>'', 'description'=>''], … ] */
define( 'OA_SCHEMA_META_TERMS', '_schema_defined_terms' );

// ---------------------------------------------------------------------------
// Custom user-meta keys
// ---------------------------------------------------------------------------

/** Author's job title (e.g. "Founder & CEO"). */
define( 'OA_SCHEMA_UMETA_JOB_TITLE',       'oa_author_job_title' );

/** Author's social profiles — stored as a JSON-encoded array of URLs. */
define( 'OA_SCHEMA_UMETA_SOCIAL_PROFILES', 'oa_author_social_profiles' );

/** WordPress attachment ID of the author's headshot. */
define( 'OA_SCHEMA_UMETA_PHOTO_ID',        'oa_author_photo_id' );
