<?php
/**
 * Title: Pull quote
 * Slug: clipto/pull-quote
 * Categories: clipto
 * Keywords: quote, pull quote, highlight, blockquote, citation
 * Description: A large serif italic quote framed by vermilion crop marks. Use once or twice in a long article.
 * Viewport Width: 760
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:quote {"className":"is-style-clipto-pull"} -->
<blockquote class="wp-block-quote is-style-clipto-pull"><!-- wp:paragraph -->
<p><?php esc_html_e( 'Replace with the most quotable line from the article or an interview — ideally under thirty words.', 'clipto' ); ?></p>
<!-- /wp:paragraph --><cite><?php esc_html_e( 'Replace with the speaker’s name and role, or delete', 'clipto' ); ?></cite></blockquote>
<!-- /wp:quote -->
