<?php
/**
 * Page shell — closing half. Ends <main> (opened in header.php), then the site-wide
 * newsletter band, the site footer and wp_footer().
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;
?>
</main>

<?php get_template_part( 'template-parts/newsletter' ); ?>

<?php get_template_part( 'template-parts/footer/site-footer' ); ?>

<?php wp_footer(); ?>
</body>
</html>
