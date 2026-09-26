<?php
/**
 * Title: Comparison table
 * Slug: clipto/comparison-table
 * Categories: clipto
 * Keywords: comparison, compare, versus, vs, table, features, alternatives
 * Description: Side-by-side comparison with a sticky first column on phones. Type ✓ or ✗ for yes/no marks. The second column is highlighted by the extra class clipto-highlight-2 (change to 3, 4 or 5, or remove it).
 * Viewport Width: 900
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'How it compares', 'clipto' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:table {"hasFixedLayout":false,"className":"is-style-clipto-compare clipto-highlight-2"} -->
<figure class="wp-block-table is-style-clipto-compare clipto-highlight-2"><table><thead><tr><th scope="col"><?php esc_html_e( 'Feature', 'clipto' ); ?></th><th scope="col"><?php esc_html_e( 'Tool reviewed', 'clipto' ); ?></th><th scope="col"><?php esc_html_e( 'Alternative A', 'clipto' ); ?></th><th scope="col"><?php esc_html_e( 'Alternative B', 'clipto' ); ?></th></tr></thead><tbody><tr><th scope="row"><?php esc_html_e( 'Free plan', 'clipto' ); ?></th><td>✓</td><td>✗</td><td>✓</td></tr><tr><th scope="row"><?php esc_html_e( 'Starting price', 'clipto' ); ?></th><td><?php esc_html_e( 'Replace', 'clipto' ); ?></td><td><?php esc_html_e( 'Replace', 'clipto' ); ?></td><td><?php esc_html_e( 'Replace', 'clipto' ); ?></td></tr><tr><th scope="row"><?php esc_html_e( 'Best for', 'clipto' ); ?></th><td><?php esc_html_e( 'Replace', 'clipto' ); ?></td><td><?php esc_html_e( 'Replace', 'clipto' ); ?></td><td><?php esc_html_e( 'Replace', 'clipto' ); ?></td></tr><tr><th scope="row"><?php esc_html_e( 'Platforms', 'clipto' ); ?></th><td><?php esc_html_e( 'Replace', 'clipto' ); ?></td><td><?php esc_html_e( 'Replace', 'clipto' ); ?></td><td><?php esc_html_e( 'Replace', 'clipto' ); ?></td></tr><tr><th scope="row"><?php esc_html_e( 'API access', 'clipto' ); ?></th><td>✓</td><td>✓</td><td>✗</td></tr></tbody></table><figcaption class="wp-element-caption"><?php esc_html_e( 'Example layout — replace the tool names and every cell with details you have verified, and note the date you checked them.', 'clipto' ); ?></figcaption></figure>
<!-- /wp:table -->
