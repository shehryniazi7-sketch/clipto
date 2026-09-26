<?php
/**
 * Archive helpers — shared by category.php, tag.php, archive.php, author.php,
 * search.php, 404.php and index.php (each loads this file with require_once).
 *
 * Every function is prefixed and guarded so a child theme can replace it.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'clipto_archive_num' ) ) :
	/**
	 * Zero-padded index numeral ("01").
	 *
	 * @param int $n Number.
	 * @return string
	 */
	function clipto_archive_num( $n ) {
		return str_pad( (string) (int) $n, 2, '0', STR_PAD_LEFT );
	}
endif;

if ( ! function_exists( 'clipto_archive_dest_term_is' ) ) :
	/**
	 * Whether a term is a destination's term or one of its descendants.
	 *
	 * @param WP_Term $term Term.
	 * @param string  $key  Destination key.
	 * @return bool
	 */
	function clipto_archive_dest_term_is( $term, $key ) {
		$dest = clipto_destination( $key );
		if ( ! $dest || ! ( $term instanceof WP_Term ) || ! ( $dest['object'] instanceof WP_Term ) ) {
			return false;
		}
		$root = $dest['object'];
		if ( $root->taxonomy !== $term->taxonomy ) {
			return false;
		}
		if ( (int) $root->term_id === (int) $term->term_id ) {
			return true;
		}
		return is_taxonomy_hierarchical( $term->taxonomy ) && term_is_ancestor_of( $root->term_id, $term->term_id, $term->taxonomy );
	}
endif;

if ( ! function_exists( 'clipto_archive_variant' ) ) :
	/**
	 * Which archive composition the current request gets.
	 *
	 * @return string tools|news|earn|free|standard
	 */
	function clipto_archive_variant() {
		$term = get_queried_object();
		if ( is_category() && $term instanceof WP_Term ) {
			if ( clipto_is_tools_term( $term ) ) {
				return 'tools';
			}
			if ( clipto_archive_dest_term_is( $term, 'ai-news' ) ) {
				return 'news';
			}
			if ( clipto_archive_dest_term_is( $term, 'earn-with-ai' ) ) {
				return 'earn';
			}
		}
		if ( is_tag() && $term instanceof WP_Term && clipto_archive_dest_term_is( $term, 'free-ai' ) ) {
			return 'free';
		}
		return 'standard';
	}
endif;

if ( ! function_exists( 'clipto_archive_dest_index' ) ) :
	/**
	 * The destination's position in the site index ("01" … "05"), matching the
	 * order used across the theme. Empty when the destination does not exist.
	 *
	 * @param string $key Destination key.
	 * @return string
	 */
	function clipto_archive_dest_index( $key ) {
		$pos = array_search( $key, array_keys( clipto_destinations() ), true );
		return false === $pos ? '' : clipto_archive_num( $pos + 1 );
	}
endif;

if ( ! function_exists( 'clipto_archive_latest_post' ) ) :
	/**
	 * Most recent published post in a context (term incl. children, or author).
	 * Used for the honest "Updated" stat.
	 *
	 * @param array $args { term: WP_Term, author: int }.
	 * @return WP_Post|null
	 */
	function clipto_archive_latest_post( $args = array() ) {
		$query = array(
			'post_type'              => 'post',
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);
		if ( ! empty( $args['term'] ) && $args['term'] instanceof WP_Term ) {
			$query['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy'         => $args['term']->taxonomy,
					'field'            => 'term_id',
					'terms'            => (int) $args['term']->term_id,
					'include_children' => true,
				),
			);
		} elseif ( ! empty( $args['author'] ) ) {
			$query['author'] = (int) $args['author'];
		}
		$q = new WP_Query( $query );
		return $q->posts ? $q->posts[0] : null;
	}
endif;

