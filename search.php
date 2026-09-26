<?php
/**
 * Search results: the query echoed in the title with the real result count, the search
 * form again (prefilled), results as row cards with matches highlighted, and a helpful
 * empty state (destinations + latest stories).
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

if ( $clipto_has_q ) {
	$clipto_title_html = sprintf(
		/* translators: %s: the search query, wrapped in quotes and <em>. */
		esc_html__( 'Results for %s', 'clipto' ),
		'<em>' . esc_html( sprintf( /* translators: %s: search query. */ _x( '“%s”', 'quoted search query', 'clipto' ), $clipto_query ) ) . '</em>'
	);
} else {
	$clipto_title_html = esc_html__( 'Search Clipto', 'clipto' );
}

get_template_part(
	'template-parts/archive/header',
	null,
	array(
		'variant'    => 'search',
		'crumb'      => __( 'Search', 'clipto' ),
		'kicker'     => __( 'Search', 'clipto' ),
		'title_html' => $clipto_title_html,
		'stats'      => $clipto_has_q && $clipto_found ? array(
			clipto_archive_count_label( $clipto_found, 'result' ),
			clipto_archive_page_stat(),
		) : array(),
		'after'      => static function () {
			clipto_search_form(
				array(
					'id'    => 'search-page-field',
					'class' => 'archive-head__search',
				)
			);
		},
	)
);

if ( $clipto_has_q && have_posts() ) {
	get_template_part(
		'template-parts/archive/layout',
		'standard',
		array(
			'lead'      => false,
			'highlight' => clipto_search_terms(),
			'excerpt'   => 26,
		)
	);
} elseif ( $clipto_has_q ) {
	get_template_part(
		'template-parts/archive/empty',
		null,
		array(
			/* translators: %s: search query. */
			'title'   => sprintf( __( 'Nothing matched “%s”', 'clipto' ), $clipto_query ),
			'message' => __( 'Check the spelling, or try a broader word: a tool name, a task such as “writing” or “video”, or a topic.', 'clipto' ),
		)
	);
} else {
	get_template_part(
		'template-parts/archive/empty',
		null,
		array(
			'title'   => __( 'What are you looking for?', 'clipto' ),
			'message' => __( 'Search for a tool, a task or a topic, or start from one of the sections below.', 'clipto' ),
		)
	);
}

get_footer();
