<?php
/**
 * The header for our theme.
 *
 * @package Clipto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link rel="profile" href="https://gmpg.org/xfn/11" />
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'clipto' ); ?></a>

<header class="site-header" id="masthead">
	<div class="clipto-container site-header__inner">
		<div class="site-branding">
			<?php
			if ( has_custom_logo() ) {
				the_custom_logo();
			} else {
				?>
				<a class="site-title" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
				<?php
			}
			?>
		</div>

		<nav class="clipto-nav" aria-label="<?php esc_attr_e( 'Primary', 'clipto' ); ?>">
			<button type="button" class="clipto-nav-toggle" id="clipto-nav-toggle" aria-expanded="false" aria-controls="clipto-nav-menu">
				<span class="clipto-nav-toggle__icon" aria-hidden="true"></span>
				<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'clipto' ); ?></span>
			</button>
			<div class="clipto-nav-menu" id="clipto-nav-menu">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'clipto-primary',
						'container'      => false,
						'menu_class'     => 'clipto-nav-list',
						'fallback_cb'    => 'clipto_primary_nav_fallback',
					)
				);
				?>
			</div>
		</nav>

		<div class="clipto-search">
			<button type="button" class="clipto-search-toggle" id="clipto-search-toggle" aria-expanded="false" aria-controls="clipto-search-form">
				<svg aria-hidden="true" viewBox="0 0 24 24" width="20" height="20"><path fill="none" stroke="currentColor" stroke-width="2" d="M21 21l-4.35-4.35M18 11a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
				<span class="screen-reader-text"><?php esc_html_e( 'Search', 'clipto' ); ?></span>
			</button>
			<div class="clipto-search-form" id="clipto-search-form">
				<?php get_search_form(); ?>
			</div>
		</div>
	</div>
</header>

<main id="primary" class="site-main">
