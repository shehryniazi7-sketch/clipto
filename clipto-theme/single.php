<?php
/**
 * The template for displaying single posts.
 *
 * Article hierarchy: category eyebrow → H1 → dek → byline (author,
 * expertise, published / updated, reading time) → actions (share, save)
 * → featured image → AI Summary → body → share row → author trust box →
 * related articles → comments → previous / next.
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
	$expertise    = clipto_get_author_expertise_label( $author_id );
	$content_cls  = 'clipto-article__content clipto-prose';
	/**
	 * Filters whether the first paragraph of a single post gets the
	 * editorial drop cap. Return false to disable it sitewide.
	 *
	 * @param bool $enabled Default true.
	 * @param int  $post_id Post ID.
	 */
	if ( apply_filters( 'clipto_enable_drop_cap', true, $post_id ) ) {
		$content_cls .= ' has-clipto-drop-cap';
	}
	?>
	<div class="clipto-reading-progress" aria-hidden="true"><span class="clipto-reading-progress__bar"></span></div>

	<div class="clipto-container clipto-container--article">
		<?php clipto_breadcrumbs(); ?>

		<article <?php post_class( 'clipto-article' ); ?> id="post-<?php echo esc_attr( $post_id ); ?>">
			<header class="clipto-article__header clipto-entrance">
				<?php if ( $category ) : ?>
					<a class="clipto-article__eyebrow" href="<?php echo esc_url( get_category_link( $category->term_id ) ); ?>"><?php echo esc_html( $category->name ); ?></a>
				<?php endif; ?>

				<h1 class="clipto-article__title"><?php the_title(); ?></h1>

				<?php if ( has_excerpt() ) : ?>
					<p class="clipto-article__dek"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>

				<div class="clipto-article__byline">
					<a class="clipto-article__avatar-link" href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>" tabindex="-1" aria-hidden="true">
						<?php echo get_avatar( $author_id, 44, '', '', array( 'class' => 'clipto-article__avatar' ) ); ?>
					</a>
					<div class="clipto-article__byline-text">
						<p class="clipto-article__author">
							<a class="clipto-article__author-name" href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>"><?php echo esc_html( get_the_author() ); ?></a>
							<?php if ( $expertise ) : ?>
								<span class="clipto-article__author-role"><?php echo esc_html( $expertise ); ?></span>
							<?php endif; ?>
						</p>
						<ul class="clipto-article__meta" aria-label="<?php esc_attr_e( 'Article details', 'clipto' ); ?>">
							<li>
								<span class="screen-reader-text"><?php esc_html_e( 'Published', 'clipto' ); ?></span>
								<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
							</li>
							<?php if ( $is_updated ) : ?>
								<li class="clipto-article__updated">
									<?php esc_html_e( 'Updated', 'clipto' ); ?>
									<time datetime="<?php echo esc_attr( get_the_modified_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_modified_date() ); ?></time>
								</li>
							<?php endif; ?>
							<li><?php echo esc_html( $reading_time ); ?></li>
						</ul>
					</div>

					<div class="clipto-article__actions">
						<?php clipto_render_share_button( $post_id ); ?>
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
							<svg aria-hidden="true" viewBox="0 0 24 24" width="16" height="16"><path class="clipto-bookmark-btn__shape" d="M6 3.5h12a1 1 0 011 1V21l-7-4-7 4V4.5a1 1 0 011-1z"/></svg>
							<span class="clipto-bookmark-label"><?php esc_html_e( 'Save', 'clipto' ); ?></span>
						</button>
					</div>
				</div>
			</header>

			<?php
			if ( has_post_thumbnail() ) :
				$caption = wp_get_attachment_caption( get_post_thumbnail_id( $post_id ) );
				?>
				<figure class="clipto-article__thumb clipto-article__thumb--wide clipto-media-in">
					<?php the_post_thumbnail( 'clipto-hero', array( 'fetchpriority' => 'high', 'decoding' => 'async', 'sizes' => clipto_article_hero_sizes(), 'alt' => wp_strip_all_tags( get_the_title( $post_id ) ) ) ); ?>
					<?php if ( $caption ) : ?>
						<figcaption><?php echo esc_html( $caption ); ?></figcaption>
					<?php endif; ?>
				</figure>
			<?php endif; ?>

			<?php if ( $ai_summary ) : ?>
				<aside class="clipto-ai-summary clipto-reveal" aria-labelledby="clipto-ai-summary-title-<?php echo esc_attr( $post_id ); ?>">
					<h2 class="clipto-ai-summary__title" id="clipto-ai-summary-title-<?php echo esc_attr( $post_id ); ?>">
						<svg aria-hidden="true" viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M12 2l1.8 5.4L19 9l-5.2 1.6L12 16l-1.8-5.4L5 9l5.2-1.6zM19 14l.9 2.6L22.5 17l-2.6.9L19 20.5l-.9-2.6L15.5 17l2.6-.4z"/></svg>
						<?php esc_html_e( 'AI Summary', 'clipto' ); ?>
					</h2>
					<div class="clipto-ai-summary__body"><?php echo $ai_summary; // phpcs:ignore -- already wp_kses_post()'d in clipto_get_ai_summary(). ?></div>
					<?php if ( $ai_takeaway ) : ?>
						<p class="clipto-ai-summary__takeaway"><strong><?php esc_html_e( 'Key takeaway', 'clipto' ); ?></strong> <?php echo esc_html( $ai_takeaway ); ?></p>
					<?php endif; ?>
				</aside>
			<?php endif; ?>

			<div class="<?php echo esc_attr( $content_cls ); ?>">
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

			<footer class="clipto-article__footer">
				<?php
				$tags = get_the_tags();
				if ( $tags && ! is_wp_error( $tags ) ) :
					?>
					<ul class="clipto-article__tags" aria-label="<?php esc_attr_e( 'Tags', 'clipto' ); ?>">
						<?php foreach ( $tags as $post_tag ) : ?>
							<li><a href="<?php echo esc_url( get_tag_link( $post_tag ) ); ?>">#<?php echo esc_html( $post_tag->name ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php clipto_render_share_row( $post_id ); ?>
				<?php clipto_render_author_box( $author_id ); ?>
			</footer>
		</article>
	</div>

	<?php
	$related = clipto_get_related_posts( $post_id, 3 );
	if ( $related->have_posts() ) :
		?>
		<section class="clipto-related clipto-container clipto-container--wide" aria-labelledby="clipto-related-title">
			<h2 class="clipto-section-title" id="clipto-related-title"><span class="clipto-kicker"><?php esc_html_e( 'Keep reading', 'clipto' ); ?></span><?php esc_html_e( 'Related Articles', 'clipto' ); ?></h2>
			<div class="clipto-grid clipto-grid--posts clipto-grid--related">
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

	<div class="clipto-container clipto-container--article">
		<?php clipto_render_comments( $post_id ); ?>

		<?php
		$prev = get_previous_post();
		$next = get_next_post();
		if ( $prev || $next ) :
			?>
			<nav class="clipto-post-nav" aria-label="<?php esc_attr_e( 'More articles', 'clipto' ); ?>">
				<?php if ( $prev ) : ?>
					<a class="clipto-post-nav__link clipto-post-nav__prev" href="<?php echo esc_url( get_permalink( $prev ) ); ?>" rel="prev">
						<span class="clipto-post-nav__label"><span class="clipto-post-nav__arrow" aria-hidden="true">←</span> <?php esc_html_e( 'Previous article', 'clipto' ); ?></span>
						<span class="clipto-post-nav__title"><?php echo esc_html( get_the_title( $prev ) ); ?></span>
					</a>
				<?php endif; ?>
				<?php if ( $next ) : ?>
					<a class="clipto-post-nav__link clipto-post-nav__next" href="<?php echo esc_url( get_permalink( $next ) ); ?>" rel="next">
						<span class="clipto-post-nav__label"><?php esc_html_e( 'Next article', 'clipto' ); ?> <span class="clipto-post-nav__arrow" aria-hidden="true">→</span></span>
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
