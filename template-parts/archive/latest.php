<?php
/**
 * "Latest stories" strip: the newest posts as compact cards (404, empty states).
 *
 * Args: title, kicker, count (default 4), exclude (int[]), index (numeral), id.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

$a = wp_parse_args(
	isset( $args ) && is_array( $args ) ? $args : array(),
	array(
		'title'   => __( 'Latest stories', 'clipto' ),
		'kicker'  => __( 'Latest', 'clipto' ),
		'count'   => 4,
		'exclude' => array(),
		'index'   => '',
		'id'      => 'latest-stories-title',
	)
);

$latest = clipto_posts(
	array(
		'posts_per_page' => max( 1, (int) $a['count'] ),
		'post__not_in'   => array_map( 'intval', (array) $a['exclude'] ),
	),
	false
);
if ( ! $latest ) {
	return;
}
?>
<section class="latest-strip" aria-labelledby="<?php echo esc_attr( $a['id'] ); ?>">
	<?php
	clipto_section_head(
		array(
			'index'  => $a['index'],
			'kicker' => $a['kicker'],
			'title'  => $a['title'],
			'id'     => $a['id'],
		)
	);
	?>
	<div class="latest-strip__grid">
		<?php
		foreach ( $latest as $n => $item ) {
			clipto_card(
				$item,
				'compact',
				array(
					'heading' => 'h3',
					'index'   => $n + 1,
				)
			);
		}
		?>
	</div>
</section>
