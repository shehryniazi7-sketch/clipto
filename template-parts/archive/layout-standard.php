<?php
/**
 * Generic archive (other categories, tags, dates, authors, search, fallback loop):
 * page 1 opens with a feature, then a row list beside an index of the site's
 * destinations. Main query throughout.
 *
 * Args: list_label (string), lead (bool, default true on page 1), heading (h2|h3 for
 * list cards), highlight (string[] search terms), aside (bool, default true).
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

global $wp_query;
$a = wp_parse_args(
	isset( $args ) && is_array( $args ) ? $args : array(),
	array(
		'list_label' => '',
		'lead'       => ! is_paged(),
		'highlight'  => array(),
		'aside'      => true,
		'excerpt'    => 24,
	)
);

if ( ! have_posts() ) {
	get_template_part( 'template-parts/archive/empty' );
	return;
}
?>
<div class="archive-body archive-body--standard container">

	<?php
	if ( $a['lead'] && $wp_query->post_count > 1 ) :
		the_post();
		?>
		<div class="archive-lead">
			<?php
			clipto_card(
				get_post(),
				'feature',
				array(
					'heading' => 'h2',
					'eager'   => true,
					'reveal'  => false,
					'sizes'   => '(min-width: 64em) 55vw, 100vw',
					'class'   => 'archive-lead__card',
				)
			);
			?>
		</div>
	<?php endif; ?>

	<div class="archive-layout<?php echo $a['aside'] ? ' has-aside' : ''; ?>">
		<div class="archive-layout__main">
			<?php if ( $a['list_label'] ) : ?>
				<h2 class="archive-label" id="archive-list-label">
					<span class="archive-label__text"><?php echo esc_html( $a['list_label'] ); ?></span>
				</h2>
			<?php endif; ?>
			<div class="archive-list">
				<?php
				$i = 0;
				while ( have_posts() ) :
					the_post();
					++$i;
					$card_args = array(
						'heading' => $a['list_label'] ? 'h3' : 'h2',
						'excerpt' => $a['excerpt'],
						'index'   => min( $i, 4 ),
					);
					if ( $a['highlight'] ) {
						clipto_card_highlighted( get_post(), 'row', $card_args, $a['highlight'] );
					} else {
						clipto_card( get_post(), 'row', $card_args );
					}
				endwhile;
				?>
			</div>
			<?php clipto_pagination(); ?>
		</div>

		<?php if ( $a['aside'] ) : ?>
			<aside class="archive-layout__aside" aria-labelledby="archive-aside-label">
				<div class="archive-layout__aside-inner">
					<?php
					get_template_part(
						'template-parts/archive/destinations',
						null,
						array(
							'style'    => 'compact',
							'title'    => __( 'Explore Clipto', 'clipto' ),
							'title_id' => 'archive-aside-label',
						)
					);
					?>
				</div>
			</aside>
		<?php endif; ?>
	</div>
</div>
