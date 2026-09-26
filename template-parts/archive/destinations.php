<?php
/**
 * The site's destinations (only those that exist), in three styles:
 *   index   — numbered list with descriptions and counts (404)
 *   compact — numbered list, smaller (archive aside)
 *   chips   — pill links (search empty state)
 *
 * Args: style, title, title_id, heading (h2|h3), exclude (destination key), kicker.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

$a = wp_parse_args(
	isset( $args ) && is_array( $args ) ? $args : array(),
	array(
		'style'    => 'index',
		'title'    => '',
		'title_id' => '',
		'heading'  => 'h2',
		'exclude'  => '',
		'kicker'   => '',
	)
);

$dests = clipto_destinations();
if ( $a['exclude'] ) {
	unset( $dests[ $a['exclude'] ] );
}
if ( ! $dests ) {
	return;
}

$style   = in_array( $a['style'], array( 'index', 'compact', 'chips' ), true ) ? $a['style'] : 'index';
$heading = in_array( $a['heading'], array( 'h2', 'h3' ), true ) ? $a['heading'] : 'h2';
$all     = array_keys( clipto_destinations() );
?>
<div class="dest dest--<?php echo esc_attr( $style ); ?>">
	<?php if ( $a['title'] ) : ?>
		<<?php echo esc_html( $heading ); ?> class="dest__title"<?php echo $a['title_id'] ? ' id="' . esc_attr( $a['title_id'] ) . '"' : ''; ?>>
			<?php echo esc_html( $a['title'] ); ?>
		</<?php echo esc_html( $heading ); ?>>
	<?php endif; ?>

	<?php if ( 'chips' === $style ) : ?>
		<ul class="dest__chips cluster" role="list">
			<?php foreach ( $dests as $dest ) : ?>
				<li><a class="chip" href="<?php echo esc_url( $dest['url'] ); ?>"><?php echo esc_html( $dest['label'] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<ol class="dest__list" role="list">
			<?php
			foreach ( $dests as $key => $dest ) :
				$num   = clipto_archive_num( array_search( $key, $all, true ) + 1 );
				$blurb = clipto_destination_blurb( $dest );
				?>
				<li class="dest__item">
					<a class="dest__link" href="<?php echo esc_url( $dest['url'] ); ?>">
						<span class="dest__num" aria-hidden="true"><?php echo esc_html( $num ); ?></span>
						<span class="dest__text">
							<span class="dest__label"><span class="headline-link"><?php echo esc_html( $dest['label'] ); ?></span></span>
							<?php if ( $blurb ) : ?>
								<span class="dest__desc"><?php echo esc_html( $blurb ); ?></span>
							<?php endif; ?>
						</span>
						<?php if ( 'index' === $style && null !== $dest['count'] && $dest['count'] > 0 ) : ?>
							<span class="dest__count">
								<?php
								/* translators: %s: number of articles. */
								echo esc_html( sprintf( _n( '%s article', '%s articles', (int) $dest['count'], 'clipto' ), number_format_i18n( (int) $dest['count'] ) ) );
								?>
							</span>
						<?php endif; ?>
						<?php clipto_the_icon( 'arrow-right', array( 'class' => 'dest__arrow' ) ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ol>
	<?php endif; ?>
</div>