if ( ! function_exists( 'clipto_archive_count_label' ) ) :
	/**
	 * "1 article" / "24 articles" (number formatted, escaped by caller).
	 *
	 * @param int    $count Count.
	 * @param string $noun  'article'|'result'|'tool'.
	 * @return array { value: string, label: string }
	 */
	function clipto_archive_count_label( $count, $noun = 'article' ) {
		$count = (int) $count;
		switch ( $noun ) {
			case 'result':
				$label = _n( 'Result', 'Results', $count, 'clipto' );
				break;
			case 'category':
				$label = _n( 'Category', 'Categories', $count, 'clipto' );
				break;
			default:
				$label = _n( 'Article', 'Articles', $count, 'clipto' );
		}
		return array(
			'value' => number_format_i18n( $count ),
			'label' => $label,
		);
	}
endif;

if ( ! function_exists( 'clipto_archive_page_stat' ) ) :
	/**
	 * "Page 2 of 5" stat for paged archives, or null on page 1.
	 *
	 * @return array|null
	 */
	function clipto_archive_page_stat() {
		global $wp_query;
		$paged = max( 1, (int) get_query_var( 'paged' ) );
		$max   = isset( $wp_query->max_num_pages ) ? (int) $wp_query->max_num_pages : 1;
		if ( $paged < 2 || $max < 2 ) {
			return null;
		}
		return array(
			/* translators: 1: current page, 2: total pages. */
			'value' => sprintf( __( '%1$s / %2$s', 'clipto' ), number_format_i18n( $paged ), number_format_i18n( $max ) ),
			'label' => __( 'Page', 'clipto' ),
		);
	}
endif;

if ( ! function_exists( 'clipto_archive_range_label' ) ) :
	/**
	 * Where the current page sits in the whole list: "11–20 of 31" when the list runs to
	 * more than one page, otherwise the plain count ("16 results").
	 *
	 * @param string $noun 'result' or 'article'.
	 * @return string Plain text.
	 */
	function clipto_archive_range_label( $noun = 'article' ) {
		global $wp_query;
		$found = (int) $wp_query->found_posts;
		$shown = (int) $wp_query->post_count;
		if ( ! $found || ! $shown ) {
			return '';
		}
		if ( (int) $wp_query->max_num_pages < 2 ) {
			return 'result' === $noun
				/* translators: %s: number of search results. */
				? sprintf( _n( '%s result', '%s results', $found, 'clipto' ), number_format_i18n( $found ) )
				/* translators: %s: number of articles. */
				: sprintf( _n( '%s article', '%s articles', $found, 'clipto' ), number_format_i18n( $found ) );
		}
		$per   = max( 1, (int) $wp_query->get( 'posts_per_page' ) );
		$first = ( max( 1, (int) get_query_var( 'paged' ) ) - 1 ) * $per + 1;
		return sprintf(
			/* translators: 1: first item on this page, 2: last item on this page, 3: total. */
			__( '%1$s–%2$s of %3$s', 'clipto' ),
			number_format_i18n( $first ),
			number_format_i18n( $first + $shown - 1 ),
			number_format_i18n( $found )
		);
	}
endif;

if ( ! function_exists( 'clipto_archive_updated_stat' ) ) :
	/**
	 * "Updated" stat from the latest post date, or null.
	 *
	 * @param WP_Post|null $post Latest post.
	 * @return array|null
	 */
	function clipto_archive_updated_stat( $post ) {
		if ( ! $post instanceof WP_Post ) {
			return null;
		}
		return array(
			'value' => clipto_time( $post ), // Escaped <time>.
			'label' => __( 'Updated', 'clipto' ),
			'html'  => true,
		);
	}
endif;

if ( ! function_exists( 'clipto_archive_breadcrumbs' ) ) :
	/**
	 * Breadcrumbs for any archive context. Category trails use the shared helper;
	 * other contexts get Home / {current}. Same markup and classes either way.
	 *
	 * @param string $current Label of the current page (non-category contexts).
	 */
	function clipto_archive_breadcrumbs( $current = '' ) {
		if ( is_category() ) {
			clipto_breadcrumbs();
			return;
		}
		?>
		<nav class="breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'clipto' ); ?>">
			<ol class="breadcrumbs__list" role="list">
				<li class="breadcrumbs__item"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'clipto' ); ?></a></li>
				<?php if ( '' !== $current ) : ?>
					<li class="breadcrumbs__item"><span aria-current="page"><?php echo esc_html( $current ); ?></span></li>
				<?php endif; ?>
			</ol>
		</nav>
		<?php
	}
