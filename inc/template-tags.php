<?php
/**
 * Core template helpers shared by every template: site destinations, taxonomy
 * helpers, post metadata, media, cards and section headers.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Destinations — the fixed information architecture (DESIGN.md §11)
 * ---------------------------------------------------------------------- */

/**
 * The site's primary destinations, resolved against live data. A destination that does
 * not exist on this installation is omitted, so the theme never prints dead links.
 *
 * Each item: key, label, short (menu label), url, type (category|tag|page), object
 * (WP_Term|WP_Post), count (int|null), description (string).
 *
 * @return array<string,array>
 */
function clipto_destinations() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}

	$map = apply_filters(
		'clipto_destination_map',
		array(
			'ai-tools'     => array( 'type' => 'category', 'slug' => 'ai-tools', 'label' => __( 'AI Tools', 'clipto' ) ),
			'ai-news'      => array( 'type' => 'category', 'slug' => 'ai-news', 'label' => __( 'AI News', 'clipto' ) ),
			'earn-with-ai' => array( 'type' => 'category', 'slug' => 'earn-with-ai', 'label' => __( 'Earn With AI', 'clipto' ) ),
			'free-ai'      => array( 'type' => 'tag', 'slug' => 'free-ai', 'label' => __( 'Free AI', 'clipto' ) ),
			'ai-guide'     => array( 'type' => 'page', 'slug' => 'artificial-intelligence-clipto-org', 'label' => __( 'AI Guide', 'clipto' ) ),
		)
	);

	$cache = array();
	foreach ( $map as $key => $def ) {
		if ( 'page' === $def['type'] ) {
			$page = get_page_by_path( $def['slug'] );
			if ( ! $page || 'publish' !== $page->post_status ) {
				continue;
			}
			$cache[ $key ] = array(
				'key'         => $key,
				'label'       => $def['label'],
				'url'         => get_permalink( $page ),
				'type'        => 'page',
				'object'      => $page,
				'count'       => null,
				'description' => '',
			);
			continue;
		}

		$taxonomy = 'tag' === $def['type'] ? 'post_tag' : 'category';
		$term     = get_term_by( 'slug', $def['slug'], $taxonomy );
		if ( ! $term || is_wp_error( $term ) ) {
			continue;
		}
		$link = get_term_link( $term );
		if ( is_wp_error( $link ) ) {
			continue;
		}
		$cache[ $key ] = array(
			'key'         => $key,
			'label'       => $def['label'],
			'url'         => $link,
			'type'        => $def['type'],
			'object'      => $term,
			'count'       => clipto_term_post_count( $term ),
			'description' => term_description( $term ),
		);
	}

	return $cache;
}

/**
 * Get one destination by key, or null if it does not exist on this site.
 *
 * @param string $key Destination key.
 * @return array|null
 */
function clipto_destination( $key ) {
	$all = clipto_destinations();
	return isset( $all[ $key ] ) ? $all[ $key ] : null;
}

/**
 * Post count for a term including its descendants (categories only).
 *
 * @param WP_Term $term Term.
 * @return int
 */
