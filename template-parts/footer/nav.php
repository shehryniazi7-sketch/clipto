<?php
/**
 * Footer index: Explore (the site's destinations and the newsletter) and AI Tools (its
 * existing subcategories, set in two columns when there are more than five). Every link
 * is resolved from live data; a column with nothing real to show is not printed.
 * Utility links (Footer menu, privacy policy) sit in the colophon bar (site-footer.php).
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'clipto_footer_link' ) ) {
	return;
}

$clipto_fn_cols = array();

/* Explore — the five destinations, then the newsletter band ------------------- */
$clipto_fn_items = '';
foreach ( clipto_destinations() as $clipto_fn_dest ) {
	$clipto_fn_obj = $clipto_fn_dest['object'];
	if ( 'page' === $clipto_fn_dest['type'] ) {
		$clipto_fn_current = is_page( $clipto_fn_obj->ID );
	} elseif ( 'tag' === $clipto_fn_dest['type'] ) {
		$clipto_fn_current = is_tag( $clipto_fn_obj->term_id );
	} else {
		$clipto_fn_current = is_category( $clipto_fn_obj->term_id );
	}
	$clipto_fn_items .= clipto_footer_link( $clipto_fn_dest['url'], $clipto_fn_dest['label'], $clipto_fn_dest['count'], $clipto_fn_current );
}
if ( function_exists( 'clipto_newsletter_is_ready' ) && clipto_newsletter_is_ready() ) {
	$clipto_fn_items .= clipto_footer_link( '#newsletter', __( 'Newsletter', 'clipto' ) );
}
if ( $clipto_fn_items ) {
	$clipto_fn_cols[] = array(
		'key'   => 'explore',
		'title' => __( 'Explore', 'clipto' ),
		'items' => $clipto_fn_items,
		'split' => false,
		'after' => '',
	);
}

/* AI Tools — existing subcategories ------------------------------------------- */
$clipto_fn_tools = clipto_destination( 'ai-tools' );
$clipto_fn_subs  = $clipto_fn_tools ? clipto_tool_subcategories( 10 ) : array();
$clipto_fn_items = '';
foreach ( $clipto_fn_subs as $clipto_fn_term ) {
	$clipto_fn_items .= clipto_footer_link( get_category_link( $clipto_fn_term ), $clipto_fn_term->name, clipto_term_post_count( $clipto_fn_term ), is_category( $clipto_fn_term->term_id ) );
}
if ( $clipto_fn_items ) {
	$clipto_fn_cols[] = array(
		'key'   => 'tools',
		'title' => $clipto_fn_tools['label'],
		'items' => $clipto_fn_items,
		'split' => count( $clipto_fn_subs ) > 5,
		'after' => '<a class="link-arrow site-footer__all" href="' . esc_url( $clipto_fn_tools['url'] ) . '">'
			. esc_html__( 'All AI tools', 'clipto' ) . clipto_icon( 'arrow-right' ) . '</a>',
	);
}

if ( ! $clipto_fn_cols ) {
	return;
}

// Grid tracks used at tablet/desktop widths: a split column spans two.
$clipto_fn_tracks = 0;
foreach ( $clipto_fn_cols as $clipto_fn_col ) {
	$clipto_fn_tracks += $clipto_fn_col['split'] ? 2 : 1;
}
?>
<nav class="site-footer__nav site-footer__nav--<?php echo (int) $clipto_fn_tracks; ?>" aria-label="<?php esc_attr_e( 'Footer', 'clipto' ); ?>">
	<?php foreach ( $clipto_fn_cols as $clipto_fn_i => $clipto_fn_col ) : ?>
		<div class="site-footer__col site-footer__col--<?php echo esc_attr( $clipto_fn_col['key'] ); ?><?php echo $clipto_fn_col['split'] ? ' site-footer__col--split' : ''; ?>">
			<h2 class="site-footer__heading" id="footer-<?php echo esc_attr( $clipto_fn_col['key'] ); ?>">
				<span class="index-num" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( $clipto_fn_i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
				<span><?php echo esc_html( $clipto_fn_col['title'] ); ?></span>
			</h2>
			<ul class="site-footer__list" role="list" aria-labelledby="footer-<?php echo esc_attr( $clipto_fn_col['key'] ); ?>">
				<?php echo $clipto_fn_col['items']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts. ?>
			</ul>
			<?php echo $clipto_fn_col['after']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
		</div>
	<?php endforeach; ?>
</nav>
