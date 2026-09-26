<?php
/**
 * Free AI: a compact discovery list of stories tagged free-ai, marked with the quiet
 * "Free" status dot rather than a coloured section. Skipped when the tag does not exist
 * or has nothing left to show.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

$clipto_free = clipto_destination( 'free-ai' );
if ( ! $clipto_free ) {
	return;
}

$clipto_posts = clipto_posts(
	array(
		'tag_id'         => (int) $clipto_free['object']->term_id,
		'posts_per_page' => 6,
	)
);
if ( ! $clipto_posts ) {
	return;
}
?>
<section class="home-free section" aria-labelledby="home-free-title">
	<div class="container">
		<?php
		clipto_section_head(
			array(
				'index'      => clipto_home_index(),
				'kicker'     => $clipto_free['label'],
				'title'      => __( 'Useful AI that costs <em>nothing</em>', 'clipto' ),
				'desc'       => __( 'Free tools and generous free tiers that hold up in real work.', 'clipto' ),
				'url'        => $clipto_free['url'],
				'link_label' => __( 'All free AI tools', 'clipto' ),
				'id'         => 'home-free-title',
			)
		);
		?>

		<ul class="home-free__list" role="list">
			<?php foreach ( $clipto_posts as $clipto_i => $clipto_p ) : ?>
				<li class="home-free__item">
					<?php
					clipto_card(
						$clipto_p,
						'row',
						array(
							'index' => $clipto_i % 3,
							'meta'  => array(
								'date'    => false,
								'reading' => true,
							),
						)
					);
					?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
