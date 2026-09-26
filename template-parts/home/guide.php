<?php
/**
 * Featured AI Guide: the pillar page (clipto_destination( 'ai-guide' )) presented as the
 * publication's "start here" feature, with a "What's inside" preview built from the
 * guide's real H2 headings. Skipped when the page does not exist.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

$clipto_guide = clipto_destination( 'ai-guide' );
if ( ! $clipto_guide || ! $clipto_guide['object'] instanceof WP_Post ) {
	return;
}

$clipto_page     = $clipto_guide['object'];
$clipto_url      = $clipto_guide['url'];
$clipto_title    = get_the_title( $clipto_page );
$clipto_summary  = has_excerpt( $clipto_page )
	? wp_trim_words( wp_strip_all_tags( $clipto_page->post_excerpt ), 42, '…' )
	: clipto_excerpt( $clipto_page, 42 );
$clipto_minutes  = clipto_reading_time( $clipto_page );
$clipto_sections = clipto_home_guide_sections( $clipto_page, 6 );
$clipto_modified = (int) get_post_modified_time( 'U', true, $clipto_page );
$clipto_created  = (int) get_post_time( 'U', true, $clipto_page );
$clipto_updated  = $clipto_modified - $clipto_created > DAY_IN_SECONDS;
?>
<section class="home-guide section" aria-labelledby="home-guide-title">
	<div class="container">
		<div class="home-guide__panel" data-reveal>

			<p class="home-guide__bar">
				<?php echo clipto_logo_mark( 'home-guide__mark' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup. ?>
				<span class="kicker"><?php esc_html_e( 'The Clipto Guide', 'clipto' ); ?></span>
				<span class="home-guide__start"><?php esc_html_e( 'Start here', 'clipto' ); ?></span>
			</p>

			<div class="home-guide__grid">
				<a class="home-guide__media" href="<?php echo esc_url( $clipto_url ); ?>" tabindex="-1" aria-hidden="true">
					<?php
					echo clipto_media( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in clipto_media().
						$clipto_page,
						array(
							'size'  => 'clipto-wide',
							'ratio' => '16x9',
							'sizes' => '(min-width: 80em) 760px, (min-width: 64em) 58vw, 100vw',
						)
					);
					?>
				</a>

				<div class="home-guide__body">
					<h2 class="home-guide__title" id="home-guide-title">
						<a class="home-guide__link" href="<?php echo esc_url( $clipto_url ); ?>"><span class="headline-link"><?php echo esc_html( $clipto_title ); ?></span></a>
					</h2>

					<?php if ( $clipto_summary ) : ?>
						<p class="home-guide__summary"><?php echo esc_html( $clipto_summary ); ?></p>
					<?php endif; ?>

					<div class="meta home-guide__meta">
						<span class="meta__reading">
							<?php
							/* translators: %d: minutes. */
							echo esc_html( sprintf( _n( '%d min read', '%d min read', $clipto_minutes, 'clipto' ), $clipto_minutes ) );
							?>
						</span>
						<span>
							<?php
							if ( $clipto_updated ) {
								printf(
									/* translators: %s: date. */
									esc_html__( 'Updated %s', 'clipto' ),
									'<time datetime="' . esc_attr( get_the_modified_date( DATE_W3C, $clipto_page ) ) . '">' . esc_html( get_the_modified_date( '', $clipto_page ) ) . '</time>'
								);
							} else {
								echo '<time datetime="' . esc_attr( get_the_date( DATE_W3C, $clipto_page ) ) . '">' . esc_html( get_the_date( '', $clipto_page ) ) . '</time>';
							}
							?>
						</span>
					</div>

					<a class="btn home-guide__cta" href="<?php echo esc_url( $clipto_url ); ?>">
						<?php esc_html_e( 'Read the guide', 'clipto' ); ?>
						<?php clipto_the_icon( 'arrow-right' ); ?>
					</a>
				</div>

				<?php if ( $clipto_sections ) : ?>
					<nav class="home-guide__inside" aria-labelledby="home-guide-inside">
						<h3 class="home-guide__inside-title" id="home-guide-inside"><?php esc_html_e( 'What’s inside', 'clipto' ); ?></h3>
						<ol class="home-guide__toc" role="list">
							<?php foreach ( $clipto_sections as $clipto_i => $clipto_section ) : ?>
								<li class="home-guide__toc-item">
									<a class="home-guide__toc-link" href="<?php echo esc_url( $clipto_section['anchor'] ? $clipto_url . '#' . rawurlencode( $clipto_section['anchor'] ) : $clipto_url ); ?>">
										<span class="index-num" aria-hidden="true"><?php echo esc_html( clipto_home_num( $clipto_i + 1 ) ); ?></span>
										<span class="home-guide__toc-text"><?php echo esc_html( $clipto_section['text'] ); ?></span>
									</a>
								</li>
							<?php endforeach; ?>
						</ol>
					</nav>
				<?php endif; ?>
			</div>

		</div>
	</div>
</section>
