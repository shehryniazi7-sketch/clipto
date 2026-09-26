<?php
/**
 * Customizer: "Clipto: Newsletter" and "Clipto: Footer" sections, plus the helpers the
 * newsletter band and site footer read from.
 *
 * The newsletter is never a fake form. It renders only when a real provider is configured:
 *
 *  - Form action mode: the form posts straight to an email provider's https form endpoint
 *    (Mailchimp, Buttondown, Kit, MailerLite …) with the field names that provider expects.
 *  - Shortcode mode: a newsletter plugin's shortcode (MailPoet, The Newsletter Plugin,
 *    Jetpack …) is rendered with do_shortcode() and styled to match.
 *
 * Success state: set the provider's "after subscribe" redirect to
 * home_url( '/?clipto_subscribed=1#newsletter' ). The band then shows a confirmation.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Defaults
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'clipto_newsletter_defaults' ) ) {
	/**
	 * Default values for every newsletter / footer theme mod.
	 *
	 * @return array<string,mixed>
	 */
	function clipto_newsletter_defaults() {
		return array(
			'clipto_nl_enabled'     => false,
			'clipto_nl_heading'     => __( 'The week in AI, in one *considered* email.', 'clipto' ),
			'clipto_nl_description' => __( 'A weekly briefing on the AI tools, news and guides worth your time — chosen, summarised and put in context, so you can skip the noise.', 'clipto' ),
			'clipto_nl_mode'        => 'action',
			'clipto_nl_shortcode'   => '',
			'clipto_nl_action'      => '',
			'clipto_nl_field'       => 'email',
			'clipto_nl_hidden'      => '',
			'clipto_nl_submit'      => __( 'Subscribe', 'clipto' ),
			'clipto_nl_trust'       => __( 'One email a week. No spam. Unsubscribe anytime.', 'clipto' ),
			'clipto_nl_privacy'     => true,
			'clipto_footer_about'   => __( 'Clipto is a field guide to artificial intelligence: the tools worth trying, the news worth knowing and practical guides for doing better work with AI.', 'clipto' ),
			'clipto_footer_mark'    => false,
		);
	}
}

if ( ! function_exists( 'clipto_mod' ) ) {
	/**
	 * Read a Clipto theme mod with its registered default.
	 *
	 * @param string $key Theme mod name.
	 * @return mixed
	 */
	function clipto_mod( $key ) {
		$defaults = clipto_newsletter_defaults();
		return get_theme_mod( $key, isset( $defaults[ $key ] ) ? $defaults[ $key ] : '' );
	}
}

/* -------------------------------------------------------------------------
 * Sanitizers
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'clipto_sanitize_checkbox' ) ) {
	/**
	 * Checkbox → bool.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	function clipto_sanitize_checkbox( $value ) {
		return in_array( $value, array( true, 1, '1', 'true', 'on', 'yes' ), true );
	}
}

if ( ! function_exists( 'clipto_sanitize_nl_mode' ) ) {
	/**
	 * Provider mode: 'action' or 'shortcode'.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	function clipto_sanitize_nl_mode( $value ) {
		return in_array( $value, array( 'action', 'shortcode' ), true ) ? $value : 'action';
	}
}

if ( ! function_exists( 'clipto_sanitize_https_url' ) ) {
	/**
	 * Absolute https URL, or ''. A provider endpoint must never receive email addresses
	 * over plain http.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	function clipto_sanitize_https_url( $value ) {
		$url = esc_url_raw( trim( (string) $value ), array( 'https' ) );
		return ( $url && 0 === strpos( $url, 'https://' ) && wp_parse_url( $url, PHP_URL_HOST ) ) ? $url : '';
	}
}

if ( ! function_exists( 'clipto_sanitize_field_name' ) ) {
	/**
	 * Form field name. Like sanitize_key() but case-preserving and allowing brackets,
	 * because providers expect names such as EMAIL (Mailchimp), email_address (Kit) or
	 * fields[email] (MailerLite).
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	function clipto_sanitize_field_name( $value ) {
		$value = preg_replace( '/[^A-Za-z0-9_\-\[\]\.]/', '', (string) $value );
		return substr( (string) $value, 0, 64 );
	}
}

if ( ! function_exists( 'clipto_sanitize_email_field' ) ) {
	/**
	 * Email field name, falling back to 'email'.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	function clipto_sanitize_email_field( $value ) {
		$name = clipto_sanitize_field_name( $value );
		return '' !== $name ? $name : 'email';
	}
}

if ( ! function_exists( 'clipto_parse_hidden_fields' ) ) {
	/**
	 * Parse "key=value" lines into a clean associative array.
	 *
	 * @param string $value Raw textarea value.
	 * @return array<string,string>
	 */
	function clipto_parse_hidden_fields( $value ) {
		$fields = array();
		foreach ( preg_split( '/\r\n|\r|\n/', (string) $value ) as $line ) {
			$line = trim( $line );
			if ( '' === $line || false === strpos( $line, '=' ) ) {
				continue;
			}
			list( $key, $val ) = array_map( 'trim', explode( '=', $line, 2 ) );
			$key               = clipto_sanitize_field_name( $key );
			if ( '' === $key || count( $fields ) >= 20 ) {
				continue;
			}
			$fields[ $key ] = sanitize_text_field( $val );
		}
		return $fields;
	}
}

