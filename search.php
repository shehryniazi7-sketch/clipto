<?php
/**
 * Search results: the query echoed in the title with the real result count, the search
 * form again (prefilled), results as row cards with matches highlighted. No results (or
 * no query): the title says so, the advice sits under it, and the body offers the
 * site's sections and the latest stories.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

require_once get_theme_file_path( 'template-parts/archive/helpers.php' );

get_header();

global $wp_query;

$clipto_query = trim( (string) get_search_query( false ) );
$clipto_found = (int) $wp_query->found_posts;
$clipto_has_q = '' !== $clipto_query;
$clipto_hits  = $clipto_has_q && have_posts();
$clipto_q_em  = '<em>' . esc_html( sprintf( /* translators: %s: search query. */ _x( '“%s”', 'quoted search query', 'clipto' ), $clipto_query ) ) . '</em>';

if ( $clipto_hits ) {
	/* translators: %s: the search query, wrapped in quotes and <em>. */
	$clipto_title_html = sprintf( esc_html__( 'Results for %s', 'clipto' ), $clipto_q_em );
	$clipto_desc       = '';
} elseif ( $clipto_has_q ) {
	/* translators: %s: the search query, wrapped in quotes and <em>. */
	$clipto_title_html = sprintf( esc_html__( 'No results for %s', 'clipto' ), $clipto_q_em );
	$clipto_desc       = __( 'Check the spelling, or try a broader word: a tool name, a task such as “writing” or “video”, or a topic.', 'clipto' );
} else {
	$clipto_title_html = esc_html__( 'Search Clipto', 'clipto' );
	$clipto_desc       = __( 'Search for a tool, a task or a topic, or start from one of the sections below.', 'clipto' );
}

get_template_part(
	'template-parts/archive/header',
	null,
	array(
		'variant'    => 'search',
		'crumb'      => __( 'Search', 'clipto' ),
		'kicker'     => __( 'Search', 'clipto' ),
		'title_html' => $clipto_title_html,
		'desc'       => $clipto_desc ? esc_html( $clipto_desc ) : '',
		// The page position is in the results label ("11–16 of 16").
		'stats'      => $clipto_hits ? array( clipto_archive_count_label( $clipto_found, 'result' ) ) : array(),
		'after'      => static function () {
			clipto_search_form(
				array(
					'id'          => 'search-page-field',
					'class'       => 'archive-head__search',
					'placeholder' => __( 'Search Clipto', 'clipto' ),
				)
			);
		},
	)
);

if ( $clipto_hits ) {
	get_template_part(
		'template-parts/archive/layout',
		'standard',
		array(
			'lead'       => false,
			'list_label' => __( 'Most relevant first', 'clipto' ),
			'list_count' => clipto_archive_range_label( 'result' ),
			'highlight'  => clipto_search_terms(),
			'excerpt'    => 26,
		)
	);
} else {
	// The message is already under the title; the body offers the ways onward.
	get_template_part(
		'template-parts/archive/empty',
		null,
		array(
			'title'   => '',
			'message' => '',
		)
	);
}

get_footer();
