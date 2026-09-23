<?php
/**
 * The main template file — also the default homepage when
 * Settings > Reading is set to "Your latest posts".
 *
 * @package Clipto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$is_home = is_front_page();

if ( $is_home ) {
	clipto_render_homepage_hero();
	clipto_render_stats_strip();
	clipto_render_category_showcase();
}
?>
<div class="clipto-container" id="clipto-latest">
	<?php clipto_breadcrumbs(); ?>

	<?php if ( is_404() ) : ?>
		<header class="clipto-archive-header">
			<h1 class="clipto-archive-title"><?php esc_html_e( 'Page Not Found', 'clipto' ); ?></h1>
			<div class="clipto-archive-description"><?php esc_html_e( 'The page you were looking for doesn\'t exist. Try a search instead.', 'clipto' ); ?></div>
		</header>
		<?php get_search_form(); ?>
	<?php elseif ( $is_home ) : ?>
		<h2 class="clipto-section-title clipto-reveal"><span class="clipto-kicker"><?php esc_html_e( 'Fresh', 'clipto' ); ?></span><?php esc_html_e( 'Latest Articles', 'clipto' ); ?></h2>
	<?php elseif ( is_home() ) : ?>
		<header class="clipto-archive-header">
			<h1 class="clipto-archive-title"><?php single_post_title(); ?></h1>
		</header>
	<?php endif; ?>

	<?php if ( is_404() ) : ?>
		<?php // Nothing further to loop over on a 404 — the notice and search form above are the full response. ?>
	<?php elseif ( have_posts() ) : ?>
		<div class="clipto-grid clipto-grid--posts">
			<?php
			$i = 0;
			while ( have_posts() ) :
				the_post();
				$i++;
				clipto_render_post_card( get_the_ID(), $i );
			endwhile;
			?>
		</div>
		<?php clipto_pagination(); ?>
	<?php else : ?>
		<div class="clipto-empty-state">
			<p><?php esc_html_e( 'No articles published yet — check back soon.', 'clipto' ); ?></p>
		</div>
	<?php endif; ?>
</div>
<?php
get_footer();
