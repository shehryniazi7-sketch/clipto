<?php
/**
 * Heading anchors + table of contents.
 *
 * On singular views, h2/h3 headings in the main post content receive stable, unique ids
 * (existing ids are always kept, so author-made anchors never break). The table of contents
 * is computed by the very same routine, so its links always match the rendered headings.
 *
 * Public API:
 *   clipto_heading_id( $text )          Slug for a heading text (sanitize_title based, UTF-8 aware).
 *   clipto_get_toc( $post )             [ [ 'id', 'text', 'level' ], … ] for a post's content.
 *   clipto_toc_tree( $toc )             Nests h3 entries under their h2 ('children').
 *   clipto_toc_min_headings()           Minimum number of headings before a TOC is shown (3).
 *   clipto_toc_process( $html )         [ 'html' => string, 'toc' => array ] — the shared routine.
 *
 * A heading can opt out of the TOC (it still gets an id) with the class "no-toc". Headings
 * that directly label a Pros / Cons list (35-blocks.css draws them as small-caps list
 * labels, not section headings) are left out automatically.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'clipto_heading_id' ) ) {
	/**
	 * Slug for a heading: sanitize_title() for latin text; a readable Unicode slug for
	 * non-latin text (instead of a percent-encoded one).
	 *
	 * @param string $text Heading text or HTML.
	 * @return string Never empty.
	 */
	function clipto_heading_id( $text ) {
		$text = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = trim( (string) preg_replace( '/\s+/u', ' ', $text ) );

		$slug = sanitize_title( $text );

		if ( false !== strpos( $slug, '%' ) ) {
			// Non-ASCII letters survived as %xx sequences: build a readable Unicode slug instead.
			$plain = remove_accents( $text );
			$plain = function_exists( 'mb_strtolower' ) ? mb_strtolower( $plain, 'UTF-8' ) : strtolower( $plain );
			$slug  = (string) preg_replace( '/[^\p{L}\p{N}]+/u', '-', $plain );
			$slug  = trim( $slug, '-' );
		}

		if ( function_exists( 'mb_substr' ) ) {
			$slug = mb_substr( $slug, 0, 72, 'UTF-8' );
		} else {
			$slug = substr( $slug, 0, 72 );
		}
		$slug = trim( $slug, '-' );

		if ( '' === $slug ) {
			$slug = 'section';
		}

		/**
		 * Filter the generated heading id (before de-duplication).
		 *
		 * @param string $slug Slug.
		 * @param string $text Plain heading text.
		 */
		return (string) apply_filters( 'clipto_heading_id', $slug, $text );
	}
}

if ( ! function_exists( 'clipto_toc_min_headings' ) ) {
	/**
	 * Minimum number of headings before a table of contents is rendered.
	 *
	 * @return int
	 */
	function clipto_toc_min_headings() {
		return (int) apply_filters( 'clipto_toc_min_headings', 3 );
	}
}

if ( ! function_exists( 'clipto_toc_reserved_ids' ) ) {
	/**
	 * Ids used by the page shell and the article chrome — generated heading ids avoid them.
	 *
	 * @return string[]
	 */
	function clipto_toc_reserved_ids() {
		return (array) apply_filters(
			'clipto_toc_reserved_ids',
			array(
				'content',
				'comments',
				'respond',
				'reply-title',
				'commentform',
				'cancel-comment-reply-link',
				'author',
				'email',
				'url',
				'comment',
				'submit',
				'search-dialog',
				'mobile-menu',
				'mega-ai-tools',
				'newsletter',
				'related',
				'toc',
				'wpadminbar',
			)
		);
	}
}