if ( ! function_exists( 'clipto_sanitize_hidden_fields' ) ) {
	/**
	 * Normalise the hidden-fields textarea (one key=value per line).
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	function clipto_sanitize_hidden_fields( $value ) {
		$lines = array();
		foreach ( clipto_parse_hidden_fields( $value ) as $key => $val ) {
			$lines[] = $key . '=' . $val;
		}
		return implode( "\n", $lines );
	}
}

if ( ! function_exists( 'clipto_sanitize_shortcode' ) ) {
	/**
	 * A single shortcode such as [mailpoet_form id="1"], or ''.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	function clipto_sanitize_shortcode( $value ) {
		$value = trim( sanitize_text_field( (string) $value ) );
		return preg_match( '/^\[[A-Za-z0-9_\-]+(\s[^\[\]]*)?\](.*\[\/[A-Za-z0-9_\-]+\])?$/s', $value ) ? $value : '';
	}
}

/* -------------------------------------------------------------------------
 * Newsletter state
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'clipto_newsletter_config' ) ) {
	/**
	 * Resolved newsletter configuration.
	 *
	 * Keys: enabled, ready (bool — a real provider is configured), reason (why not ready:
	 * disabled|no_action|no_shortcode|missing_shortcode), mode, heading, description,
	 * shortcode, action, field, hidden (array), submit, trust, privacy_url.
	 *
	 * @return array<string,mixed>
	 */
	function clipto_newsletter_config() {
		$defaults = clipto_newsletter_defaults();

		$config = array(
			'enabled'     => clipto_sanitize_checkbox( clipto_mod( 'clipto_nl_enabled' ) ),
			'mode'        => clipto_sanitize_nl_mode( clipto_mod( 'clipto_nl_mode' ) ),
			'heading'     => trim( (string) clipto_mod( 'clipto_nl_heading' ) ),
			'description' => trim( (string) clipto_mod( 'clipto_nl_description' ) ),
			'shortcode'   => clipto_sanitize_shortcode( clipto_mod( 'clipto_nl_shortcode' ) ),
			'action'      => clipto_sanitize_https_url( clipto_mod( 'clipto_nl_action' ) ),
			'field'       => clipto_sanitize_email_field( clipto_mod( 'clipto_nl_field' ) ),
			'hidden'      => clipto_parse_hidden_fields( clipto_mod( 'clipto_nl_hidden' ) ),
			'submit'      => trim( (string) clipto_mod( 'clipto_nl_submit' ) ),
			'trust'       => trim( (string) clipto_mod( 'clipto_nl_trust' ) ),
			'privacy_url' => '',
			'ready'       => false,
			'reason'      => '',
		);

		if ( '' === $config['heading'] ) {
			$config['heading'] = $defaults['clipto_nl_heading'];
		}
		if ( '' === $config['submit'] ) {
			$config['submit'] = $defaults['clipto_nl_submit'];
		}
		if ( clipto_sanitize_checkbox( clipto_mod( 'clipto_nl_privacy' ) ) ) {
			$config['privacy_url'] = (string) get_privacy_policy_url(); // '' unless a published privacy page is set.
		}

		if ( ! $config['enabled'] ) {
			$config['reason'] = 'disabled';
		} elseif ( 'action' === $config['mode'] ) {
			$config['ready']  = '' !== $config['action'];
			$config['reason'] = $config['ready'] ? '' : 'no_action';
		} elseif ( '' === $config['shortcode'] ) {
			$config['reason'] = 'no_shortcode';
		} else {
			preg_match( '/^\[([A-Za-z0-9_\-]+)/', $config['shortcode'], $m );
			$config['ready']  = ! empty( $m[1] ) && shortcode_exists( $m[1] );
			$config['reason'] = $config['ready'] ? '' : 'missing_shortcode';
		}

		return apply_filters( 'clipto_newsletter_config', $config );
	}
}

