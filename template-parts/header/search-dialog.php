<?php
/**
 * Search overlay: a native modal <dialog> top sheet. Opened by [data-search-open]
 * and by "/" or Ctrl/Cmd+K (src/js/modules/search.js). Without JavaScript the
 * header shows a plain link to the search results page instead.
 *
 * "Browse" suggestions are real destinations and existing AI Tools subcategories.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

$clipto_destinations = clipto_destinations();
$clipto_subcats      = clipto_tool_subcategories( 8 );
?>
<dialog class="search-dialog" id="search-dialog" aria-labelledby="search-dialog-label">
	<div class="search-dialog__sheet">
		<div class="container search-dialog__inner">

			<div class="search-dialog__top">
				<p class="search-dialog__eyebrow">
					<?php echo clipto_logo_mark( 'search-dialog__mark' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup. ?>
					<label class="search-dialog__title" id="search-dialog-label" for="search-dialog-input"><?php esc_html_e( 'Search Clipto', 'clipto' ); ?></label>
				</p>
				<button type="button" class="search-dialog__close" data-dialog-close>
					<kbd class="search-dialog__esc" aria-hidden="true"><?php esc_html_e( 'Esc', 'clipto' ); ?></kbd>
					<?php clipto_the_icon( 'close' ); ?>
					<span class="screen-reader-text"><?php esc_html_e( 'Close search', 'clipto' ); ?></span>
				</button>
			</div>

			<form role="search" method="get" class="search-dialog__form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php clipto_the_icon( 'search', array( 'class' => 'search-dialog__icon' ) ); ?>
				<input class="search-dialog__input" type="search" id="search-dialog-input" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search tools, guides & news', 'clipto' ); ?>" autocomplete="off" autocapitalize="off" spellcheck="false" enterkeyhint="search" autofocus />
				<button class="btn search-dialog__submit" type="submit">
					<span class="search-dialog__submit-label"><?php esc_html_e( 'Search', 'clipto' ); ?></span>
					<?php clipto_the_icon( 'arrow-right' ); ?>
				</button>
			</form>

			<?php if ( $clipto_destinations || $clipto_subcats ) : ?>
				<div class="search-dialog__browse">
					<?php if ( $clipto_destinations ) : ?>
						<div class="search-dialog__group">
							<p class="search-dialog__label" id="search-browse-sections"><?php esc_html_e( 'Browse sections', 'clipto' ); ?></p>
							<ul class="search-dialog__chips scroller" role="list" aria-labelledby="search-browse-sections">
								<?php foreach ( $clipto_destinations as $clipto_key => $clipto_dest ) : ?>
									<li>
										<a class="chip" href="<?php echo esc_url( $clipto_dest['url'] ); ?>">
											<?php echo esc_html( $clipto_dest['label'] ); ?>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>

					<?php if ( $clipto_subcats ) : ?>
						<div class="search-dialog__group">
							<p class="search-dialog__label" id="search-browse-tools"><?php esc_html_e( 'AI tools by category', 'clipto' ); ?></p>
							<ul class="search-dialog__chips scroller" role="list" aria-labelledby="search-browse-tools">
								<?php foreach ( $clipto_subcats as $clipto_term ) : ?>
									<li>
										<a class="chip" href="<?php echo esc_url( get_category_link( $clipto_term ) ); ?>">
											<?php echo esc_html( $clipto_term->name ); ?>
											<span class="chip__count"><?php echo esc_html( number_format_i18n( clipto_term_post_count( $clipto_term ) ) ); ?></span>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

		</div>
	</div>
</dialog>
