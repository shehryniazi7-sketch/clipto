<?php
/**
 * Author box: avatar, name, bio, article count and a link to the author archive.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

$clipto_author_id = (int) get_post_field( 'post_author', get_the_ID() );
if ( ! $clipto_author_id ) {
	return;
}

$clipto_name   = get_the_author_meta( 'display_name', $clipto_author_id );
$clipto_bio    = get_the_author_meta( 'description', $clipto_author_id );
$clipto_url    = get_author_posts_url( $clipto_author_id );
$clipto_count  = (int) count_user_posts( $clipto_author_id, 'post', true );
$clipto_avatar = get_avatar(
	$clipto_author_id,
	72,
	'',
	'',
	array(
		'class'    => 'author-box__avatar',
		'loading'  => 'lazy',
		'decoding' => 'async',
	)
);
$clipto_title  = 'author-box-title-' . get_the_ID();
?>
<section class="author-box" aria-labelledby="<?php echo esc_attr( $clipto_title ); ?>">
	<div class="author-box__photo" aria-hidden="true">
		<?php if ( $clipto_avatar ) : ?>
			<?php echo $clipto_avatar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped avatar markup. ?>
		<?php else : ?>
			<span class="author-box__monogram"><?php echo esc_html( function_exists( 'mb_substr' ) ? mb_substr( $clipto_name, 0, 1 ) : substr( $clipto_name, 0, 1 ) ); ?></span>
		<?php endif; ?>
	</div>
	<div class="author-box__body">
		<p class="kicker kicker--muted author-box__eyebrow"><?php esc_html_e( 'Written by', 'clipto' ); ?></p>
		<h2 class="author-box__name" id="<?php echo esc_attr( $clipto_title ); ?>">
			<a href="<?php echo esc_url( $clipto_url ); ?>"><span class="headline-link"><?php echo esc_html( $clipto_name ); ?></span></a>
		</h2>
		<?php if ( $clipto_bio ) : ?>
			<p class="author-box__bio"><?php echo esc_html( $clipto_bio ); ?></p>
		<?php endif; ?>
		<div class="author-box__foot">
			<?php if ( $clipto_count ) : ?>
				<span class="author-box__count">
					<?php
					/* translators: %s: number of published articles. */
					echo esc_html( sprintf( _n( '%s article', '%s articles', $clipto_count, 'clipto' ), number_format_i18n( $clipto_count ) ) );
					?>
				</span>
			<?php endif; ?>
			<a class="link-arrow author-box__link" href="<?php echo esc_url( $clipto_url ); ?>">
				<?php
				/* translators: %s: author name. */
				echo esc_html( sprintf( __( 'All articles by %s', 'clipto' ), $clipto_name ) );
				?>
				<?php clipto_the_icon( 'arrow-right' ); ?>
			</a>
		</div>
	</div>
</section>
