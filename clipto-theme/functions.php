<?php
/**
 * Clipto Theme functions and definitions.
 *
 * THEME owns layout, design, templates and presentation.
 * PLUGIN (Clipto Core) owns AI Tools, AI Summary editing, and reading-time
 * calculation. This file is written so every template still works if the
 * plugin is deactivated (see clipto_reading_time() and clipto_get_ai_summary()).
 *
 * @package Clipto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CLIPTO_VERSION', '1.1.0' );

/**
 * ------------------------------------------------------------------
 * 1. THEME SETUP
 * ------------------------------------------------------------------
 */
function clipto_theme_setup() {
	load_theme_textdomain( 'clipto', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 64,
			'width'       => 200,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );

	register_nav_menus(
		array(
			'clipto-primary'   => __( 'Primary Menu', 'clipto' ),
			'clipto-footer'    => __( 'Footer Menu', 'clipto' ),
			'clipto-secondary' => __( 'Secondary Menu', 'clipto' ),
		)
	);

	add_image_size( 'clipto-card', 640, 400, true );
	add_image_size( 'clipto-hero', 1280, 720, true );
	// Same 16:9 crop at a phone-friendly width, so the hero's srcset has a
	// smaller candidate (without it, phones downloaded the 1280px crop).
	add_image_size( 'clipto-hero-md', 768, 432, true );

	$GLOBALS['content_width'] = 780;
}
add_action( 'after_setup_theme', 'clipto_theme_setup' );

/**
 * ------------------------------------------------------------------
 * 2. ASSETS
 * ------------------------------------------------------------------
 * Everything lives in one stylesheet and one inline footer script by
 * design: for a content site, one consolidated, well-organized CSS file
 * beats several conditionally-loaded ones, and it keeps HTTP requests to
 * an unavoidable minimum. There is no separate JS file to enqueue; the
 * small amount of vanilla JS the theme needs is printed in footer.php.
 */
function clipto_enqueue_assets() {
	wp_enqueue_style( 'clipto-style', get_stylesheet_uri(), array(), CLIPTO_VERSION );

	if ( is_singular() && comments_open() ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'clipto_enqueue_assets' );

/**
 * sizes attribute for the article/page/tool hero image, which is rendered
 * at the article column width (740px container minus 2 x 24px padding).
 */
function clipto_hero_image_sizes() {
	return '(max-width: 740px) 100vw, 692px';
}

/**
 * sizes attribute for card thumbnails in .clipto-grid (1 column on phones,
 * then 2, 3 and at most 4 columns of ~280-300px inside the 1240px container).
 * Without it, core's default "(max-width: 640px) 100vw, 640px" made desktop
 * browsers fetch the 640px file for a ~280px card.
 */
function clipto_card_image_sizes() {
	return '(max-width: 640px) calc(100vw - 32px), (max-width: 940px) 50vw, (max-width: 1240px) 33vw, 300px';
}

/**
 * The card grids show up to four cards in their first row, so let core keep
 * the first four content images eager (its default is three) — the fourth
 * card is above the fold on desktop.
 */
function clipto_loading_threshold() {
	return 4;
}
add_filter( 'wp_omit_loading_attr_threshold', 'clipto_loading_threshold' );

/**
 * ------------------------------------------------------------------
 * 3. PERFORMANCE + SECURITY HARDENING
 * ------------------------------------------------------------------
 */
function clipto_performance_and_security_cleanup() {
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'rest_output_link_wp_head' );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	add_filter( 'xmlrpc_enabled', '__return_false' );
}
add_action( 'init', 'clipto_performance_and_security_cleanup' );
add_filter( 'the_generator', '__return_empty_string' );

/**
 * ------------------------------------------------------------------
 * 3b. TEMPLATE ROUTING
 * ------------------------------------------------------------------
 * The theme intentionally ships no search.php. Without this filter
 * WordPress falls back to index.php for search results, which has no
 * H1 and a "No articles published yet" empty state; archive.php already
 * contains the search-specific heading and empty state, so route search
 * there. A child theme's own search.php still wins.
 */
function clipto_search_template( $template ) {
	if ( '' !== $template ) {
		return $template;
	}
	$archive = locate_template( 'archive.php' );
	return $archive ? $archive : $template;
}
add_filter( 'search_template', 'clipto_search_template' );

/**
 * The AI Tools archive H1 reads "AI Tools" rather than core's default
 * "Archives: AI Tools". Category/tag prefixes are left as core prints them.
 */
function clipto_archive_title_prefix( $prefix ) {
	return is_post_type_archive( 'clipto_tool' ) ? '' : $prefix;
}
add_filter( 'get_the_archive_title_prefix', 'clipto_archive_title_prefix' );

