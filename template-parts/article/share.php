<?php
/**
 * Share actions: Copy link (with confirmation), X and LinkedIn share intents, and the
 * native share sheet on touch devices that support it (revealed by share.js).
 *
 * Args: variant 'inline' (article header) | 'rail' (sticky reading rail).
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

$clipto_args    = wp_parse_args( isset( $args ) ? $args : array(), array( 'variant' => 'inline' ) );
$clipto_variant = 'rail' === $clipto_args['variant'] ? 'rail' : 'inline';
$clipto_url     = get_permalink();
$clipto_title   = wp_strip_all_tags( get_the_title() );

if ( ! $clipto_url ) {
	return;
}

$clipto_x_url  = add_query_arg(
	array(
		'url'  => rawurlencode( $clipto_url ),
		'text' => rawurlencode( $clipto_title ),
	),
	'https://x.com/intent/tweet'
);
$clipto_li_url = add_query_arg( 'url', rawurlencode( $clipto_url ), 'https://www.linkedin.com/sharing/share-offsite/' );
$clipto_label  = 'rail' === $clipto_variant ? __( 'Share this article', 'clipto' ) : __( 'Share', 'clipto' );
?>
<div class="share share--<?php echo esc_attr( $clipto_variant ); ?>" data-share data-share-url="<?php echo esc_url( $clipto_url ); ?>" data-share-title="<?php echo esc_attr( $clipto_title ); ?>" role="group" aria-label="<?php echo esc_attr( $clipto_label ); ?>">
	<?php if ( 'rail' === $clipto_variant ) : ?>
		<p class="share__heading" aria-hidden="true"><?php esc_html_e( 'Share', 'clipto' ); ?></p>
	<?php endif; ?>
	<div class="share__actions">
		<button type="button" class="share__btn share__copy" data-share-copy data-label-copy="<?php esc_attr_e( 'Copy link', 'clipto' ); ?>" data-label-copied="<?php esc_attr_e( 'Link copied', 'clipto' ); ?>" data-label-failed="<?php esc_attr_e( 'Could not copy the link', 'clipto' ); ?>">
			<span class="share__icon" aria-hidden="true">
				<?php clipto_the_icon( 'link', array( 'class' => 'share__glyph share__glyph--link' ) ); ?>
				<?php clipto_the_icon( 'check', array( 'class' => 'share__glyph share__glyph--check' ) ); ?>
			</span>
			<span class="share__label" data-share-label><?php esc_html_e( 'Copy link', 'clipto' ); ?></span>
		</button>
		<a class="share__btn share__btn--icon" href="<?php echo esc_url( $clipto_x_url ); ?>" target="_blank" rel="noopener noreferrer">
			<?php clipto_the_icon( 'x' ); ?>
			<span class="screen-reader-text"><?php esc_html_e( 'Share on X (opens in a new tab)', 'clipto' ); ?></span>
		</a>
		<a class="share__btn share__btn--icon" href="<?php echo esc_url( $clipto_li_url ); ?>" target="_blank" rel="noopener noreferrer">
			<?php clipto_the_icon( 'linkedin' ); ?>
			<span class="screen-reader-text"><?php esc_html_e( 'Share on LinkedIn (opens in a new tab)', 'clipto' ); ?></span>
		</a>
		<button type="button" class="share__btn share__btn--icon share__native" data-share-native hidden>
			<?php clipto_the_icon( 'share' ); ?>
			<span class="screen-reader-text"><?php esc_html_e( 'More sharing options', 'clipto' ); ?></span>
		</button>
	</div>
	<span class="screen-reader-text" role="status" aria-live="polite" data-share-status></span>
</div>
