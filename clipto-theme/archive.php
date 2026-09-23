<?php
/**
 * The template for displaying category, tag, and general archives —
 * and also the AI Tools post-type archive (there is no dedicated
 * archive-clipto_tool.php, by design, to keep the theme's file count
 * fixed), which gets the plugin's premium tool-card grid instead of
 * the generic post-card grid when the plugin is active.
 *
 * @package Clipto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$is_tools_archive = is_post_type_archive( 'clipto_tool' ) && function_exists( 'clipto_render_tool_card' );
?>
<div class="clipto-container">
	<?php clipto_breadcrumbs(); ?>

	<header class="clipto-archive-header">
		<?php if ( is_search() ) : ?>
			<h1 class="clipto-archive-title">
				<?php
				printf(
					/* translators: %s: the searched term */
					esc_html__( 'Search results for: %s', 'clipto' ),
					'<span>' . esc_html( get_search_query() ) . '</span>'
				);
				?>
			</h1>
		<?php else : ?>
			<h1 class="clipto-archive-title"><?php the_archive_title(); ?></h1>
			<?php $description = get_the_archive_description(); ?>
			<?php if ( $description ) : ?>
				<div class="clipto-archive-description"><?php echo wp_kses_post( $description ); ?></div>
			<?php endif; ?>
		<?php endif; ?>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="clipto-grid <?php echo $is_tools_archive ? 'clipto-grid--tools' : 'clipto-grid--posts'; ?>">
			<?php
			$i = 0;
			while ( have_posts() ) :
				the_post();
				$i++;
				if ( $is_tools_archive ) {
					clipto_render_tool_card( get_the_ID(), 'h2' );
				} else {
					clipto_render_post_card( get_the_ID(), $i );
				}
			endwhile;
			?>
		</div>
		<?php clipto_pagination(); ?>
	<?php elseif ( is_search() ) : ?>
		<div class="clipto-empty-state">
			<p>
				<?php
				printf(
					/* translators: %s: the searched term */
					esc_html__( 'Nothing matched "%s" — try a different search.', 'clipto' ),
					esc_html( get_search_query() )
				);
				?>
			</p>
			<?php get_search_form(); ?>
		</div>
	<?php elseif ( $is_tools_archive ) : ?>
		<div class="clipto-empty-state">
			<p><?php esc_html_e( 'No AI tools found yet.', 'clipto' ); ?></p>
		</div>
	<?php else : ?>
		<div class="clipto-empty-state">
			<p><?php esc_html_e( 'No articles found in this section yet.', 'clipto' ); ?></p>
		</div>
	<?php endif; ?>
</div>
<?php
get_footer();
