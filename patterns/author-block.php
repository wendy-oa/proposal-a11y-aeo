<?php
/**
 * Block Pattern: Author Block
 *
 * Displays author avatar, name, date, and role with Article/Author
 * schema markup (JSON-LD + microdata), semantic HTML5, and WCAG 2.1 AA.
 *
 * @package A11yAEO\BlockPatterns
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

register_block_pattern(
	'a11y-aeo/author-block',
	array(
		'title'       => __( 'Author Block', 'a11y-aeo' ),
		'description' => __( 'Author bio card with avatar, name, role, date, Article/Author schema markup, and WCAG 2.1 AA compliance.', 'a11y-aeo' ),
		'categories'  => array( 'text', 'featured' ),
		'keywords'    => array( 'author', 'bio', 'schema', 'byline', 'date', 'seo' ),
		'content'     => '
<!-- wp:group {
	"tagName":"section",
	"className":"a11y-author-block",
	"style":{
		"border":{"radius":"4px","width":"1px","color":"#767676"},
		"spacing":{"padding":{"top":"var:preset|spacing|40","right":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40"}}
	},
	"backgroundColor":"base-2",
	"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center","justifyContent":"left"}
} -->
<section
	class="wp-block-group a11y-author-block has-base-2-background-color has-background"
	aria-label="' . esc_attr__( 'Article author information', 'a11y-aeo' ) . '"
	itemscope
	itemtype="https://schema.org/Article"
>

	<!-- wp:html -->
	<!-- Avatar -->
	<div class="a11y-author-block__avatar" aria-hidden="true">
		<img
			src="' . esc_url( get_avatar_url( 0, array( 'size' => 80, 'default' => 'mystery' ) ) ) . '"
			alt=""
			width="80"
			height="80"
			class="a11y-author-block__avatar-img"
			itemprop="image"
			loading="lazy"
			decoding="async"
		/>
	</div>
	<!-- /wp:html -->

	<!-- wp:group {
		"className":"a11y-author-block__meta",
		"style":{"spacing":{"padding":{"left":"var:preset|spacing|30"}}},
		"layout":{"type":"flex","orientation":"vertical","justifyContent":"left"}
	} -->
	<div
		class="wp-block-group a11y-author-block__meta"
		itemprop="author"
		itemscope
		itemtype="https://schema.org/Person"
	>

		<!-- wp:paragraph {
			"className":"a11y-author-block__name",
			"style":{"typography":{"fontWeight":"700","fontSize":"1rem"}}
		} -->
		<p class="a11y-author-block__name" style="font-weight:700;font-size:1rem;">
			<a
				href="#"
				class="a11y-author-block__name-link"
				itemprop="url"
				rel="author"
			>
				<span itemprop="name">' . esc_html__( 'Author Full Name', 'a11y-aeo' ) . '</span>
			</a>
		</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {
			"className":"a11y-author-block__role",
			"style":{"typography":{"fontSize":"0.875rem"},"color":{"text":"#595959"}}
		} -->
		<p
			class="a11y-author-block__role has-text-color"
			style="font-size:0.875rem;color:#595959;"
			itemprop="jobTitle"
		>' . esc_html__( 'Job Title / Role', 'a11y-aeo' ) . '</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {
			"className":"a11y-author-block__date",
			"style":{"typography":{"fontSize":"0.875rem"},"color":{"text":"#595959"}}
		} -->
		<p class="a11y-author-block__date has-text-color" style="font-size:0.875rem;color:#595959;">
			<time
				datetime="' . esc_attr( gmdate( 'Y-m-d' ) ) . '"
				itemprop="datePublished"
			>' . esc_html(
	sprintf(
		/* translators: %s: formatted publish date */
		__( 'Published %s', 'a11y-aeo' ),
		gmdate( get_option( 'date_format', 'F j, Y' ) )
	)
) . '</time>
		</p>
		<!-- /wp:paragraph -->

	</div>
	<!-- /wp:group -->

	<!-- wp:html -->
	<!-- JSON-LD: Article + Author schema -->
	<script type="application/ld+json">
	{
		"@context": "https://schema.org",
		"@type": "Article",
		"author": {
			"@type": "Person",
			"name": "' . esc_js( __( 'Author Full Name', 'a11y-aeo' ) ) . '",
			"jobTitle": "' . esc_js( __( 'Job Title / Role', 'a11y-aeo' ) ) . '",
			"url": ""
		},
		"datePublished": "' . esc_js( gmdate( 'Y-m-d' ) ) . '",
		"dateModified": "' . esc_js( gmdate( 'Y-m-d' ) ) . '"
	}
	</script>
	<!-- /wp:html -->

</section>
<!-- /wp:group -->
',
	)
);