endif;

if ( ! function_exists( 'clipto_archive_plain' ) ) :
	/**
	 * Plain text from a (possibly HTML) description, trimmed to N words.
	 *
	 * @param string $html  HTML.
	 * @param int    $words Word limit (0 = no trim).
	 * @return string
	 */
	function clipto_archive_plain( $html, $words = 0 ) {
		$text = trim( wp_strip_all_tags( (string) $html ) );
		$text = html_entity_decode( $text, ENT_QUOTES, get_bloginfo( 'charset' ) );
		return $words ? wp_trim_words( $text, $words, '…' ) : $text;
	}
endif;

if ( ! function_exists( 'clipto_destination_blurb' ) ) :
	/**
	 * One-line description for a destination: its live term description / page excerpt,
	 * or a short editorial fallback.
	 *
	 * @param array $dest Destination from clipto_destinations().
	 * @return string Plain text.
	 */
	function clipto_destination_blurb( $dest ) {
		$text = '';
		if ( ! empty( $dest['description'] ) ) {
			$text = clipto_archive_plain( $dest['description'], 18 );
		} elseif ( 'page' === $dest['type'] && $dest['object'] instanceof WP_Post ) {
			$text = clipto_excerpt( $dest['object'], 18 );
		}
		if ( '' !== $text ) {
			return $text;
		}
		$fallback = array(
			'ai-tools'     => __( 'AI tools organised by the job to be done, with pricing and best-for notes.', 'clipto' ),
			'ai-news'      => __( 'The developments in AI that matter, reported clearly.', 'clipto' ),
			'earn-with-ai' => __( 'Practical ways to build and grow your work with AI.', 'clipto' ),
			'free-ai'      => __( 'Capable AI tools you can start using without paying.', 'clipto' ),
			'ai-guide'     => __( 'Our pillar guide to artificial intelligence. Start here.', 'clipto' ),
		);
		return isset( $fallback[ $dest['key'] ] ) ? $fallback[ $dest['key'] ] : '';
	}
endif;

if ( ! function_exists( 'clipto_archive_day_label' ) ) :
	/**
	 * Day heading for the news stream: "Today", "Yesterday" or a date (site timezone).
	 *
	 * @param WP_Post $post Post.
	 * @return array { key: Y-m-d, label: string, date: string (long date), datetime: string, relative: bool (Today/Yesterday) }
	 */
	function clipto_archive_day_label( $post ) {
		$key       = get_the_date( 'Y-m-d', $post );
		$today     = current_time( 'Y-m-d' );
		$yesterday = wp_date( 'Y-m-d', time() - DAY_IN_SECONDS );
		$same_year = get_the_date( 'Y', $post ) === current_time( 'Y' );
		$long      = get_the_date( $same_year ? _x( 'l, F j', 'news day heading date format', 'clipto' ) : _x( 'l, F j, Y', 'news day heading date format with year', 'clipto' ), $post );

		if ( $key === $today ) {
			$label = __( 'Today', 'clipto' );
		} elseif ( $key === $yesterday ) {
			$label = __( 'Yesterday', 'clipto' );
		} else {
			$label = get_the_date( $same_year ? _x( 'M j', 'news day label format', 'clipto' ) : _x( 'M j, Y', 'news day label format with year', 'clipto' ), $post );
			$long  = get_the_date( _x( 'l', 'weekday format', 'clipto' ), $post );
		}

		return array(
			'key'      => $key,
			'label'    => $label,
			'date'     => $long,
			'datetime' => $key,
			'relative' => $key === $today || $key === $yesterday,
		);
	}
endif;