if ( ! function_exists( 'clipto_toc_attr_id' ) ) {
	/**
	 * Read an id attribute out of a raw attribute string.
	 *
	 * @param string $attrs Raw attributes (e.g. ' class="a" id="b"').
	 * @return string|null Decoded id, or null when absent.
	 */
	function clipto_toc_attr_id( $attrs ) {
		if ( preg_match( '/\sid\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'>]+))/i', ' ' . $attrs, $m ) ) {
			$raw = '';
			foreach ( array( 1, 2, 3 ) as $i ) {
				if ( isset( $m[ $i ] ) && '' !== $m[ $i ] ) {
					$raw = $m[ $i ];
					break;
				}
			}
			return html_entity_decode( $raw, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		}
		return null;
	}
}

if ( ! function_exists( 'clipto_toc_process' ) ) {
	/**
	 * Add ids to h2/h3 headings that lack one and collect the table of contents.
	 * Regex based (not DOMDocument) so the surrounding HTML is left byte-for-byte intact.
	 *
	 * @param string $html Rendered content.
	 * @return array { html: string, toc: array<int, array{id:string,text:string,level:int}> }
	 */
	function clipto_toc_process( $html ) {
		$html = (string) $html;
		$toc  = array();

		if ( false === stripos( $html, '<h2' ) && false === stripos( $html, '<h3' ) ) {
			return array(
				'html' => $html,
				'toc'  => $toc,
			);
		}

		// Every id already present in the content (or used by the page shell) is taken.
		$used = array_fill_keys( clipto_toc_reserved_ids(), true );
		if ( preg_match_all( '/<[a-z][a-z0-9-]*\s[^>]*?\bid\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i', $html, $all, PREG_SET_ORDER ) ) {
			foreach ( $all as $m ) {
				$raw = isset( $m[2] ) && '' !== $m[2] ? $m[2] : $m[1];
				if ( '' !== $raw ) {
					$used[ html_entity_decode( $raw, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ] = true;
				}
			}
		}

		// Group 4 (a zero-width lookahead) is set when a Pros / Cons list follows the heading.
		$processed = preg_replace_callback(
			'#<h([23])(\s[^>]*)?>(.*?)</h\1\s*>(?=(\s*<(?:ul|ol)\s[^>]*\bis-style-clipto-(?:pros|cons)\b)?)#is',
			static function ( $m ) use ( &$used, &$toc ) {
				$level = (int) $m[1];
				$attrs = isset( $m[2] ) ? $m[2] : '';
				$inner = $m[3];
				$text  = html_entity_decode( wp_strip_all_tags( $inner ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				$text  = trim( (string) preg_replace( '/\s+/u', ' ', $text ) );

				if ( '' === $text ) {
					return $m[0];
				}

				$id = clipto_toc_attr_id( $attrs );
				if ( null === $id || '' === $id ) {
					$base = clipto_heading_id( $text );
					$id   = $base;
					$n    = 2;
					while ( isset( $used[ $id ] ) ) {
						$id = $base . '-' . $n;
						++$n;
					}
					$used[ $id ] = true;
					$attrs      .= ' id="' . esc_attr( $id ) . '"';
				}

				$skip = ! empty( $m[4] ) || preg_match( '/\sclass\s*=\s*(["\'])[^"\']*\bno-toc\b/i', ' ' . $attrs );
				if ( ! $skip ) {
					$toc[] = array(
						'id'    => $id,
						'text'  => $text,
						'level' => $level,
					);
				}

				return '<h' . $level . $attrs . '>' . $inner . '</h' . $level . '>';
			},
			$html
		);

		return array(
			'html' => null === $processed ? $html : $processed,
			'toc'  => null === $processed ? array() : $toc,
		);
	}
}

if ( ! function_exists( 'clipto_toc_cache' ) ) {
	/**
	 * Per-request TOC store, keyed by post ID.
	 *
	 * @param int        $post_id Post ID.
	 * @param array|null $toc     TOC to store; omit to read.
	 * @return array|null
	 */
	function clipto_toc_cache( $post_id, $toc = null ) {
		static $store = array();
		$post_id = (int) $post_id;
		if ( null !== $toc ) {
			$store[ $post_id ] = $toc;
		}
		return isset( $store[ $post_id ] ) ? $store[ $post_id ] : null;
	}
}

if ( ! function_exists( 'clipto_content_heading_anchors' ) ) {
	/**
	 * the_content filter: add heading ids in the main singular post and remember its TOC.
	 *
	 * @param string $content Content.
	 * @return string
	 */
	function clipto_content_heading_anchors( $content ) {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$post_id = (int) get_the_ID();
		if ( ! $post_id || get_queried_object_id() !== $post_id || post_password_required( $post_id ) ) {
			return $content;
		}
		$result = clipto_toc_process( $content );
		clipto_toc_cache( $post_id, $result['toc'] );
		return $result['html'];
	}
}
add_filter( 'the_content', 'clipto_content_heading_anchors', 20 );

if ( ! function_exists( 'clipto_get_toc' ) ) {
	/**
	 * Table of contents for a post: [ [ 'id' => string, 'text' => string, 'level' => 2|3 ], … ].
	 *
	 * When the post's content has already been filtered in this request the stored result is
	 * returned (exact match with the page). Otherwise the content is rendered (blocks,
	 * shortcodes) and run through the same routine.
	 *
	 * @param int|WP_Post|null $post Post.
	 * @return array
	 */
	function clipto_get_toc( $post = null ) {
		$post = get_post( $post );
		if ( ! $post || post_password_required( $post ) ) {
			return array();
		}
		$cached = clipto_toc_cache( $post->ID );
		if ( null !== $cached ) {
			return $cached;
		}

		$source = (string) $post->post_content;
		$html   = has_blocks( $source ) ? do_blocks( $source ) : wpautop( $source );
		$html   = do_shortcode( $html );
		$result = clipto_toc_process( $html );

		clipto_toc_cache( $post->ID, $result['toc'] );
		return $result['toc'];
	}
}

if ( ! function_exists( 'clipto_toc_tree' ) ) {
	/**
	 * Nest h3 entries under the preceding h2. Leading h3s (before any h2) stay top-level.
	 *
	 * @param array $toc Flat TOC.
	 * @return array Items with a 'children' key.
	 */
	function clipto_toc_tree( $toc ) {
		$tree   = array();
		$parent = null;
		foreach ( (array) $toc as $item ) {
			$item['children'] = array();
			if ( 3 === (int) $item['level'] && null !== $parent ) {
				$tree[ $parent ]['children'][] = $item;
				continue;
			}
			$tree[] = $item;
			$parent = 2 === (int) $item['level'] ? count( $tree ) - 1 : $parent;
		}
		return $tree;
	}
}

if ( ! function_exists( 'clipto_toc_render_list' ) ) {
	/**
	 * Print the nested TOC list (used by template-parts/article/toc.php).
	 *
	 * @param array $tree Nested TOC.
	 */
	function clipto_toc_render_list( $tree ) {
		echo '<ol class="toc__list" role="list">';
		foreach ( $tree as $i => $item ) {
			echo '<li class="toc__item">';
			printf(
				'<a class="toc__link" href="%1$s" data-toc-link><span class="toc__num" aria-hidden="true">%2$s</span><span class="toc__text">%3$s</span></a>',
				esc_attr( '#' . $item['id'] ),
				esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ),
				esc_html( $item['text'] )
			);
			if ( ! empty( $item['children'] ) ) {
				echo '<ol class="toc__sub" role="list">';
				foreach ( $item['children'] as $child ) {
					printf(
						'<li class="toc__item toc__item--sub"><a class="toc__link toc__link--sub" href="%1$s" data-toc-link><span class="toc__text">%2$s</span></a></li>',
						esc_attr( '#' . $child['id'] ),
						esc_html( $child['text'] )
					);
				}
				echo '</ol>';
			}
			echo '</li>';
		}
		echo '</ol>';
	}
}