/**
 * Tool taxonomy archives (/tool-pricing/…, /tool-category/…) and the
 * /ai-tools/ archive get the plugin's tool cards rather than post cards.
 */
function clipto_is_tools_listing() {
	return function_exists( 'clipto_render_tool_card' )
		&& ( is_post_type_archive( 'clipto_tool' ) || is_tax( array( 'clipto_pricing', 'clipto_tool_category' ) ) );
}

/**
 * Search form with a distinct accessible name, so pages that show a
 * second search form (404, empty search results) don't expose two
 * identically-named "search" landmarks.
 */
function clipto_search_form( $label ) {
	get_search_form( array( 'aria_label' => $label ) );
}

/**
 * ------------------------------------------------------------------
 * 4. NAV MENU FALLBACK (no hardcoded links)
 * ------------------------------------------------------------------
 */
function clipto_primary_nav_fallback() {
	echo '<ul class="clipto-nav-list">';
	wp_list_pages(
		array(
			'title_li' => '',
			'depth'    => 1,
		)
	);
	echo '</ul>';
}

/**
 * ------------------------------------------------------------------
 * 5. EXCERPTS
 * ------------------------------------------------------------------
 */
function clipto_excerpt_length( $length ) {
	return is_admin() ? $length : 30;
}
add_filter( 'excerpt_length', 'clipto_excerpt_length' );

function clipto_excerpt_more( $more ) {
	return '&hellip;';
}
add_filter( 'excerpt_more', 'clipto_excerpt_more' );

/**
 * ------------------------------------------------------------------
 * 6. READING TIME
 * ------------------------------------------------------------------
 * Canonical calculation lives in the Clipto Core plugin
 * (clipto_calculate_reading_time). This wrapper falls back to a local
 * calculation if the plugin is inactive, so templates never break.
 */
function clipto_theme_fallback_reading_time( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return 1;
	}
	$text    = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
	$words   = str_word_count( $text );
	$wpm     = (int) apply_filters( 'clipto_reading_time_wpm', 220 );
	$wpm     = max( 100, $wpm );
	$minutes = (int) ceil( $words / $wpm );
	return max( 1, $minutes );
}

function clipto_reading_time( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	if ( function_exists( 'clipto_calculate_reading_time' ) ) {
		return clipto_calculate_reading_time( $post_id );
	}
	return clipto_theme_fallback_reading_time( $post_id );
}

function clipto_reading_time_text( $post_id = null ) {
	$minutes = clipto_reading_time( $post_id );
	/* translators: %d: number of minutes */
	return sprintf( _n( '%d min read', '%d min read', $minutes, 'clipto' ), $minutes );
}

/**
 * ------------------------------------------------------------------
 * 7. AI SUMMARY (reads the meta the plugin's meta box saves; works
 *    read-only even if the plugin is later deactivated)
 * ------------------------------------------------------------------
 */
function clipto_get_ai_summary( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$summary = get_post_meta( $post_id, '_clipto_ai_summary', true );
	return $summary ? wp_kses_post( $summary ) : '';
}

function clipto_get_ai_takeaway( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	return sanitize_text_field( get_post_meta( $post_id, '_clipto_ai_takeaway', true ) );
}

/**
 * ------------------------------------------------------------------
 * 8. AUTHOR PROFILE — DISPLAY HELPERS ONLY
 * ------------------------------------------------------------------
 * The Dashboard > Users > Profile edit screen (the fields themselves,
 * their sanitization, and the save handler) now lives in the Clipto
 * Core plugin — see clipto-core-plugin.php section 7. Author metadata
 * is data, not presentation, so it belongs with the plugin and should
 * survive both a plugin update and, eventually, a theme switch. These
 * theme-side helpers only ever READ that meta, so display keeps
 * working even if the plugin is deactivated.
 */
function clipto_social_field_labels() {
	return array(
		'clipto_linkedin'  => __( 'LinkedIn', 'clipto' ),
		'clipto_twitter'   => __( 'X / Twitter', 'clipto' ),
		'clipto_youtube'   => __( 'YouTube', 'clipto' ),
		'clipto_instagram' => __( 'Instagram', 'clipto' ),
		'clipto_facebook'  => __( 'Facebook', 'clipto' ),
		'clipto_website'   => __( 'Website', 'clipto' ),
	);
}

function clipto_get_author_expertise_label( $user_id ) {
	return sanitize_text_field( get_user_meta( $user_id, 'clipto_expertise_level', true ) );
}

function clipto_get_author_social_links( $user_id ) {
	$links = array();
	foreach ( array_keys( clipto_social_field_labels() ) as $key ) {
		$url = get_user_meta( $user_id, $key, true );
		if ( $url && wp_http_validate_url( $url ) ) {
			$links[ $key ] = esc_url( $url );
		}
	}
	return $links;
}