if ( ! function_exists( 'clipto_search_terms' ) ) :
	/**
	 * The words of the current search, as WordPress parsed them (≥ 2 characters,
	 * longest first so overlapping matches prefer the longer word).
	 *
	 * @return string[]
	 */
	function clipto_search_terms() {
		$terms = get_query_var( 'search_terms' );
		if ( ! is_array( $terms ) || ! $terms ) {
			$raw   = trim( (string) get_search_query( false ) );
			$terms = '' === $raw ? array() : preg_split( '/\s+/u', $raw );
		}
		$terms = array_filter(
			array_map( 'trim', (array) $terms ),
			static function ( $t ) {
				return function_exists( 'mb_strlen' ) ? mb_strlen( $t ) >= 2 : strlen( $t ) >= 2;
			}
		);
		$terms = array_values( array_unique( $terms ) );
		usort(
			$terms,
			static function ( $a, $b ) {
				return strlen( $b ) - strlen( $a );
			}
		);
		return array_slice( $terms, 0, 8 );
	}
endif;

if ( ! function_exists( 'clipto_highlight' ) ) :
	/**
	 * Escape a plain string and wrap case-insensitive matches of the search terms in
	 * <mark>. The text is split on the matches first and every piece is escaped on its
	 * own, so neither the text nor the query can inject markup or break an entity.
	 *
	 * @param string   $text  Plain text (entities allowed; they are decoded first).
	 * @param string[] $terms Search terms.
	 * @return string Safe HTML.
	 */
	function clipto_highlight( $text, $terms ) {
		$text = html_entity_decode( (string) $text, ENT_QUOTES, 'UTF-8' );
		if ( ! $terms ) {
			return esc_html( $text );
		}
		// Short words (≤ 3 characters, e.g. "AI") only match at the start of a word, so
		// "AI" never lights up the middle of "maintain".
		$alts    = array_map(
			static function ( $t ) {
				$len = function_exists( 'mb_strlen' ) ? mb_strlen( $t ) : strlen( $t );
				return ( $len <= 3 ? '(?<![\\p{L}\\p{N}])' : '' ) . preg_quote( $t, '/' );
			},
			$terms
		);
		$pattern = '/(' . implode( '|', $alts ) . ')/iu';
		$parts   = preg_split( $pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE );
		if ( false === $parts ) {
			return esc_html( $text );
		}
		$out = '';
		foreach ( $parts as $i => $part ) {
			if ( '' === $part ) {
				continue;
			}
			$out .= ( $i % 2 ) ? '<mark class="search-hit">' . esc_html( $part ) . '</mark>' : esc_html( $part );
		}
		return $out;
	}
endif;

if ( ! function_exists( 'clipto_card_highlighted' ) ) :
	/**
	 * Render a story card through the shared renderer, then swap its (escaped) title and
	 * excerpt text for highlighted versions. If the card markup ever changes shape the
	 * replacement simply does not match and the plain card is printed.
	 *
	 * @param WP_Post  $post    Post.
	 * @param string   $variant Card variant.
	 * @param array    $args    clipto_card() args.
	 * @param string[] $terms   Search terms.
	 */
	function clipto_card_highlighted( $post, $variant, $args, $terms ) {
		ob_start();
		clipto_card( $post, $variant, $args );
		$html = (string) ob_get_clean();

		if ( $terms ) {
			$title = clipto_highlight( get_the_title( $post ), $terms );
			$html  = preg_replace_callback(
				'#(<span class="headline-link">)[^<]*(</span>)#',
				static function ( $m ) use ( $title ) {
					return $m[1] . $title . $m[2];
				},
				$html,
				1
			);
			if ( ! empty( $args['excerpt'] ) ) {
				$excerpt = clipto_highlight( clipto_excerpt( $post, (int) $args['excerpt'] ), $terms );
				$html    = preg_replace_callback(
					'#(<p class="card__excerpt">)[^<]*(</p>)#',
					static function ( $m ) use ( $excerpt ) {
						return $m[1] . $excerpt . $m[2];
					},
					$html,
					1
				);
			}
		}

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- card output is escaped by the renderer; replacements are built by clipto_highlight(), which escapes every piece.
	}
endif;

