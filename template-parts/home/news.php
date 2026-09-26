<?php
/**
 * AI News: a lead story beside an ordered list of the next five, with "Latest" markers
 * for stories under 24 hours old. Skipped when AI News does not exist or is empty.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

$clipto_news = clipto_destination( 'ai-news' );
if ( ! $clipto_news ) {
	return;
}

$clipto_posts = clipto_posts(
	array(
		'cat'            => (int) $clipto_news['object']->term_id,
		'posts_per_page' => 6,
	)
);
if ( ! $clipto_posts ) {
	return;
}
$clipto_lead = array_shift( $clipto_posts );
?>
<section class="home-news section" aria-labelledby="home-news-title">
	<div class="container">
		<?php
		clipto_section_head(
			array(
				'index'      => clipto_home_index(),
				'kicker'     => $clipto_news['label'],
				'title'      => __( 'What’s moving <em>in AI</em>', 'clipto' ),
				'desc'       => __( 'The launches, research and policy shifts that change how you work — reported briefly, explained properly.', 'clipto' ),
				'url'        => $clipto_news['url'],
				'link_label' => __( 'More AI news', 'clipto' ),
				'id'         => 'home-news-title',
			)
		);
		?>

		<div class="home-news__grid<?php echo $clipto_posts ? '' : ' home-news__grid--solo'; ?>">
			<div class="home-news__lead">
				<?php
				clipto_card(
					$clipto_lead,
					'lead',
					array(
						'latest' => true,
						'sizes'  => '(min-width: 80em) 760px, (min-width: 64em) 58vw, 100vw',
					)
				);
				?>
			</div>

			<?php if ( $clipto_posts ) : ?>
				<div class="home-news__rail">
					<h3 class="home-news__rail-title"><?php esc_html_e( 'Also in AI News', 'clipto' ); ?></h3>
					<ol class="home-news__list" role="list">
						<?php foreach ( $clipto_posts as $clipto_i => $clipto_p ) : ?>
							<li>
								<?php
								clipto_card(
									$clipto_p,
									'numbered',
									array(
										'index'   => $clipto_i + 1,
										'heading' => 'h4',
										'kicker'  => false,
										'latest'  => clipto_is_recent( $clipto_p, 24 ),
									)
								);
								?>
							</li>
						<?php endforeach; ?>
					</ol>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
