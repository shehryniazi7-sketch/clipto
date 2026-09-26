<?php
/**
 * Title: Tool review — Overview
 * Slug: clipto/tool-overview
 * Categories: clipto
 * Keywords: review, overview, tool, at a glance, summary, facts
 * Description: Opening section of an AI tool review: a heading, a short factual overview and an "At a glance" fact list.
 * Viewport Width: 760
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'What is [tool name]?', 'clipto' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Replace with a two- or three-sentence overview: what the tool does, who makes it and the problem it solves. Stick to what you have verified yourself or on the official website.', 'clipto' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:list {"className":"is-style-clipto-glance"} -->
<ul class="wp-block-list is-style-clipto-glance"><!-- wp:list-item -->
<li><strong><?php esc_html_e( 'Pricing', 'clipto' ); ?></strong> <?php esc_html_e( 'Replace with the pricing model: Free, Freemium, Free trial or Paid', 'clipto' ); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><strong><?php esc_html_e( 'Starting price', 'clipto' ); ?></strong> <?php esc_html_e( 'Replace with the lowest paid plan and its billing period', 'clipto' ); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><strong><?php esc_html_e( 'Best for', 'clipto' ); ?></strong> <?php esc_html_e( 'Replace with the main audience or use case', 'clipto' ); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><strong><?php esc_html_e( 'Platforms', 'clipto' ); ?></strong> <?php esc_html_e( 'Replace with where it runs, e.g. Web, iOS, Android, API', 'clipto' ); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><strong><?php esc_html_e( 'Last checked', 'clipto' ); ?></strong> <?php esc_html_e( 'Replace with the date you verified these details', 'clipto' ); ?></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->
