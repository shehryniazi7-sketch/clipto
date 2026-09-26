<?php
/**
 * AI Tools archive (the category and its descendants): "tool discovery".
 * Page 1: featured tool (the newest reviewed tool on the page, see
 * clipto_archive_promote_tool_feature()) with its "At a glance" facts, then the
 * directory grid of the page's other posts. Later pages: grid only. Main query
 * throughout, so pagination is untouched.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

global $wp_query;
$term    = get_queried_object();
$is_root = $term instanceof WP_Term && ! $term->parent;
$labels  = clipto_pricing_labels();

if ( ! have_posts() ) {
	get_template_part( 'template-parts/archive/empty' );
	return;
}
?>
<div class="archive-body archive-body--tools container">

	<?php
	if ( ! is_paged() ) :
		clipto_archive_promote_tool_feature( $wp_query );
		the_post();
		$feature = get_post();
		$facts   = clipto_tool_facts( $feature );
		?>
		<section class="tools-feature" aria-labelledby="tools-feature-label">
			<h2 class="archive-label" id="tools-feature-label">
				<span class="archive-label__text"><?php esc_html_e( 'Featured tool', 'clipto' ); ?></span>
			</h2>
			<div class="tools-feature__grid">
				<?php
				clipto_card(
					$feature,
					'feature',
					array(
						'heading' => 'h3',
						'eager'   => true,
						'reveal'  => false,
						'excerpt' => 30,
						'sizes'   => '(min-width: 64em) 55vw, 100vw',
						'class'   => 'tools-feature__card',
						'kicker'  => clipto_archive_kicker( $feature ),
					)
				);

				if ( $facts ) :
					$pricing = isset( $facts['pricing'], $labels[ $facts['pricing'] ] ) ? $labels[ $facts['pricing'] ] : '';
					?>
					<aside class="tools-feature__glance" aria-label="<?php esc_attr_e( 'At a glance', 'clipto' ); ?>">
						<p class="tools-feature__glance-title"><?php esc_html_e( 'At a glance', 'clipto' ); ?></p>
						<dl class="glance">
							<?php if ( $pricing || ! empty( $facts['price'] ) ) : ?>
								<div class="glance__row">
									<dt><?php esc_html_e( 'Pricing', 'clipto' ); ?></dt>
									<dd>
										<?php
										echo esc_html( $pricing );
										if ( ! empty( $facts['price'] ) ) {
											echo $pricing ? '<span class="glance__sep" aria-hidden="true"> · </span>' : '';
											echo '<span class="glance__price">' . esc_html( $facts['price'] ) . '</span>';
										}
										?>
									</dd>
								</div>
							<?php endif; ?>
							<?php if ( ! empty( $facts['best_for'] ) ) : ?>
								<div class="glance__row">
									<dt><?php esc_html_e( 'Best for', 'clipto' ); ?></dt>
									<dd><?php echo esc_html( $facts['best_for'] ); ?></dd>
								</div>
							<?php endif; ?>
							<?php if ( ! empty( $facts['platforms'] ) ) : ?>
								<div class="glance__row">
									<dt><?php esc_html_e( 'Platforms', 'clipto' ); ?></dt>
									<dd><?php echo esc_html( $facts['platforms'] ); ?></dd>
								</div>
							<?php endif; ?>
							<?php if ( ! empty( $facts['rating'] ) ) : ?>
								<div class="glance__row">
									<dt><?php esc_html_e( 'Our rating', 'clipto' ); ?></dt>
									<dd class="glance__rating">
										<?php clipto_the_icon( 'star', array( 'class' => 'icon--sm' ) ); ?>
										<span><?php echo esc_html( number_format_i18n( $facts['rating'], 1 ) ); ?></span><span class="glance__of"><?php esc_html_e( '/ 5', 'clipto' ); ?></span>
									</dd>
								</div>
							<?php endif; ?>
						</dl>
						<?php if ( ! empty( $facts['verdict'] ) ) : ?>
							<p class="glance__verdict"><?php echo esc_html( $facts['verdict'] ); ?></p>
						<?php endif; ?>
					</aside>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $wp_query->current_post + 1 < $wp_query->post_count ) : ?>
		<section class="tools-directory" aria-labelledby="tools-directory-label">
			<h2 class="archive-label" id="tools-directory-label">
				<span class="archive-label__text">
					<?php
					if ( $is_root ) {
						is_paged() ? esc_html_e( 'All AI tools', 'clipto' ) : esc_html_e( 'More AI tools', 'clipto' );
					} else {
						/* translators: %s: category name. */
						printf( is_paged() ? esc_html__( 'All tools in %s', 'clipto' ) : esc_html__( 'More in %s', 'clipto' ), esc_html( single_term_title( '', false ) ) );
					}
					?>
				</span>
				<?php if ( is_paged() ) : ?>
					<span class="archive-label__count"><?php echo esc_html( clipto_archive_range_label() ); ?></span>
				<?php endif; ?>
			</h2>
			<div class="tools-grid">
				<?php
				$i = 0;
				while ( have_posts() ) :
					the_post();
					clipto_card(
						get_post(),
						'tool',
						array(
							'heading' => 'h3',
							'index'   => ( $i % 3 ) + 1,
							'kicker'  => clipto_archive_kicker( get_post() ),
						)
					);
					++$i;
				endwhile;
				?>
			</div>
		</section>
	<?php endif; ?>

	<?php clipto_pagination(); ?>
</div>
