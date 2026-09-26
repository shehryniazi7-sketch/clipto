<?php
/**
 * Front page — the designed Clipto homepage.
 *
 * Renders the same editorial composition whether Settings → Reading shows the latest
 * posts or a static page (a static front page's own content is intentionally not shown).
 * Each section lives in template-parts/home/ and skips itself when its destination or
 * posts are missing. Posts are drawn with clipto_posts(), so nothing repeats down the page.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

// With "Your latest posts" on the front page, /page/2/ and beyond also resolve to this
// template: those pages show the plain posts stream instead of the homepage again.
if ( is_home() && is_paged() ) {
	require get_theme_file_path( 'home.php' );
	return;
}

require_once get_theme_file_path( 'template-parts/home/helpers.php' );

get_header();

/**
 * Filters the homepage section order. Each slug maps to template-parts/home/{slug}.php.
 *
 * @param string[] $sections Section slugs.
 */
$clipto_home_sections = apply_filters(
	'clipto_home_sections',
	array( 'hero', 'guide', 'tools', 'news', 'earn', 'free', 'latest' )
);

foreach ( (array) $clipto_home_sections as $clipto_home_section ) {
	get_template_part( 'template-parts/home/' . sanitize_key( $clipto_home_section ) );
}
unset( $clipto_home_sections, $clipto_home_section );

get_footer();
