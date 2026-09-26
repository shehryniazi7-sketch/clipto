<?php
/**
 * Article / page header: breadcrumbs, kicker, H1, dek, byline (avatar, author, dates,
 * reading time) and share actions.
 *
 * Args:
 *   context     'post' | 'page'
 *   kicker      Override kicker text (e.g. "The Clipto Guide"); '' = primary category.
 *   byline      bool — show the author (posts, pillar pages).
 *   share       bool — show share actions.
 *   reading     bool — show reading time.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

$clipto_args = wp_parse_args(
	isset( $args ) ? $args : array(),
	array(
		'context' => 'post',
		'kicker'  => '',
		'byline'  => true,
		'share'   => true,
		'reading' => true,
	)
);

$clipto_post      = get_post();
$clipto_is_post   = 'post' === $clipto_args['context'];
$clipto_cat       = $clipto_is_post ? clipto_primary_category( $clipto_post ) : null;
$clipto_author_id = (int) $clipto_post->post_author;
$clipto_published = (int) get_post_time( 'U', true, $clipto_post );
$clipto_modified  = (int) get_post_modified_time( 'U', true, $clipto_post );
$clipto_updated   = $clipto_modified > $clipto_published + DAY_IN_SECONDS;
$clipto_locked    = post_password_required( $clipto_post );
$clipto_dek       = ( has_excerpt( $clipto_post ) && ! $clipto_locked ) ? trim( wp_strip_all_tags( $clipto_post->post_excerpt ) ) : '';
$clipto_badges    = $clipto_is_post ? clipto_badges( $clipto_post ) : '';
$clipto_minutes   = clipto_reading_time( $clipto_post );
?>
<header class="article-header">
	<?php
	if ( $clipto_is_post ) {
		clipto_breadcrumbs( $clipto_post );
	} else {
		// Pages: Home / ancestors / this page (the shared helper stops at the ancestors).
		$clipto_trail = array(
			array(
				'label' => __( 'Home', 'clipto' ),
				'url'   => home_url( '/' ),
			),
		);
		foreach ( array_reverse( get_post_ancestors( $clipto_post ) ) as $clipto_ancestor ) {
			// Skip private/draft ancestors the visitor may not read (their titles are not public).
			if ( ! is_post_publicly_viewable( $clipto_ancestor ) && ! current_user_can( 'read_post', $clipto_ancestor ) ) {
				continue;
			}
			$clipto_trail[] = array(
				'label' => wp_strip_all_tags( get_the_title( $clipto_ancestor ) ),
				'url'   => get_permalink( $clipto_ancestor ),
			);
		}
		$clipto_trail[] = array(
			'label' => wp_strip_all_tags( get_the_title( $clipto_post ) ),
			'url'   => '',
		);
		?>
		<nav class="breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'clipto' ); ?>">
			<ol class="breadcrumbs__list" role="list">
				<?php foreach ( $clipto_trail as $clipto_crumb ) : ?>
					<li class="breadcrumbs__item">
						<?php if ( $clipto_crumb['url'] ) : ?>
							<a href="<?php echo esc_url( $clipto_crumb['url'] ); ?>"><?php echo esc_html( $clipto_crumb['label'] ); ?></a>
						<?php else : ?>
							<span aria-current="page"><?php echo esc_html( $clipto_crumb['label'] ); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</nav>
		<?php
	}
	?>

	<?php if ( $clipto_args['kicker'] || $clipto_cat || $clipto_badges ) : ?>
		<div class="article-header__eyebrow">
			<?php if ( $clipto_args['kicker'] ) : ?>
				<span class="kicker article-header__kicker"><?php clipto_the_icon( 'crop', array( 'class' => 'icon--sm' ) ); ?><?php echo esc_html( $clipto_args['kicker'] ); ?></span>
			<?php elseif ( $clipto_cat ) : ?>
				<a class="kicker article-header__kicker" href="<?php echo esc_url( get_category_link( $clipto_cat ) ); ?>"><?php echo esc_html( $clipto_cat->name ); ?></a>
			<?php endif; ?>
			<?php echo $clipto_badges; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in clipto_badges(). ?>
		</div>
	<?php endif; ?>

	<h1 class="article-header__title"><?php echo wp_kses_post( get_the_title( $clipto_post ) ); ?></h1>

	<?php if ( $clipto_dek ) : ?>
		<p class="article-header__dek"><?php echo esc_html( $clipto_dek ); ?></p>
	<?php endif; ?>

	<div class="article-header__byline byline<?php echo $clipto_args['byline'] ? '' : ' byline--no-author'; ?>">
		<div class="byline__main">
			<?php if ( $clipto_args['byline'] && $clipto_author_id ) : ?>
				<?php
				$clipto_avatar = get_avatar(
					$clipto_author_id,
					40,
					'',
					'',
					array(
						'class'    => 'byline__avatar',
						'loading'  => 'eager',
						'decoding' => 'async',
					)
				);
				$clipto_name   = get_the_author_meta( 'display_name', $clipto_author_id );
				?>
				<a class="byline__photo" href="<?php echo esc_url( get_author_posts_url( $clipto_author_id ) ); ?>" tabindex="-1" aria-hidden="true">
					<?php if ( $clipto_avatar ) : ?>
						<?php echo $clipto_avatar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped avatar markup. ?>
					<?php else : ?>
						<span class="byline__monogram"><?php echo esc_html( function_exists( 'mb_substr' ) ? mb_substr( $clipto_name, 0, 1 ) : substr( $clipto_name, 0, 1 ) ); ?></span>
					<?php endif; ?>
				</a>
			<?php endif; ?>

			<div class="byline__text">
				<?php if ( $clipto_args['byline'] && $clipto_author_id ) : ?>
					<p class="byline__name">
						<?php
						printf(
							/* translators: %s: author name linked to the author archive. */
							esc_html__( 'By %s', 'clipto' ),
							'<a class="byline__author" href="' . esc_url( get_author_posts_url( $clipto_author_id ) ) . '" rel="author">' . esc_html( $clipto_name ) . '</a>'
						);
						?>
					</p>
				<?php endif; ?>
				<p class="meta byline__meta">
					<?php if ( $clipto_is_post || ! $clipto_updated ) : ?>
						<span>
							<?php if ( ! $clipto_is_post ) : ?>
								<span class="byline__label"><?php esc_html_e( 'Published', 'clipto' ); ?></span>
							<?php endif; ?>
							<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $clipto_post ) ); ?>"><?php echo esc_html( get_the_date( '', $clipto_post ) ); ?></time>
						</span>
					<?php endif; ?>
					<?php if ( $clipto_updated ) : ?>
						<span class="byline__updated">
							<span class="byline__label"><?php esc_html_e( 'Updated', 'clipto' ); ?></span>
							<time datetime="<?php echo esc_attr( get_the_modified_date( DATE_W3C, $clipto_post ) ); ?>"><?php echo esc_html( get_the_modified_date( '', $clipto_post ) ); ?></time>
						</span>
					<?php endif; ?>
					<?php if ( $clipto_args['reading'] && ! $clipto_locked ) : ?>
						<span class="byline__reading">
							<?php
							/* translators: %d: minutes. */
							echo esc_html( sprintf( _n( '%d min read', '%d min read', $clipto_minutes, 'clipto' ), $clipto_minutes ) );
							?>
						</span>
					<?php endif; ?>
				</p>
			</div>
		</div>

		<?php
		if ( $clipto_args['share'] ) {
			get_template_part( 'template-parts/article/share', null, array( 'variant' => 'inline' ) );
		}
		?>
	</div>
</header>
