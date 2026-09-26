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
 * Enqueue styles and scripts.
 */
function clipto_enqueue_assets() {
	wp_enqueue_style( 'clipto', CLIPTO_URI . '/assets/css/main.css', array(), clipto_asset_version( 'assets/css/main.css' ) );

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
