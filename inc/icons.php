<?php
/**
 * Inline SVG icon set (1.6 stroke line icons, 24px grid). Inline icons avoid an
 * extra request and inherit currentColor.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return the SVG markup for an icon.
 *
 * @param string $name  Icon name.
 * @param array  $args  { class: string, label: string (accessible name; omit for decorative) }.
 * @return string
 */
function clipto_icon( $name, $args = array() ) {
	static $paths = null;

	if ( null === $paths ) {
		$paths = array(
			'search'         => '<circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.35-4.35"/>',
			'arrow-right'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
			'arrow-left'     => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
			'arrow-up-right' => '<path d="M7 17 17 7M8 7h9v9"/>',
			'arrow-up'       => '<path d="M12 19V5M6 11l6-6 6 6"/>',
			'chevron-down'   => '<path d="m6 9 6 6 6-6"/>',
			'chevron-right'  => '<path d="m9 6 6 6-6 6"/>',
			'menu'           => '<path d="M4 7h16M4 12h16M4 17h10"/>',
			'close'          => '<path d="M6 6l12 12M18 6 6 18"/>',
			'sun'            => '<circle cx="12" cy="12" r="4"/><path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4 6 18M18 6l1.4-1.4"/>',
			'moon'           => '<path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5Z"/>',
			'link'           => '<path d="M10 14a4.5 4.5 0 0 0 6.4.4l3-3a4.5 4.5 0 0 0-6.4-6.4l-1.2 1.2"/><path d="M14 10a4.5 4.5 0 0 0-6.4-.4l-3 3a4.5 4.5 0 0 0 6.4 6.4l1.2-1.2"/>',
			'check'          => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
			'clock'          => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
			'list'           => '<path d="M9 6h11M9 12h11M9 18h11M4.5 6h.01M4.5 12h.01M4.5 18h.01"/>',
			'plus'           => '<path d="M12 5v14M5 12h14"/>',
			'minus'          => '<path d="M5 12h14"/>',
			'star'           => '<path d="m12 3.5 2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9l-5.2 2.7 1-5.8-4.3-4.1 5.9-.9L12 3.5Z"/>',
			'mail'           => '<rect x="3.5" y="5.5" width="17" height="13" rx="1.5"/><path d="m4 7 8 6 8-6"/>',
			'x'              => '<path d="M4 4l16 16M20 4 4 20" stroke-width="1.8"/>',
			'linkedin'       => '<rect x="3.5" y="3.5" width="17" height="17" rx="2"/><path d="M8 10.5V16M8 7.8v.01M11.5 16v-5.5M11.5 13c0-1.6 1-2.6 2.4-2.6s2.1.9 2.1 2.6V16"/>',
			'share'          => '<circle cx="18" cy="5.5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="18.5" r="2.5"/><path d="m8.2 10.8 7.6-4.1M8.2 13.2l7.6 4.1"/>',
			'external'       => '<path d="M14 4.5h5.5V10M19.5 4.5 11 13M17 13.5v5a1 1 0 0 1-1 1H5.5a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1h5"/>',
			'sparkle'        => '<path d="M12 3.5c.6 4.3 2.2 5.9 6.5 6.5-4.3.6-5.9 2.2-6.5 6.5-.6-4.3-2.2-5.9-6.5-6.5 4.3-.6 5.9-2.2 6.5-6.5Z"/>',
			'crop'           => '<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>',
		);
	}

	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}

	$class = 'icon icon--' . sanitize_html_class( $name );
	if ( ! empty( $args['class'] ) ) {
		$class .= ' ' . $args['class'];
	}

	$a11y = empty( $args['label'] )
		? ' aria-hidden="true" focusable="false"'
		: ' role="img" aria-label="' . esc_attr( $args['label'] ) . '"';

	return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 24 24" width="24" height="24"' . $a11y . '>' . $paths[ $name ] . '</svg>';
}

/**
 * Echo an icon. Output is built from a fixed internal whitelist above.
 *
 * @param string $name Icon name.
 * @param array  $args Args.
 */
function clipto_the_icon( $name, $args = array() ) {
	echo clipto_icon( $name, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static whitelist markup.
}

/**
 * The Clipto crop-mark logo mark (22px). Two corner brackets framing a vermilion square.
 *
 * @param string $class Extra class.
 * @return string
 */
function clipto_logo_mark( $class = '' ) {
	return '<svg class="logo-mark ' . esc_attr( $class ) . '" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">'
		. '<path d="M2.75 9V2.75H9M21.25 15v6.25H15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="square"/>'
		. '<rect class="logo-mark__dot" x="8.25" y="8.25" width="7.5" height="7.5" rx="0.5"/>'
		. '</svg>';
}
