<?php
/**
 * Earn With AI: the homepage's contrast band. A large 4:3 feature and up to four practical
 * stories labelled with their reading time — headlines only, so no dek is ever cut off
 * mid-phrase. Colour tokens are re-pointed to the invert palette inside the band (see
 * 20-home.css), so shared card styles apply unchanged. Skipped when Earn With AI does not
 * exist or is empty.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

$clipto_earn = clipto_destination( 'earn-with-ai' );
if ( ! $clipto_earn ) {
	return;
}

$clipto_posts = clipto_posts(
	array(
		'cat'            => (int) $clipto_earn['object']->term_id,
		'posts_per_page' => 5,
	)
);
if ( ! $clipto_posts ) {
	return;
}
$clipto_lead = array_shift( $clipto_posts );
?>
<section class="home-earn section" aria-labelledby="home-earn-title">
	<div class="container">
		<div class="home-earn__grid<?php echo $clipto_posts ? '' : ' home-earn__grid--solo'; ?>">
			<?php
			clipto_section_head(
				array(
					'index'      => clipto_home_index(),
					'kicker'     => $clipto_earn['label'],
					'title'      => __( 'Put AI to <em>work</em>', 'clipto' ),
					'desc'       => __( 'Practical, professional ways to turn AI skills into better work and income — tested workflows and honest numbers, not shortcuts.', 'clipto' ),
					'url'        => $clipto_earn['url'],
					'link_label' => __( 'All Earn With AI stories', 'clipto' ),
					'id'         => 'home-earn-title',
				)
			);
			?>

			<div class="home-earn__feature">
				<?php
				clipto_card(
					$clipto_lead,
					'feature',
					array(
						'ratio'   => '4x3',
						'excerpt' => 34,
						'sizes'   => '(min-width: 80em) 760px, (min-width: 64em) 58vw, 100vw',
					)
				);
				?>
			</div>

			<?php if ( $clipto_posts ) : ?>
				<ol class="home-earn__list" role="list">
					<?php foreach ( $clipto_posts as $clipto_i => $clipto_p ) : ?>
						<li class="home-earn__item">
							<span class="home-earn__num" aria-hidden="true"><?php echo esc_html( clipto_home_num( $clipto_i + 1 ) ); ?></span>
							<?php
							clipto_card(
								$clipto_p,
								'compact',
								array(
									'index'   => $clipto_i + 1,
									'kicker'  => false,
									'excerpt' => false,
									'meta'    => array( 'date' => false ),
								)
							);
							?>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
		</div>
	</div>
</section>
