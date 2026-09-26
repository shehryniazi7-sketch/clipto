<?php
/**
 * Masthead: brand, primary navigation (with the AI Tools mega-menu) and controls.
 *
 * Hooks for other engineers:
 *   .site-header / .is-scrolled   Sticky bar; .is-scrolled after 8px of scroll.
 *   [data-search-open]            Any element with this attribute opens the search dialog.
 *   [data-theme-toggle]           Any button with this attribute toggles light/dark.
 *   [data-menu-open]              Opens the mobile menu (#mobile-menu).
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

$clipto_nav_items = clipto_primary_nav_items();
$clipto_has_nav   = $clipto_nav_items || has_nav_menu( 'primary' );
?>
<header class="site-header" data-site-header>
	<div class="site-header__bar container">

		<div class="site-header__brand">
			<?php clipto_brand( array( 'class' => 'site-header__logo' ) ); ?>
		</div>

		<?php if ( $clipto_has_nav ) : ?>
			<nav class="site-nav" aria-label="<?php esc_attr_e( 'Primary', 'clipto' ); ?>">
				<?php clipto_primary_nav(); ?>
			</nav>
		<?php endif; ?>

		<div class="site-header__actions">
			<button type="button" class="site-header__search" data-search-open aria-haspopup="dialog" aria-controls="search-dialog" aria-keyshortcuts="/ Control+K Meta+K">
				<?php clipto_the_icon( 'search' ); ?>
				<span class="site-header__search-label"><?php esc_html_e( 'Search', 'clipto' ); ?></span>
				<kbd class="site-header__kbd" aria-hidden="true">/</kbd>
			</button>
			<a class="icon-btn site-header__search-link" href="<?php echo esc_url( home_url( '/?s=' ) ); ?>">
				<?php clipto_the_icon( 'search' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( 'Search', 'clipto' ); ?></span>
			</a>

			<button type="button" class="icon-btn theme-toggle" data-theme-toggle aria-label="<?php esc_attr_e( 'Switch colour theme', 'clipto' ); ?>">
				<span class="theme-toggle__icons" aria-hidden="true">
					<?php
					clipto_the_icon( 'moon', array( 'class' => 'theme-toggle__icon theme-toggle__icon--moon' ) );
					clipto_the_icon( 'sun', array( 'class' => 'theme-toggle__icon theme-toggle__icon--sun' ) );
					?>
				</span>
			</button>

			<?php if ( $clipto_has_nav ) : ?>
				<button type="button" class="site-header__menu" data-menu-open aria-haspopup="dialog" aria-controls="mobile-menu" aria-expanded="false">
					<?php clipto_the_icon( 'menu' ); ?>
					<span class="site-header__menu-label"><?php esc_html_e( 'Menu', 'clipto' ); ?></span>
				</button>
			<?php endif; ?>
		</div>

	</div>
</header>
<?php
get_template_part( 'template-parts/header/search-dialog' );
if ( $clipto_has_nav ) {
	get_template_part( 'template-parts/header/mobile-menu' );
}