/**
 * ------------------------------------------------------------------
 * 9. SEO PLUGIN DETECTION (avoid duplicate meta/schema output)
 * ------------------------------------------------------------------
 */
function clipto_seo_plugin_active() {
	return (
		defined( 'WPSEO_VERSION' )
		|| class_exists( 'RankMath' )
		|| defined( 'AIOSEO_VERSION' )
		|| defined( 'SEOPRESS_VERSION' )
	);
}

/**
 * ------------------------------------------------------------------
 * 10. META TAGS (description, Open Graph, Twitter Card)
 *     WordPress core already prints rel=canonical, so we never add our own.
 * ------------------------------------------------------------------
 */
function clipto_get_meta_description() {
	if ( is_singular( 'clipto_tool' ) ) {
		$summary = get_post_meta( get_queried_object_id(), '_clipto_tool_summary', true );
		if ( $summary ) {
			return wp_strip_all_tags( $summary );
		}
	}
	if ( is_singular() ) {
		return wp_strip_all_tags( get_the_excerpt( get_queried_object_id() ) );
	}
	if ( is_category() || is_tag() || is_tax() ) {
		return wp_strip_all_tags( term_description() );
	}
	if ( is_author() ) {
		return wp_strip_all_tags( get_the_author_meta( 'description' ) );
	}
	return get_bloginfo( 'description' );
}

function clipto_output_meta_tags() {
	if ( clipto_seo_plugin_active() ) {
		return;
	}

	$description = clipto_get_meta_description();
	$title       = wp_get_document_title();
	// get_pagenum_link() resolves the current archive/search URL relative to
	// the real home path (home_url( REQUEST_URI ) doubled the path on
	// subdirectory installs, e.g. /blog/blog/category/…).
	$url         = is_singular() ? get_permalink() : get_pagenum_link( max( 1, (int) get_query_var( 'paged' ) ) );
	$image       = '';

	if ( is_singular() && has_post_thumbnail() ) {
		$image = get_the_post_thumbnail_url( get_the_ID(), 'clipto-hero' );
	} elseif ( has_custom_logo() ) {
		$logo_src = wp_get_attachment_image_src( get_theme_mod( 'custom_logo' ), 'full' );
		$image    = $logo_src ? $logo_src[0] : '';
	}

	if ( $description ) {
		printf( '<meta name="description" content="%s" />' . "\n", esc_attr( wp_trim_words( $description, 45, '' ) ) );
	}

	printf( '<meta property="og:type" content="%s" />' . "\n", is_singular( 'post' ) ? 'article' : 'website' );
	printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( $title ) );
	if ( $description ) {
		printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( wp_trim_words( $description, 45, '' ) ) );
	}
	printf( '<meta property="og:url" content="%s" />' . "\n", esc_url( $url ) );
	printf( '<meta property="og:site_name" content="%s" />' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
	if ( $image ) {
		printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $image ) );
	}

	printf( '<meta name="twitter:card" content="%s" />' . "\n", $image ? 'summary_large_image' : 'summary' );
	printf( '<meta name="twitter:title" content="%s" />' . "\n", esc_attr( $title ) );
	if ( $description ) {
		printf( '<meta name="twitter:description" content="%s" />' . "\n", esc_attr( wp_trim_words( $description, 45, '' ) ) );
	}
	if ( $image ) {
		printf( '<meta name="twitter:image" content="%s" />' . "\n", esc_url( $image ) );
	}
}
add_action( 'wp_head', 'clipto_output_meta_tags', 5 );

/**
 * Internal search result pages are thin, near-infinite URL space; keep them
 * out of the index (links are still followed). Uses core's wp_robots API,
 * and defers to an SEO plugin's own robots settings when one is active.
 */
function clipto_search_robots( $robots ) {
	if ( is_search() && ! clipto_seo_plugin_active() ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'clipto_search_robots' );

/**
 * ------------------------------------------------------------------
 * 11. STRUCTURED DATA (NewsArticle / Person / WebSite)
 * ------------------------------------------------------------------
 */
/**
 * Plain text for JSON-LD: strips tags and decodes HTML entities (e.g. the
 * "&hellip;" excerpt suffix or "&#8217;" from wptexturize) so structured
 * data contains real characters rather than literal entity strings.
 */
function clipto_schema_text( $text ) {
	return trim( html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES | ENT_HTML5, get_bloginfo( 'charset' ) ) );
}

