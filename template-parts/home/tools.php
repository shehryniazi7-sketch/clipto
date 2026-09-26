<?php
/**
 * AI Tools: section head, live subcategory chips, one featured tool and four supporting
 * tool cards (drawn from different use cases where possible). Skipped when the AI Tools
 * category does not exist or has no posts left to show.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

$clipto_tools = clipto_destination( 'ai-tools' );
if ( ! $clipto_tools ) {
	return;
}
$clipto_cat = (int) $clipto_tools['object']->term_id;

/* Featured tool: a sticky AI Tools post if there is one, otherwise the latest. */
$clipto_feature = array();
$clipto_sticky  = array_filter( array_map( 'intval', (array) get_option( 'sticky_posts', array() ) ) );
if ( $clipto_sticky ) {
	$clipto_feature = clipto_posts(
		array(
			'cat'            => $clipto_cat,
			'post__in'       => $clipto_sticky,
			'posts_per_page' => 1,
		)
	);
}
if ( ! $clipto_feature ) {
	$clipto_feature = clipto_posts(
		array(
			'cat'            => $clipto_cat,
			'posts_per_page' => 1,
		)
	);
}
if ( ! $clipto_feature ) {
	return;
}
$clipto_feature = $clipto_feature[0];

/* Supporting tools: newest first, one per use case before repeating a use case. */
$clipto_pool  = clipto_posts(
	array(
		'cat'            => $clipto_cat,
		'posts_per_page' => 16,
		'post__not_in'   => clipto_shown(),
	),
	false
);
$clipto_picks = array();
$clipto_seen  = array();
$clipto_fcat  = clipto_primary_category( $clipto_feature );
if ( $clipto_fcat ) {
	$clipto_seen[] = $clipto_fcat->term_id;
}
foreach ( $clipto_pool as $clipto_p ) {
	$clipto_pc = clipto_primary_category( $clipto_p );
	$clipto_id = $clipto_pc ? $clipto_pc->term_id : 0;
	if ( ! in_array( $clipto_id, $clipto_seen, true ) ) {
		$clipto_picks[] = $clipto_p;
		$clipto_seen[]  = $clipto_id;
	}
	if ( 4 === count( $clipto_picks ) ) {
		break;
	}
}
foreach ( $clipto_pool as $clipto_p ) {
	if ( count( $clipto_picks ) >= 4 ) {
		break;
	}
	if ( ! in_array( $clipto_p, $clipto_picks, true ) ) {
		$clipto_picks[] = $clipto_p;
	}
}
if ( $clipto_picks ) {
	clipto_shown( wp_list_pluck( $clipto_picks, 'ID' ) );
}

$clipto_subcats = clipto_tool_subcategories();

/* "At a glance" facts for the featured tool — only the ones the review provides. */
$clipto_facts   = clipto_tool_facts( $clipto_feature );
$clipto_labels  = clipto_pricing_labels();
$clipto_glance  = array();
$clipto_pricing = isset( $clipto_facts['pricing'], $clipto_labels[ $clipto_facts['pricing'] ] ) ? $clipto_labels[ $clipto_facts['pricing'] ] : '';
if ( ! empty( $clipto_facts['price'] ) ) {
	$clipto_pricing = $clipto_pricing ? $clipto_pricing . ' · ' . $clipto_facts['price'] : $clipto_facts['price'];
}
if ( $clipto_pricing ) {
	$clipto_glance[ __( 'Pricing', 'clipto' ) ] = $clipto_pricing;
}
if ( ! empty( $clipto_facts['best_for'] ) ) {
	$clipto_glance[ __( 'Best for', 'clipto' ) ] = $clipto_facts['best_for'];
}
if ( ! empty( $clipto_facts['platforms'] ) ) {
	$clipto_glance[ __( 'Platforms', 'clipto' ) ] = $clipto_facts['platforms'];
}
if ( ! empty( $clipto_facts['rating'] ) ) {
	/* translators: %s: rating out of 5, e.g. 4.5. */
	$clipto_glance[ __( 'Our rating', 'clipto' ) ] = sprintf( __( '%s / 5', 'clipto' ), number_format_i18n( (float) $clipto_facts['rating'], 1 ) );
}
?>
<section class="home-tools section" aria-labelledby="home-tools-title">
	<div class="container">
		<?php
		clipto_section_head(
			array(
				'index'      => clipto_home_index(),
				'kicker'     => $clipto_tools['label'],
				'title'      => __( 'Tools worth <em>knowing</em>', 'clipto' ),
				'desc'       => __( 'Hands-on reviews of the AI tools that matter, organised by the job you need done.', 'clipto' ),
				'url'        => $clipto_tools['url'],
				'link_label' => __( 'All AI tools', 'clipto' ),
				'id'         => 'home-tools-title',
			)
		);
		?>

		<?php if ( $clipto_subcats ) : ?>
			<nav class="home-tools__cats" aria-label="<?php esc_attr_e( 'AI tool categories', 'clipto' ); ?>" data-reveal>
				<ul class="scroller home-tools__chips" role="list">
					<?php foreach ( $clipto_subcats as $clipto_term ) : ?>
						<?php
						$clipto_link = get_term_link( $clipto_term );
						if ( is_wp_error( $clipto_link ) ) {
							continue;
						}
						?>
						<li>
							<a class="chip" href="<?php echo esc_url( $clipto_link ); ?>">
								<?php echo esc_html( $clipto_term->name ); ?>
								<span class="chip__count"><?php echo esc_html( number_format_i18n( clipto_term_post_count( $clipto_term ) ) ); ?><span class="screen-reader-text"> <?php esc_html_e( 'stories', 'clipto' ); ?></span></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>
		<?php endif; ?>

		<div class="home-tools__grid<?php echo $clipto_picks ? '' : ' home-tools__grid--solo'; ?>">
			<div class="home-tools__feature">
				<?php
				clipto_card(
					$clipto_feature,
					'feature',
					array(
						'sizes' => '(min-width: 80em) 640px, (min-width: 48em) 58vw, 100vw',
					)
				);
				?>
				<?php if ( $clipto_glance ) : ?>
					<dl class="home-tools__facts">
						<?php foreach ( $clipto_glance as $clipto_label => $clipto_value ) : ?>
							<div class="home-tools__fact">
								<dt><?php echo esc_html( $clipto_label ); ?></dt>
								<dd><?php echo esc_html( $clipto_value ); ?></dd>
							</div>
						<?php endforeach; ?>
					</dl>
				<?php endif; ?>
			</div>

			<?php if ( $clipto_picks ) : ?>
				<ul class="home-tools__list" role="list">
					<?php foreach ( $clipto_picks as $clipto_i => $clipto_p ) : ?>
						<li><?php clipto_card( $clipto_p, 'tool', array( 'index' => $clipto_i + 1 ) ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</section>
