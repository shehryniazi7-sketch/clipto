<?php
/**
 * The template for displaying author archives.
 *
 * @package Clipto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$author_id = get_queried_object_id();
$bio       = get_the_author_meta( 'description', $author_id );
$expertise = clipto_get_author_expertise_label( $author_id );
$count     = count_user_posts( $author_id, 'post', true );
$links     = clipto_get_author_social_links( $author_id );
?>
<div class="clipto-container">
	<?php clipto_breadcrumbs(); ?>

	<header class="clipto-author-header">
		<div class="clipto-author-header__avatar"><?php echo get_avatar( $author_id, 96 ); ?></div>
		<div class="clipto-author-header__body">
			<h1><?php echo esc_html( get_the_author_meta( 'display_name', $author_id ) ); ?></h1>
			<?php if ( $expertise ) : ?>
				<span class="clipto-badge clipto-badge--expertise"><?php echo esc_html( $expertise ); ?></span>
			<?php endif; ?>
			<?php if ( $bio ) : ?>
				<p class="clipto-author-box__bio"><?php echo esc_html( $bio ); ?></p>
			<?php endif; ?>
			<p class="clipto-author-box__stats">
				<?php
				printf(
					/* translators: %d: number of published articles */
					esc_html( _n( '%d article published', '%d articles published', $count, 'clipto' ) ),
					(int) $count
				);
				?>
			</p>
			<?php clipto_render_social_icons( $links ); ?>
		</div>
	</header>

	<?php if ( have_posts() ) : ?>
		<h2 class="clipto-section-title"><?php esc_html_e( 'Articles', 'clipto' ); ?></h2>
		<div class="clipto-grid clipto-grid--posts">
			<?php
			$i = 0;
			while ( have_posts() ) :
				the_post();
				$i++;
				clipto_render_post_card( get_the_ID(), $i );
			endwhile;
			?>
		</div>
		<?php clipto_pagination(); ?>
	<?php else : ?>
		<div class="clipto-empty-state">
			<p><?php esc_html_e( 'This author has not published any articles yet.', 'clipto' ); ?></p>
		</div>
	<?php endif; ?>
</div>
<?php
get_footer();
