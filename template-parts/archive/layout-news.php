<?php
/**
 * AI News archive: lead story (page 1), then a chronological stream grouped by day
 * ("Today", "Yesterday", then dates). Later pages: stream only. Main query throughout.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

global $wp_query;

if ( ! have_posts() ) {
	get_template_part( 'template-parts/archive/empty' );
	return;
}
?>
<div class="archive-body archive-body--news container">

	<?php
	if ( ! is_paged() ) :
		the_post();
		?>
		<div class="news-lead">
			<?php
			clipto_card(
				get_post(),
				'lead',
				array(
					'heading' => 'h2',
					'eager'   => true,
					'reveal'  => false,
					'latest'  => true,
					'sizes'   => '(min-width: 64em) 58vw, 100vw',
					'meta'    => array( 'author' => true ),
					'kicker'  => clipto_archive_kicker( get_post() ),
				)
			);
			?>
		</div>
	<?php endif; ?>

	<?php if ( $wp_query->current_post + 1 < $wp_query->post_count ) : ?>
		<div class="news-stream">
			<?php
			$day  = '';
			$i    = 0;
			$open = false;
			while ( have_posts() ) :
				the_post();
				$d = clipto_archive_day_label( get_post() );
				if ( $d['key'] !== $day ) :
					if ( $open ) {
						echo '</div></section>';
					}
					$day  = $d['key'];
					$i    = 0;
					$open = true;
					$hid  = 'news-day-' . $d['key'];
					?>
					<section class="news-day" aria-labelledby="<?php echo esc_attr( $hid ); ?>">
						<h2 class="news-day__head" id="<?php echo esc_attr( $hid ); ?>">
							<span class="news-day__label"><?php echo esc_html( $d['label'] ); ?></span>
							<time class="news-day__date" datetime="<?php echo esc_attr( $d['datetime'] ); ?>"><?php echo esc_html( $d['date'] ); ?></time>
						</h2>
						<div class="news-day__list">
					<?php
				endif;
				++$i;
				clipto_card(
					get_post(),
					'row',
					array(
						'heading' => 'h3',
						'ratio'   => '3x2',
						'size'    => 'clipto-card',
						'sizes'   => '(min-width: 64em) 13rem, 7rem',
						'excerpt' => 22,
						'index'   => min( $i, 4 ),
						'class'   => 'news-row',
						'kicker'  => clipto_archive_kicker( get_post() ),
						// Older days already carry the date in the day heading; the relative
						// time ("3 hours ago") still adds something under Today / Yesterday.
						'meta'    => array( 'date' => $d['relative'] ),
					)
				);
			endwhile;
			if ( $open ) {
				echo '</div></section>';
			}
			?>
		</div>
	<?php endif; ?>

	<?php clipto_pagination(); ?>
</div>
