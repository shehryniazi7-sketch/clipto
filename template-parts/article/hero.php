<?php
/**
 * Article featured image: the LCP element (eager + fetchpriority high), 16:9, with the
 * attachment caption. Renders nothing when the post has no featured image — an
 * article never shows a placeholder hero.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

if ( ! has_post_thumbnail() || post_password_required() ) {
	return;
}

$clipto_caption = wp_get_attachment_caption( get_post_thumbnail_id() );
?>
<figure class="article-hero">
	<?php
	echo clipto_media( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in clipto_media().
		null,
		array(
			'size'  => 'clipto-wide',
			'ratio' => '16x9',
			'sizes' => '(min-width: 80em) 60rem, (min-width: 64em) 72vw, 100vw',
			'eager' => true,
			'class' => 'article-hero__frame',
		)
	);
	?>
	<?php if ( $clipto_caption ) : ?>
		<figcaption class="article-hero__caption"><?php echo wp_kses_post( $clipto_caption ); ?></figcaption>
	<?php endif; ?>
</figure>