if ( ! function_exists( 'clipto_author_avatar' ) ) :
	/**
	 * Author portrait. Uses the avatar only when it is served from this site (local
	 * avatars via a plugin or filter) so author pages never add a third-party request;
	 * otherwise a typographic monogram.
	 *
	 * @param int $user_id User ID.
	 * @param int $size    CSS pixel size.
	 * @return string HTML.
	 */
	function clipto_author_avatar( $user_id, $size = 96 ) {
		$user_id = (int) $user_id;
		$name    = get_the_author_meta( 'display_name', $user_id );
		$html    = '';

		if ( get_option( 'show_avatars' ) ) {
			$url  = get_avatar_url( $user_id, array( 'size' => $size * 2 ) );
			$host = wp_parse_url( home_url(), PHP_URL_HOST );
			if ( $url && ( wp_parse_url( $url, PHP_URL_HOST ) === $host || 0 === strpos( $url, '/' ) ) ) {
				$html = get_avatar(
					$user_id,
					$size,
					'',
					'',
					array(
						'class'         => 'author-avatar__img',
						'loading'       => 'eager',
						'force_display' => true,
					)
				);
			}
		}

		if ( ! $html ) {
			$words    = preg_split( '/\s+/u', trim( (string) $name ) );
			$initials = '';
			foreach ( array_slice( array_filter( (array) $words ), 0, 2 ) as $w ) {
				$initials .= function_exists( 'mb_substr' ) ? mb_substr( $w, 0, 1 ) : substr( $w, 0, 1 );
			}
			$html = '<span class="author-avatar__mono" aria-hidden="true">' . esc_html( strtoupper( $initials ) ) . '</span>';
		}

		/**
		 * Filter the author portrait markup on author archives.
		 *
		 * @param string $html    Markup.
		 * @param int    $user_id User ID.
		 * @param int    $size    Size.
		 */
		return (string) apply_filters( 'clipto_author_avatar', $html, $user_id, $size );
	}
endif;

if ( ! function_exists( 'clipto_author_topics' ) ) :
	/**
	 * Categories an author writes about most (from their recent posts). Real data only.
	 *
	 * @param int $user_id User ID.
	 * @param int $limit   Max terms.
	 * @return WP_Term[]
	 */
	function clipto_author_topics( $user_id, $limit = 4 ) {
		$ids = get_posts(
			array(
				'author'                 => (int) $user_id,
				'post_type'              => 'post',
				'post_status'            => 'publish',
				'posts_per_page'         => 60,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
			)
		);
		if ( ! $ids ) {
			return array();
		}
		$tally = array();
		$terms = array();
		foreach ( $ids as $id ) {
			$cat = clipto_primary_category( $id );
			if ( ! $cat || 'uncategorized' === $cat->slug ) {
				continue;
			}
			$tally[ $cat->term_id ] = isset( $tally[ $cat->term_id ] ) ? $tally[ $cat->term_id ] + 1 : 1;
			$terms[ $cat->term_id ] = $cat;
		}
		arsort( $tally );
		$out = array();
		foreach ( array_slice( array_keys( $tally ), 0, $limit ) as $term_id ) {
			$out[] = $terms[ $term_id ];
		}
		return $out;
	}
endif;

if ( ! function_exists( 'clipto_archive_tool_chips' ) ) :
	/**
	 * Chip items for the AI Tools discovery nav: "All" + every existing subcategory.
	 * The current page gets aria-current; an ancestor of the current term is marked active.
	 *
	 * @param WP_Term $current Current term.
	 * @return array[] { label, sr (screen-reader suffix), url, count, current: bool, active: bool }
	 */
	function clipto_archive_tool_chips( $current ) {
		$tools = clipto_destination( 'ai-tools' );
		if ( ! $tools ) {
			return array();
		}
		$subs = clipto_tool_subcategories();
		if ( ! $subs ) {
			return array();
		}
		$current_id = $current instanceof WP_Term ? (int) $current->term_id : 0;
		$chips      = array(
			array(
				/* translators: Chip for every AI tool, shown before the subcategory chips. */
				'label'   => _x( 'All', 'all AI tools chip', 'clipto' ),
				'sr'      => __( 'AI tools', 'clipto' ),
				'url'     => $tools['url'],
				'count'   => (int) $tools['count'],
				'current' => (int) $tools['object']->term_id === $current_id,
				'active'  => false,
			),
		);
		foreach ( $subs as $sub ) {
			$link = get_term_link( $sub );
			if ( is_wp_error( $link ) ) {
				continue;
			}
			$chips[] = array(
				'label'   => $sub->name,
				'url'     => $link,
				'count'   => clipto_term_post_count( $sub ),
				'current' => (int) $sub->term_id === $current_id,
				'active'  => $current_id && (int) $sub->term_id !== $current_id && term_is_ancestor_of( $sub->term_id, $current_id, 'category' ),
			);
		}
		return $chips;
	}
