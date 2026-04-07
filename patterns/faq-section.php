<?php
/**
 * Block Pattern: FAQ Section
 *
 * Renders a FAQ section with FAQPage schema (JSON-LD), semantic HTML5
 * <section> + <details>/<summary> accordion, and WCAG 2.1 AA compliance:
 *   - 4.5:1 text contrast minimum
 *   - Visible focus rings on interactive elements
 *   - Keyboard-operable accordion (native <details>)
 *   - Role/aria attributes for screen readers
 *
 * @package A11yAEO\BlockPatterns
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

register_block_pattern(
	'a11y-aeo/faq-section',
	array(
		'title'       => __( 'FAQ Section', 'a11y-aeo' ),
		'description' => __( 'An accessible FAQ section with FAQPage JSON-LD schema, native <details> accordion, ARIA labels, and WCAG 2.1 AA contrast.', 'a11y-aeo' ),
		'categories'  => array( 'text', 'featured' ),
		'keywords'    => array( 'faq', 'questions', 'schema', 'accordion', 'seo', 'a11y' ),
		'content'     => '
<!-- wp:group {
	"tagName":"section",
	"className":"a11y-faq-section",
	"style":{
		"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}
	}
} -->
<section
	class="wp-block-group a11y-faq-section"
	aria-labelledby="faq-heading"
	itemscope
	itemtype="https://schema.org/FAQPage"
>

	<!-- wp:heading {
		"level":2,
		"className":"a11y-faq-section__heading",
		"anchor":"faq-heading"
	} -->
	<h2
		id="faq-heading"
		class="wp-block-heading a11y-faq-section__heading"
	>' . esc_html__( 'Frequently Asked Questions', 'a11y-aeo' ) . '</h2>
	<!-- /wp:heading -->

	<!-- wp:html -->
	<div class="a11y-faq-section__items">

		<!-- FAQ Item 1 -->
		<div
			class="a11y-faq-item"
			itemprop="mainEntity"
			itemscope
			itemtype="https://schema.org/Question"
		>
			<details class="a11y-faq-item__details">
				<summary
					class="a11y-faq-item__question"
					itemprop="name"
				>' . esc_html__( 'What is the first frequently asked question?', 'a11y-aeo' ) . '</summary>
				<div
					class="a11y-faq-item__answer"
					itemprop="acceptedAnswer"
					itemscope
					itemtype="https://schema.org/Answer"
				>
					<p itemprop="text">' . esc_html__( 'Replace this with a clear, helpful answer. Answers should directly address the question without burying the lead.', 'a11y-aeo' ) . '</p>
				</div>
			</details>
		</div>

		<!-- FAQ Item 2 -->
		<div
			class="a11y-faq-item"
			itemprop="mainEntity"
			itemscope
			itemtype="https://schema.org/Question"
		>
			<details class="a11y-faq-item__details">
				<summary
					class="a11y-faq-item__question"
					itemprop="name"
				>' . esc_html__( 'What is the second frequently asked question?', 'a11y-aeo' ) . '</summary>
				<div
					class="a11y-faq-item__answer"
					itemprop="acceptedAnswer"
					itemscope
					itemtype="https://schema.org/Answer"
				>
					<p itemprop="text">' . esc_html__( 'Replace this with a clear, helpful answer. Use plain language at a grade-8 reading level or below.', 'a11y-aeo' ) . '</p>
				</div>
			</details>
		</div>

		<!-- FAQ Item 3 -->
		<div
			class="a11y-faq-item"
			itemprop="mainEntity"
			itemscope
			itemtype="https://schema.org/Question"
		>
			<details class="a11y-faq-item__details">
				<summary
					class="a11y-faq-item__question"
					itemprop="name"
				>' . esc_html__( 'What is the third frequently asked question?', 'a11y-aeo' ) . '</summary>
				<div
					class="a11y-faq-item__answer"
					itemprop="acceptedAnswer"
					itemscope
					itemtype="https://schema.org/Answer"
				>
					<p itemprop="text">' . esc_html__( 'Replace this with a clear, helpful answer. Keep answers focused — one idea per paragraph.', 'a11y-aeo' ) . '</p>
				</div>
			</details>
		</div>

	</div>

	<!-- JSON-LD: FAQPage schema (mirrors the visible markup above) -->
	<script type="application/ld+json">
	{
		"@context": "https://schema.org",
		"@type": "FAQPage",
		"mainEntity": [
			{
				"@type": "Question",
				"name": "' . esc_js( __( 'What is the first frequently asked question?', 'a11y-aeo' ) ) . '",
				"acceptedAnswer": {
					"@type": "Answer",
					"text": "' . esc_js( __( 'Replace this with a clear, helpful answer. Answers should directly address the question without burying the lead.', 'a11y-aeo' ) ) . '"
				}
			},
			{
				"@type": "Question",
				"name": "' . esc_js( __( 'What is the second frequently asked question?', 'a11y-aeo' ) ) . '",
				"acceptedAnswer": {
					"@type": "Answer",
					"text": "' . esc_js( __( 'Replace this with a clear, helpful answer. Use plain language at a grade-8 reading level or below.', 'a11y-aeo' ) ) . '"
				}
			},
			{
				"@type": "Question",
				"name": "' . esc_js( __( 'What is the third frequently asked question?', 'a11y-aeo' ) ) . '",
				"acceptedAnswer": {
					"@type": "Answer",
					"text": "' . esc_js( __( 'Replace this with a clear, helpful answer. Keep answers focused — one idea per paragraph.', 'a11y-aeo' ) ) . '"
				}
			}
		]
	}
	</script>
	<!-- /wp:html -->

</section>
<!-- /wp:group -->
',
	)
);