function clipto_output_structured_data() {
	if ( clipto_seo_plugin_active() ) {
		return;
	}

	$graph = null;

	if ( is_singular( 'post' ) ) {
		$post_id   = get_the_ID();
		$author_id = (int) get_post_field( 'post_author', $post_id );
		$image     = has_post_thumbnail( $post_id ) ? get_the_post_thumbnail_url( $post_id, 'clipto-hero' ) : '';
		$logo      = '';
		if ( has_custom_logo() ) {
			$logo_src = wp_get_attachment_image_src( get_theme_mod( 'custom_logo' ), 'full' );
			$logo     = $logo_src ? $logo_src[0] : '';
		}

		$publisher = array(
			'@type' => 'Organization',
			'name'  => clipto_schema_text( get_bloginfo( 'name' ) ),
		);
		if ( $logo ) {
			$publisher['logo'] = array(
				'@type' => 'ImageObject',
				'url'   => $logo,
			);
		}

		$graph = array(
			'@context'         => 'https://schema.org',
			'@type'            => 'NewsArticle',
			'headline'         => clipto_schema_text( get_the_title( $post_id ) ),
			'description'      => clipto_schema_text( clipto_get_meta_description() ),
			'datePublished'    => get_the_date( DATE_W3C, $post_id ),
			'dateModified'     => get_the_modified_date( DATE_W3C, $post_id ),
			'mainEntityOfPage' => array(
				'@type' => 'WebPage',
				'@id'   => get_permalink( $post_id ),
			),
			'author'           => array(
				'@type' => 'Person',
				'name'  => clipto_schema_text( get_the_author_meta( 'display_name', $author_id ) ),
				'url'   => get_author_posts_url( $author_id ),
			),
			'publisher'        => $publisher,
		);
		if ( $image ) {
			$graph['image'] = array( $image );
		}
	} elseif ( is_author() ) {
		$author_id = get_queried_object_id();
		$graph     = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'Person',
			'name'        => clipto_schema_text( get_the_author_meta( 'display_name', $author_id ) ),
			'description' => clipto_schema_text( get_the_author_meta( 'description', $author_id ) ),
			'url'         => get_author_posts_url( $author_id ),
		);
	} elseif ( is_front_page() || is_home() ) {
		$graph = array(
			'@context' => 'https://schema.org',
			'@type'    => 'WebSite',
			'name'     => clipto_schema_text( get_bloginfo( 'name' ) ),
			'url'      => home_url( '/' ),
		);
	}

	if ( ! $graph ) {
		return;
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
add_action( 'wp_head', 'clipto_output_structured_data', 6 );

/**
 * ------------------------------------------------------------------
 * 12. BREADCRUMBS
 * ------------------------------------------------------------------
 */
/**
 * Breadcrumb trail as data: an ordered list of array( label, url ), where
 * the final (current) item has a null URL. Used by both the visible
 * breadcrumb (clipto_breadcrumbs) and the BreadcrumbList structured data,
 * so the two can never disagree.
 */
function clipto_get_breadcrumb_items() {
	if ( is_front_page() ) {
		return array();
	}
	$items   = array( array( __( 'Home', 'clipto' ), home_url( '/' ) ) );
	$tool_pt = post_type_exists( 'clipto_tool' ) ? get_post_type_object( 'clipto_tool' ) : null;

	if ( is_singular( 'post' ) ) {
		$categories = get_the_category();
		if ( ! empty( $categories ) ) {
			$items[] = array( $categories[0]->name, get_category_link( $categories[0]->term_id ) );
		}
		$items[] = array( get_the_title(), null );
	} elseif ( is_singular( 'clipto_tool' ) && $tool_pt ) {
		$items[] = array( $tool_pt->labels->name, get_post_type_archive_link( 'clipto_tool' ) );
		$items[] = array( get_the_title(), null );
	} elseif ( is_tax( array( 'clipto_pricing', 'clipto_tool_category' ) ) && $tool_pt ) {
		$items[] = array( $tool_pt->labels->name, get_post_type_archive_link( 'clipto_tool' ) );
		$items[] = array( single_term_title( '', false ), null );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$items[] = array( single_term_title( '', false ), null );
	} elseif ( is_author() ) {
		$items[] = array( get_the_author_meta( 'display_name', get_queried_object_id() ), null );
	} elseif ( is_singular() ) {
		$items[] = array( get_the_title(), null );
	} elseif ( is_search() ) {
		$items[] = array( __( 'Search Results', 'clipto' ), null );
	} elseif ( is_post_type_archive() ) {
		$items[] = array( post_type_archive_title( '', false ), null );
	} elseif ( is_home() ) {
		$items[] = array( __( 'Latest Articles', 'clipto' ), null );
	}

	return $items;
}

function clipto_breadcrumbs() {
	$items = clipto_get_breadcrumb_items();
	if ( empty( $items ) ) {
		return;
	}
	echo '<nav class="clipto-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'clipto' ) . '"><ol>';
	foreach ( $items as $item ) {
		if ( $item[1] ) {
			echo '<li><a href="' . esc_url( $item[1] ) . '">' . esc_html( $item[0] ) . '</a></li>';
		} else {
			echo '<li aria-current="page">' . esc_html( $item[0] ) . '</li>';
		}
	}
	echo '</ol></nav>';
}

/**
 * BreadcrumbList JSON-LD mirroring the visible breadcrumb. Skipped on the
 * front page / 404 (no trail) and when an SEO plugin is active (they emit
 * their own breadcrumb schema).
 */
function clipto_output_breadcrumb_schema() {
	if ( clipto_seo_plugin_active() || is_404() ) {
		return;
	}
	$items = clipto_get_breadcrumb_items();
	if ( count( $items ) < 2 ) {
		return;
	}
	$list = array();
	foreach ( array_values( $items ) as $i => $item ) {
		$entry = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => clipto_schema_text( $item[0] ),
		);
		if ( $item[1] ) {
			$entry['item'] = $item[1];
		}
		$list[] = $entry;
	}
	$graph = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $list,
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
add_action( 'wp_head', 'clipto_output_breadcrumb_schema', 7 );

/**
 * ------------------------------------------------------------------
 * 13. RELATED POSTS
 * ------------------------------------------------------------------
 */
function clipto_get_related_posts( $post_id, $limit = 3 ) {
	$categories = wp_get_post_categories( $post_id );
	if ( empty( $categories ) ) {
		return new WP_Query();
	}
	return new WP_Query(
		array(
			'category__in'        => $categories,
			'post__not_in'        => array( $post_id ),
			'posts_per_page'      => (int) $limit,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'post_status'         => 'publish',
		)
	);
}

/**
 * ------------------------------------------------------------------
 * 14. PAGINATION
 * ------------------------------------------------------------------
 */
function clipto_pagination() {
	the_posts_pagination(
		array(
			'mid_size'  => 1,
			'prev_text' => __( '&larr; Newer', 'clipto' ),
			'next_text' => __( 'Older &rarr;', 'clipto' ),
		)
	);
}

/**
 * ------------------------------------------------------------------
 * 15. REUSABLE RENDER HELPERS (kept in functions.php so index.php,
 *     archive.php and author.php can share markup without extra
 *     template-part files)
 * ------------------------------------------------------------------
 */
/**
 * $loading: '' lets WordPress core decide (it keeps the first, likely
 * above-the-fold images eager and lazy-loads the rest); pass 'lazy' for
 * sections that are always below the fold, e.g. Related Articles.
 */
function clipto_render_post_card( $post_id = null, $reveal_index = 0, $heading_tag = 'h2', $loading = '' ) {
	$post_id     = $post_id ? $post_id : get_the_ID();
	$heading_tag = in_array( $heading_tag, array( 'h2', 'h3' ), true ) ? $heading_tag : 'h2';
	$categories = get_the_category( $post_id );
	$category   = ! empty( $categories ) ? $categories[0] : null;
	$delay      = min( 6, max( 0, (int) $reveal_index ) );
	?>
	<article class="clipto-card clipto-reveal" style="--clipto-reveal-i: <?php echo esc_attr( $delay ); ?>;" id="post-<?php echo esc_attr( $post_id ); ?>">
		<a class="clipto-card__thumb-link" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" tabindex="-1" aria-hidden="true">
			<?php if ( has_post_thumbnail( $post_id ) ) : ?>
				<div class="clipto-card__thumb">
					<?php echo get_the_post_thumbnail( $post_id, 'clipto-card', array_filter( array( 'loading' => 'lazy' === $loading ? 'lazy' : '', 'decoding' => 'async', 'sizes' => clipto_card_image_sizes(), 'alt' => wp_strip_all_tags( get_the_title( $post_id ) ) ) ) ); ?>
				</div>
			<?php endif; ?>
		</a>
		<div class="clipto-card__body">
			<?php if ( $category ) : ?>
				<a class="clipto-badge clipto-badge--category" href="<?php echo esc_url( get_category_link( $category->term_id ) ); ?>"><?php echo esc_html( $category->name ); ?></a>
			<?php endif; ?>
			<<?php echo esc_html( $heading_tag ); ?> class="clipto-card__title">
				<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a>
			</<?php echo esc_html( $heading_tag ); ?>>
			<p class="clipto-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $post_id ), 20 ) ); ?></p>
			<div class="clipto-card__meta">
				<span><?php echo esc_html( get_the_date( '', $post_id ) ); ?></span>
				<span aria-hidden="true">&middot;</span>
				<span><?php echo esc_html( clipto_reading_time_text( $post_id ) ); ?></span>
			</div>
		</div>
	</article>
	<?php
}

/**
 * Single AI Tool page (/ai-tools/{slug}/). Rendered from single.php when
 * the queried post is a clipto_tool and the Clipto Core plugin is active
 * (the post type only exists when it is). Tools are directory entries,
 * not articles, so this shows the tool's own data — category, pricing,
 * editorial rating, official website — instead of article byline,
 * reading time, bookmark, author box and prev/next-article navigation.
 */
function clipto_render_tool_single( $tool_id ) {
	$data = clipto_get_tool_data( $tool_id );
	if ( ! $data ) {
		return;
	}
	$title = get_the_title( $tool_id );
	$lede  = $data['summary'] ? $data['summary'] : ( has_excerpt( $tool_id ) ? get_the_excerpt( $tool_id ) : '' );
	?>
	<div class="clipto-container clipto-container--article">
		<?php clipto_breadcrumbs(); ?>

		<article <?php post_class( 'clipto-article clipto-tool', $tool_id ); ?> id="post-<?php echo esc_attr( $tool_id ); ?>">
			<header class="clipto-article__header clipto-tool__header">
				<h1 class="clipto-article__title"><?php echo esc_html( $title ); ?></h1>

				<?php if ( $lede ) : ?>
					<p class="clipto-article__lede"><?php echo esc_html( $lede ); ?></p>
				<?php endif; ?>

				<div class="clipto-tool__meta">
					<?php clipto_render_tool_badges( $data, true ); ?>
					<span class="clipto-tool__updated">
						<?php esc_html_e( 'Updated', 'clipto' ); ?>
						<time datetime="<?php echo esc_attr( get_the_modified_date( DATE_W3C, $tool_id ) ); ?>"><?php echo esc_html( get_the_modified_date( '', $tool_id ) ); ?></time>
					</span>
				</div>
				<?php if ( null !== $data['rating'] ) : ?>
					<p class="clipto-tool__rating-note"><?php esc_html_e( 'The rating is an editorial score assigned by our editors after testing — not an average of user reviews.', 'clipto' ); ?></p>
				<?php endif; ?>

				<div class="clipto-tool__actions">
					<?php if ( $data['url'] ) : ?>
						<a class="clipto-btn clipto-btn--primary" href="<?php echo esc_url( $data['url'] ); ?>" target="_blank" rel="nofollow noopener noreferrer">
							<?php
							/* translators: %s: tool name */
							echo esc_html( sprintf( __( 'Visit %s', 'clipto' ), $title ) );
							?>
							<span class="clipto-btn__arrow" aria-hidden="true">↗</span>
							<span class="screen-reader-text"><?php esc_html_e( '(official website, opens in a new tab)', 'clipto' ); ?></span>
						</a>
					<?php endif; ?>
					<a class="clipto-btn clipto-btn--secondary" href="<?php echo esc_url( get_post_type_archive_link( 'clipto_tool' ) ); ?>">
						<?php esc_html_e( 'All AI Tools', 'clipto' ); ?>
					</a>
				</div>
			</header>

			<?php if ( has_post_thumbnail( $tool_id ) ) : ?>
				<div class="clipto-article__thumb">
					<?php echo get_the_post_thumbnail( $tool_id, 'clipto-hero', array( 'fetchpriority' => 'high', 'decoding' => 'async', 'sizes' => clipto_hero_image_sizes(), 'alt' => wp_strip_all_tags( $title ) ) ); ?>
				</div>
			<?php endif; ?>

			<div class="clipto-article__content">
				<?php the_content(); ?>
			</div>

			<?php
			if ( $data['category'] ) :
				$more = new WP_Query(
					array(
						'post_type'           => 'clipto_tool',
						'post_status'         => 'publish',
						'posts_per_page'      => 3,
						'post__not_in'        => array( $tool_id ),
						'no_found_rows'       => true,
						'ignore_sticky_posts' => true,
						'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery -- single term, 3 rows.
							array(
								'taxonomy' => 'clipto_tool_category',
								'field'    => 'term_id',
								'terms'    => $data['category']->term_id,
							),
						),
					)
				);
				if ( $more->have_posts() ) :
					?>
					<section class="clipto-related">
						<h2 class="clipto-section-title">
							<?php
							/* translators: %s: tool category name */
							echo esc_html( sprintf( __( 'More %s tools', 'clipto' ), $data['category']->name ) );
							?>
						</h2>
						<div class="clipto-grid clipto-grid--tools">
							<?php
							while ( $more->have_posts() ) :
								$more->the_post();
								clipto_render_tool_card( get_the_ID(), 'h3', 'lazy' );
							endwhile;
							wp_reset_postdata();
							?>
						</div>
					</section>
					<?php
				endif;
			endif;
			?>
		</article>
	</div>
	<?php
}