endif;

if ( ! function_exists( 'clipto_archive_term_head_args' ) ) :
	/**
	 * Header args for a category/tag archive, per composition.
	 *
	 * @param string $variant tools|news|earn|free|standard.
	 * @return array
	 */
	function clipto_archive_term_head_args( $variant ) {
		global $wp_query;
		$term = get_queried_object();
		if ( ! $term instanceof WP_Term ) {
			return array( 'variant' => $variant, 'title' => get_the_archive_title() );
		}

		$dest_keys = array(
			'tools' => 'ai-tools',
			'news'  => 'ai-news',
			'earn'  => 'earn-with-ai',
			'free'  => 'free-ai',
		);
		$kickers   = array(
			'tools' => __( 'Tool discovery', 'clipto' ),
			'news'  => __( 'News & analysis', 'clipto' ),
			'earn'  => __( 'Practical playbooks', 'clipto' ),
			'free'  => '',
		);

		$args = array(
			'variant' => $variant,
			'crumb'   => $term->name,
			'title'   => $term->name,
			'desc'    => term_description( $term ),
			'index'   => isset( $dest_keys[ $variant ] ) ? clipto_archive_dest_index( $dest_keys[ $variant ] ) : '',
			'kicker'  => isset( $kickers[ $variant ] ) ? $kickers[ $variant ] : '',
		);

		// Generic archives: the parent category is the kicker, otherwise the taxonomy.
		if ( 'standard' === $variant ) {
			if ( $term->parent && is_taxonomy_hierarchical( $term->taxonomy ) ) {
				$parent = get_term( $term->parent, $term->taxonomy );
				if ( $parent && ! is_wp_error( $parent ) ) {
					$args['kicker']     = $parent->name;
					$args['kicker_url'] = get_term_link( $parent );
				}
			}
			if ( ! $args['kicker'] ) {
				$args['kicker'] = 'post_tag' === $term->taxonomy ? __( 'Topic', 'clipto' ) : __( 'Section', 'clipto' );
			}
		}

		if ( 'free' === $variant ) {
			$args['badge'] = '<span class="badge badge--free">' . esc_html__( 'Free', 'clipto' ) . '</span>';
			$args['note']  = __( 'Each entry shows its pricing: Free means no paid plan is needed, Freemium means a free tier with paid upgrades.', 'clipto' );
		}

		// An empty archive says so in its body; a "0 articles" stat would only repeat it.
		$args['stats'] = array(
			$wp_query->found_posts ? clipto_archive_count_label( $wp_query->found_posts ) : null,
		);
		if ( 'tools' === $variant && ! $term->parent ) {
			$subs = clipto_tool_subcategories();
			if ( $subs ) {
				$args['stats'][] = clipto_archive_count_label( count( $subs ), 'category' );
			}
		}
		$args['stats'][] = clipto_archive_updated_stat( clipto_archive_latest_post( array( 'term' => $term ) ) );
		// The tool directory's label already says "11–20 of 31" on later pages.
		if ( 'tools' !== $variant ) {
			$args['stats'][] = clipto_archive_page_stat();
		}

		if ( 'earn' === $variant && ! is_paged() && have_posts() ) {
			$args['class'] = 'has-overlap';
		}

		if ( 'tools' === $variant ) {
			$args['chips']       = clipto_archive_tool_chips( $term );
			$args['chips_label'] = __( 'AI Tools categories', 'clipto' );
		}

		return $args;
	}