function clipto_term_post_count( $term ) {
	if ( ! $term instanceof WP_Term ) {
		return 0;
	}
	if ( 'category' !== $term->taxonomy ) {
		return (int) $term->count;
	}
	$key   = 'clipto_count_' . $term->term_id;
	$count = wp_cache_get( $key, 'clipto' );
	if ( false === $count ) {
		$children = get_term_children( $term->term_id, 'category' );
		$count    = (int) $term->count;
		if ( ! is_wp_error( $children ) && $children ) {
			$q     = new WP_Query(
				array(
					'post_type'              => 'post',
					'post_status'            => 'publish',
					'cat'                    => $term->term_id,
					'posts_per_page'         => 1,
					'fields'                 => 'ids',
					'no_found_rows'          => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);
			$count = (int) $q->found_posts;
		}
		wp_cache_set( $key, $count, 'clipto', HOUR_IN_SECONDS );
	}
	return (int) $count;
}

/**
 * Existing child categories of AI Tools (direct children, non-empty, by size).
 *
 * @param int $limit 0 = all.
 * @return WP_Term[]
 */
function clipto_tool_subcategories( $limit = 0 ) {
	$tools = clipto_destination( 'ai-tools' );
	if ( ! $tools ) {
		return array();
	}
	$terms = get_terms(
		array(
			'taxonomy'   => 'category',
			'parent'     => $tools['object']->term_id,
			'hide_empty' => true,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => $limit ? (int) $limit : 0,
		)
	);
	return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * Whether a category is AI Tools or one of its descendants.
 *
 * @param WP_Term|int $term Term or ID.
 * @return bool
 */
function clipto_is_tools_term( $term ) {
	$tools = clipto_destination( 'ai-tools' );
	if ( ! $tools ) {
		return false;
	}
	$term_id = $term instanceof WP_Term ? $term->term_id : (int) $term;
	return $term_id === $tools['object']->term_id || term_is_ancestor_of( $tools['object']->term_id, $term_id, 'category' );
}

/* -------------------------------------------------------------------------
 * Post helpers
 * ---------------------------------------------------------------------- */

/**
 * The most meaningful category of a post: SEO-plugin primary category if set,
 * otherwise the most specific (deepest) assigned category.
 *
 * @param int|WP_Post|null $post Post.
 * @return WP_Term|null
 */
function clipto_primary_category( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return null;
	}

	foreach ( array( '_yoast_wpseo_primary_category', 'rank_math_primary_category' ) as $meta_key ) {
		$primary = (int) get_post_meta( $post->ID, $meta_key, true );
		if ( $primary ) {
			$term = get_term( $primary, 'category' );
			if ( $term && ! is_wp_error( $term ) ) {
				return $term;
			}
		}
	}

	$cats = get_the_category( $post->ID );
	if ( ! $cats ) {
		return null;
	}

	$best       = $cats[0];
	$best_depth = -1;
	foreach ( $cats as $cat ) {
		if ( 'uncategorized' === $cat->slug && count( $cats ) > 1 ) {
			continue;
		}
		$depth = count( get_ancestors( $cat->term_id, 'category', 'taxonomy' ) );
		if ( $depth > $best_depth ) {
			$best       = $cat;
			$best_depth = $depth;
		}
	}
	return $best;
}

/**
 * Estimated reading time in minutes (230 wpm, minimum 1).
 *
 * @param int|WP_Post|null $post Post.
 * @return int
 */
function clipto_reading_time( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return 1;
	}
	$words = str_word_count( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ) );
	return max( 1, (int) round( $words / 230 ) );
}

/**
 * Trimmed excerpt text (plain text, escaped on output by caller).
 *
 * @param int|WP_Post|null $post  Post.
 * @param int              $words Word limit.
 * @return string
 */
function clipto_excerpt( $post = null, $words = 28 ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	$text = has_excerpt( $post ) ? $post->post_excerpt : $post->post_content;
	$text = wp_strip_all_tags( strip_shortcodes( excerpt_remove_blocks( $text ) ) );
	return wp_trim_words( $text, $words, '…' );
}

/**
 * Whether the post was published within the last N hours.
 *
 * @param int|WP_Post|null $post  Post.
 * @param int              $hours Hours.
 * @return bool
 */
function clipto_is_recent( $post = null, $hours = 24 ) {
	$time = get_post_time( 'U', true, $post );
	return $time && ( time() - $time ) < $hours * HOUR_IN_SECONDS;
}

/**
 * Human date: "3 hours ago" within 2 days, otherwise "Sep 24, 2026".
 *
 * @param int|WP_Post|null $post Post.
 * @return string Escaped <time> element.
 */
function clipto_time( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	$ts    = get_post_time( 'U', true, $post );
	$label = ( time() - $ts ) < 2 * DAY_IN_SECONDS
		/* translators: %s: human-readable time difference. */
		? sprintf( __( '%s ago', 'clipto' ), human_time_diff( $ts, time() ) )
		: get_the_date( '', $post );
	return sprintf(
		'<time datetime="%1$s">%2$s</time>',
		esc_attr( get_the_date( DATE_W3C, $post ) ),
		esc_html( $label )
	);
}

