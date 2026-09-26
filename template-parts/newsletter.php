<?php
/**
 * Site-wide newsletter band (between </main> and the site footer).
 *
 * Renders only when a real provider is configured in Customize → Clipto: Newsletter.
 * Unconfigured: nothing for visitors; a small setup note for users who can fix it.
 * After the provider redirects back with ?clipto_subscribed=1 the form is replaced by a
 * confirmation. The form posts straight to the provider — no fake AJAX success.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'clipto_newsletter_config' ) ) {
	return;
}

$clipto_nl = clipto_newsletter_config();

/* Not configured ----------------------------------------------------------- */
if ( ! $clipto_nl['ready'] ) {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$clipto_nl_reasons = array(
		'disabled'          => __( 'It is switched off.', 'clipto' ),
		'no_action'         => __( 'Add your provider’s https form action URL.', 'clipto' ),
		'no_shortcode'      => __( 'Add your newsletter plugin’s shortcode.', 'clipto' ),
		/* translators: %s: shortcode. */
		'missing_shortcode' => sprintf( __( 'The shortcode %s is not registered — is the plugin active?', 'clipto' ), $clipto_nl['shortcode'] ),
	);
	$clipto_nl_reason  = isset( $clipto_nl_reasons[ $clipto_nl['reason'] ] ) ? $clipto_nl_reasons[ $clipto_nl['reason'] ] : '';
	$clipto_nl_path    = isset( $GLOBALS['wp']->request ) ? trim( (string) $GLOBALS['wp']->request, '/' ) : '';
	$clipto_nl_setup   = add_query_arg(
		array(
			'autofocus[section]' => 'clipto_newsletter',
			'url'                => rawurlencode( user_trailingslashit( home_url( '/' . $clipto_nl_path ) ) ),
		),
		admin_url( 'customize.php' )
	);
	?>
	<aside class="newsletter-setup" data-clipto-newsletter aria-label="<?php esc_attr_e( 'Newsletter setup', 'clipto' ); ?>">
		<div class="container">
			<p class="newsletter-setup__note">
				<?php clipto_the_icon( 'mail', array( 'class' => 'newsletter-setup__icon' ) ); ?>
				<span>
					<strong><?php esc_html_e( 'Newsletter not configured', 'clipto' ); ?></strong>
					<?php esc_html_e( '— set it up in', 'clipto' ); ?>
					<a class="link-u" href="<?php echo esc_url( $clipto_nl_setup ); ?>"><?php esc_html_e( 'Customize → Clipto: Newsletter', 'clipto' ); ?></a>.
					<?php if ( $clipto_nl_reason ) : ?>
						<span class="newsletter-setup__reason"><?php echo esc_html( $clipto_nl_reason ); ?></span>
					<?php endif; ?>
					<span class="newsletter-setup__who"><?php esc_html_e( 'Only visible to site administrators.', 'clipto' ); ?></span>
				</span>
			</p>
		</div>
	</aside>
	<?php
	return;
}

