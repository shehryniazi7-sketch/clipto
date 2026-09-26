<?php
/**
 * Posts index — the "Latest" stream. Used for the Posts page when a static front page is
 * set, and for page 2+ of the front page when it shows the latest posts.
 *
 * A calm editorial list: two lead stories on the first page, then rows with excerpts,
 * a rail of the site's real sections, and numbered pagination.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

require_once get_theme_file_path( 'template-parts/home/helpers.php' );

get_header();

$clipto_posts_page = (int) get_option( 'page_for_posts' );
$clipto_is_page    = $clipto_posts_page && ! is_front_page();
$clipto_title      = $clipto_is_page ? get_the_title( $clipto_posts_page ) : '';
$clipto_title      = '' !== trim( (string) $clipto_title ) ? $clipto_title : __( 'Latest stories', 'clipto' );
$clipto_intro      = $clipto_is_page && has_excerpt( $clipto_posts_page ) ? get_the_excerpt( $clipto_posts_page ) : __( 'Every story from Clipto — tools, news, guides and practical ways to earn with AI — newest first.', 'clipto' );
$clipto_paged      = max( 1, (int) get_query_var( 'paged' ) );
$clipto_pages      = (int) $GLOBALS['wp_query']->max_num_pages;
$clipto_rail       = array_filter(
	clipto_destinations(),
	static function ( $d ) {
		return 'page' === $d['type'] || $d['count'];
	}
);
?>
<div class="home-stream">
	<div class="container">

		<header class="home-stream__head">
			<p class="home-stream__eyebrow">
				<span class="kicker"><?php esc_html_e( 'From the desk', 'clipto' ); ?></span>
				<?php if ( $clipto_paged > 1 && $clipto_pages > 1 ) : ?>
					<span class="home-stream__page">
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: current page, 2: total pages. */
								__( 'Page %1$s of %2$s', 'clipto' ),
								number_format_i18n( $clipto_paged ),
								number_format_i18n( $clipto_pages )
							)
						);
						?>
					</span>
				<?php endif; ?>
			</p>
			<h1 class="home-stream__title"><?php echo esc_html( $clipto_title ); ?></h1>
			<?php if ( $clipto_intro ) : ?>
				<p class="home-stream__intro"><?php echo esc_html( $clipto_intro ); ?></p>
			<?php endif; ?>
		</header>

		<div class="home-stream__layout">
			<div class="home-stream__main">
				<?php if ( have_posts() ) : ?>
					<?php
					$clipto_n = 0;
					if ( 1 === $clipto_paged ) :
						?>
						<div class="home-stream__leads">
							<?php
							while ( $clipto_n < 2 && have_posts() ) :
								the_post();
								clipto_card(
									get_post(),
									'standard',
									array(
										'heading' => 'h2',
										'excerpt' => 26,
										'eager'   => 0 === $clipto_n,
										'index'   => $clipto_n,
										'sizes'   => '(min-width: 64em) 30vw, (min-width: 40em) 50vw, 100vw',
									)
								);
								++$clipto_n;
							endwhile;
							?>
						</div>
					<?php endif; ?>

					<?php if ( have_posts() ) : ?>
						<div class="home-stream__list">
							<?php
							while ( have_posts() ) :
								the_post();
								clipto_card(
									get_post(),
									'row',
									array(
										'heading' => 'h2',
										'excerpt' => 26,
										'index'   => $clipto_n % 3,
									)
								);
								++$clipto_n;
							endwhile;
							?>
						</div>
					<?php endif; ?>

					<?php clipto_pagination(); ?>

				<?php else : ?>
					<div class="home-stream__empty">
						<p><?php esc_html_e( 'Nothing has been published here yet. Try a search, or explore one of the sections.', 'clipto' ); ?></p>
						<?php clipto_search_form( array( 'id' => 'home-stream-search' ) ); ?>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( $clipto_rail ) : ?>
				<aside class="home-stream__rail" aria-labelledby="home-stream-rail-title">
					<h2 class="home-stream__rail-title" id="home-stream-rail-title"><?php esc_html_e( 'Browse Clipto', 'clipto' ); ?></h2>
					<ol class="home-stream__sections" role="list">
						<?php
						$clipto_i = 0;
						foreach ( $clipto_rail as $clipto_dest ) :
							++$clipto_i;
							?>
							<li>
								<a class="home-stream__section" href="<?php echo esc_url( $clipto_dest['url'] ); ?>">
									<span class="index-num" aria-hidden="true"><?php echo esc_html( clipto_home_num( $clipto_i ) ); ?></span>
									<span class="home-stream__section-label"><?php echo esc_html( $clipto_dest['label'] ); ?></span>
									<?php if ( null !== $clipto_dest['count'] ) : ?>
										<span class="home-stream__section-count"><?php echo esc_html( number_format_i18n( (int) $clipto_dest['count'] ) ); ?><span class="screen-reader-text"> <?php esc_html_e( 'stories', 'clipto' ); ?></span></span>
									<?php else : ?>
										<span class="home-stream__section-count"><?php esc_html_e( 'Guide', 'clipto' ); ?></span>
									<?php endif; ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ol>
				</aside>
			<?php endif; ?>
		</div>

	</div>
</div>
<?php
get_footer();
