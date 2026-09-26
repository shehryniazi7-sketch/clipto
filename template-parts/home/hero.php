<?php
/**
 * Homepage hero: headline, search and the main destinations (left); "The Index" of AI Tools
 * use cases with proportional bars drawn from real post counts (right); a "Top stories"
 * strip underneath for immediate content density.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

$clipto_tools = clipto_destination( 'ai-tools' );

/* The Index: AI Tools subcategories by size, or the main destinations as a fallback. */
$clipto_rows    = array();
$clipto_by_tool = false;
foreach ( clipto_tool_subcategories( 7 ) as $clipto_term ) {
	$clipto_link = get_term_link( $clipto_term );
	if ( is_wp_error( $clipto_link ) ) {
		continue;
	}
	$clipto_rows[]  = array(
		'label' => $clipto_term->name,
		'url'   => $clipto_link,
		'count' => clipto_term_post_count( $clipto_term ),
	);
	$clipto_by_tool = true;
}
if ( ! $clipto_rows ) {
	foreach ( clipto_destinations() as $clipto_dest ) {
		if ( null === $clipto_dest['count'] || ! $clipto_dest['count'] ) {
			continue;
		}
		$clipto_rows[] = array(
			'label' => $clipto_dest['label'],
			'url'   => $clipto_dest['url'],
			'count' => (int) $clipto_dest['count'],
		);
	}
	usort(
		$clipto_rows,
		static function ( $a, $b ) {
			return $b['count'] - $a['count'];
		}
	);
}
$clipto_max = $clipto_rows ? max( 1, max( wp_list_pluck( $clipto_rows, 'count' ) ) ) : 1;

/* Main destinations for the quick links under the search field. */
$clipto_quick = array();
foreach ( array( 'ai-tools', 'ai-news', 'ai-guide', 'earn-with-ai' ) as $clipto_key ) {
	$clipto_dest = clipto_destination( $clipto_key );
	if ( ! $clipto_dest ) {
		continue;
	}
	if ( 'page' === $clipto_dest['type'] ) {
		$clipto_minutes = clipto_reading_time( $clipto_dest['object'] );
		/* translators: %d: minutes. */
		$clipto_meta = sprintf( _n( '%d min read', '%d min read', $clipto_minutes, 'clipto' ), $clipto_minutes );
	} else {
		$clipto_meta = clipto_home_count_label( (int) $clipto_dest['count'] );
	}
	$clipto_quick[] = array(
		'label' => $clipto_dest['label'],
		'url'   => $clipto_dest['url'],
		'meta'  => $clipto_meta,
	);
}

