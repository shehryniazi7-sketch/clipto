<?php
/**
 * Site footer: brand + tagline + about line, the footer index (navigation built only from
 * live destinations and taxonomy), then the colophon bar — copyright, the Footer menu and
 * privacy policy (when they exist) and "Back to top". An oversized wordmark can be switched
 * on in Customize → Clipto: Footer (off by default).
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

/* Colophon links: the Footer menu (depth 1), then the privacy policy unless the menu has it. */
$clipto_ft_links   = '';
$clipto_ft_privacy = get_privacy_policy_url();
if ( has_nav_menu( 'footer' ) ) {
	$clipto_ft_links = (string) wp_nav_menu(
		array(
			'theme_location' => 'footer',
			'container'      => false,
			'items_wrap'     => '%3$s',
			'depth'          => 1,
			'fallback_cb'    => false,
			'echo'           => false,
		)
	);
	$clipto_ft_locations = get_nav_menu_locations();
	$clipto_ft_menu      = ! empty( $clipto_ft_locations['footer'] ) ? wp_get_nav_menu_items( $clipto_ft_locations['footer'] ) : array();
	foreach ( (array) $clipto_ft_menu as $clipto_ft_item ) {
		if ( $clipto_ft_privacy && untrailingslashit( $clipto_ft_item->url ) === untrailingslashit( $clipto_ft_privacy ) ) {
			$clipto_ft_privacy = '';
		}
	}
}
if ( $clipto_ft_privacy ) {
	$clipto_ft_privacy_title = get_the_title( (int) get_option( 'wp_page_for_privacy_policy' ) );
	$clipto_ft_links        .= sprintf(
		'<li><a class="site-footer__legal-link" href="%1$s"%2$s>%3$s</a></li>',
		esc_url( $clipto_ft_privacy ),
		is_privacy_policy() ? ' aria-current="page"' : '',
		esc_html( $clipto_ft_privacy_title ? $clipto_ft_privacy_title : __( 'Privacy policy', 'clipto' ) )
	);
}

// Oversized wordmark: the site name set in SVG and fitted edge to edge (textLength), so any
// name fills the measure without JS. Skipped for very long names.
$clipto_ft_mark  = function_exists( 'clipto_mod' ) && clipto_sanitize_checkbox( clipto_mod( 'clipto_footer_mark' ) );
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
			<div class="site-footer__mark" aria-hidden="true">
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
			<?php if ( $clipto_ft_links ) : ?>
				<ul class="site-footer__legal" role="list" aria-label="<?php esc_attr_e( 'Site information', 'clipto' ); ?>">
					<?php echo $clipto_ft_links; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_nav_menu output / escaped above. ?>
				</ul>
			<?php endif; ?>
			<a class="link-arrow site-footer__totop" href="#content">
				<?php esc_html_e( 'Back to top', 'clipto' ); ?>
				<?php clipto_the_icon( 'arrow-up' ); ?>
			</a>
		</div>

	</div>
</footer>