/**
 * Comments for single posts. The theme deliberately ships no
 * comments.php (fixed file set), and calling comments_template() without
 * one loads WordPress's deprecated theme-compat file, so the list and form
 * are rendered here directly. Only shown when comments are open or the
 * post already has approved comments.
 */
function clipto_render_comments( $post_id ) {
	if ( post_password_required( $post_id ) || ( ! comments_open( $post_id ) && ! get_comments_number( $post_id ) ) ) {
		return;
	}

	// Same rule as core's comments_template(): a commenter sees their own
	// comment while it awaits moderation.
	$include_unapproved = array();
	if ( is_user_logged_in() ) {
		$include_unapproved[] = get_current_user_id();
	} else {
		$unapproved_email = wp_get_unapproved_comment_author_email();
		if ( $unapproved_email ) {
			$include_unapproved[] = $unapproved_email;
		}
	}

	$comments = get_comments(
		array(
			'post_id'            => $post_id,
			'status'             => 'approve',
			'include_unapproved' => $include_unapproved,
			'order'              => 'ASC',
			'orderby'            => 'comment_date_gmt',
		)
	);
	$count    = (int) get_comments_number( $post_id );
	?>
	<section class="clipto-comments" id="comments">
		<?php if ( $comments ) : ?>
			<h2 class="clipto-section-title">
				<?php
				/* translators: %s: number of comments */
				echo esc_html( sprintf( _n( '%s Comment', '%s Comments', $count, 'clipto' ), number_format_i18n( $count ) ) );
				?>
			</h2>
			<ol class="clipto-comment-list">
				<?php
				wp_list_comments(
					array(
						'style'       => 'ol',
						'short_ping'  => true,
						'avatar_size' => 40,
					),
					$comments
				);
				?>
			</ol>
		<?php endif; ?>

		<?php if ( ! comments_open( $post_id ) ) : ?>
			<p class="clipto-comments__closed"><?php esc_html_e( 'Comments are closed.', 'clipto' ); ?></p>
		<?php else : ?>
			<?php
			comment_form(
				array(
					'class_container'    => 'comment-respond clipto-comment-form',
					'title_reply_before' => '<h2 id="reply-title" class="comment-reply-title">',
					'title_reply_after'  => '</h2>',
				),
				$post_id
			);
			?>
		<?php endif; ?>
	</section>
	<?php
}

