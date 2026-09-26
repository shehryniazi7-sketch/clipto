<?php
/**
 * Empty state for archives and search: a short, friendly message, the site's real
 * destinations as chips, and the latest stories.
 *
 * Args: title, message, hint, show_latest (bool, default true). With no title and no
 * message (the page header already explains), only the section chips and the latest
 * stories are shown.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

$a = wp_parse_args(
	isset( $args ) && is_array( $args ) ? $args : array(),
	array(
		'title'       => __( 'Nothing published here yet', 'clipto' ),
		'message'     => __( 'This section is waiting for its first story. In the meantime, these are good places to start.', 'clipto' ),
		'hint'        => '',
		'show_latest' => true,
	)
);
?>
<div class="archive-body archive-body--empty container">
	<?php if ( $a['title'] || $a['message'] ) : ?>
		<section class="archive-empty"<?php echo $a['title'] ? ' aria-labelledby="archive-empty-title"' : ''; ?>>
			<div class="archive-empty__main">
				<?php if ( $a['title'] ) : ?>
					<h2 class="archive-empty__title" id="archive-empty-title"><?php echo esc_html( $a['title'] ); ?></h2>
				<?php endif; ?>
				<?php if ( $a['message'] ) : ?>
					<p class="archive-empty__message"><?php echo esc_html( $a['message'] ); ?></p>
				<?php endif; ?>
				<?php if ( $a['hint'] ) : ?>
					<p class="archive-empty__hint"><?php echo esc_html( $a['hint'] ); ?></p>
				<?php endif; ?>
			</div>
			<?php
			get_template_part(
				'template-parts/archive/destinations',
				null,
				array(
					'style'   => 'chips',
					'title'   => __( 'Try a section', 'clipto' ),
					'heading' => 'h3',
				)
			);
			?>
		</section>
	<?php else : ?>
		<?php
		// The page title already explains the situation: just the ways onward.
		get_template_part(
			'template-parts/archive/destinations',
			null,
			array(
				'style'   => 'chips',
				'title'   => __( 'Browse a section', 'clipto' ),
				'heading' => 'h2',
				'class'   => 'archive-empty__browse',
			)
		);
		?>
	<?php endif; ?>

	<?php
	if ( $a['show_latest'] ) {
		get_template_part( 'template-parts/archive/latest' );
	}
	?>
</div>
