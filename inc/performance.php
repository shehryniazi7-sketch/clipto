<?php
/**
 * Front-end weight reduction. Nothing here changes content or URLs.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

// Emoji detection script & styles (modern systems render emoji natively).
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );
remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
add_filter( 'emoji_svg_url', '__return_false' );

// Generator tag and legacy discovery links.
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );

// Only load CSS for core blocks actually present on the page.
add_filter( 'should_load_separate_core_block_assets', '__return_true' );

// Classic-theme button compatibility CSS is not needed (theme styles buttons).
add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_dequeue_style( 'classic-theme-styles' );
		if ( ! is_user_logged_in() ) {
			wp_deregister_style( 'dashicons' );
		}
	},
	20
);

/**
 * Remove the emoji DNS prefetch hint.
 *
 * @param array  $urls          URLs.
 * @param string $relation_type Relation.
 * @return array
 */
function clipto_resource_hints( $urls, $relation_type ) {
	if ( 'dns-prefetch' === $relation_type ) {
		$urls = array_filter(
			$urls,
			static function ( $url ) {
				return false === strpos( is_array( $url ) ? ( $url['href'] ?? '' ) : $url, 's.w.org' );
			}
		);
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'clipto_resource_hints', 10, 2 );
