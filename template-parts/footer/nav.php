<?php
/**
 * Footer index: Explore (the site's destinations), AI Tools (its existing subcategories)
 * and a site column (Newsletter anchor, Footer menu, privacy policy). Every link is
 * resolved from live data; a column with nothing real to show is not printed.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'clipto_footer_link' ) ) {
	return;
}

$clipto_fn_cols = array();

/* Explore — the five destinations ----------------------------------------- */
$clipto_fn_items = '';
foreach ( clipto_destinations() as $clipto_fn_dest ) {
	$clipto_fn_obj     = $clipto_fn_dest['object'];
	$clipto_fn_current = false;
	if ( 'page' === $clipto_fn_dest['type'] ) {
		$clipto_fn_current = is_page( $clipto_fn_obj->ID );
	} elseif ( 'tag' === $clipto_fn_dest['type'] ) {
		$clipto_fn_current = is_tag( $clipto_fn_obj->term_id );
	} else {
		$clipto_fn_current = is_category( $clipto_fn_obj->term_id );
	}
	$clipto_fn_items .= clipto_footer_link( $clipto_fn_dest['url'], $clipto_fn_dest['label'], $clipto_fn_dest['count'], $clipto_fn_current );
}
if ( $clipto_fn_items ) {
	$clipto_fn_cols[] = array(
		'key'   => 'explore',
		'title' => __( 'Explore', 'clipto' ),
		'items' => $clipto_fn_items,
		'after' => '',
	);
}

/* AI Tools — existing subcategories ----------------------------------------- */
$clipto_fn_tools = clipto_destination( 'ai-tools' );
$clipto_fn_subs  = $clipto_fn_tools ? clipto_tool_subcategories( 8 ) : array();
if ( $clipto_fn_subs ) {
	$clipto_fn_items = '';
	foreach ( $clipto_fn_subs as $clipto_fn_term ) {
		$clipto_fn_link = get_term_link( $clipto_fn_term );
		if ( is_wp_error( $clipto_fn_link ) ) {
			continue;
		}
		$clipto_fn_items .= clipto_footer_link( $clipto_fn_link, $clipto_fn_term->name, clipto_term_post_count( $clipto_fn_term ), is_category( $clipto_fn_term->term_id ) );
	}
	$clipto_fn_cols[] = array(
		'key'   => 'tools',
		'title' => $clipto_fn_tools['label'],
		'items' => $clipto_fn_items,
		'after' => '<a class="link-arrow site-footer__all" href="' . esc_url( $clipto_fn_tools['url'] ) . '">'
			. esc_html__( 'All AI tools', 'clipto' ) . clipto_icon( 'arrow-right' ) . '</a>',
	);
}

/* Site column — newsletter, Footer menu, privacy, feed ----------------------- */
$clipto_fn_items = '';
if ( function_exists( 'clipto_newsletter_is_ready' ) && clipto_newsletter_is_ready() ) {
	$clipto_fn_items .= clipto_footer_link( '#newsletter', __( 'Newsletter', 'clipto' ) );
}

$clipto_fn_privacy = get_privacy_policy_url();
if ( has_nav_menu( 'footer' ) ) {
	$clipto_fn_menu = wp_nav_menu(
		array(
			'theme_location' => 'footer',
			'container'      => false,
			'items_wrap'     => '%3$s',
			'depth'          => 1,
			'fallback_cb'    => false,
			'echo'           => false,
			'link_before'    => '<span class="site-footer__label">',
			'link_after'     => '</span>',
		)
	);
	if ( $clipto_fn_menu ) {
		$clipto_fn_items .= $clipto_fn_menu;
	}
	// Do not repeat the privacy policy if the menu already links it.
	$clipto_fn_locations = get_nav_menu_locations();
	$clipto_fn_menu_obj  = ! empty( $clipto_fn_locations['footer'] ) ? wp_get_nav_menu_items( $clipto_fn_locations['footer'] ) : array();
	foreach ( (array) $clipto_fn_menu_obj as $clipto_fn_mi ) {
		if ( $clipto_fn_privacy && untrailingslashit( $clipto_fn_mi->url ) === untrailingslashit( $clipto_fn_privacy ) ) {
			$clipto_fn_privacy = '';
		}
	}
}
if ( $clipto_fn_privacy ) {
	$clipto_fn_privacy_title = get_the_title( (int) get_option( 'wp_page_for_privacy_policy' ) );
	$clipto_fn_items        .= clipto_footer_link( $clipto_fn_privacy, $clipto_fn_privacy_title ? $clipto_fn_privacy_title : __( 'Privacy policy', 'clipto' ), null, is_privacy_policy() );
}
if ( $clipto_fn_items ) {
	$clipto_fn_cols[] = array(
		'key'   => 'site',
		'title' => get_bloginfo( 'name' ),
		'items' => $clipto_fn_items,
		'after' => '',
	);
}

if ( ! $clipto_fn_cols ) {
	return;
}
?>
<nav class="site-footer__nav site-footer__nav--<?php echo (int) count( $clipto_fn_cols ); ?>" aria-label="<?php esc_attr_e( 'Footer', 'clipto' ); ?>">
	<?php foreach ( $clipto_fn_cols as $clipto_fn_i => $clipto_fn_col ) : ?>
		<div class="site-footer__col site-footer__col--<?php echo esc_attr( $clipto_fn_col['key'] ); ?>">
			<h2 class="site-footer__heading" id="footer-<?php echo esc_attr( $clipto_fn_col['key'] ); ?>">
				<span class="index-num" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( $clipto_fn_i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
				<span><?php echo esc_html( $clipto_fn_col['title'] ); ?></span>
			</h2>
			<ul class="site-footer__list" role="list" aria-labelledby="footer-<?php echo esc_attr( $clipto_fn_col['key'] ); ?>">
				<?php echo $clipto_fn_col['items']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts / wp_nav_menu. ?>
			</ul>
			<?php echo $clipto_fn_col['after']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
		</div>
	<?php endforeach; ?>
</nav>