endif;

if ( ! function_exists( 'clipto_archive_promote_tool_feature' ) ) :
	/**
	 * Pick the AI Tools featured slot from the main query's own first page: the newest
	 * post with tool facts (pricing) and a featured image, else one with facts and a
	 * logo, else the newest post. The pick is moved to the front of the page's posts,
	 * so the loop renders it once (as the feature) and the grid gets the rest — page
	 * sizes, found_posts and pagination are untouched.
	 *
	 * @param WP_Query $query Main query (page 1, before the loop starts).
	 * @return void
	 */
	function clipto_archive_promote_tool_feature( $query ) {
		if ( ! $query instanceof WP_Query || $query->current_post > -1 || count( $query->posts ) < 2 ) {
			return;
		}
		$with_logo = null;
		$pick      = null;
		foreach ( $query->posts as $i => $item ) {
			$item = get_post( $item );
			if ( ! $item || '' === (string) get_post_meta( $item->ID, '_clipto_pricing', true ) ) {
				continue;
			}
			if ( has_post_thumbnail( $item ) ) {
				$pick = $i;
				break;
			}
			if ( null === $with_logo && (int) get_post_meta( $item->ID, '_clipto_logo_id', true ) ) {
				$with_logo = $i;
			}
		}
		if ( null === $pick ) {
			$pick = $with_logo;
		}
		if ( ! $pick ) {
			return; // Nothing better than the newest post, or it already leads.
		}
		$posts = $query->posts;
		$lead  = $posts[ $pick ];
		unset( $posts[ $pick ] );
		array_unshift( $posts, $lead );
		$query->posts = array_values( $posts );
		$query->post  = $query->posts[0];
	}
endif;

if ( ! function_exists( 'clipto_archive_row_pattern' ) ) :
	/**
	 * Row plan for an editorial grid of pairs (half width) and triples (third width)
	 * that always fills its rows: no lone card at the end of a page.
	 *
	 *   remainder 0 → triples only
	 *   remainder 2 → one pair (first, or last on later pages) + triples
	 *   remainder 1 → two pairs (first and last, or both last on later pages) + triples
	 *
	 * @param int  $count     Number of cards.
	 * @param bool $lead_pair Open with a pair (page 1, under the feature).
	 * @return array[] One { span: 'pair'|'triple', pos: 1-based position in its row } per card.
	 */
	function clipto_archive_row_pattern( $count, $lead_pair = true ) {
		$count = max( 0, (int) $count );
		if ( $count < 3 ) {
			$rows = $count ? array( $count ) : array();
		} else {
			$pairs = array( 0, 2, 1 ); // Pairs needed for a remainder of 0, 1 or 2.
			$pair  = $pairs[ $count % 3 ];
			$rows = array_fill( 0, (int) ( ( $count - 2 * $pair ) / 3 ), 3 );
			if ( $pair ) {
				if ( $lead_pair ) {
					array_unshift( $rows, 2 );
					if ( 2 === $pair ) {
						$rows[] = 2;
					}
				} else {
					$rows = array_merge( $rows, array_fill( 0, $pair, 2 ) );
				}
			}
		}
		$plan = array();
		foreach ( $rows as $size ) {
			for ( $pos = 1; $pos <= $size; $pos++ ) {
				$plan[] = array(
					'span' => 3 === $size ? 'triple' : 'pair',
					'pos'  => $pos,
				);
			}
		}
		return $plan;
	}
endif;

if ( ! function_exists( 'clipto_archive_kicker' ) ) :
	/**
	 * Whether a card in the current archive should show its category kicker. It is
	 * redundant (and hidden) when the post's primary category is the archive's own term.
	 *
	 * @param int|WP_Post $post Post.
	 * @return bool
	 */
	function clipto_archive_kicker( $post ) {
		$term = get_queried_object();
		if ( ! is_category() || ! $term instanceof WP_Term ) {
			return true;
		}
		$cat = clipto_primary_category( $post );
		return ! $cat || (int) $cat->term_id !== (int) $term->term_id;
	}
endif;
