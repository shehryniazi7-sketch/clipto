<?php
/**
 * Editorial block styles and the "Clipto editorial" pattern category.
 *
 * The styles themselves live in src/css/35-blocks.css (compiled into both the
 * front-end stylesheet and the editor stylesheet). Patterns are plain PHP files in
 * /patterns and are registered automatically by core from their file headers.
 *
 * Component labels that CSS draws with ::before ("Note", "Key takeaways" …) read
 * from custom properties with English fallbacks; clipto_block_label_css() prints
 * translated values so the labels follow the site language.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'clipto_block_style_map' ) ) {
	/**
	 * Block styles registered by the theme, keyed by block name.
	 *
	 * @return array<string,array<string,string>> block => [ style slug => label ].
	 */
	function clipto_block_style_map() {
		return array(
			'core/group'   => array(
				'clipto-takeaways' => __( 'Key takeaways', 'clipto' ),
				'clipto-note'      => __( 'Note', 'clipto' ),
				'clipto-tip'       => __( 'Tip', 'clipto' ),
				'clipto-warning'   => __( 'Warning', 'clipto' ),
				'clipto-verdict'   => __( 'Verdict', 'clipto' ),
			),
			'core/list'    => array(
				'clipto-takeaways' => __( 'Key takeaways', 'clipto' ),
				'clipto-pros'      => __( 'Pros', 'clipto' ),
				'clipto-cons'      => __( 'Cons', 'clipto' ),
				'clipto-glance'    => __( 'At a glance', 'clipto' ),
				'clipto-index'     => __( 'Index list', 'clipto' ),
			),
			'core/details' => array(
				'clipto-faq' => __( 'FAQ', 'clipto' ),
			),
			'core/table'   => array(
				'clipto-compare' => __( 'Comparison', 'clipto' ),
			),
			'core/quote'   => array(
				'clipto-pull' => __( 'Pull quote', 'clipto' ),
			),
		);
	}
}

/**
 * Register the editorial block styles.
 */
function clipto_register_block_styles() {
	if ( ! function_exists( 'register_block_style' ) ) {
		return;
	}
	foreach ( clipto_block_style_map() as $block => $styles ) {
		foreach ( $styles as $name => $label ) {
			register_block_style(
				$block,
				array(
					'name'  => $name,
					'label' => $label,
				)
			);
		}
	}
}
add_action( 'init', 'clipto_register_block_styles' );

/**
 * Register the "Clipto editorial" pattern category. Patterns in /patterns use it.
 */
function clipto_register_pattern_category() {
	if ( ! function_exists( 'register_block_pattern_category' ) ) {
		return;
	}
	register_block_pattern_category(
		'clipto',
		array(
			'label'       => __( 'Clipto editorial', 'clipto' ),
			'description' => __( 'Article and AI tool review components: key takeaways, callouts, pros and cons, comparisons, FAQ and verdict.', 'clipto' ),
		)
	);
}
add_action( 'init', 'clipto_register_pattern_category', 9 );

/* -------------------------------------------------------------------------
 * Translated component labels
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'clipto_css_string' ) ) {
	/**
	 * Quote a plain-text value as a CSS string literal.
	 *
	 * @param string $text Text.
	 * @return string e.g. "Key takeaways"
	 */
	function clipto_css_string( $text ) {
		$text = wp_strip_all_tags( (string) $text );
		$text = preg_replace( '/[\x00-\x1F\x7F<>]/u', ' ', $text );
		$text = str_replace( array( '\\', '"' ), array( '\\\\', '\\"' ), (string) $text );
		return '"' . trim( $text ) . '"';
	}
}

/**
 * Custom properties holding the translated ::before labels. Returns '' when every
 * label equals the English fallback already written in the stylesheet.
 *
 * @return string CSS.
 */
