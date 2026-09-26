<?php
/**
 * "At a glance" tool facts for AI tool reviews. Renders only the facts that are set;
 * nothing at all when the post has none. Rating appears only when the editor filled it in.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

$clipto_facts = clipto_tool_facts();
if ( ! $clipto_facts ) {
	return;
}

$clipto_labels  = clipto_pricing_labels();
$clipto_logo    = clipto_tool_logo( null, 'tool-facts__logo' );
$clipto_heading = 'tool-facts-title-' . get_the_ID();
$clipto_rows    = array();

if ( isset( $clipto_facts['pricing'], $clipto_labels[ $clipto_facts['pricing'] ] ) ) {
	$clipto_rows[] = array(
		'key'   => 'pricing',
		'label' => __( 'Pricing', 'clipto' ),
		'html'  => '<span class="badge badge--' . esc_attr( $clipto_facts['pricing'] ) . '">' . esc_html( $clipto_labels[ $clipto_facts['pricing'] ] ) . '</span>',
	);
}
if ( ! empty( $clipto_facts['price'] ) ) {
	$clipto_rows[] = array(
		'key'   => 'price',
		'label' => __( 'Price', 'clipto' ),
		'html'  => esc_html( $clipto_facts['price'] ),
	);
}
if ( ! empty( $clipto_facts['best_for'] ) ) {
	$clipto_rows[] = array(
		'key'   => 'best-for',
		'label' => __( 'Best for', 'clipto' ),
		'html'  => esc_html( $clipto_facts['best_for'] ),
	);
}
if ( ! empty( $clipto_facts['platforms'] ) ) {
	$clipto_rows[] = array(
		'key'   => 'platforms',
		'label' => __( 'Platforms', 'clipto' ),
		'html'  => esc_html( $clipto_facts['platforms'] ),
	);
}
if ( ! empty( $clipto_facts['rating'] ) ) {
	$clipto_rating = (float) $clipto_facts['rating'];
	$clipto_stars  = str_repeat( clipto_icon( 'star', array( 'class' => 'stars__star' ) ), 5 );
	$clipto_rows[] = array(
		'key'   => 'rating',
		'label' => __( 'Our rating', 'clipto' ),
		'html'  => '<span class="stars" style="--rating:' . esc_attr( number_format( $clipto_rating, 1, '.', '' ) ) . '" aria-hidden="true">'
			. '<span class="stars__base">' . $clipto_stars . '</span>'
			. '<span class="stars__fill">' . $clipto_stars . '</span></span>'
			. '<span class="stars__value">' . esc_html( number_format_i18n( $clipto_rating, 1 ) ) . '<span class="stars__max">/5</span></span>'
			/* translators: %s: rating, e.g. 4.5 */
			. '<span class="screen-reader-text">' . esc_html( sprintf( __( 'Rated %s out of 5', 'clipto' ), number_format_i18n( $clipto_rating, 1 ) ) ) . '</span>',
	);
}
if ( ! empty( $clipto_facts['website'] ) ) {
	$clipto_url  = esc_url( $clipto_facts['website'] );
	$clipto_host = wp_parse_url( $clipto_facts['website'], PHP_URL_HOST );
	$clipto_host = $clipto_host ? preg_replace( '/^www\./i', '', $clipto_host ) : __( 'Visit website', 'clipto' );
	if ( $clipto_url ) {
		$clipto_rows[] = array(
			'key'   => 'website',
			'label' => __( 'Website', 'clipto' ),
			'html'  => '<a class="tool-facts__site" href="' . $clipto_url . '" target="_blank" rel="nofollow noopener">'
				. '<span class="link-u">' . esc_html( $clipto_host ) . '</span>'
				. clipto_icon( 'arrow-up-right', array( 'class' => 'icon--sm' ) )
				. '<span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'clipto' ) . '</span></a>',
		);
	}
}

if ( ! $clipto_rows && empty( $clipto_facts['verdict'] ) ) {
	return;
}
?>
<section class="tool-facts" aria-labelledby="<?php echo esc_attr( $clipto_heading ); ?>">
	<header class="tool-facts__head">
		<?php echo $clipto_logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in clipto_tool_logo(). ?>
		<h2 class="tool-facts__title" id="<?php echo esc_attr( $clipto_heading ); ?>">
			<span class="kicker"><?php esc_html_e( 'At a glance', 'clipto' ); ?></span>
		</h2>
	</header>

	<?php if ( $clipto_rows ) : ?>
		<dl class="tool-facts__list">
			<?php foreach ( $clipto_rows as $clipto_row ) : ?>
				<div class="tool-facts__row tool-facts__row--<?php echo esc_attr( $clipto_row['key'] ); ?>">
					<dt class="tool-facts__label"><?php echo esc_html( $clipto_row['label'] ); ?></dt>
					<dd class="tool-facts__value"><?php echo $clipto_row['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped when built above. ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>
	<?php endif; ?>

	<?php if ( ! empty( $clipto_facts['verdict'] ) ) : ?>
		<div class="tool-facts__verdict">
			<p class="tool-facts__verdict-label"><?php esc_html_e( 'Verdict', 'clipto' ); ?></p>
			<p class="tool-facts__verdict-text"><?php echo esc_html( $clipto_facts['verdict'] ); ?></p>
		</div>
	<?php endif; ?>
</section>