if ( ! function_exists( 'clipto_newsletter_is_ready' ) ) {
	/**
	 * Whether the newsletter band renders for visitors (enabled + real provider).
	 * Other templates may use this before linking to #newsletter.
	 *
	 * @return bool
	 */
	function clipto_newsletter_is_ready() {
		$config = clipto_newsletter_config();
		return (bool) $config['ready'];
	}
}

if ( ! function_exists( 'clipto_newsletter_subscribed' ) ) {
	/**
	 * Whether the visitor has just been redirected back by the provider
	 * (?clipto_subscribed=1). Display-only; no state is changed.
	 *
	 * @return bool
	 */
	function clipto_newsletter_subscribed() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display flag.
		return isset( $_GET['clipto_subscribed'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['clipto_subscribed'] ) );
	}
}

if ( ! function_exists( 'clipto_newsletter_heading_html' ) ) {
	/**
	 * Escaped heading; one *word* wrapped in asterisks becomes the italic accent.
	 *
	 * @param string $text Heading text.
	 * @return string Safe HTML.
	 */
	function clipto_newsletter_heading_html( $text ) {
		$html = esc_html( $text );
		return (string) preg_replace( '/\*([^*]+)\*/', '<em>$1</em>', $html, 1 );
	}
}

if ( ! function_exists( 'clipto_newsletter_redirect_url' ) ) {
	/**
	 * The URL site owners paste into their provider's post-subscribe redirect.
	 *
	 * @return string
	 */
	function clipto_newsletter_redirect_url() {
		return add_query_arg( 'clipto_subscribed', '1', home_url( '/' ) ) . '#newsletter';
	}
}

/**
 * The confirmation view (?clipto_subscribed=1) is a duplicate of the page: keep it out
 * of search indexes.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function clipto_newsletter_robots( $robots ) {
	if ( clipto_newsletter_subscribed() ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'clipto_newsletter_robots' );

/* -------------------------------------------------------------------------
 * Footer helpers
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'clipto_copyright_years' ) ) {
	/**
	 * "2021–2026" from the first published post to today (cached), or just this year.
	 *
	 * @return string
	 */
	function clipto_copyright_years() {
		$now   = (int) wp_date( 'Y' );
		$first = get_transient( 'clipto_first_post_year' );
		if ( false === $first ) {
			$ids   = get_posts(
				array(
					'post_type'              => 'post',
					'post_status'            => 'publish',
					'numberposts'            => 1,
					'orderby'                => 'date',
					'order'                  => 'ASC',
					'fields'                 => 'ids',
					'suppress_filters'       => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);
			$first = $ids ? (int) get_the_date( 'Y', $ids[0] ) : $now;
			set_transient( 'clipto_first_post_year', $first, DAY_IN_SECONDS );
		}
		$first = (int) $first;
		return ( $first && $first < $now ) ? $first . '–' . $now : (string) $now;
	}
}

if ( ! function_exists( 'clipto_footer_link' ) ) {
	/**
	 * One footer index link: label, optional real post count, current-page state.
	 *
	 * @param string   $url     URL.
	 * @param string   $label   Label.
	 * @param int|null $count   Post count (null/0 = none shown).
	 * @param bool     $current Whether it is the current page.
	 * @return string
	 */
	function clipto_footer_link( $url, $label, $count = null, $current = false ) {
		$count_html = '';
		if ( $count ) {
			$count_html = '<span class="site-footer__count"><span class="screen-reader-text">, </span>'
				. esc_html( number_format_i18n( (int) $count ) )
				. '<span class="screen-reader-text"> ' . esc_html( _n( 'article', 'articles', (int) $count, 'clipto' ) ) . '</span></span>';
		}
		return '<li><a class="site-footer__link" href="' . esc_url( $url ) . '"' . ( $current ? ' aria-current="page"' : '' ) . '>'
			. '<span class="site-footer__label">' . esc_html( $label ) . '</span>' . $count_html . '</a></li>';
	}
}

/**
 * Footer menu links (colophon bar, depth 1) share the legal-link class.
 *
 * @param array    $atts Link attributes.
 * @param WP_Post  $item Menu item.
 * @param stdClass $args wp_nav_menu() args.
 * @return array
 */
function clipto_footer_menu_link_atts( $atts, $item, $args ) {
	if ( isset( $args->theme_location ) && 'footer' === $args->theme_location ) {
		$atts['class'] = trim( ( isset( $atts['class'] ) ? $atts['class'] . ' ' : '' ) . 'site-footer__legal-link' );
	}
	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'clipto_footer_menu_link_atts', 10, 3 );

