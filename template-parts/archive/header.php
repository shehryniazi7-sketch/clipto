<?php
/**
 * Archive header: breadcrumbs, eyebrow (index numeral + kicker), H1, description,
 * honest stats (count, updated, page) and — for AI Tools — the subcategory chip nav.
 *
 * Args (all optional):
 *   variant     tools|news|earn|free|standard|search|author
 *   crumb       Current label for non-category breadcrumbs ('' = none).
 *   index       Index numeral ("01").
 *   kicker      Kicker text.
 *   kicker_url  Kicker link (real URL only).
 *   title       Plain-text title (escaped here).
 *   title_html  Pre-built title HTML (limited tags allowed) — overrides title.
 *   desc        Description HTML (term description; kses'd here).
 *   note        Extra plain-text line under the description.
 *   badge       Badge HTML (built from constants; kses'd here).
 *   stats       Array of { value, label, html?: bool }.
 *   chips       Array of { label, sr?, url, count, current, active } (tools nav).
 *   chips_label Accessible name of the chip nav.
 *   media       Leading media HTML (author portrait; kses'd here).
 *   after       Callback printed at the end of the main column.
 *   class       Extra class(es) on the header.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

$a = wp_parse_args(
	isset( $args ) && is_array( $args ) ? $args : array(),
	array(
		'variant'     => 'standard',
		'crumb'       => '',
		'index'       => '',
		'kicker'      => '',
		'kicker_url'  => '',
		'title'       => '',
		'title_html'  => '',
		'desc'        => '',
		'note'        => '',
		'badge'       => '',
		'stats'       => array(),
		'chips'       => array(),
		'chips_label' => '',
		'media'       => '',
		'after'       => null,
		'class'       => '',
	)
);

$variant    = sanitize_html_class( $a['variant'] );
$stats      = array_filter( (array) $a['stats'] );
$title_tags = array(
	'em'   => array(),
	'mark' => array( 'class' => true ),
	'span' => array( 'class' => true ),
);
$badge_tags = array(
	'span' => array( 'class' => true ),
	'div'  => array( 'class' => true ),
);
$media_tags = array(
	'img'  => array(
		'src'      => true,
		'srcset'   => true,
		'sizes'    => true,
		'alt'      => true,
		'class'    => true,
		'width'    => true,
		'height'   => true,
		'loading'  => true,
		'decoding' => true,
	),
	'span' => array(
		'class'       => true,
		'aria-hidden' => true,
	),
);
?>
<header class="archive-head archive-head--<?php echo esc_attr( $variant . ( $a['class'] ? ' ' . $a['class'] : '' ) ); ?>">
	<div class="container archive-head__inner">
		<div class="archive-head__crumbs">
			<?php clipto_archive_breadcrumbs( $a['crumb'] ); ?>
		</div>

		<div class="archive-head__grid<?php echo $a['media'] ? ' has-media' : ''; ?>">
			<?php if ( $a['media'] ) : ?>
				<div class="archive-head__media">
					<?php echo wp_kses( $a['media'], $media_tags ); ?>
				</div>
			<?php endif; ?>

			<div class="archive-head__main">
				<?php if ( $a['index'] || $a['kicker'] || $a['badge'] ) : ?>
					<p class="archive-head__eyebrow">
						<?php if ( $a['index'] ) : ?>
							<span class="index-num"><?php echo esc_html( $a['index'] ); ?></span>
						<?php endif; ?>
						<?php if ( $a['kicker'] && $a['kicker_url'] ) : ?>
							<a class="kicker" href="<?php echo esc_url( $a['kicker_url'] ); ?>"><?php echo esc_html( $a['kicker'] ); ?></a>
						<?php elseif ( $a['kicker'] ) : ?>
							<span class="kicker"><?php echo esc_html( $a['kicker'] ); ?></span>
						<?php endif; ?>
						<?php
						if ( $a['badge'] ) {
							echo wp_kses( $a['badge'], $badge_tags );
						}
						?>
					</p>
				<?php endif; ?>

				<h1 class="archive-head__title">
					<?php
					if ( $a['title_html'] ) {
						echo wp_kses( $a['title_html'], $title_tags );
					} else {
						echo esc_html( $a['title'] );
					}
					?>
				</h1>

				<?php if ( $a['desc'] ) : ?>
					<div class="archive-head__desc"><?php echo wp_kses_post( wpautop( $a['desc'] ) ); ?></div>
				<?php endif; ?>

				<?php if ( $a['note'] ) : ?>
					<p class="archive-head__note"><?php echo esc_html( $a['note'] ); ?></p>
				<?php endif; ?>

				<?php
				if ( is_callable( $a['after'] ) ) {
					call_user_func( $a['after'] );
				}
				?>
			</div>

			<?php if ( $stats ) : ?>
				<dl class="archive-head__stats">
					<?php foreach ( $stats as $stat ) : ?>
						<div class="archive-head__stat">
							<dt><?php echo esc_html( $stat['label'] ); ?></dt>
							<dd>
								<?php
								if ( ! empty( $stat['html'] ) ) {
									echo wp_kses( $stat['value'], array( 'time' => array( 'datetime' => true ) ) );
								} else {
									echo esc_html( $stat['value'] );
								}
								?>
							</dd>
						</div>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>
		</div>

		<?php if ( $a['chips'] ) : ?>
			<nav class="archive-head__nav" aria-label="<?php echo esc_attr( $a['chips_label'] ? $a['chips_label'] : __( 'Categories', 'clipto' ) ); ?>">
				<ul class="archive-head__chips scroller" role="list">
					<?php foreach ( $a['chips'] as $chip ) : ?>
						<li>
							<a class="chip<?php echo ! empty( $chip['active'] ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( $chip['url'] ); ?>"<?php echo ! empty( $chip['current'] ) ? ' aria-current="page"' : ''; ?>>
								<span><?php echo esc_html( $chip['label'] ); ?><?php if ( ! empty( $chip['sr'] ) ) : ?><span class="screen-reader-text"> <?php echo esc_html( $chip['sr'] ); ?></span><?php endif; ?></span>
								<?php if ( isset( $chip['count'] ) && null !== $chip['count'] ) : ?>
									<span class="chip__count"><span class="screen-reader-text">(</span><?php echo esc_html( number_format_i18n( (int) $chip['count'] ) ); ?><span class="screen-reader-text"> <?php echo esc_html( _n( 'article', 'articles', (int) $chip['count'], 'clipto' ) ); ?>)</span></span>
								<?php endif; ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>
		<?php endif; ?>
	</div>
</header>
