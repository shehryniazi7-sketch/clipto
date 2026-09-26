<?php
/**
 * Story card — every variant (lead, feature, standard, compact, row, tool, numbered).
 * Rendered via clipto_card(); a child theme may override one variant with
 * template-parts/cards/card-{variant}.php.
 *
 * The whole card is clickable through a stretched headline link (one link per
 * card for assistive tech); the kicker stays independently clickable.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

$c       = get_query_var( 'clipto_card' );
$variant = $c['variant'];
$heading = $c['heading'];
$post_id = get_the_ID();
$title   = get_the_title();
$url     = get_permalink();
$has_img = ! in_array( $variant, array( 'compact', 'numbered' ), true );
$reveal  = $c['reveal'] ? ' data-reveal' : '';
$style   = $c['index'] ? ' style="--i:' . (int) $c['index'] . '"' : '';
$facts   = 'tool' === $variant ? clipto_tool_facts( $post_id ) : array();
?>
<article class="<?php echo esc_attr( implode( ' ', $c['classes'] ) ); ?>"<?php echo $reveal . $style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from ints/constants. ?>>

	<?php if ( 'numbered' === $variant && $c['index'] ) : ?>
		<span class="card__num" aria-hidden="true"><?php echo esc_html( str_pad( (string) (int) $c['index'], 2, '0', STR_PAD_LEFT ) ); ?></span>
	<?php endif; ?>

	<?php if ( $has_img ) : ?>
		<div class="card__media">
			<?php
			$logo = 'tool' === $variant ? clipto_tool_logo( $post_id ) : '';
			if ( $logo ) {
				echo $logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in clipto_tool_logo().
			} else {
				echo clipto_media( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in clipto_media().
				$post_id,
				array(
					'ratio' => $c['ratio'],
					'size'  => $c['size'],
					'sizes' => $c['sizes'],
					'eager' => $c['eager'],
				)
			);
			}
			?>
		</div>
	<?php endif; ?>

	<div class="card__body">
		<?php if ( $c['kicker'] || ( 'tool' !== $variant && $c['latest'] ) ) : ?>
			<div class="card__top">
				<?php
				if ( $c['kicker'] ) {
					echo clipto_card_kicker( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper.
				}
				if ( 'tool' !== $variant && $c['latest'] && clipto_is_recent( $post_id, 24 ) ) {
					echo '<span class="badge badge--latest">' . esc_html__( 'Latest', 'clipto' ) . '</span>';
				}
				?>
			</div>
		<?php endif; ?>

		<<?php echo esc_html( $heading ); ?> class="card__title">
			<a class="card__link" href="<?php echo esc_url( $url ); ?>"><span class="headline-link"><?php echo esc_html( $title ); ?></span></a>
		</<?php echo esc_html( $heading ); ?>>

		<?php if ( $c['excerpt'] ) : ?>
			<p class="card__excerpt"><?php echo esc_html( clipto_excerpt( $post_id, (int) $c['excerpt'] ) ); ?></p>
		<?php endif; ?>

		<?php if ( 'tool' === $variant ) : ?>
			<?php
			echo clipto_badges( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper.
			if ( ! empty( $facts['best_for'] ) ) :
				?>
				<p class="card__bestfor"><span><?php esc_html_e( 'Best for', 'clipto' ); ?></span> <?php echo esc_html( $facts['best_for'] ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $facts['rating'] ) ) : ?>
				<p class="card__rating">
					<?php clipto_the_icon( 'star', array( 'class' => 'icon--sm' ) ); ?>
					<span><?php echo esc_html( number_format_i18n( $facts['rating'], 1 ) ); ?></span><span class="screen-reader-text"><?php esc_html_e( 'out of 5', 'clipto' ); ?></span>
				</p>
			<?php endif; ?>
		<?php elseif ( in_array( $variant, array( 'lead', 'feature' ), true ) ) : ?>
			<?php echo clipto_badges( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
		<?php endif; ?>

		<?php
		$meta_args = wp_parse_args(
			$c['meta'],
			array(
				'author'  => in_array( $variant, array( 'lead', 'feature' ), true ),
				'reading' => ! in_array( $variant, array( 'numbered', 'tool' ), true ),
				'class'   => 'card__meta',
			)
		);
		clipto_meta( $post_id, $meta_args );
		?>
	</div>
</article>