function clipto_render_author_box( $author_id ) {
	$bio       = get_the_author_meta( 'description', $author_id );
	$expertise = clipto_get_author_expertise_label( $author_id );
	$count     = count_user_posts( $author_id, 'post', true );
	$links     = clipto_get_author_social_links( $author_id );
	?>
	<div class="clipto-author-box clipto-reveal">
		<a class="clipto-author-box__avatar" href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>" tabindex="-1" aria-hidden="true">
			<?php echo get_avatar( $author_id, 72 ); ?>
		</a>
		<div class="clipto-author-box__body">
			<a class="clipto-author-box__name" href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>"><?php echo esc_html( get_the_author_meta( 'display_name', $author_id ) ); ?></a>
			<?php if ( $expertise ) : ?>
				<span class="clipto-badge clipto-badge--expertise"><?php echo esc_html( $expertise ); ?></span>
			<?php endif; ?>
			<?php if ( $bio ) : ?>
				<p class="clipto-author-box__bio"><?php echo esc_html( $bio ); ?></p>
			<?php endif; ?>
			<p class="clipto-author-box__stats">
				<?php
				printf(
					/* translators: %d: number of published articles */
					esc_html( _n( '%d article published', '%d articles published', $count, 'clipto' ) ),
					(int) $count
				);
				?>
			</p>
			<?php clipto_render_social_icons( $links ); ?>
		</div>
	</div>
	<?php
}