/**
 * Forget the cached first-post year when posts change.
 */
function clipto_flush_copyright_years() {
	delete_transient( 'clipto_first_post_year' );
}
add_action( 'save_post_post', 'clipto_flush_copyright_years' );
add_action( 'deleted_post', 'clipto_flush_copyright_years' );

/* -------------------------------------------------------------------------
 * Customizer registration
 * ---------------------------------------------------------------------- */

/**
 * Register the Clipto Customizer sections, settings, controls and partials.
 *
 * @param WP_Customize_Manager $wp_customize Manager.
 */
function clipto_customize_register( $wp_customize ) {
	$d = clipto_newsletter_defaults();

	/* Newsletter ------------------------------------------------------------ */
	$wp_customize->add_section(
		'clipto_newsletter',
		array(
			'title'       => __( 'Clipto: Newsletter', 'clipto' ),
			'priority'    => 160,
			'description' => sprintf(
				/* translators: %s: redirect URL. */
				__( 'The newsletter band appears above the footer on every page — only once a real provider is connected. Choose a form action URL from your email provider or a newsletter plugin shortcode. To show the built-in confirmation, set your provider’s “after subscribe” redirect to: %s', 'clipto' ),
				'<code style="word-break:break-all">' . esc_html( clipto_newsletter_redirect_url() ) . '</code>'
			),
		)
	);

	$is_mode = static function ( $mode ) use ( $wp_customize ) {
		return static function () use ( $wp_customize, $mode ) {
			$setting = $wp_customize->get_setting( 'clipto_nl_mode' );
			return $setting && clipto_sanitize_nl_mode( $setting->value() ) === $mode;
		};
	};

	$settings = array(
		'clipto_nl_enabled'     => array(
			'sanitize' => 'clipto_sanitize_checkbox',
			'control'  => array(
				'type'  => 'checkbox',
				'label' => __( 'Show the newsletter band', 'clipto' ),
			),
		),
		'clipto_nl_heading'     => array(
			'sanitize' => 'sanitize_text_field',
			'control'  => array(
				'type'        => 'text',
				'label'       => __( 'Heading', 'clipto' ),
				'description' => __( 'Wrap one word in *asterisks* to set it in italic.', 'clipto' ),
			),
		),
		'clipto_nl_description' => array(
			'sanitize' => 'sanitize_textarea_field',
			'control'  => array(
				'type'  => 'textarea',
				'label' => __( 'Description', 'clipto' ),
			),
		),
		'clipto_nl_mode'        => array(
			'sanitize' => 'clipto_sanitize_nl_mode',
			'control'  => array(
				'type'    => 'radio',
				'label'   => __( 'Provider', 'clipto' ),
				'choices' => array(
					'action'    => __( 'Form action URL from my email provider', 'clipto' ),
					'shortcode' => __( 'Shortcode from a newsletter plugin', 'clipto' ),
				),
			),
		),
		'clipto_nl_action'      => array(
			'sanitize' => 'clipto_sanitize_https_url',
			'control'  => array(
				'type'            => 'url',
				'label'           => __( 'Form action URL', 'clipto' ),
				'description'     => __( 'The “action” of your provider’s embedded signup form. Must start with https://.', 'clipto' ),
				'input_attrs'     => array( 'placeholder' => 'https://' ),
				'active_callback' => $is_mode( 'action' ),
			),
		),
		'clipto_nl_field'       => array(
			'sanitize' => 'clipto_sanitize_email_field',
			'control'  => array(
				'type'            => 'text',
				'label'           => __( 'Email field name', 'clipto' ),
				'description'     => __( 'The name your provider expects for the email input, e.g. email, EMAIL or email_address.', 'clipto' ),
				'active_callback' => $is_mode( 'action' ),
			),
		),
		'clipto_nl_hidden'      => array(
			'sanitize' => 'clipto_sanitize_hidden_fields',
			'control'  => array(
				'type'            => 'textarea',
				'label'           => __( 'Hidden fields (optional)', 'clipto' ),
				'description'     => __( 'One key=value per line — list IDs, tags or tokens your provider’s form requires.', 'clipto' ),
				'input_attrs'     => array( 'placeholder' => "list_id=abc123\ntag=website" ),
				'active_callback' => $is_mode( 'action' ),
			),
		),
		'clipto_nl_submit'      => array(
			'sanitize' => 'sanitize_text_field',
			'control'  => array(
				'type'            => 'text',
				'label'           => __( 'Button label', 'clipto' ),
				'active_callback' => $is_mode( 'action' ),
			),
		),
		'clipto_nl_shortcode'   => array(
			'sanitize' => 'clipto_sanitize_shortcode',
			'control'  => array(
				'type'            => 'text',
				'label'           => __( 'Newsletter shortcode', 'clipto' ),
				'description'     => __( 'For example [mailpoet_form id="1"]. The plugin must be active. For the closest match, turn off the plugin’s own form styling.', 'clipto' ),
				'input_attrs'     => array( 'placeholder' => '[newsletter_form]' ),
				'active_callback' => $is_mode( 'shortcode' ),
			),
		),
		'clipto_nl_trust'       => array(
			'sanitize' => 'sanitize_text_field',
			'control'  => array(
				'type'        => 'text',
				'label'       => __( 'Reassurance line (optional)', 'clipto' ),
				'description' => __( 'Shown under the form. Keep it true to how your list works.', 'clipto' ),
			),
		),
		'clipto_nl_privacy'     => array(
			'sanitize' => 'clipto_sanitize_checkbox',
			'control'  => array(
				'type'        => 'checkbox',
				'label'       => __( 'Link to the privacy policy', 'clipto' ),
				'description' => get_privacy_policy_url()
					? __( 'Uses the page set in Settings → Privacy.', 'clipto' )
					: __( 'No published privacy policy page is set in Settings → Privacy, so no link is shown.', 'clipto' ),
			),
		),
	);

	foreach ( $settings as $id => $def ) {
		$wp_customize->add_setting(
			$id,
			array(
				'default'           => $d[ $id ],
				'type'              => 'theme_mod',
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => $def['sanitize'],
				// Switching provider re-evaluates the controls' active_callback (full refresh).
				'transport'         => 'clipto_nl_mode' === $id ? 'refresh' : 'postMessage',
			)
		);
		$wp_customize->add_control( $id, array_merge( array( 'section' => 'clipto_newsletter' ), $def['control'] ) );
	}

	/* Footer ---------------------------------------------------------------- */
	$wp_customize->add_section(
		'clipto_footer',
		array(
			'title'    => __( 'Clipto: Footer', 'clipto' ),
			'priority' => 161,
		)
	);

	$wp_customize->add_setting(
		'clipto_footer_about',
		array(
			'default'           => $d['clipto_footer_about'],
			'type'              => 'theme_mod',
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'sanitize_textarea_field',
			'transport'         => 'postMessage',
		)
	);
	$wp_customize->add_control(
		'clipto_footer_about',
		array(
			'section'     => 'clipto_footer',
			'type'        => 'textarea',
			'label'       => __( 'About line', 'clipto' ),
			'description' => __( 'One or two sentences under the logo and tagline. Leave empty to hide.', 'clipto' ),
		)
	);

	$wp_customize->add_setting(
		'clipto_footer_mark',
		array(
			'default'           => $d['clipto_footer_mark'],
			'type'              => 'theme_mod',
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'clipto_sanitize_checkbox',
			'transport'         => 'postMessage',
		)
	);
	$wp_customize->add_control(
		'clipto_footer_mark',
		array(
			'section'     => 'clipto_footer',
			'type'        => 'checkbox',
			'label'       => __( 'Show the large wordmark', 'clipto' ),
			'description' => __( 'A faint, oversized site name above the copyright line. Off by default.', 'clipto' ),
		)
	);

	/* Selective refresh ------------------------------------------------------ */
	if ( isset( $wp_customize->selective_refresh ) ) {
		$wp_customize->selective_refresh->add_partial(
			'clipto_newsletter',
			array(
				'selector'            => '[data-clipto-newsletter]',
				'settings'            => array_keys( $settings ),
				'container_inclusive' => true,
				'render_callback'     => static function () {
					get_template_part( 'template-parts/newsletter' );
				},
				'fallback_refresh'    => true,
			)
		);
		$wp_customize->selective_refresh->add_partial(
			'clipto_footer',
			array(
				'selector'            => '.site-footer',
				'settings'            => array( 'clipto_footer_about', 'clipto_footer_mark', 'clipto_nl_enabled', 'clipto_nl_mode', 'clipto_nl_action', 'clipto_nl_shortcode' ),
				'container_inclusive' => true,
				'render_callback'     => static function () {
					get_template_part( 'template-parts/footer/site-footer' );
				},
				'fallback_refresh'    => true,
			)
		);
	}
}
add_action( 'customize_register', 'clipto_customize_register' );