/**
 * Tool facts stored on a post (see inc/review-meta.php). Empty values are omitted.
 *
 * Keys: pricing (free|freemium|free-trial|paid), price, best_for, platforms, website, rating (float), verdict.
 *
 * @param int|WP_Post|null $post Post.
 * @return array
 */
function clipto_tool_facts( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return array();
	}
	$facts = array();
	foreach ( array( 'pricing', 'price', 'best_for', 'platforms', 'website', 'rating', 'verdict' ) as $key ) {
		$value = get_post_meta( $post->ID, '_clipto_' . $key, true );
		if ( '' !== $value && null !== $value && false !== $value ) {
			$facts[ $key ] = $value;
		}
	}
	if ( isset( $facts['rating'] ) ) {
		$facts['rating'] = max( 0, min( 5, (float) $facts['rating'] ) );
		if ( ! $facts['rating'] ) {
			unset( $facts['rating'] );
		}
	}
	return $facts;
}

/**
 * Whether a post is a free AI resource (tagged free-ai, or pricing = free).
 *
 * @param int|WP_Post|null $post Post.
 * @return bool
 */
function clipto_is_free( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return false;
	}
	if ( has_tag( 'free-ai', $post ) ) {
		return true;
	}
	$facts = clipto_tool_facts( $post );
	return isset( $facts['pricing'] ) && 'free' === $facts['pricing'];
}

/**
 * Pricing labels.
 *
 * @return array
 */
function clipto_pricing_labels() {
	return array(
		'free'       => __( 'Free', 'clipto' ),
		'freemium'   => __( 'Freemium', 'clipto' ),
		'free-trial' => __( 'Free trial', 'clipto' ),
		'paid'       => __( 'Paid', 'clipto' ),
	);
}

/**
 * Badge markup for a post: Free / pricing, and "Latest" for fresh news when requested.
 *
 * @param int|WP_Post|null $post Post.
 * @param array            $args { latest: bool }.
 * @return string
 */
function clipto_badges( $post = null, $args = array() ) {
	$post   = get_post( $post );
	$badges = array();
	if ( ! $post ) {
		return '';
	}

	if ( ! empty( $args['latest'] ) && clipto_is_recent( $post, 24 ) ) {
		$badges[] = '<span class="badge badge--latest">' . esc_html__( 'Latest', 'clipto' ) . '</span>';
	}

	$facts   = clipto_tool_facts( $post );
	$labels  = clipto_pricing_labels();
	$pricing = isset( $facts['pricing'], $labels[ $facts['pricing'] ] ) ? $facts['pricing'] : '';

	if ( clipto_is_free( $post ) ) {
		$badges[] = '<span class="badge badge--free">' . esc_html( $labels['free'] ) . '</span>';
	} elseif ( $pricing ) {
		$badges[] = '<span class="badge badge--' . esc_attr( $pricing ) . '">' . esc_html( $labels[ $pricing ] ) . '</span>';
	}

	if ( ! $badges ) {
		return '';
	}
	return '<div class="badges">' . implode( '', $badges ) . '</div>';
}

/**
 * Meta row: author · date · reading time.
 *
 * @param int|WP_Post|null $post Post.
 * @param array            $args { author: bool, date: bool, reading: bool, class: string }.
 */