/* Top stories: sticky posts first, topped up with the latest. */
$clipto_top    = array();
$clipto_sticky = array_filter( array_map( 'intval', (array) get_option( 'sticky_posts', array() ) ) );
if ( $clipto_sticky ) {
	$clipto_top = clipto_posts(
		array(
			'post__in'       => $clipto_sticky,
			'posts_per_page' => 4,
		)
	);
}
if ( count( $clipto_top ) < 4 ) {
	$clipto_top = array_merge( $clipto_top, clipto_posts( array( 'posts_per_page' => 4 - count( $clipto_top ) ) ) );
}
?>
<section class="home-hero" aria-labelledby="home-hero-title">
	<div class="container">
		<div class="home-hero__grid grid-12<?php echo $clipto_rows ? '' : ' home-hero__grid--solo'; ?>">

			<div class="home-hero__main">
				<p class="home-hero__kicker">
					<span class="kicker"><?php esc_html_e( 'The field guide to AI', 'clipto' ); ?></span>
					<time class="home-hero__date" datetime="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>"><?php echo esc_html( wp_date( _x( 'l, F j, Y', 'homepage dateline format', 'clipto' ) ) ); ?></time>
				</p>

				<h1 class="home-hero__title" id="home-hero-title">
					<?php
					printf(
						/* translators: %s: the emphasised word "right". */
						esc_html__( 'Find the %s AI tool for the job.', 'clipto' ),
						'<em>' . esc_html__( 'right', 'clipto' ) . '</em>'
					);
					?>
				</h1>

				<p class="home-hero__lede"><?php esc_html_e( 'Independent reviews, practical guides and the AI news worth your time — organised so you can choose the right tool and put it to work.', 'clipto' ); ?></p>

				<div class="home-hero__search">
					<?php
					clipto_search_form(
						array(
							'id'          => 'home-hero-search',
							'class'       => 'search-form--hero',
							'placeholder' => __( 'Search tools, guides and news', 'clipto' ),
						)
					);
					?>
				</div>

				<?php if ( $clipto_quick ) : ?>
					<nav class="home-hero__dest" aria-label="<?php esc_attr_e( 'Explore Clipto', 'clipto' ); ?>">
						<ul class="home-dest" role="list">
							<?php foreach ( $clipto_quick as $clipto_i => $clipto_item ) : ?>
								<li class="home-dest__item">
									<a class="home-dest__link" href="<?php echo esc_url( $clipto_item['url'] ); ?>">
										<span class="home-dest__num" aria-hidden="true"><?php echo esc_html( clipto_home_num( $clipto_i + 1 ) ); ?></span>
										<span class="home-dest__label"><?php echo esc_html( $clipto_item['label'] ); ?></span>
										<span class="home-dest__meta"><?php echo esc_html( $clipto_item['meta'] ); ?></span>
										<?php clipto_the_icon( 'arrow-up-right', array( 'class' => 'home-dest__arrow' ) ); ?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</nav>
				<?php endif; ?>
			</div>

			<?php if ( $clipto_rows ) : ?>
				<section class="home-hero__index hero-index" aria-labelledby="home-index-title">
					<header class="hero-index__head">
						<p class="hero-index__eyebrow"><span class="kicker"><?php esc_html_e( 'The Index', 'clipto' ); ?></span></p>
						<h2 class="hero-index__title" id="home-index-title">
							<?php
							echo $clipto_by_tool
								? esc_html__( 'Browse tools by use case', 'clipto' )
								: esc_html__( 'Browse Clipto by section', 'clipto' );
							?>
						</h2>
					</header>

					<div class="hero-index__cols" aria-hidden="true">
						<span><?php esc_html_e( 'No.', 'clipto' ); ?></span>
						<span><?php echo $clipto_by_tool ? esc_html__( 'Use case', 'clipto' ) : esc_html__( 'Section', 'clipto' ); ?></span>
						<span><?php esc_html_e( 'Stories', 'clipto' ); ?></span>
					</div>

					<ol class="hero-index__list" role="list">
						<?php foreach ( $clipto_rows as $clipto_i => $clipto_row ) : ?>
							<li class="hero-index__item" style="--i:<?php echo (int) $clipto_i; ?>">
								<a class="hero-index__link" href="<?php echo esc_url( $clipto_row['url'] ); ?>">
									<span class="hero-index__num" aria-hidden="true"><?php echo esc_html( clipto_home_num( $clipto_i + 1 ) ); ?></span>
									<span class="hero-index__name"><?php echo esc_html( $clipto_row['label'] ); ?></span>
									<span class="hero-index__count"><?php echo esc_html( number_format_i18n( $clipto_row['count'] ) ); ?><span class="screen-reader-text"> <?php echo esc_html( _n( 'story', 'stories', $clipto_row['count'], 'clipto' ) ); ?></span></span>
									<?php clipto_the_icon( 'arrow-right', array( 'class' => 'hero-index__arrow' ) ); ?>
									<span class="hero-index__bar" aria-hidden="true" style="--v:<?php echo esc_attr( number_format( $clipto_row['count'] / $clipto_max, 3, '.', '' ) ); ?>"></span>
								</a>
							</li>
						<?php endforeach; ?>
					</ol>

					<?php if ( $clipto_by_tool && $clipto_tools ) : ?>
						<footer class="hero-index__foot">
							<p class="hero-index__total">
								<?php
								echo esc_html(
									sprintf(
										/* translators: 1: number of stories, 2: number of categories. */
										__( '%1$s across %2$s categories', 'clipto' ),
										clipto_home_count_label( (int) $clipto_tools['count'] ),
										number_format_i18n( count( clipto_tool_subcategories() ) )
									)
								);
								?>
							</p>
							<a class="link-arrow hero-index__all" href="<?php echo esc_url( $clipto_tools['url'] ); ?>">
								<?php esc_html_e( 'All categories', 'clipto' ); ?>
								<?php clipto_the_icon( 'arrow-right' ); ?>
							</a>
						</footer>
					<?php endif; ?>
				</section>
			<?php endif; ?>

		</div>

		<?php if ( $clipto_top ) : ?>
			<section class="home-hero__top hero-top" aria-labelledby="home-top-title">
				<h2 class="hero-top__title" id="home-top-title"><?php esc_html_e( 'Top stories', 'clipto' ); ?></h2>
				<ol class="hero-top__list" role="list">
					<?php foreach ( $clipto_top as $clipto_i => $clipto_post ) : ?>
						<li class="hero-top__item" style="--i:<?php echo (int) $clipto_i; ?>">
							<?php
							clipto_card(
								$clipto_post,
								'numbered',
								array(
									'index'  => $clipto_i + 1,
									'kicker' => false,
									'reveal' => false,
									'meta'   => array( 'reading' => false ),
								)
							);
							?>
						</li>
					<?php endforeach; ?>
				</ol>
			</section>
		<?php endif; ?>
	</div>
</section>
