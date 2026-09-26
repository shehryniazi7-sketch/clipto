<?php
/**
 * Reading progress bar (fixed under the sticky header). Driven by a CSS scroll-driven
 * animation where supported (view timeline of .entry-content), otherwise by
 * src/js/modules/progress.js. Must be rendered inside the <article> so the named
 * timeline is in scope.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="reading-progress" data-reading-progress aria-hidden="true"><span class="reading-progress__bar"></span></div>
