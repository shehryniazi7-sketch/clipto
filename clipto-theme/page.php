<?php
/**
 * The template for displaying static pages.
 *
 * If this page is set as the site's static front page (Settings > Reading),
 * it also gets the homepage hero/stats/category-showcase treatment so the
 * premium homepage identity isn't tied to one specific Reading setting.
 *
 * @package Clipto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

if ( is_front_page() ) {
	clipto_render_homepage_hero();
	clipto_render_stats_strip();
	clipto_render_category_showcase();
}

while ( have_posts() ) :
	the_post();
	?>
	<div class="clipto-container clipto-container--article" id="clipto-latest">
		<?php clipto_breadcrumbs(); ?>
		<article <?php post_class( 'clipto-page' ); ?> id="post-<?php the_ID(); ?>">
			<?php if ( ! is_front_page() ) : ?>
				<header class="clipto-article__header">
					<h1 class="clipto-article__title"><?php the_title(); ?></h1>
				</header>
			<?php endif; ?>

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="clipto-article__thumb">
					<?php the_post_thumbnail( 'clipto-hero', array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => wp_strip_all_tags( get_the_title() ) ) ); ?>
				</div>
			<?php endif; ?>

			<div class="clipto-article__content">
				<?php the_content(); ?>
				<?php
				wp_link_pages(
					array(
						'before' => '<nav class="clipto-page-links" aria-label="' . esc_attr__( 'Page pages', 'clipto' ) . '"><span>' . esc_html__( 'Pages:', 'clipto' ) . '</span>',
						'after'  => '</nav>',
					)
				);
				?>
			</div>
		</article>
	</div>
	<?php
endwhile;

get_footer();
