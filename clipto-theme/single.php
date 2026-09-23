<?php
/**
 * The template for displaying single posts.
 *
 * @package Clipto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	// AI Tool detail pages (/ai-tools/{slug}/) get their own directory-entry
	// presentation instead of the article layout below.
	if ( is_singular( 'clipto_tool' ) && function_exists( 'clipto_get_tool_data' ) ) {
		clipto_render_tool_single( get_the_ID() );
		continue;
	}

	$post_id      = get_the_ID();
	$author_id    = (int) get_the_author_meta( 'ID' );
	$categories   = get_the_category();
	$category     = ! empty( $categories ) ? $categories[0] : null;
	$reading_time = clipto_reading_time_text( $post_id );
	$ai_summary   = clipto_get_ai_summary( $post_id );
	$ai_takeaway  = clipto_get_ai_takeaway( $post_id );
	$is_updated   = get_the_modified_time( 'U', $post_id ) > ( get_the_time( 'U', $post_id ) + 60 );
	?>
	<div class="clipto-container clipto-container--article">
		<?php clipto_breadcrumbs(); ?>

		<article <?php post_class( 'clipto-article' ); ?> id="post-<?php echo esc_attr( $post_id ); ?>">
			<header class="clipto-article__header">
				<?php if ( $category ) : ?>
					<a class="clipto-badge clipto-badge--category" href="<?php echo esc_url( get_category_link( $category->term_id ) ); ?>"><?php echo esc_html( $category->name ); ?></a>
				<?php endif; ?>

				<h1 class="clipto-article__title"><?php the_title(); ?></h1>

				<?php if ( has_excerpt() ) : ?>
					<p class="clipto-article__lede"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>

				<div class="clipto-article__meta">
					<a class="clipto-article__author" href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>">
						<?php echo get_avatar( $author_id, 36 ); ?>
						<span><?php echo esc_html( get_the_author() ); ?></span>
					</a>
					<span class="clipto-article__date">
						<?php esc_html_e( 'Published', 'clipto' ); ?>
						<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
					</span>
					<?php if ( $is_updated ) : ?>
						<span class="clipto-article__updated">
							<?php esc_html_e( 'Updated', 'clipto' ); ?>
							<time datetime="<?php echo esc_attr( get_the_modified_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_modified_date() ); ?></time>
						</span>
					<?php endif; ?>
					<span class="clipto-article__reading-time"><?php echo esc_html( $reading_time ); ?></span>

					<button
						type="button"
						class="clipto-bookmark-btn"
						aria-pressed="false"
						data-post-id="<?php echo esc_attr( $post_id ); ?>"
						data-permalink="<?php echo esc_url( get_permalink( $post_id ) ); ?>"
						data-title="<?php echo esc_attr( get_the_title( $post_id ) ); ?>"
						data-label-save="<?php echo esc_attr__( 'Save', 'clipto' ); ?>"
						data-label-saved="<?php echo esc_attr__( 'Saved', 'clipto' ); ?>"
					>
						<svg aria-hidden="true" viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M6 2h12a1 1 0 011 1v19l-7-4-7 4V3a1 1 0 011-1z"/></svg>
						<span class="clipto-bookmark-label"><?php esc_html_e( 'Save', 'clipto' ); ?></span>
					</button>
				</div>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="clipto-article__thumb">
					<?php the_post_thumbnail( 'clipto-hero', array( 'fetchpriority' => 'high', 'decoding' => 'async', 'sizes' => clipto_hero_image_sizes(), 'alt' => wp_strip_all_tags( get_the_title( $post_id ) ) ) ); ?>
				</div>
			<?php endif; ?>

			<div class="clipto-article__content">
				<?php the_content(); ?>
				<?php
				wp_link_pages(
					array(
						'before' => '<nav class="clipto-page-links" aria-label="' . esc_attr__( 'Article pages', 'clipto' ) . '"><span>' . esc_html__( 'Pages:', 'clipto' ) . '</span>',
						'after'  => '</nav>',
					)
				);
				?>
			</div>

			<?php if ( $ai_summary ) : ?>
				<aside class="clipto-ai-summary clipto-reveal" aria-label="<?php esc_attr_e( 'AI Summary', 'clipto' ); ?>">
					<h2><?php esc_html_e( 'AI Summary', 'clipto' ); ?></h2>
					<div class="clipto-ai-summary__body"><?php echo $ai_summary; // phpcs:ignore -- already wp_kses_post()'d in clipto_get_ai_summary(). ?></div>
					<?php if ( $ai_takeaway ) : ?>
						<p class="clipto-ai-summary__takeaway"><strong><?php esc_html_e( 'Key takeaway:', 'clipto' ); ?></strong> <?php echo esc_html( $ai_takeaway ); ?></p>
					<?php endif; ?>
				</aside>
			<?php endif; ?>

			<?php
			$related = clipto_get_related_posts( $post_id, 3 );
			if ( $related->have_posts() ) :
				?>
				<section class="clipto-related">
					<h2 class="clipto-section-title"><?php esc_html_e( 'Related Articles', 'clipto' ); ?></h2>
					<div class="clipto-grid clipto-grid--posts">
						<?php
						$r = 0;
						while ( $related->have_posts() ) :
							$related->the_post();
							$r++;
							clipto_render_post_card( get_the_ID(), $r, 'h3', 'lazy' );
						endwhile;
						wp_reset_postdata();
						?>
					</div>
				</section>
			<?php endif; ?>

			<footer class="clipto-article__footer">
				<?php clipto_render_author_box( $author_id ); ?>
			</footer>

			<?php clipto_render_comments( $post_id ); ?>
		</article>

		<?php
		$prev = get_previous_post();
		$next = get_next_post();
		if ( $prev || $next ) :
			?>
			<nav class="clipto-post-nav" aria-label="<?php esc_attr_e( 'Post navigation', 'clipto' ); ?>">
				<?php if ( $prev ) : ?>
					<a class="clipto-post-nav__prev" href="<?php echo esc_url( get_permalink( $prev ) ); ?>">
						<span class="clipto-post-nav__label"><?php esc_html_e( '← Previous', 'clipto' ); ?></span>
						<span class="clipto-post-nav__title"><?php echo esc_html( get_the_title( $prev ) ); ?></span>
					</a>
				<?php endif; ?>
				<?php if ( $next ) : ?>
					<a class="clipto-post-nav__next" href="<?php echo esc_url( get_permalink( $next ) ); ?>">
						<span class="clipto-post-nav__label"><?php esc_html_e( 'Next →', 'clipto' ); ?></span>
						<span class="clipto-post-nav__title"><?php echo esc_html( get_the_title( $next ) ); ?></span>
					</a>
				<?php endif; ?>
			</nav>
			<?php
		endif;
		?>
	</div>
	<?php
endwhile;

get_footer();
