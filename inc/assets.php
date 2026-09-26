<?php
/**
 * Front-end assets: one stylesheet, two small deferred scripts, font preloads and the
 * render-blocking theme bootstrap (sets data-theme before first paint → no flash).
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

/**
 * File version from mtime so caches bust on deploy.
 *
 * @param string $rel Relative path.
 * @return string
 */
function clipto_asset_version( $rel ) {
	$path = CLIPTO_DIR . '/' . $rel;
	return is_readable( $path ) ? (string) filemtime( $path ) : CLIPTO_VERSION;
}

/**
 * Which template stylesheet the current view needs: home, article or archive.
 *
 * @return string
 */
function clipto_css_bundle() {
	if ( is_front_page() || is_home() ) {
		$bundle = 'home';
	} elseif ( is_singular() ) {
		$bundle = 'article';
	} else {
		$bundle = 'archive'; // Archives, search, author, 404.
	}
	return (string) apply_filters( 'clipto_css_bundle', $bundle );
}

/**
 * Enqueue a theme stylesheet. By default the CSS (~11–22 KB gzipped per page) is printed inline in
 * <head>: no render-blocking request, which is worth ~0.3–0.5 s of LCP on mobile. Sites behind a
 * CDN that prefer cacheable files can opt out: add_filter( 'clipto_inline_css', '__return_false' ).
 * Either way the handle is registered, so plugins can depend on it.
 *
 * @param string   $handle Style handle.
 * @param string   $rel    Path relative to the theme root.
 * @param string[] $deps   Dependencies.
 */
function clipto_enqueue_css( $handle, $rel, $deps = array() ) {
	$path = CLIPTO_DIR . '/' . $rel;
	if ( apply_filters( 'clipto_inline_css', true, $handle ) && is_readable( $path ) ) {
		wp_register_style( $handle, false, $deps, clipto_asset_version( $rel ) );
		wp_enqueue_style( $handle );
		wp_add_inline_style( $handle, (string) file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
		return;
	}
	wp_enqueue_style( $handle, CLIPTO_URI . '/' . $rel, $deps, clipto_asset_version( $rel ) );
}

/**
 * Enqueue styles and scripts.
 */
function clipto_enqueue_assets() {
	// main.css + one template bundle per page type keeps each page's CSS small.
	$bundle = clipto_css_bundle();
	clipto_enqueue_css( 'clipto', 'assets/css/main.css' );
	if ( $bundle ) {
		clipto_enqueue_css( 'clipto-' . $bundle, 'assets/css/' . $bundle . '.css', array( 'clipto' ) );
	}

	wp_enqueue_script(
		'clipto',
		CLIPTO_URI . '/assets/js/main.js',
		array(),
		clipto_asset_version( 'assets/js/main.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	if ( is_singular() && ! is_front_page() ) {
		wp_enqueue_script(
			'clipto-article',
			CLIPTO_URI . '/assets/js/article.js',
			array(),
			clipto_asset_version( 'assets/js/article.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

	wp_localize_script(
		'clipto',
		'cliptoI18n',
		array(
			'copied'    => __( 'Link copied', 'clipto' ),
			'copy'      => __( 'Copy link', 'clipto' ),
			'toLight'   => __( 'Switch to light theme', 'clipto' ),
			'toDark'    => __( 'Switch to dark theme', 'clipto' ),
			'openMenu'  => __( 'Open menu', 'clipto' ),
			'closeMenu' => __( 'Close menu', 'clipto' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'clipto_enqueue_assets' );

/**
 * Preload the two critical font files (roman serif + sans). Italic loads on demand.
 */
function clipto_preload_fonts() {
	foreach ( array( 'newsreader-var.woff2', 'schibsted-grotesk-var.woff2' ) as $font ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( CLIPTO_URI . '/assets/fonts/' . $font )
		);
	}
}
add_action( 'wp_head', 'clipto_preload_fonts', 1 );

/**
 * Theme bootstrap, printed first in <head>: marks JS as available and applies the
 * reader's saved light/dark choice before first paint.
 */
function clipto_theme_bootstrap() {
	?>
	<script>(function(d){var e=d.documentElement;e.classList.add('js');try{var t=localStorage.getItem('clipto-theme');if(t==='light'||t==='dark'){e.setAttribute('data-theme',t);}}catch(_){}})(document);</script>
	<?php
}
add_action( 'wp_head', 'clipto_theme_bootstrap', 0 );

/**
 * Theme colour for mobile browser chrome, per scheme.
 */
function clipto_meta_theme_color() {
	echo '<meta name="theme-color" content="#F6F4EE" media="(prefers-color-scheme: light)">' . "\n";
	echo '<meta name="theme-color" content="#0D0F11" media="(prefers-color-scheme: dark)">' . "\n";
}
add_action( 'wp_head', 'clipto_meta_theme_color', 2 );
