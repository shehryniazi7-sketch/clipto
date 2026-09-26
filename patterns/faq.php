<?php
/**
 * Title: FAQ
 * Slug: clipto/faq
 * Categories: clipto
 * Keywords: faq, questions, answers, accordion, details
 * Description: "Frequently asked questions" heading with three expandable questions (native details, keyboard accessible).
 * Viewport Width: 760
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Frequently asked questions', 'clipto' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:details {"className":"is-style-clipto-faq"} -->
<details class="wp-block-details is-style-clipto-faq"><summary><?php esc_html_e( 'Replace with a question readers actually ask — e.g. “Is [tool name] free?”', 'clipto' ); ?></summary><!-- wp:paragraph -->
<p><?php esc_html_e( 'Replace with a direct answer in the first sentence, then one or two sentences of useful detail.', 'clipto' ); ?></p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->

<!-- wp:details {"className":"is-style-clipto-faq"} -->
<details class="wp-block-details is-style-clipto-faq"><summary><?php esc_html_e( 'Replace with a second question — e.g. “Is my data used to train the model?”', 'clipto' ); ?></summary><!-- wp:paragraph -->
<p><?php esc_html_e( 'Replace with the answer. Link to the official policy page when you cite one.', 'clipto' ); ?></p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->

<!-- wp:details {"className":"is-style-clipto-faq"} -->
<details class="wp-block-details is-style-clipto-faq"><summary><?php esc_html_e( 'Replace with a third question — e.g. “What is the best alternative?”', 'clipto' ); ?></summary><!-- wp:paragraph -->
<p><?php esc_html_e( 'Replace with the answer, or delete this question if you only need two.', 'clipto' ); ?></p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->
