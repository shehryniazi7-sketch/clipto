<?php
/**
 * Free AI tag archive: a compact discovery list — row cards in two columns on
 * desktop, each with its real tool facts (pricing badge, best for, platforms).
 * Main query throughout.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

global $wp_query;
$labels = clipto_pricing_labels();

if ( ! have_posts() ) {
	get_template_part( 'template-parts/archive/empty' );
	return;
}
?>
<div class="archive-body archive-body--free container">
	<section class="free-list" aria-labelledby="free-list-label">
		<h2 class="archive-label" id="free-list-label">
			<span class="archive-label__text"><?php esc_html_e( 'The free list', 'clipto' ); ?></span>
			<span class="archive-label__count"><?php echo esc_html( number_format_i18n( $wp_query->found_posts ) ); ?></span>
		</h2>
		<ul class="free-list__items" role="list">
			<?php
			$i = 0;
			while ( have_posts() ) :
				the_post();
				$post_id = get_the_ID();
				$facts   = clipto_tool_facts( $post_id );
				$pricing = isset( $facts['pricing'], $labels[ $facts['pricing'] ] ) ? $facts['pricing'] : '';
				++$i;
				?>
				<li class="free-item" data-reveal style="--i:<?php echo (int) ( ( ( $i - 1 ) % 4 ) + 1 ); ?>">
					<?php
					clipto_card(
						get_post(),
						'row',
						array(
							'heading' => 'h3',
							'reveal'  => false,
						)
					);
					?>
					<?php if ( $pricing || ! empty( $facts['best_for'] ) || ! empty( $facts['price'] ) ) : ?>
						<div class="free-item__facts">
							<?php if ( $pricing ) : ?>
								<span class="badge badge--<?php echo esc_attr( $pricing ); ?>"><?php echo esc_html( $labels[ $pricing ] ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $facts['price'] ) && 'free' !== $pricing ) : ?>
								<span class="free-item__price"><?php echo esc_html( $facts['price'] ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $facts['best_for'] ) ) : ?>
								<span class="free-item__bestfor"><span class="free-item__key"><?php esc_html_e( 'Best for', 'clipto' ); ?></span> <?php echo esc_html( $facts['best_for'] ); ?></span>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</li>
			<?php endwhile; ?>
		</ul>
	</section>

	<?php clipto_pagination(); ?>
</div>
