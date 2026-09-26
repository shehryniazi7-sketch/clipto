<?php
/**
 * Title: Verdict
 * Slug: clipto/verdict
 * Categories: clipto
 * Keywords: review, verdict, conclusion, bottom line, score, rating
 * Description: Closing verdict under a 2px ink rule: a one-sentence serif statement, supporting detail and an optional score line (delete it if you don't score).
 * Viewport Width: 760
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"className":"is-style-clipto-verdict"} -->
<div class="wp-block-group is-style-clipto-verdict"><!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Verdict', 'clipto' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Replace with your one-sentence verdict: who should use this tool, and why.', 'clipto' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Replace with two or three sentences that back it up — what stood out in testing, the main trade-off, and when a reader should choose an alternative instead.', 'clipto' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"clipto-score"} -->
<p class="clipto-score"><?php esc_html_e( 'Optional: replace with your score, e.g. out of 10 — or delete this line.', 'clipto' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
