<?php
/**
 * Block Pattern Registration Loader
 *
 * Registers the a11y-aeo pattern category and loads all block patterns.
 * Drop this file into your theme's functions.php or a plugin file and call:
 *
 *   require_once get_template_directory() . '/patterns/register-patterns.php';
 *
 * @package A11yAEO\BlockPatterns
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Register the custom pattern category and all block patterns.
 */
function a11y_aeo_register_block_patterns() {
	// Register a shared category for all a11y/AEO patterns.
	register_block_pattern_category(
		'a11y-aeo',
		array(
			'label'       => __( 'A11y & AEO', 'a11y-aeo' ),
			'description' => __( 'Accessible, schema-rich block patterns optimised for featured snippets and answer engine results.', 'a11y-aeo' ),
		)
	);

	$pattern_files = array(
		'definition-block.php',
		'tldr-summary.php',
		'faq-section.php',
		'author-block.php',
	);

	foreach ( $pattern_files as $file ) {
		$path = __DIR__ . '/' . $file;
		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}
}
add_action( 'init', 'a11y_aeo_register_block_patterns' );
