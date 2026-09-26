<?php
/**
 * Theme setup: supports, menus, image sizes, excerpt, body classes.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register theme features.
 */
function clipto_setup() {
	load_theme_textdomain( 'clipto', CLIPTO_DIR . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);
	add_theme_support(
		'custom-logo',
		array(
			'height'               => 64,
			'width'                => 240,
			'flex-height'          => true,
			'flex-width'           => true,
			'unlink-homepage-logo' => false,
		)
	);

	add_editor_style( 'assets/css/editor.css' );

	register_nav_menus(
		array(
			'primary' => __( 'Primary navigation', 'clipto' ),
			'footer'  => __( 'Footer navigation', 'clipto' ),
		)
	);

	// Editorial image sizes (DESIGN.md §6). Ratios are enforced in CSS too.
	add_image_size( 'clipto-wide', 1600, 900, true );
	add_image_size( 'clipto-feature', 1200, 800, true );
	add_image_size( 'clipto-card', 720, 480, true );
	add_image_size( 'clipto-thumb', 240, 240, true );
}
add_action( 'after_setup_theme', 'clipto_setup' );

/**
 * Content width for embeds (matches the reading measure at 16px).
 */
function clipto_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'clipto_content_width', 640 ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
}
add_action( 'after_setup_theme', 'clipto_content_width', 0 );

/**
 * Excerpts: shorter and without the default [...] marker.
 */
add_filter( 'excerpt_length', static function () { return 32; } );
add_filter( 'excerpt_more', static function () { return '…'; } );

/**
 * Body classes for template context.
 *
 * @param string[] $classes Classes.
 * @return string[]
 */
function clipto_body_classes( $classes ) {
	if ( is_category() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$classes[] = 'archive-' . sanitize_html_class( $term->slug );
			if ( clipto_is_tools_term( $term ) ) {
				$classes[] = 'is-tools-archive';
			}
		}
	}
	if ( is_singular() && ! is_front_page() ) {
		$classes[] = 'is-reading';
	}
	return $classes;
}
add_filter( 'body_class', 'clipto_body_classes' );

/**
 * Named sizes in the Image block size picker.
 *
 * @param array $sizes Sizes.
 * @return array
 */
function clipto_image_size_names( $sizes ) {
	return array_merge(
		$sizes,
		array(
			'clipto-wide'    => __( 'Wide 16:9', 'clipto' ),
			'clipto-feature' => __( 'Feature 3:2', 'clipto' ),
		)
	);
}
add_filter( 'image_size_names_choose', 'clipto_image_size_names' );
