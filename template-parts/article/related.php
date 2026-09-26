<?php
/**
 * "Keep reading": three related articles from the post's primary category (topped up from
 * the parent category, then the latest articles, when the category is small).
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

$clipto_post_id = get_the_ID();
$clipto_cat     = clipto_primary_category( $clipto_post_id );
$clipto_exclude = array( $clipto_post_id );
$clipto_related = array();
$clipto_limit   = 3;

$clipto_sources = array();
if ( $clipto_cat ) {
	$clipto_sources[] = array( 'cat' => $clipto_cat->term_id );
	if ( $clipto_cat->parent ) {
		$clipto_sources[] = array( 'cat' => $clipto_cat->parent );
	}
}
$clipto_sources[] = array();

foreach ( $clipto_sources as $clipto_source ) {
	$clipto_need = $clipto_limit - count( $clipto_related );
	if ( $clipto_need <= 0 ) {
		break;
	}
	$clipto_found = clipto_posts(
		array_merge(
			$clipto_source,
			array(
				'posts_per_page' => $clipto_need,
				'post__not_in'   => $clipto_exclude,
			)
		),
		false
	);
	foreach ( $clipto_found as $clipto_p ) {
		$clipto_related[] = $clipto_p;
		$clipto_exclude[] = $clipto_p->ID;
	}
}

if ( ! $clipto_related ) {
	return;
}
?>
<section class="related section container" aria-labelledby="related-title">
	<?php
	clipto_section_head(
		array(
			'kicker'     => $clipto_cat ? $clipto_cat->name : '',
			'title'      => __( 'Keep reading', 'clipto' ),
			'id'         => 'related-title',
			'url'        => $clipto_cat ? get_category_link( $clipto_cat ) : '',
			/* translators: %s: category name. */
			'link_label' => $clipto_cat ? sprintf( __( 'More in %s', 'clipto' ), $clipto_cat->name ) : '',
		)
	);
	?>
	<div class="related__grid">
		<?php
		foreach ( $clipto_related as $clipto_i => $clipto_p ) {
			clipto_card(
				$clipto_p,
				'standard',
				array(
					'heading' => 'h3',
					'index'   => $clipto_i,
					'sizes'   => '(min-width: 64em) 26rem, (min-width: 48em) 33vw, 7rem',
				)
			);
		}
		?>
	</div>
</section>
