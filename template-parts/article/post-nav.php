<?php
/**
 * Previous / next article navigation (chronological, same post type).
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

$clipto_prev = get_previous_post();
$clipto_next = get_next_post();

if ( ! $clipto_prev && ! $clipto_next ) {
	return;
}
?>
<nav class="post-nav container" aria-label="<?php esc_attr_e( 'More articles', 'clipto' ); ?>">
	<div class="post-nav__grid">
		<?php if ( $clipto_prev ) : ?>
			<a class="post-nav__link post-nav__link--prev" href="<?php echo esc_url( get_permalink( $clipto_prev ) ); ?>" rel="prev">
				<span class="post-nav__dir"><?php clipto_the_icon( 'arrow-left', array( 'class' => 'post-nav__arrow' ) ); ?><?php esc_html_e( 'Previous article', 'clipto' ); ?></span>
				<span class="post-nav__title"><span class="headline-link"><?php echo esc_html( wp_strip_all_tags( get_the_title( $clipto_prev ) ) ); ?></span></span>
			</a>
		<?php endif; ?>
		<?php if ( $clipto_next ) : ?>
			<a class="post-nav__link post-nav__link--next" href="<?php echo esc_url( get_permalink( $clipto_next ) ); ?>" rel="next">
				<span class="post-nav__dir"><?php esc_html_e( 'Next article', 'clipto' ); ?><?php clipto_the_icon( 'arrow-right', array( 'class' => 'post-nav__arrow' ) ); ?></span>
				<span class="post-nav__title"><span class="headline-link"><?php echo esc_html( wp_strip_all_tags( get_the_title( $clipto_next ) ) ); ?></span></span>
			</a>
		<?php endif; ?>
	</div>
</nav>
