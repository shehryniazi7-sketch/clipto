<?php
/**
 * Pages. Long pages (≥3 headings) get the full reading layout — sticky TOC rail,
 * collapsible mobile TOC, reading progress. The AI Guide (the pillar resource) is
 * presented as "The Clipto Guide" with byline, dek, share actions and featured image.
 * Short pages (legal, about…) use a calm single reading column.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	ob_start();
	the_content();
	wp_link_pages(
		array(
			'before'      => '<nav class="page-links" aria-label="' . esc_attr__( 'Page sections', 'clipto' ) . '"><span class="page-links__label">' . esc_html__( 'Pages', 'clipto' ) . '</span>',
			'after'       => '</nav>',
			'link_before' => '<span class="page-links__num">',
			'link_after'  => '</span>',
		)
	);
	$clipto_content = ob_get_clean();

	$clipto_guide    = clipto_destination( 'ai-guide' );
	$clipto_is_guide = $clipto_guide && isset( $clipto_guide['object']->ID ) && (int) $clipto_guide['object']->ID === (int) get_the_ID();
	$clipto_toc      = clipto_get_toc();
	$clipto_has_toc  = count( $clipto_toc ) >= clipto_toc_min_headings();
	$clipto_classes  = array( 'reading', 'container' );
	$clipto_classes[] = $clipto_has_toc ? 'has-toc' : 'reading--simple';
	if ( $clipto_is_guide ) {
		$clipto_classes[] = 'reading--guide';
	}
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( $clipto_is_guide ? 'article article--page article--guide' : 'article article--page' ); ?>>
		<?php
		if ( $clipto_has_toc ) {
			get_template_part( 'template-parts/article/progress' );
		}
		?>

		<div class="<?php echo esc_attr( implode( ' ', $clipto_classes ) ); ?>">
			<?php
			get_template_part(
				'template-parts/article/header',
				null,
				array(
					'context' => 'page',
					'kicker'  => $clipto_is_guide ? __( 'The Clipto Guide', 'clipto' ) : '',
					'byline'  => $clipto_is_guide,
					'share'   => $clipto_is_guide,
					'reading' => $clipto_is_guide || $clipto_has_toc,
				)
			);
			?>

			<?php get_template_part( 'template-parts/article/hero' ); ?>

			<?php if ( $clipto_has_toc ) : ?>
				<div class="reading__rail">
					<div class="reading__rail-inner">
						<?php
						get_template_part(
							'template-parts/article/toc',
							null,
							array(
								'toc'     => $clipto_toc,
								'variant' => 'rail',
							)
						);
						if ( $clipto_is_guide ) {
							get_template_part( 'template-parts/article/share', null, array( 'variant' => 'rail' ) );
						}
						?>
					</div>
				</div>
			<?php endif; ?>

			<div class="reading__main">
				<?php
				if ( $clipto_has_toc ) {
					get_template_part(
						'template-parts/article/toc',
						null,
						array(
							'toc'     => $clipto_toc,
							'variant' => 'disclosure',
						)
					);
				}
				?>

				<div class="entry-content">
					<?php echo $clipto_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content() output. ?>
				</div>

				<?php
				if ( $clipto_is_guide ) {
					echo '<footer class="article-footer">';
					get_template_part( 'template-parts/article/author-box' );
					echo '</footer>';
				}
				?>
			</div>
		</div>
	</article>

	<?php if ( ! post_password_required() && ( comments_open() || get_comments_number() ) ) : ?>
		<div class="reading reading--comments container<?php echo $clipto_has_toc ? '' : ' reading--simple'; ?>">
			<div class="reading__main">
				<?php comments_template(); ?>
			</div>
		</div>
	<?php endif; ?>
	<?php
endwhile;

get_footer();