function clipto_render_social_icons( $links ) {
	if ( empty( $links ) ) {
		return;
	}
	$labels = clipto_social_field_labels();
	echo '<ul class="clipto-social-links">';
	foreach ( $links as $key => $url ) {
		$label = isset( $labels[ $key ] ) ? str_replace( ' URL', '', $labels[ $key ] ) : $key;
		echo '<li><a href="' . esc_url( $url ) . '" rel="nofollow noopener" target="_blank">' . esc_html( $label ) . '</a></li>';
	}
	echo '</ul>';
}

/**
 * ------------------------------------------------------------------
 * 16. HOMEPAGE: HERO / STATS / CATEGORY SHOWCASE
 * ------------------------------------------------------------------
 * All content here is pulled live from WordPress (Site Title, Tagline,
 * real published-post counts, real categories) — nothing is invented,
 * per the "no fake claims" requirement. Every piece hides gracefully
 * when the underlying data isn't there yet.
 * ------------------------------------------------------------------
 */
function clipto_render_homepage_hero() {
	$tagline = get_bloginfo( 'description' );
	if ( 'Just another WordPress site' === $tagline ) {
		$tagline = '';
	}
	$tools_active = post_type_exists( 'clipto_tool' );
	?>
	<section class="clipto-hero">
		<div class="clipto-hero__ambient" aria-hidden="true">
			<span class="clipto-hero__wash"></span>
			<span class="clipto-orb clipto-orb--1"></span>
			<span class="clipto-orb clipto-orb--2"></span>
			<span class="clipto-orb clipto-orb--3"></span>
			<span class="clipto-hero__trail"></span>
			<span class="clipto-hero__grid"></span>
		</div>
		<div class="clipto-container clipto-hero__content">
			<p class="clipto-hero__eyebrow clipto-reveal"><?php esc_html_e( 'AI News · Tools · Guides', 'clipto' ); ?></p>
			<h1 class="clipto-hero__title clipto-reveal"><?php bloginfo( 'name' ); ?></h1>
			<?php if ( $tagline ) : ?>
				<p class="clipto-hero__subtitle clipto-reveal"><?php echo esc_html( $tagline ); ?></p>
			<?php endif; ?>
			<div class="clipto-hero__ctas clipto-reveal">
				<a class="clipto-btn clipto-btn--primary" href="#clipto-latest">
					<?php esc_html_e( 'Explore Articles', 'clipto' ); ?>
					<span class="clipto-btn__arrow" aria-hidden="true">→</span>
				</a>
				<?php if ( $tools_active ) : ?>
					<a class="clipto-btn clipto-btn--secondary" href="<?php echo esc_url( get_post_type_archive_link( 'clipto_tool' ) ); ?>">
						<?php esc_html_e( 'Browse AI Tools', 'clipto' ); ?>
						<span class="clipto-btn__arrow" aria-hidden="true">→</span>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
}

