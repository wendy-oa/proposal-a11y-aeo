<?php
/**
 * Block Pattern: Definition Block
 *
 * Displays a term definition with semantic HTML5, DefinedTerm markup,
 * aria-label, and WCAG 2.1 AA compliance.
 *
 * @package A11yAEO\BlockPatterns
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

register_block_pattern(
	'a11y-aeo/definition-block',
	array(
		'title'       => __( 'Definition Block', 'a11y-aeo' ),
		'description' => __( 'A semantic definition section with DefinedTerm schema markup, accessible heading, and WCAG 2.1 AA compliant contrast.', 'a11y-aeo' ),
		'categories'  => array( 'text', 'featured' ),
		'keywords'    => array( 'definition', 'term', 'glossary', 'schema', 'seo' ),
		'content'     => '
<!-- wp:group {
	"tagName":"section",
	"className":"a11y-definition-block",
	"style":{
		"border":{"radius":"4px","width":"1px","color":"#767676"},
		"spacing":{"padding":{"top":"var:preset|spacing|40","right":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40"}}
	},
	"backgroundColor":"base-2"
} -->
<section
	class="wp-block-group a11y-definition-block has-base-2-background-color has-background"
	aria-label="' . esc_attr__( 'Definition', 'a11y-aeo' ) . '"
	itemscope
	itemtype="https://schema.org/DefinedTerm"
>

	<!-- wp:heading {
		"level":2,
		"className":"a11y-definition-block__heading",
		"style":{"typography":{"fontWeight":"700"}}
	} -->
	<h2
		class="wp-block-heading a11y-definition-block__heading"
		itemprop="name"
	>' . esc_html__( 'What is [Term]?', 'a11y-aeo' ) . '</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {
		"className":"a11y-definition-block__body"
	} -->
	<p
		class="a11y-definition-block__body"
		itemprop="description"
	>' . esc_html__( 'Replace this text with a clear, concise definition of the term. Keep it under 50 words for best readability and featured-snippet eligibility.', 'a11y-aeo' ) . '</p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph {
		"className":"a11y-definition-block__meta",
		"style":{"typography":{"fontSize":"0.875rem"}}
	} -->
	<p class="a11y-definition-block__meta">
		<span itemprop="inDefinedTermSet" itemscope itemtype="https://schema.org/DefinedTermSet">
			<span itemprop="name">' . esc_html__( 'Glossary Category', 'a11y-aeo' ) . '</span>
		</span>
	</p>
	<!-- /wp:paragraph -->

</section>
<!-- /wp:group -->
',
	)
);
