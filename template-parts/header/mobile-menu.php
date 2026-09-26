<?php
/**
 * Mobile menu (< 64em): a native modal <dialog> sheet from the right with the
 * primary destinations, AI Tools subcategories (<details>), search and the theme
 * toggle. Opened by [data-menu-open] (src/js/modules/header.js).
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

$clipto_items   = clipto_primary_nav_items();
$clipto_subcats = clipto_tool_subcategories();
?>
<dialog class="menu-sheet" id="mobile-menu" aria-label="<?php esc_attr_e( 'Menu', 'clipto' ); ?>">
	<div class="menu-sheet__panel">

		<div class="menu-sheet__head">
			<?php clipto_brand( array( 'class' => 'menu-sheet__brand' ) ); ?>
			<button type="button" class="icon-btn menu-sheet__close" data-dialog-close>
				<?php clipto_the_icon( 'close' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( 'Close menu', 'clipto' ); ?></span>
			</button>
		</div>

		<?php if ( $clipto_items ) : ?>
			<nav class="menu-sheet__nav" aria-label="<?php esc_attr_e( 'Primary', 'clipto' ); ?>">
				<ol class="menu-sheet__list" role="list">
					<?php foreach ( $clipto_items as $clipto_i => $clipto_item ) : ?>
						<li class="menu-sheet__item" style="--i:<?php echo (int) $clipto_i; ?>">
							<a class="menu-sheet__link" href="<?php echo esc_url( $clipto_item['url'] ); ?>"<?php echo $clipto_item['current'] ? ' aria-current="' . esc_attr( $clipto_item['current'] ) . '"' : ''; ?><?php echo $clipto_item['target'] ? ' target="' . esc_attr( $clipto_item['target'] ) . '"' : ''; ?><?php echo $clipto_item['rel'] ? ' rel="' . esc_attr( $clipto_item['rel'] ) . '"' : ''; ?>>
								<span class="menu-sheet__num" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( $clipto_i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
								<span class="menu-sheet__label"><?php echo esc_html( $clipto_item['label'] ); ?></span>
								<?php if ( ! empty( $clipto_item['count'] ) ) : ?>
									<span class="menu-sheet__count">
										<?php echo esc_html( number_format_i18n( (int) $clipto_item['count'] ) ); ?>
										<span class="screen-reader-text"><?php echo esc_html( _n( 'article', 'articles', (int) $clipto_item['count'], 'clipto' ) ); ?></span>
									</span>
								<?php endif; ?>
							</a>

							<?php if ( $clipto_item['mega'] && $clipto_subcats ) : ?>
								<details class="menu-sheet__sub">
									<summary class="menu-sheet__sub-toggle">
										<span>
											<?php
											/* translators: %s: number of categories. */
											echo esc_html( sprintf( _n( 'Browse %s category', 'Browse %s categories', count( $clipto_subcats ), 'clipto' ), number_format_i18n( count( $clipto_subcats ) ) ) );
											?>
										</span>
										<?php clipto_the_icon( 'chevron-down', array( 'class' => 'menu-sheet__sub-icon' ) ); ?>
									</summary>
									<ul class="menu-sheet__sub-list" role="list">
										<?php foreach ( $clipto_subcats as $clipto_term ) : ?>
											<li>
												<a class="menu-sheet__sub-link" href="<?php echo esc_url( get_category_link( $clipto_term ) ); ?>"<?php echo is_category( $clipto_term->term_id ) ? ' aria-current="page"' : ''; ?>>
													<span><?php echo esc_html( $clipto_term->name ); ?></span>
													<span class="menu-sheet__sub-count"><?php echo esc_html( number_format_i18n( clipto_term_post_count( $clipto_term ) ) ); ?></span>
												</a>
											</li>
										<?php endforeach; ?>
									</ul>
								</details>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ol>
			</nav>
		<?php endif; ?>

		<div class="menu-sheet__foot">
			<?php
			clipto_search_form(
				array(
					'id'    => 'menu-search-input',
					'class' => 'menu-sheet__search',
				)
			);
			?>
			<button type="button" class="menu-sheet__theme" data-theme-toggle>
				<span class="theme-toggle__icons" aria-hidden="true">
					<?php
					clipto_the_icon( 'moon', array( 'class' => 'theme-toggle__icon theme-toggle__icon--moon' ) );
					clipto_the_icon( 'sun', array( 'class' => 'theme-toggle__icon theme-toggle__icon--sun' ) );
					?>
				</span>
				<span class="menu-sheet__theme-label" data-theme-label><?php esc_html_e( 'Switch colour theme', 'clipto' ); ?></span>
			</button>
		</div>

	</div>
</dialog>
