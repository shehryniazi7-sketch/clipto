<?php
/**
 * Category archive. Chooses a composition per destination: AI Tools (and its
 * subcategories) → tool discovery; AI News → lead + day-grouped stream; Earn With AI →
 * contrast band + editorial grid; anything else → the generic editorial list.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

require_once get_theme_file_path( 'template-parts/archive/helpers.php' );

get_header();

$clipto_variant = clipto_archive_variant();

get_template_part( 'template-parts/archive/header', null, clipto_archive_term_head_args( $clipto_variant ) );

if ( in_array( $clipto_variant, array( 'tools', 'news', 'earn' ), true ) ) {
	get_template_part( 'template-parts/archive/layout', $clipto_variant );
} else {
	get_template_part(
		'template-parts/archive/layout',
		'standard',
		array(
			/* translators: %s: category name. */
			'list_label' => sprintf( __( 'More in %s', 'clipto' ), single_cat_title( '', false ) ),
		)
	);
}

get_footer();
