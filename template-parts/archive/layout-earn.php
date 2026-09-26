<?php
/**
 * Earn With AI archive: the header is a contrast band (see 40-archive.css); page 1
 * opens with a large 4:3 feature that overlaps the band, then an editorial grid —
 * one row of two, then rows of three. Later pages: rows of three. Main query.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

global $wp_query;
$paged = is_paged();

if ( ! have_posts() ) {
	get_template_part( 'template-parts/archive/empty' );
	return;
}
?>
<div class="archive-body archive-body--earn container">

	<?php
	if ( ! $paged ) :
		the_post();
		?>
		<div class="earn-lead">
			<?php
			clipto_card(
				get_post(),
				'feature',
				array(
					'heading' => 'h2',
					'eager'   => true,
					'reveal'  => false,
					'ratio'   => '4x3',
					'size'    => 'clipto-feature',
					'sizes'   => '(min-width: 64em) 58vw, 100vw',
					'excerpt' => 34,
					'class'   => 'earn-lead__card',
				)
			);
			?>
		</div>
	<?php endif; ?>

	<?php if ( $wp_query->current_post + 1 < $wp_query->post_count ) : ?>
		<section class="earn-more" aria-labelledby="earn-more-label">
			<h2 class="archive-label" id="earn-more-label">
				<span class="archive-label__text">
					<?php
					/* translators: %s: category name. */
					printf( esc_html__( 'More from %s', 'clipto' ), esc_html( single_term_title( '', false ) ) );
					?>
				</span>
			</h2>
			<div class="earn-grid">
				<?php
				$i = 0;
				while ( have_posts() ) :
					the_post();
					$wide = ! $paged && $i < 2;
					clipto_card(
						get_post(),
						'standard',
						array(
							'heading' => 'h3',
							'index'   => $wide ? $i + 1 : ( ( $i - ( $paged ? 0 : 2 ) ) % 3 ) + 1,
							'excerpt' => $wide ? 22 : false,
							'size'    => $wide ? 'clipto-feature' : 'clipto-card',
							'sizes'   => $wide ? '(min-width: 64em) 45vw, (min-width: 40em) 50vw, 100vw' : '(min-width: 64em) 30vw, (min-width: 40em) 50vw, 100vw',
							'class'   => $wide ? 'earn-grid__wide' : '',
						)
					);
					++$i;
				endwhile;
				?>
			</div>
		</section>
	<?php endif; ?>

	<?php clipto_pagination(); ?>
</div>
