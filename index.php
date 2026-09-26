<?php
/**
 * Fallback template. Singular views get a plain, readable article; everything else
 * gets the generic editorial list (feature, then rows beside the destinations index).
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

require_once get_theme_file_path( 'template-parts/archive/helpers.php' );

get_header();

if ( is_singular() ) :
	while ( have_posts() ) :
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'fallback-entry container container--read' ); ?>>
			<header class="fallback-entry__head">
				<?php the_title( '<h1 class="fallback-entry__title">', '</h1>' ); ?>
				<?php clipto_meta( get_post(), array( 'author' => true ) ); ?>
			</header>
			<div class="entry-content fallback-entry__content">
				<?php
				the_content();
				wp_link_pages();
				?>
			</div>
		</article>
		<?php
	endwhile;
else :
	global $wp_query;

	$clipto_posts_page = (int) get_option( 'page_for_posts' );
	$clipto_title      = is_home() && $clipto_posts_page ? get_the_title( $clipto_posts_page ) : __( 'Latest stories', 'clipto' );

	get_template_part(
		'template-parts/archive/header',
		null,
		array(
			'variant' => 'standard',
			'crumb'   => $clipto_title,
			'kicker'  => get_bloginfo( 'name' ),
			'title'   => $clipto_title,
			'desc'    => get_bloginfo( 'description' ),
			'stats'   => array(
				clipto_archive_count_label( $wp_query->found_posts ),
				clipto_archive_updated_stat( clipto_archive_latest_post() ),
				clipto_archive_page_stat(),
			),
		)
	);

	get_template_part( 'template-parts/archive/layout', 'standard', array( 'list_label' => __( 'More stories', 'clipto' ) ) );
endif;

get_footer();
