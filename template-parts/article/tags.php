<?php
/**
 * Post tags as chip links (real tag archives only).
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

$clipto_tags = get_the_tags();
if ( ! $clipto_tags || is_wp_error( $clipto_tags ) ) {
	return;
}
?>
<div class="article-tags">
	<h2 class="article-tags__title"><?php esc_html_e( 'Topics', 'clipto' ); ?></h2>
	<ul class="article-tags__list" role="list">
		<?php foreach ( $clipto_tags as $clipto_tag ) : ?>
			<?php
			$clipto_link = get_tag_link( $clipto_tag );
			if ( ! $clipto_link || is_wp_error( $clipto_link ) ) {
				continue;
			}
			?>
			<li><a class="chip" href="<?php echo esc_url( $clipto_link ); ?>" rel="tag"><?php echo esc_html( $clipto_tag->name ); ?></a></li>
		<?php endforeach; ?>
	</ul>
</div>
