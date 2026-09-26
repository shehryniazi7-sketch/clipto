<?php
/**
 * Site footer: brand + tagline + about line, the footer index (navigation built only from
 * live destinations, taxonomy and the Footer menu), the colophon bar and an optional
 * oversized wordmark set on the colophon rule.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

$clipto_ft_name    = get_bloginfo( 'name' );
$clipto_ft_tagline = trim( (string) get_bloginfo( 'description' ) );
if ( 'Just another WordPress site' === $clipto_ft_tagline || __( 'Just another WordPress site' ) === $clipto_ft_tagline ) { // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- core string.
	$clipto_ft_tagline = '';
}
$clipto_ft_about = function_exists( 'clipto_mod' ) ? trim( (string) clipto_mod( 'clipto_footer_about' ) ) : '';
$clipto_ft_years = function_exists( 'clipto_copyright_years' ) ? clipto_copyright_years() : wp_date( 'Y' );

// Oversized wordmark: the site name set in SVG and fitted edge to edge (textLength), so any
// name fills the measure without JS. Skipped for very long names.
$clipto_ft_mark  = function_exists( 'clipto_mod' ) ? (bool) clipto_mod( 'clipto_footer_mark' ) : true;
$clipto_ft_chars = function_exists( 'mb_strlen' ) ? mb_strlen( $clipto_ft_name ) : strlen( $clipto_ft_name );
$clipto_ft_mark  = $clipto_ft_mark && $clipto_ft_chars > 0 && $clipto_ft_chars <= 20;
if ( $clipto_ft_mark ) {
	// Newsreader 500 averages ≈0.46em per character; size the type so the natural width is a
	// touch wider than the box and textLength tightens it into a display setting.
	$clipto_ft_size = round( 1000 / max( 3, $clipto_ft_chars ) / 0.445, 1 );
	$clipto_ft_high = round( $clipto_ft_size * 0.74, 1 ); // Ascender height: glyphs stand on the rule, descenders are cropped.
}
?>
<footer class="site-footer" id="colophon">
	<div class="container">

		<div class="site-footer__top">
			<div class="site-footer__about">
				<?php clipto_brand( array( 'class' => 'site-footer__brand' ) ); ?>
				<?php if ( $clipto_ft_tagline ) : ?>
					<p class="site-footer__tagline"><?php echo esc_html( $clipto_ft_tagline ); ?></p>
				<?php endif; ?>
				<?php if ( $clipto_ft_about ) : ?>
					<p class="site-footer__text"><?php echo esc_html( $clipto_ft_about ); ?></p>
				<?php endif; ?>
			</div>

			<?php get_template_part( 'template-parts/footer/nav' ); ?>
		</div>

		<?php if ( $clipto_ft_mark ) : ?>
			<div class="site-footer__mark" aria-hidden="true" data-reveal>
				<svg viewBox="0 0 1000 <?php echo esc_attr( $clipto_ft_high ); ?>" preserveAspectRatio="xMinYMax meet" focusable="false">
					<text x="0" y="<?php echo esc_attr( $clipto_ft_high ); ?>" font-size="<?php echo esc_attr( $clipto_ft_size ); ?>" textLength="1000" lengthAdjust="spacing"><?php echo esc_html( $clipto_ft_name ); ?></text>
				</svg>
			</div>
		<?php endif; ?>

		<div class="site-footer__bar<?php echo $clipto_ft_mark ? ' has-mark' : ''; ?>">
			<p class="site-footer__copy">
				<span>&copy; <?php echo esc_html( $clipto_ft_years ); ?></span>
				<span><?php echo esc_html( $clipto_ft_name ); ?></span>
			</p>
			<a class="link-arrow site-footer__totop" href="#content">
				<?php esc_html_e( 'Back to top', 'clipto' ); ?>
				<?php clipto_the_icon( 'arrow-up' ); ?>
			</a>
		</div>

	</div>
</footer>
