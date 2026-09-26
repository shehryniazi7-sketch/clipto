<?php
/**
 * Author archive: compact editorial header (portrait, name, bio, article count, topics,
 * website) and the author's articles as a row list.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

require_once get_theme_file_path( 'template-parts/archive/helpers.php' );

get_header();

global $wp_query;

$clipto_author = get_queried_object();
$clipto_id     = $clipto_author instanceof WP_User ? (int) $clipto_author->ID : (int) get_query_var( 'author' );
$clipto_name   = get_the_author_meta( 'display_name', $clipto_id );
$clipto_bio    = get_the_author_meta( 'description', $clipto_id );
$clipto_site   = esc_url( get_the_author_meta( 'user_url', $clipto_id ) );
$clipto_count  = (int) count_user_posts( $clipto_id, 'post', true );
$clipto_topics = clipto_author_topics( $clipto_id, 4 );

$clipto_first = get_posts(
	array(
		'author'         => $clipto_id,
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'orderby'        => 'date',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	)
);

$clipto_stats = array( clipto_archive_count_label( $clipto_count ) );
if ( $clipto_first ) {
	$clipto_stats[] = array(
		'value' => get_the_date( _x( 'M Y', 'author since date format', 'clipto' ), $clipto_first[0] ),
		'label' => __( 'Writing since', 'clipto' ),
	);
}
$clipto_stats[] = clipto_archive_updated_stat( clipto_archive_latest_post( array( 'author' => $clipto_id ) ) );
$clipto_stats[] = clipto_archive_page_stat();

get_template_part(
	'template-parts/archive/header',
	null,
	array(
		'variant' => 'author',
		'crumb'   => $clipto_name,
		'kicker'  => __( 'Author', 'clipto' ),
		'title'   => $clipto_name,
		'desc'    => $clipto_bio,
		'media'   => '<span class="author-avatar">' . clipto_author_avatar( $clipto_id, 96 ) . '</span>',
		'stats'   => $clipto_stats,
		'after'   => static function () use ( $clipto_topics, $clipto_site, $clipto_name ) {
			if ( ! $clipto_topics && ! $clipto_site ) {
				return;
			}
			?>
			<div class="author-extra">
				<?php if ( $clipto_topics ) : ?>
					<div class="author-extra__topics">
						<span class="author-extra__label"><?php esc_html_e( 'Covers', 'clipto' ); ?></span>
						<ul class="cluster" role="list">
							<?php foreach ( $clipto_topics as $clipto_topic ) : ?>
								<li><a class="chip" href="<?php echo esc_url( get_term_link( $clipto_topic ) ); ?>"><?php echo esc_html( $clipto_topic->name ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
				<?php if ( $clipto_site ) : ?>
					<a class="link-arrow author-extra__site" href="<?php echo esc_url( $clipto_site ); ?>" rel="me noopener">
						<?php esc_html_e( 'Website', 'clipto' ); ?>
						<?php clipto_the_icon( 'arrow-up-right' ); ?>
						<span class="screen-reader-text">
							<?php
							/* translators: %s: author name. */
							echo esc_html( sprintf( __( 'of %s', 'clipto' ), $clipto_name ) );
							?>
						</span>
					</a>
				<?php endif; ?>
			</div>
			<?php
		},
	)
);

get_template_part(
	'template-parts/archive/layout',
	'standard',
	array(
		'lead'       => false,
		/* translators: %s: author name. */
		'list_label' => sprintf( __( 'Articles by %s', 'clipto' ), $clipto_name ),
	)
);

get_footer();
