<?php
/**
 * Comments: rendered only when comments exist or are open. Uses core HTML5 comment
 * markup (styled in 30-article.css) and the core comment form.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}

$clipto_count = (int) get_comments_number();

if ( ! have_comments() && ! comments_open() ) {
	return;
}
?>
<section id="comments" class="comments" aria-labelledby="comments-title">
	<header class="comments__head">
		<h2 class="comments__title" id="comments-title">
			<?php
			if ( $clipto_count ) {
				/* translators: %s: number of comments. */
				echo esc_html( sprintf( _n( '%s response', '%s responses', $clipto_count, 'clipto' ), number_format_i18n( $clipto_count ) ) );
			} else {
				esc_html_e( 'Join the discussion', 'clipto' );
			}
			?>
		</h2>
		<?php if ( $clipto_count && comments_open() ) : ?>
			<a class="link-arrow comments__jump" href="#respond"><?php esc_html_e( 'Leave a reply', 'clipto' ); ?><?php clipto_the_icon( 'arrow-right' ); ?></a>
		<?php endif; ?>
	</header>

	<?php if ( have_comments() ) : ?>
		<ol class="comments__list" role="list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 40,
					'format'      => 'html5',
				)
			);
			?>
		</ol>

		<?php
		the_comments_navigation(
			array(
				'prev_text' => clipto_icon( 'arrow-left' ) . '<span>' . esc_html__( 'Older comments', 'clipto' ) . '</span>',
				'next_text' => '<span>' . esc_html__( 'Newer comments', 'clipto' ) . '</span>' . clipto_icon( 'arrow-right' ),
				'class'     => 'comments__nav',
			)
		);
		?>
	<?php endif; ?>

	<?php if ( ! comments_open() && $clipto_count ) : ?>
		<p class="comments__closed"><?php esc_html_e( 'Comments are closed for this article.', 'clipto' ); ?></p>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'class_container'      => 'comment-respond comments__respond',
			'class_form'           => 'comment-form comments__form',
			'title_reply'          => $clipto_count ? __( 'Leave a reply', 'clipto' ) : __( 'Share your thoughts', 'clipto' ),
			// With no comments yet, "Join the discussion" already heads the form: keep the
			// form's own title for screen readers only.
			'title_reply_before'   => $clipto_count ? '<h3 id="reply-title" class="comment-reply-title">' : '<h3 id="reply-title" class="comment-reply-title screen-reader-text">',
			'title_reply_after'    => '</h3>',
			'class_submit'         => 'btn submit',
			'submit_button'        => '<button name="%1$s" type="submit" id="%2$s" class="%3$s">%4$s</button>',
			'label_submit'         => __( 'Post comment', 'clipto' ),
			'comment_notes_before' => '<p class="comment-notes">' . esc_html__( 'Your email address will not be published. Required fields are marked *', 'clipto' ) . '</p>',
		)
	);
	?>
</section>
