<?php
/**
 * Builds the minified stylesheets the Clipto theme serves in production.
 *
 *   php tools/build-css.php
 *
 * Writes style.min.css and assets/css/article.min.css next to their
 * sources. The theme only serves a .min.css while it is at least as new as
 * its source (see clipto_asset_uri()), so forgetting to re-run this after
 * editing CSS falls back to the readable file instead of shipping stale
 * rules. Re-run it before building the release ZIP.
 *
 * The minifier is deliberately conservative: it is string-aware, drops
 * comments, collapses whitespace, and removes it only around { } ; , where
 * it can never be significant. It does not touch ":" (".a :not(b)" needs
 * its space), "+"/"-" (calc()) or ">" .
 */

$theme = dirname( __DIR__ ) . '/clipto-theme';
$files = array( 'style.css', 'assets/css/article.css' );

function clipto_minify_css( $css ) {
	$parts = preg_split( '~("(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\'|/\*.*?\*/)~s', $css, -1, PREG_SPLIT_DELIM_CAPTURE );
	$out   = '';
	foreach ( $parts as $i => $part ) {
		if ( 1 === $i % 2 ) {
			// Strings are kept verbatim; comments are dropped.
			$out .= ( 0 === strpos( $part, '/*' ) ) ? ' ' : $part;
			continue;
		}
		$part = preg_replace( '/\s+/', ' ', $part );
		$part = preg_replace( '/\s*([{};,])\s*/', '$1', $part );
		$out .= $part;
	}
	$out = preg_replace( '/\s*([{};,])\s*/', '$1', $out ); // Around dropped comments.
	$out = str_replace( ';}', '}', $out );
	return trim( $out ) . "\n";
}

foreach ( $files as $file ) {
	$src = $theme . '/' . $file;
	$dst = preg_replace( '/\.css$/', '.min.css', $src );
	$css = file_get_contents( $src );

	// Keep the theme header comment in style.min.css (WordPress reads the
	// header from style.css, but licences travel with the served file).
	$banner = '';
	if ( 'style.css' === $file && preg_match( '~^/\*.*?\*/~s', $css, $m ) ) {
		$banner = "/*! Clipto v" . ( preg_match( '/^Version:\s*(\S+)/m', $m[0], $v ) ? $v[1] : '' ) . " | GPL-2.0-or-later */\n";
	}

	$min = $banner . clipto_minify_css( $css );
	file_put_contents( $dst, $min );
	printf( "%-26s %6d -> %6d bytes (gzip %5d -> %5d)\n", $file, strlen( $css ), strlen( $min ), strlen( gzencode( $css, 9 ) ), strlen( gzencode( $min, 9 ) ) );
}
