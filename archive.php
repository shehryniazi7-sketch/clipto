<?php
/**
 * Generic archive: date archives, custom taxonomies and post type archives. Uses the
 * standard editorial list (feature, then rows beside the destinations index).
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

require_once get_theme_file_path( 'template-parts/archive/helpers.php' );

get_header();

global $wp_query;

if ( is_day() ) {
	$clipto_kicker = __( 'Daily archive', 'clipto' );
	$clipto_title  = get_the_date();
} elseif ( is_month() ) {
	$clipto_kicker = __( 'Monthly archive', 'clipto' );
	$clipto_title  = get_the_date( _x( 'F Y', 'monthly archives date format', 'clipto' ) );
} elseif ( is_year() ) {
	$clipto_kicker = __( 'Yearly archive', 'clipto' );
	$clipto_title  = get_the_date( _x( 'Y', 'yearly archives date format', 'clipto' ) );
} else {
	$clipto_kicker = __( 'Archive', 'clipto' );
	add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );
	$clipto_title = wp_strip_all_tags( get_the_archive_title() );
	remove_filter( 'get_the_archive_title_prefix', '__return_empty_string' );
}

get_template_part(
	'template-parts/archive/header',
	null,
	array(
		'variant' => 'standard',
		'crumb'   => $clipto_title,
		'kicker'  => $clipto_kicker,
		'title'   => $clipto_title,
		'desc'    => get_the_archive_description(),
		'stats'   => array(
			clipto_archive_count_label( $wp_query->found_posts ),
			clipto_archive_page_stat(),
		),
	)
);

get_template_part(
	'template-parts/archive/layout',
	'standard',
	array( 'list_label' => __( 'More from this archive', 'clipto' ) )
);

get_footer();
