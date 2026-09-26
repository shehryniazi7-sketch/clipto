<?php
/**
 * Latest from Clipto: one featured story and a two-column editorial stream of the most
 * recent stories not already shown on the page. The "More" link points at a real archive
 * (page 2 of the posts front page, or the posts page) and is omitted otherwise.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

$clipto_posts = clipto_posts( array( 'posts_per_page' => 9 ) );
if ( ! $clipto_posts ) {
	return;
}
$clipto_lead = array_shift( $clipto_posts );
$clipto_more = clipto_home_more_url();
?>
<section class="home-latest section" aria-labelledby="home-latest-title">
	<div class="container">
		<?php
		clipto_section_head(
			array(
				'index'      => clipto_home_index(),
				'kicker'     => __( 'From the desk', 'clipto' ),
				'title'      => __( 'Latest from <em>Clipto</em>', 'clipto' ),
				'url'        => $clipto_more,
				'link_label' => __( 'More stories', 'clipto' ),
				'id'         => 'home-latest-title',
			)
		);
		?>

		<div class="home-latest__grid<?php echo $clipto_posts ? '' : ' home-latest__grid--solo'; ?>">
			<div class="home-latest__lead">
				<?php
				clipto_card(
					$clipto_lead,
					'feature',
					array(
						'excerpt' => 26,
						'sizes'   => '(min-width: 64em) 460px, (min-width: 48em) 50vw, 100vw',
					)
				);
				?>
			</div>

			<?php if ( $clipto_posts ) : ?>
				<ul class="home-latest__list" role="list">
					<?php foreach ( $clipto_posts as $clipto_i => $clipto_p ) : ?>
						<li class="home-latest__item">
							<?php clipto_card( $clipto_p, 'row', array( 'index' => $clipto_i % 2 ) ); ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</section>
