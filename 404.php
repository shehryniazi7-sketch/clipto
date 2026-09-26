<?php
/**
 * 404: a calm, branded dead end with clear ways back — search, the site's destinations
 * as a numbered index, and the latest stories. The Clipto crop mark appears scaled up
 * with an empty frame: nothing to show here.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

require_once get_theme_file_path( 'template-parts/archive/helpers.php' );

get_header();
?>
<div class="error-404">
	<section class="error-404__hero container" aria-labelledby="error-404-title">
		<div class="error-404__grid">
			<div class="error-404__main">
				<p class="archive-head__eyebrow error-404__eyebrow">
					<span class="index-num"><?php esc_html_e( '404', 'clipto' ); ?></span>
					<span class="kicker"><?php esc_html_e( 'Off the index', 'clipto' ); ?></span>
				</p>
				<h1 class="error-404__title" id="error-404-title"><?php esc_html_e( 'Page not found', 'clipto' ); ?></h1>
				<p class="error-404__lead"><?php esc_html_e( 'The link may be old, or the page may have moved. Search for it, or pick up from one of the sections below.', 'clipto' ); ?></p>
				<?php
				clipto_search_form(
					array(
						'id'          => 'search-404-field',
						'class'       => 'error-404__search',
						'placeholder' => __( 'Search Clipto', 'clipto' ),
					)
				);
				?>
			</div>
			<div class="error-404__art" aria-hidden="true">
				<?php echo clipto_logo_mark( 'error-404__mark' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup. ?>
			</div>
		</div>
	</section>

	<div class="error-404__more container">
		<?php $clipto_has_dest = (bool) clipto_destinations(); ?>
		<?php if ( $clipto_has_dest ) : ?>
		<section class="error-404__dest" aria-labelledby="error-404-dest-title">
			<?php
			clipto_section_head(
				array(
					'index'  => '01',
					'kicker' => __( 'Where to next', 'clipto' ),
					'title'  => __( 'The Clipto <em>index</em>', 'clipto' ),
					'id'     => 'error-404-dest-title',
				)
			);
			get_template_part( 'template-parts/archive/destinations', null, array( 'style' => 'index' ) );
			?>
		</section>
		<?php endif; ?>

		<?php
		get_template_part(
			'template-parts/archive/latest',
			null,
			array(
				'index' => $clipto_has_dest ? '02' : '01',
				'id'    => 'error-404-latest-title',
			)
		);
		?>
	</div>
</div>
<?php
get_footer();
