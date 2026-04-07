<?php
/**
 * Block Pattern: TL;DR Summary Box
 *
 * A styled aside block with aria-label="Summary", bullet list,
 * semantic HTML5 <aside>, and WCAG 2.1 AA compliant contrast.
 *
 * @package A11yAEO\BlockPatterns
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

register_block_pattern(
	'a11y-aeo/tldr-summary',
	array(
		'title'       => __( 'TL;DR Summary Box', 'a11y-aeo' ),
		'description' => __( 'A styled aside summary block with an accessible label, key-point bullet list, and WCAG 2.1 AA contrast.', 'a11y-aeo' ),
		'categories'  => array( 'text', 'featured' ),
		'keywords'    => array( 'tldr', 'summary', 'aside', 'key points', 'seo' ),
		'content'     => '
<!-- wp:group {
	"tagName":"aside",
	"className":"a11y-tldr-box",
	"style":{
		"border":{"radius":"4px","left":{"width":"4px","color":"#0073AA"},"right":{"width":"1px","color":"#767676"},"top":{"width":"1px","color":"#767676"},"bottom":{"width":"1px","color":"#767676"}},
		"spacing":{"padding":{"top":"var:preset|spacing|40","right":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40"}}
	},
	"backgroundColor":"base-2"
} -->
<aside
	class="wp-block-group a11y-tldr-box has-base-2-background-color has-background"
	aria-label="' . esc_attr__( 'Summary', 'a11y-aeo' ) . '"
>

	<!-- wp:heading {
		"level":2,
		"className":"a11y-tldr-box__heading",
		"style":{"typography":{"fontWeight":"700","fontSize":"1rem"},"color":{"text":"#0073AA"}}
	} -->
	<h2
		class="wp-block-heading a11y-tldr-box__heading has-text-color"
		style="color:#0073AA;font-size:1rem;font-weight:700;"
		aria-hidden="true"
	>' . esc_html__( 'TL;DR', 'a11y-aeo' ) . '</h2>
	<!-- /wp:heading -->

	<!-- wp:list {
		"className":"a11y-tldr-box__list",
		"style":{"spacing":{"padding":{"left":"var:preset|spacing|30"}}}
	} -->
	<ul class="wp-block-list a11y-tldr-box__list">

		<!-- wp:list-item -->
		<li>' . esc_html__( 'First key point — keep each bullet to one clear idea.', 'a11y-aeo' ) . '</li>
		<!-- /wp:list-item -->

		<!-- wp:list-item -->
		<li>' . esc_html__( 'Second key point — aim for 5–7 items maximum.', 'a11y-aeo' ) . '</li>
		<!-- /wp:list-item -->

		<!-- wp:list-item -->
		<li>' . esc_html__( 'Third key point — use plain language at grade 8 or below.', 'a11y-aeo' ) . '</li>
		<!-- /wp:list-item -->

	</ul>
	<!-- /wp:list -->

</aside>
<!-- /wp:group -->
',
	)
);
