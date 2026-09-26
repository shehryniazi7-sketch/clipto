<?php
/**
 * Tag archive. The Free AI tag gets the compact discovery list; other tags use the
 * generic editorial list.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

require_once get_theme_file_path( 'template-parts/archive/helpers.php' );

get_header();

$clipto_variant = clipto_archive_variant();

get_template_part( 'template-parts/archive/header', null, clipto_archive_term_head_args( $clipto_variant ) );

if ( 'free' === $clipto_variant ) {
	get_template_part( 'template-parts/archive/layout', 'free' );
} else {
	get_template_part(
		'template-parts/archive/layout',
		'standard',
		array(
			/* translators: %s: tag name. */
			'list_label' => sprintf( __( 'More on %s', 'clipto' ), single_tag_title( '', false ) ),
		)
	);
}

get_footer();
