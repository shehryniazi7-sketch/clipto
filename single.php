<?php
/**
 * Single article: the reading experience.
 *
 * Layout (30-article.css):
 *   ≥80em  [TOC rail 14rem, sticky] [content: var(--measure)] [balance column]
 *   64–80em [TOC rail] [content] (wide media breaks out to the right)
 *   <64em  single column, collapsible "In this article" TOC before the content
 *
 * The content is rendered first (buffered) so the table of contents is built from
 * exactly the headings the reader sees (see inc/toc.php).
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
			'before'      => '<nav class="page-links" aria-label="' . esc_attr__( 'Article pages', 'clipto' ) . '"><span class="page-links__label">' . esc_html__( 'Pages', 'clipto' ) . '</span>',
			'after'       => '</nav>',
			'link_before' => '<span class="page-links__num">',
			'link_after'  => '</span>',
		)
	);
	$clipto_content = ob_get_clean();

	$clipto_toc     = clipto_get_toc();
	$clipto_has_toc = count( $clipto_toc ) >= clipto_toc_min_headings();
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'article' ); ?>>
		<?php get_template_part( 'template-parts/article/progress' ); ?>

		<div class="reading container<?php echo $clipto_has_toc ? ' has-toc' : ''; ?>">
			<?php get_template_part( 'template-parts/article/header', null, array( 'context' => 'post' ) ); ?>

			<?php get_template_part( 'template-parts/article/hero' ); ?>

			<div class="reading__rail">
				<div class="reading__rail-inner">
					<?php
					if ( $clipto_has_toc ) {
						get_template_part(
							'template-parts/article/toc',
							null,
							array(
								'toc'     => $clipto_toc,
								'variant' => 'rail',
							)
						);
					}
					get_template_part( 'template-parts/article/share', null, array( 'variant' => 'rail' ) );
					?>
				</div>
			</div>

			<div class="reading__main">
				<?php get_template_part( 'template-parts/article/tool-facts' ); ?>

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

				<footer class="article-footer">
					<?php get_template_part( 'template-parts/article/tags' ); ?>
					<?php get_template_part( 'template-parts/article/author-box' ); ?>
				</footer>
			</div>
		</div>
	</article>

	<?php get_template_part( 'template-parts/article/related' ); ?>

	<?php get_template_part( 'template-parts/article/post-nav' ); ?>

	<?php if ( comments_open() || get_comments_number() ) : ?>
		<div class="reading reading--comments container">
			<div class="reading__main">
				<?php comments_template(); ?>
			</div>
		</div>
	<?php endif; ?>
	<?php
endwhile;

get_footer();