/* Configured --------------------------------------------------------------- */
$clipto_nl_subscribed = clipto_newsletter_subscribed();
$clipto_nl_trust_id   = 'newsletter-trust';
$clipto_nl_has_trust  = '' !== $clipto_nl['trust'] || '' !== $clipto_nl['privacy_url'];
// The confirmation animates on its own; scroll reveals would fight it.
$clipto_nl_reveal = $clipto_nl_subscribed ? '' : ' data-reveal';
?>
<section class="newsletter<?php echo $clipto_nl_subscribed ? ' is-subscribed' : ''; ?>" id="newsletter" aria-labelledby="newsletter-title" data-clipto-newsletter data-newsletter>
	<div class="container">
		<div class="newsletter__inner">

			<p class="newsletter__eyebrow">
				<?php clipto_the_icon( 'mail', array( 'class' => 'newsletter__eyebrow-icon' ) ); ?>
				<span class="kicker"><?php esc_html_e( 'Newsletter', 'clipto' ); ?></span>
			</p>

			<h2 class="newsletter__title" id="newsletter-title"<?php echo $clipto_nl_reveal; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- constant. ?>>
				<?php echo clipto_newsletter_heading_html( $clipto_nl['heading'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
			</h2>

			<div class="newsletter__panel"<?php echo $clipto_nl_reveal; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- constant. ?> style="--i:1">
				<?php if ( $clipto_nl_subscribed ) : ?>

					<div class="newsletter__success" id="newsletter-success" role="status" tabindex="-1" data-newsletter-success>
						<svg class="newsletter__check" viewBox="0 0 48 48" width="48" height="48" aria-hidden="true" focusable="false">
							<path class="newsletter__check-ring" pathLength="1" d="M24 3.5a20.5 20.5 0 1 1 0 41 20.5 20.5 0 0 1 0-41Z"/>
							<path class="newsletter__check-tick" pathLength="1" d="m15 24.5 6.2 6.2L33.5 18"/>
						</svg>
						<div class="newsletter__success-text">
							<p class="newsletter__success-title"><?php esc_html_e( 'You’re on the list. Thank you.', 'clipto' ); ?></p>
							<p class="newsletter__success-desc"><?php esc_html_e( 'If a confirmation email arrives first, open it to finish signing up. Then look out for the next issue in your inbox.', 'clipto' ); ?></p>
						</div>
					</div>

				<?php else : ?>

					<?php if ( '' !== $clipto_nl['description'] ) : ?>
						<p class="newsletter__desc"><?php echo esc_html( $clipto_nl['description'] ); ?></p>
					<?php endif; ?>

					<?php if ( 'shortcode' === $clipto_nl['mode'] ) : ?>

						<div class="newsletter__embed">
							<?php echo do_shortcode( $clipto_nl['shortcode'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugin output, escaped by the plugin. ?>
						</div>

					<?php else : ?>

						<form
							class="newsletter__form"
							action="<?php echo esc_url( $clipto_nl['action'] ); ?>"
							method="post"
							data-newsletter-form
							data-msg-empty="<?php esc_attr_e( 'Enter your email address to subscribe.', 'clipto' ); ?>"
							data-msg-invalid="<?php esc_attr_e( 'That doesn’t look like an email address — check it reads like name@example.com.', 'clipto' ); ?>"
							data-msg-submitting="<?php esc_attr_e( 'Subscribing…', 'clipto' ); ?>"
						>
							<label class="newsletter__label" for="newsletter-email"><?php esc_html_e( 'Email address', 'clipto' ); ?></label>
							<div class="newsletter__row">
								<input
									class="field newsletter__input"
									type="email"
									id="newsletter-email"
									name="<?php echo esc_attr( $clipto_nl['field'] ); ?>"
									required
									autocomplete="email"
									autocapitalize="off"
									spellcheck="false"
									inputmode="email"
									enterkeyhint="send"
									placeholder="<?php esc_attr_e( 'name@example.com', 'clipto' ); ?>"
									aria-describedby="newsletter-msg<?php echo $clipto_nl_has_trust ? ' ' . esc_attr( $clipto_nl_trust_id ) : ''; ?>"
								/>
								<button class="btn newsletter__submit" type="submit">
									<span class="btn__label"><?php echo esc_html( $clipto_nl['submit'] ); ?></span>
									<?php clipto_the_icon( 'arrow-right' ); ?>
								</button>
							</div>
							<?php foreach ( $clipto_nl['hidden'] as $clipto_nl_key => $clipto_nl_value ) : ?>
								<input type="hidden" name="<?php echo esc_attr( $clipto_nl_key ); ?>" value="<?php echo esc_attr( $clipto_nl_value ); ?>" />
							<?php endforeach; ?>
							<p class="newsletter__msg" id="newsletter-msg" aria-live="polite" data-newsletter-msg></p>
						</form>

					<?php endif; ?>

					<?php if ( $clipto_nl_has_trust ) : ?>
						<p class="newsletter__trust" id="<?php echo esc_attr( $clipto_nl_trust_id ); ?>">
							<?php
							if ( '' !== $clipto_nl['trust'] ) {
								clipto_the_icon( 'check', array( 'class' => 'newsletter__trust-icon' ) );
							}
							?>
							<span class="newsletter__trust-text">
								<?php if ( '' !== $clipto_nl['trust'] ) : ?>
									<span><?php echo esc_html( $clipto_nl['trust'] ); ?></span>
								<?php endif; ?>
								<?php if ( '' !== $clipto_nl['privacy_url'] ) : ?>
									<a class="link-u" href="<?php echo esc_url( $clipto_nl['privacy_url'] ); ?>"><?php esc_html_e( 'Privacy policy', 'clipto' ); ?></a>
								<?php endif; ?>
							</span>
						</p>
					<?php endif; ?>

				<?php endif; ?>
			</div>

		</div>
	</div>
</section>
