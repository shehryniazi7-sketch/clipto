<?php
/**
 * AI Tools mega-menu panel. Rendered by clipto_mega_menu() only when AI Tools has
 * existing (non-empty) subcategories. Everything here is read live.
 *
 * Expects $args: id (string), subcats (WP_Term[]), tools (destination array),
 * latest (WP_Post|null).
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

$clipto_panel_id = isset( $args['id'] ) ? $args['id'] : 'mega-ai-tools';
$clipto_subcats  = isset( $args['subcats'] ) ? (array) $args['subcats'] : array();
$clipto_tools    = isset( $args['tools'] ) ? $args['tools'] : null;
$clipto_latest   = isset( $args['latest'] ) ? $args['latest'] : null;

if ( ! $clipto_subcats || ! $clipto_tools ) {
	return;
}

$clipto_desc = trim( wp_strip_all_tags( (string) $clipto_tools['description'] ) );
$clipto_desc = $clipto_desc ? wp_trim_words( $clipto_desc, 16, '…' ) : __( 'Every tool we cover, filed by the job it does.', 'clipto' );
$clipto_cats = count( $clipto_subcats );
$clipto_list = 'mega-list-' . sanitize_html_class( $clipto_panel_id );
?>
<div class="mega<?php echo $clipto_latest ? '' : ' mega--no-feature'; ?>" id="<?php echo esc_attr( $clipto_panel_id ); ?>" data-mega-panel>
	<div class="mega__grid">

		<div class="mega__intro">
			<p class="kicker"><?php echo esc_html( $clipto_tools['label'] ); ?></p>
			<p class="mega__lede">
				<?php
				// Keep hyphenated compounds ("hands-on") whole: the balanced rag must not split them.
				echo preg_replace( '/[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)+/u', '<span class="mega__nowrap">$0</span>', esc_html( $clipto_desc ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped before wrapping.
				?>
			</p>
			<p class="meta mega__stats">
				<?php if ( $clipto_tools['count'] ) : ?>
					<span>
						<?php
						/* translators: %s: number of articles. */
						echo esc_html( sprintf( _n( '%s article', '%s articles', (int) $clipto_tools['count'], 'clipto' ), number_format_i18n( (int) $clipto_tools['count'] ) ) );
						?>
					</span>
				<?php endif; ?>
				<span>
					<?php
					/* translators: %s: number of categories. */
					echo esc_html( sprintf( _n( '%s category', '%s categories', $clipto_cats, 'clipto' ), number_format_i18n( $clipto_cats ) ) );
					?>
				</span>
			</p>
			<a class="link-arrow mega__all" href="<?php echo esc_url( $clipto_tools['url'] ); ?>">
				<?php
				/* translators: %s: destination label, e.g. "AI Tools". */
				echo esc_html( sprintf( __( 'All %s', 'clipto' ), $clipto_tools['label'] ) );
				clipto_the_icon( 'arrow-right' );
				?>
			</a>
		</div>

		<div class="mega__cats">
			<p class="mega__label" id="<?php echo esc_attr( $clipto_list ); ?>"><?php esc_html_e( 'Browse by category', 'clipto' ); ?></p>
			<ol class="mega__list<?php echo $clipto_cats > 5 ? ' mega__list--split' : ''; ?>" role="list" aria-labelledby="<?php echo esc_attr( $clipto_list ); ?>">
				<?php
				foreach ( $clipto_subcats as $clipto_i => $clipto_term ) :
					$clipto_count   = clipto_term_post_count( $clipto_term );
					$clipto_current = is_category( $clipto_term->term_id ) ? ' aria-current="page"' : '';
					?>
					<li>
						<a class="mega__cat" href="<?php echo esc_url( get_category_link( $clipto_term ) ); ?>"<?php echo $clipto_current; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- constant string. ?>>
							<span class="mega__num" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( $clipto_i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
							<span class="mega__name"><?php echo esc_html( $clipto_term->name ); ?></span>
							<span class="mega__leader" aria-hidden="true"></span>
							<span class="mega__count">
								<?php echo esc_html( number_format_i18n( $clipto_count ) ); ?>
								<span class="screen-reader-text"><?php echo esc_html( _n( 'article', 'articles', $clipto_count, 'clipto' ) ); ?></span>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>

		<?php if ( $clipto_latest ) : ?>
			<div class="mega__feature">
				<p class="mega__label"><?php esc_html_e( 'Latest in AI Tools', 'clipto' ); ?></p>
				<?php $clipto_cat = clipto_primary_category( $clipto_latest ); ?>
				<a class="mega__story" href="<?php echo esc_url( get_permalink( $clipto_latest ) ); ?>">
					<?php
					echo clipto_media( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper.
						$clipto_latest,
						array(
							'size'  => 'clipto-card',
							'ratio' => '3x2',
							'sizes' => '(min-width: 80em) 18rem, 22vw',
							'class' => 'mega__media',
						)
					);
					?>
					<?php if ( $clipto_cat ) : ?>
						<span class="kicker mega__story-kicker"><?php echo esc_html( $clipto_cat->name ); ?></span>
					<?php endif; ?>
					<span class="mega__story-title"><span class="headline-link"><?php echo esc_html( get_the_title( $clipto_latest ) ); ?></span></span>
				</a>
				<?php clipto_meta( $clipto_latest, array( 'class' => 'mega__meta' ) ); ?>
			</div>
		<?php endif; ?>

	</div>
</div>