function clipto_meta( $post = null, $args = array() ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	$args  = wp_parse_args(
		$args,
		array(
			'author'  => false,
			'date'    => true,
			'reading' => true,
			'class'   => '',
		)
	);
	$parts = array();
	if ( $args['author'] ) {
		$parts[] = '<span class="meta__author">' . esc_html( get_the_author_meta( 'display_name', $post->post_author ) ) . '</span>';
	}
	if ( $args['date'] ) {
		$parts[] = clipto_time( $post );
	}
	if ( $args['reading'] ) {
		/* translators: %d: minutes. */
		$parts[] = '<span class="meta__reading">' . esc_html( sprintf( _n( '%d min read', '%d min read', clipto_reading_time( $post ), 'clipto' ), clipto_reading_time( $post ) ) ) . '</span>';
	}
	printf(
		'<div class="meta %1$s">%2$s</div>',
		esc_attr( $args['class'] ),
		implode( '', array_map( static function ( $p ) { return '<span>' . $p . '</span>'; }, $parts ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- parts escaped above.
	);
}

/* -------------------------------------------------------------------------
 * Media
 * ---------------------------------------------------------------------- */

/**
 * Crop-frame media for a post: featured image, or a typographic fallback.
 *
 * @param int|WP_Post|null $post Post.
 * @param array            $args {
 *     size:  image size (default clipto-card),
 *     ratio: '16x9'|'3x2'|'4x3'|'1x1'|'4x5' (default 3x2),
 *     sizes: sizes attribute,
 *     eager: bool — LCP image (eager + fetchpriority high),
 *     class: extra class
 * }
 * @return string
 */
function clipto_media( $post = null, $args = array() ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	$args = wp_parse_args(
		$args,
		array(
			'size'  => 'clipto-card',
			'ratio' => '3x2',
			'sizes' => '(min-width: 64em) 33vw, 100vw',
			'eager' => false,
			'class' => '',
		)
	);

	$class = trim( 'clip-frame ratio-' . sanitize_html_class( $args['ratio'] ) . ' ' . $args['class'] );

	if ( has_post_thumbnail( $post ) ) {
		$attr = array(
			'sizes'    => $args['sizes'],
			'loading'  => $args['eager'] ? 'eager' : 'lazy',
			'decoding' => $args['eager'] ? 'sync' : 'async',
			'alt'      => '',
		);
		if ( $args['eager'] ) {
			$attr['fetchpriority'] = 'high';
		}
		$alt = trim( (string) get_post_meta( get_post_thumbnail_id( $post ), '_wp_attachment_image_alt', true ) );
		if ( $alt ) {
			$attr['alt'] = $alt;
		}
		$img = get_the_post_thumbnail( $post, $args['size'], $attr );
		return '<div class="' . esc_attr( $class ) . '">' . $img . '</div>';
	}

	$cat   = clipto_primary_category( $post );
	$label = $cat ? $cat->name : get_bloginfo( 'name' );
	return '<div class="' . esc_attr( $class ) . '"><div class="media-fallback" aria-hidden="true"><span>'
		. clipto_logo_mark( 'media-fallback__mark' )
		. '<span class="media-fallback__label">' . esc_html( $label ) . '</span></span></div></div>';
}

/**
 * Tool logo tile (from the "_clipto_logo_id" attachment set in the Tool facts box).
 * Returns '' when no logo is set so callers can fall back to the featured image.
 *
 * @param int|WP_Post|null $post Post.
 * @param string           $class Extra class.
 * @return string
 */
function clipto_tool_logo( $post = null, $class = '' ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	$logo_id = (int) get_post_meta( $post->ID, '_clipto_logo_id', true );
	if ( ! $logo_id || ! wp_attachment_is_image( $logo_id ) ) {
		return '';
	}
	$img = wp_get_attachment_image(
		$logo_id,
		'clipto-thumb',
		false,
		array(
			'class'    => 'tool-logo__img',
			'alt'      => '',
			'loading'  => 'lazy',
			'decoding' => 'async',
			'sizes'    => '64px',
		)
	);
	return $img ? '<div class="tool-logo ' . esc_attr( $class ) . '">' . $img . '</div>' : '';
}

/* -------------------------------------------------------------------------
 * Cards
 * ---------------------------------------------------------------------- */

/**
 * Render a story card. One renderer, many compositions (DESIGN.md §7).
 *
 * @param int|WP_Post|null $post    Post.
 * @param string           $variant lead|feature|standard|compact|row|tool|numbered.
 * @param array            $args {
 *     heading: h2|h3|h4 (default h3),
 *     eager:   bool (LCP),
 *     excerpt: bool|int (words),
 *     index:   int (numbered variant / reveal stagger),
 *     ratio:   override media ratio,
 *     size:    override image size,
 *     sizes:   override sizes attribute,
 *     latest:  bool (show "Latest" badge when < 24h),
 *     kicker:  bool (default true),
 *     meta:    array args for clipto_meta(),
 *     reveal:  bool (default true),
 *     class:   extra class
 * }
 */
function clipto_card( $post = null, $variant = 'standard', $args = array() ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	$args = wp_parse_args(
		$args,
		array(
			'heading' => 'h3',
			'eager'   => false,
			'excerpt' => null,
			'index'   => 0,
			'ratio'   => null,
			'size'    => null,
			'sizes'   => null,
			'latest'  => false,
			'kicker'  => true,
			'meta'    => array(),
			'reveal'  => true,
			'class'   => '',
		)
	);

	$presets = array(
		'lead'     => array( 'ratio' => '3x2', 'size' => 'clipto-feature', 'sizes' => '(min-width: 64em) 58vw, 100vw', 'excerpt' => 36 ),
		'feature'  => array( 'ratio' => '3x2', 'size' => 'clipto-feature', 'sizes' => '(min-width: 64em) 50vw, 100vw', 'excerpt' => 30 ),
		'standard' => array( 'ratio' => '3x2', 'size' => 'clipto-card', 'sizes' => '(min-width: 64em) 30vw, (min-width: 40em) 50vw, 100vw', 'excerpt' => false ),
		'compact'  => array( 'ratio' => null, 'size' => null, 'sizes' => null, 'excerpt' => false ),
		'row'      => array( 'ratio' => '1x1', 'size' => 'clipto-thumb', 'sizes' => '96px', 'excerpt' => false ),
		'tool'     => array( 'ratio' => '1x1', 'size' => 'clipto-thumb', 'sizes' => '64px', 'excerpt' => 18 ),
		'numbered' => array( 'ratio' => null, 'size' => null, 'sizes' => null, 'excerpt' => false ),
	);
	$variant = isset( $presets[ $variant ] ) ? $variant : 'standard';
	$preset  = $presets[ $variant ];

	foreach ( array( 'ratio', 'size', 'sizes', 'excerpt' ) as $k ) {
		if ( null === $args[ $k ] ) {
			$args[ $k ] = $preset[ $k ];
		}
	}

	$heading = in_array( $args['heading'], array( 'h2', 'h3', 'h4' ), true ) ? $args['heading'] : 'h3';
	$classes = array( 'card', 'card--' . $variant );
	if ( $args['class'] ) {
		$classes[] = $args['class'];
	}

	set_query_var( 'clipto_card', array_merge( $args, array( 'variant' => $variant, 'heading' => $heading, 'classes' => $classes ) ) );
	$GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restored below.
	setup_postdata( $post );
	get_template_part( 'template-parts/cards/card', $variant );
	wp_reset_postdata();
}

/**
 * Kicker link to the primary category (above headlines).
 *
 * @param int|WP_Post|null $post Post.
 * @return string
 */
function clipto_card_kicker( $post = null ) {
	$cat = clipto_primary_category( $post );
	if ( ! $cat ) {
		return '';
	}
	return '<a class="kicker card__kicker" href="' . esc_url( get_category_link( $cat ) ) . '">' . esc_html( $cat->name ) . '</a>';
}

/* -------------------------------------------------------------------------
 * Section header
 * ---------------------------------------------------------------------- */

/**
 * Section header with index numeral, kicker, title, optional description and link.
 *
 * @param array $args {
 *     index: string e.g. '01', kicker: string, title: string (may contain <em>),
 *     desc: string, url: string, link_label: string, id: string (heading id), tag: h2
 * }
 */
function clipto_section_head( $args ) {
	$args = wp_parse_args(
		$args,
		array(
			'index'      => '',
			'kicker'     => '',
			'title'      => '',
			'desc'       => '',
			'url'        => '',
			'link_label' => __( 'View all', 'clipto' ),
			'id'         => '',
			'tag'        => 'h2',
		)
	);
	$tag = in_array( $args['tag'], array( 'h1', 'h2', 'h3' ), true ) ? $args['tag'] : 'h2';
	?>
	<header class="section-head" data-reveal>
		<?php if ( $args['index'] || $args['kicker'] ) : ?>
			<div class="section-head__eyebrow">
				<?php if ( $args['index'] ) : ?>
					<span class="index-num"><?php echo esc_html( $args['index'] ); ?></span>
				<?php endif; ?>
				<?php if ( $args['kicker'] ) : ?>
					<span class="kicker"><?php echo esc_html( $args['kicker'] ); ?></span>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<<?php echo esc_html( $tag ); ?> class="section-head__title"<?php echo $args['id'] ? ' id="' . esc_attr( $args['id'] ) . '"' : ''; ?>>
			<?php echo wp_kses( $args['title'], array( 'em' => array(), 'span' => array( 'class' => true ) ) ); ?>
		</<?php echo esc_html( $tag ); ?>>
		<?php if ( $args['desc'] ) : ?>
			<p class="section-head__desc"><?php echo esc_html( $args['desc'] ); ?></p>
		<?php endif; ?>
		<?php if ( $args['url'] ) : ?>
			<a class="link-arrow section-head__link" href="<?php echo esc_url( $args['url'] ); ?>">
				<?php echo esc_html( $args['link_label'] ); ?>
				<?php clipto_the_icon( 'arrow-right' ); ?>
			</a>
		<?php endif; ?>
	</header>
	<?php
}

/* -------------------------------------------------------------------------
 * Homepage de-duplication
 * ---------------------------------------------------------------------- */

/**
 * Remember post IDs already shown on the page so later sections skip them.
 *
 * @param int[]|int|null $ids IDs to add; null to just read.
 * @return int[]
 */
function clipto_shown( $ids = null ) {
	static $shown = array();
	if ( null !== $ids ) {
		$shown = array_values( array_unique( array_merge( $shown, array_map( 'intval', (array) $ids ) ) ) );
	}
	return $shown;
}

/**
 * Lightweight post query with sensible defaults and homepage de-duplication.
 *
 * @param array $args  WP_Query args.
 * @param bool  $dedupe Exclude posts already shown & mark results as shown.
 * @return WP_Post[]
 */
function clipto_posts( $args = array(), $dedupe = true ) {
	$args = wp_parse_args(
		$args,
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 6,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
	if ( $dedupe && clipto_shown() ) {
		$args['post__not_in'] = array_merge( isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array(), clipto_shown() );
	}
	$q = new WP_Query( $args );
	if ( $dedupe && $q->posts ) {
		clipto_shown( wp_list_pluck( $q->posts, 'ID' ) );
	}
	return $q->posts;
}

/* -------------------------------------------------------------------------
 * Brand
 * ---------------------------------------------------------------------- */

/**
 * Site logo: custom logo if set (proportionally constrained), otherwise the Clipto
 * crop-mark + wordmark.
 *
 * @param array $args { class: string, tag: 'a' }.
 */
function clipto_brand( $args = array() ) {
	$class = isset( $args['class'] ) ? $args['class'] : '';
	$name  = get_bloginfo( 'name' );
	echo '<a class="brand ' . esc_attr( $class ) . '" href="' . esc_url( home_url( '/' ) ) . '" rel="home">';
	if ( has_custom_logo() ) {
		$logo_id = get_theme_mod( 'custom_logo' );
		echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core escapes.
			$logo_id,
			'medium',
			false,
			array(
				'class'   => 'brand__logo',
				'alt'     => $name,
				'loading' => 'eager',
			)
		);
	} else {
		echo clipto_logo_mark( 'brand__mark' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup.
		echo '<span class="brand__word">' . esc_html( $name ) . '</span>';
	}
	echo '</a>';
}

/* -------------------------------------------------------------------------
 * Breadcrumbs & pagination
 * ---------------------------------------------------------------------- */

/**
 * Breadcrumb trail (Home / Category ancestors / Category).
 *
 * @param int|WP_Post|null $post Post (singular) or null for archives.
 */
function clipto_breadcrumbs( $post = null ) {
	$items = array(
		array( 'label' => __( 'Home', 'clipto' ), 'url' => home_url( '/' ) ),
	);

	$term = null;
	if ( is_singular( 'post' ) ) {
		$term = clipto_primary_category( $post );
	} elseif ( is_category() ) {
		$term = get_queried_object();
	}

	if ( $term instanceof WP_Term ) {
		$ancestors = array_reverse( get_ancestors( $term->term_id, 'category', 'taxonomy' ) );
		foreach ( $ancestors as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, 'category' );
			if ( $ancestor && ! is_wp_error( $ancestor ) ) {
				$items[] = array( 'label' => $ancestor->name, 'url' => get_category_link( $ancestor ) );
			}
		}
		if ( is_singular() ) {
			$items[] = array( 'label' => $term->name, 'url' => get_category_link( $term ) );
		} else {
			$items[] = array( 'label' => $term->name, 'url' => '' );
		}
	} elseif ( is_page() ) {
		$page      = get_post( $post );
		$ancestors = $page ? array_reverse( get_post_ancestors( $page ) ) : array();
		foreach ( $ancestors as $ancestor_id ) {
			$items[] = array( 'label' => get_the_title( $ancestor_id ), 'url' => get_permalink( $ancestor_id ) );
		}
	}
	?>
	<nav class="breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'clipto' ); ?>">
		<ol class="breadcrumbs__list" role="list">
			<?php foreach ( $items as $i => $item ) : ?>
				<li class="breadcrumbs__item">
					<?php if ( $item['url'] ) : ?>
						<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
					<?php else : ?>
						<span aria-current="page"><?php echo esc_html( $item['label'] ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</nav>
	<?php
}

/**
 * Numbered archive pagination.
 */
function clipto_pagination() {
	the_posts_pagination(
		array(
			'mid_size'           => 1,
			'prev_text'          => clipto_icon( 'arrow-left' ) . '<span>' . esc_html__( 'Newer', 'clipto' ) . '</span>',
			'next_text'          => '<span>' . esc_html__( 'Older', 'clipto' ) . '</span>' . clipto_icon( 'arrow-right' ),
			'before_page_number' => '<span class="screen-reader-text">' . esc_html__( 'Page', 'clipto' ) . ' </span>',
			'class'              => 'pagination',
		)
	);
}

/**
 * Search form markup used across the theme.
 *
 * @param array $args { id: string (input id), placeholder: string, button: bool, autofocus: bool, class: string }.
 */
function clipto_search_form( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'id'          => 'search-' . wp_unique_id(),
			'placeholder' => __( 'Search AI tools, guides and news', 'clipto' ),
			'button'      => true,
			'autofocus'   => false,
			'class'       => '',
		)
	);
	?>
	<form role="search" method="get" class="search-form <?php echo esc_attr( $args['class'] ); ?>" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label class="screen-reader-text" for="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Search Clipto', 'clipto' ); ?></label>
		<?php clipto_the_icon( 'search', array( 'class' => 'search-form__icon' ) ); ?>
		<input class="field search-form__input" type="search" id="<?php echo esc_attr( $args['id'] ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>" autocomplete="off" enterkeyhint="search"<?php echo $args['autofocus'] ? ' autofocus' : ''; ?> />
		<?php if ( $args['button'] ) : ?>
			<button class="btn search-form__submit" type="submit"><span class="btn__label"><?php esc_html_e( 'Search', 'clipto' ); ?></span><?php clipto_the_icon( 'arrow-right' ); ?></button>
		<?php endif; ?>
	</form>
	<?php
}