function clipto_block_label_css() {
	$labels = array(
		// Custom property => [ English fallback used in 35-blocks.css, translated label ].
		'--clipto-label-takeaways' => array( 'Key takeaways', __( 'Key takeaways', 'clipto' ) ),
		'--clipto-label-note'      => array( 'Note', __( 'Note', 'clipto' ) ),
		'--clipto-label-tip'       => array( 'Tip', __( 'Tip', 'clipto' ) ),
		'--clipto-label-warning'   => array( 'Warning', __( 'Warning', 'clipto' ) ),
		'--clipto-label-glance'    => array( 'At a glance', __( 'At a glance', 'clipto' ) ),
	);

	/**
	 * Filter the labels drawn by the editorial block styles.
	 *
	 * @param array $labels Custom property => [ fallback, label ].
	 */
	$labels = apply_filters( 'clipto_block_labels', $labels );

	$decls = array();
	foreach ( $labels as $prop => $pair ) {
		if ( ! is_array( $pair ) || 2 !== count( $pair ) || ! preg_match( '/^--[a-z0-9-]+$/', $prop ) ) {
			continue;
		}
		list( $fallback, $label ) = array_values( $pair );
		if ( '' === trim( (string) $label ) || $label === $fallback ) {
			continue;
		}
		$decls[] = $prop . ':' . clipto_css_string( $label );
	}
	return $decls ? ':root{' . implode( ';', $decls ) . '}' : '';
}

/**
 * Front end: attach translated labels to the theme stylesheet (singular views only —
 * block content is not rendered on archives).
 */
function clipto_block_labels_front() {
	if ( ! is_singular() ) {
		return;
	}
	$css = clipto_block_label_css();
	if ( $css ) {
		wp_add_inline_style( 'clipto', $css );
	}
}
add_action( 'wp_enqueue_scripts', 'clipto_block_labels_front', 20 );

/**
 * Editor: the same labels inside the (iframed) editor canvas.
 */
function clipto_block_labels_editor() {
	if ( ! is_admin() ) {
		return;
	}
	$css = clipto_block_label_css();
	if ( ! $css ) {
		return;
	}
	wp_register_style( 'clipto-block-labels', false, array(), CLIPTO_VERSION );
	wp_enqueue_style( 'clipto-block-labels' );
	wp_add_inline_style( 'clipto-block-labels', $css );
}
add_action( 'enqueue_block_assets', 'clipto_block_labels_editor' );

/* -------------------------------------------------------------------------
 * Comparison tables: ✓ / ✗ become accessible, consistently drawn marks
 * ---------------------------------------------------------------------- */

/**
 * In tables using the "Comparison" style, replace check / cross characters with the
 * theme's line icons plus a screen-reader word ("Yes" / "No"). Only text nodes are
 * touched; tags and attributes are left exactly as core rendered them.
 *
 * @param string $block_content Rendered block.
 * @param array  $block         Parsed block.
 * @return string
 */
function clipto_compare_table_marks( $block_content, $block ) {
	$class = isset( $block['attrs']['className'] ) ? (string) $block['attrs']['className'] : '';
	if ( false === strpos( $class, 'is-style-clipto-compare' ) || ! function_exists( 'clipto_icon' ) ) {
		return $block_content;
	}
	if ( ! preg_match( '/[\x{2713}\x{2714}\x{2715}\x{2717}\x{2718}]/u', $block_content ) ) {
		return $block_content;
	}

	$parts = preg_split( '/(<[^>]*>)/u', $block_content, -1, PREG_SPLIT_DELIM_CAPTURE );
	if ( false === $parts ) {
		return $block_content;
	}

	$marks = array(
		'yes' => '<span class="clipto-compare__mark clipto-compare__mark--yes">' . clipto_icon( 'check' ) . '<span class="screen-reader-text">' . esc_html__( 'Yes', 'clipto' ) . '</span></span>',
		'no'  => '<span class="clipto-compare__mark clipto-compare__mark--no">' . clipto_icon( 'close' ) . '<span class="screen-reader-text">' . esc_html__( 'No', 'clipto' ) . '</span></span>',
	);

	foreach ( $parts as $i => $part ) {
		if ( '' === $part || '<' === $part[0] ) {
			continue;
		}
		$replaced = preg_replace_callback(
			'/[\x{2713}\x{2714}\x{2715}\x{2717}\x{2718}]\x{FE0F}?/u',
			static function ( $m ) use ( $marks ) {
				$is_yes = 0 === strpos( $m[0], "\u{2713}" ) || 0 === strpos( $m[0], "\u{2714}" );
				return $is_yes ? $marks['yes'] : $marks['no'];
			},
			$part
		);
		if ( null !== $replaced ) {
			$parts[ $i ] = $replaced;
		}
	}
	return implode( '', $parts );
}
add_filter( 'render_block_core/table', 'clipto_compare_table_marks', 10, 2 );
