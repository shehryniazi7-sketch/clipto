<?php
/**
 * Table of contents.
 *
 * Args:
 *   toc      Flat TOC from clipto_get_toc().
 *   variant  'rail'       — sticky desktop reading rail with moving indicator (≥64em)
 *            'disclosure' — collapsible <details> "In this article" (<64em)
 *
 * Hooks for toc.js: [data-toc] nav, [data-toc-link] links, [data-toc-indicator],
 * [data-toc-details] disclosure (closes after a link is followed).
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

$clipto_args = wp_parse_args(
	isset( $args ) ? $args : array(),
	array(
		'toc'     => array(),
		'variant' => 'rail',
	)
);

$clipto_tree = clipto_toc_tree( $clipto_args['toc'] );
if ( count( $clipto_args['toc'] ) < clipto_toc_min_headings() || ! $clipto_tree ) {
	return;
}

$clipto_sections = count( $clipto_tree );

if ( 'disclosure' === $clipto_args['variant'] ) :
	?>
	<details class="toc-disclosure" data-toc-details data-animate-open>
		<summary class="toc-disclosure__summary">
			<?php clipto_the_icon( 'list', array( 'class' => 'toc-disclosure__icon' ) ); ?>
			<span class="toc-disclosure__label"><?php esc_html_e( 'In this article', 'clipto' ); ?></span>
			<span class="toc-disclosure__count">
				<?php
				/* translators: %d: number of sections. */
				echo esc_html( sprintf( _n( '%d section', '%d sections', $clipto_sections, 'clipto' ), $clipto_sections ) );
				?>
			</span>
			<?php clipto_the_icon( 'chevron-down', array( 'class' => 'toc-disclosure__chevron' ) ); ?>
		</summary>
		<nav class="toc toc--inline" aria-label="<?php esc_attr_e( 'In this article', 'clipto' ); ?>" data-toc>
			<?php clipto_toc_render_list( $clipto_tree ); ?>
		</nav>
	</details>
	<?php
else :
	$clipto_label_id = 'toc-rail-label-' . get_the_ID();
	?>
	<nav class="toc toc--rail" aria-labelledby="<?php echo esc_attr( $clipto_label_id ); ?>" data-toc>
		<p class="toc__heading" id="<?php echo esc_attr( $clipto_label_id ); ?>">
			<span><?php esc_html_e( 'Contents', 'clipto' ); ?></span>
			<span class="toc__count" aria-hidden="true"><?php echo esc_html( str_pad( (string) $clipto_sections, 2, '0', STR_PAD_LEFT ) ); ?></span>
		</p>
		<div class="toc__track">
			<span class="toc__indicator" data-toc-indicator aria-hidden="true"></span>
			<?php clipto_toc_render_list( $clipto_tree ); ?>
		</div>
	</nav>
	<?php
endif;