function clipto_render_stats_strip() {
	$stats = array();

	$post_counts = wp_count_posts( 'post' );
	if ( $post_counts && (int) $post_counts->publish > 0 ) {
		$stats[] = array(
			'value' => (int) $post_counts->publish,
			'label' => _n( 'Article Published', 'Articles Published', (int) $post_counts->publish, 'clipto' ),
		);
	}

	if ( post_type_exists( 'clipto_tool' ) ) {
		$tool_counts = wp_count_posts( 'clipto_tool' );
		if ( $tool_counts && (int) $tool_counts->publish > 0 ) {
			$stats[] = array(
				'value' => (int) $tool_counts->publish,
				'label' => _n( 'AI Tool Listed', 'AI Tools Listed', (int) $tool_counts->publish, 'clipto' ),
			);
		}
	}

	$cat_count = (int) wp_count_terms( array( 'taxonomy' => 'category', 'hide_empty' => true ) );
	if ( $cat_count > 0 ) {
		$stats[] = array(
			'value' => $cat_count,
			'label' => _n( 'Category Covered', 'Categories Covered', $cat_count, 'clipto' ),
		);
	}

	if ( empty( $stats ) ) {
		return;
	}
	?>
	<section class="clipto-stats-strip clipto-reveal">
		<div class="clipto-container clipto-stats-strip__inner">
			<?php foreach ( $stats as $stat ) : ?>
				<div class="clipto-stat">
					<span class="clipto-stat__value"><?php echo esc_html( number_format_i18n( $stat['value'] ) ); ?></span>
					<span class="clipto-stat__label"><?php echo esc_html( $stat['label'] ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
}

function clipto_render_category_showcase() {
	$categories = get_categories(
		array(
			'hide_empty' => true,
			'number'     => 6,
			'orderby'    => 'count',
			'order'      => 'DESC',
		)
	);
	if ( empty( $categories ) ) {
		return;
	}
	?>
	<section class="clipto-category-showcase">
		<span class="clipto-category-showcase__glow" aria-hidden="true"></span>
		<div class="clipto-container">
			<h2 class="clipto-section-title clipto-reveal"><span class="clipto-kicker"><?php esc_html_e( 'Browse', 'clipto' ); ?></span><?php esc_html_e( 'Explore by Category', 'clipto' ); ?></h2>
			<div class="clipto-grid clipto-grid--categories">
				<?php
				$i = 0;
				foreach ( $categories as $cat ) :
					$i++;
					?>
					<a class="clipto-category-card clipto-reveal" style="--clipto-reveal-i: <?php echo esc_attr( min( 6, $i ) ); ?>;" href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>">
						<span class="clipto-category-card__name"><?php echo esc_html( $cat->name ); ?></span>
						<span class="clipto-category-card__count">
							<?php
							printf(
								/* translators: %d: number of articles in this category */
								esc_html( _n( '%d article', '%d articles', $cat->count, 'clipto' ) ),
								(int) $cat->count
							);
							?>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
}
