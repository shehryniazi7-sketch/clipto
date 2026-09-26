<?php
/**
 * Clipto theme bootstrap.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

define( 'CLIPTO_VERSION', '1.0.0' );
define( 'CLIPTO_DIR', get_template_directory() );
define( 'CLIPTO_URI', get_template_directory_uri() );

$clipto_includes = array(
	'inc/icons.php',          // Inline SVG icon set + logo mark.
	'inc/template-tags.php',  // Core helpers: destinations, cards, media, meta.
	'inc/setup.php',          // Theme supports, menus, image sizes.
	'inc/assets.php',         // Styles, scripts, font preloads, theme bootstrap.
	'inc/performance.php',    // Front-end weight reduction.
	'inc/navigation.php',     // Primary navigation, mega-menu, mobile menu.
	'inc/customizer.php',     // Newsletter & footer settings.
	'inc/toc.php',            // Heading anchors + table of contents.
	'inc/review-meta.php',    // AI tool facts meta box.
	'inc/blocks.php',         // Block styles + pattern category for editorial components.
);

foreach ( $clipto_includes as $clipto_file ) {
	$clipto_path = CLIPTO_DIR . '/' . $clipto_file;
	if ( is_readable( $clipto_path ) ) {
		require_once $clipto_path;
	}
}
unset( $clipto_includes, $clipto_file, $clipto_path );
