<?php
/**
 * Homepage helpers (loaded by front-page.php and home.php).
 *
 * Kept next to the homepage parts because only the homepage and the posts index use
 * them. Every function is prefixed and guarded so a child theme can replace it.
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'clipto_home_index' ) ) :
	/**
	 * Sequential section numeral for homepage section heads ("01", "02" …). Only
	 * sections that actually render call it, so numbering never skips.
	 *
	 * @return string
	 */
	function clipto_home_index() {
		static $n = 0;
		++$n;
		return str_pad( (string) $n, 2, '0', STR_PAD_LEFT );
	}
endif;

if ( ! function_exists( 'clipto_home_num' ) ) :
	/**
	 * Zero-padded index numeral.
	 *
	 * @param int $n Number.
	 * @return string
	 */
	function clipto_home_num( $n ) {
		return str_pad( (string) (int) $n, 2, '0', STR_PAD_LEFT );
	}
endif;

if ( ! function_exists( 'clipto_home_count_label' ) ) :
	/**
	 * "12 stories" label for a count.
	 *
	 * @param int $count Count.
	 * @return string Plain text.
	 */
	function clipto_home_count_label( $count ) {
		/* translators: %s: number of articles. */
		return sprintf( _n( '%s story', '%s stories', (int) $count, 'clipto' ), number_format_i18n( (int) $count ) );
	}
endif;

if ( ! function_exists( 'clipto_home_guide_sections' ) ) :
	/**
	 * The first H2 headings of a page's content, for a "What's inside" preview.
	 *
	 * Each item: text (plain) and anchor ('' when no matching id is guaranteed to exist on
	 * the rendered page). Anchors come from clipto_get_toc() (inc/toc.php) — the very routine
	 * that adds ids to the page's headings, de-duplication included — or, without it, from
	 * an id the heading already carries. Otherwise the item links to the page itself.
	 *
	 * Cached in a transient keyed by the page's modified time.
	 *
	 * @param WP_Post $page  Page.
	 * @param int     $limit Max headings.
	 * @return array<int,array{text:string,anchor:string}>
	 */
	function clipto_home_guide_sections( $page, $limit = 6 ) {
		if ( ! $page instanceof WP_Post || '' === trim( $page->post_content ) || post_password_required( $page ) ) {
			return array();
		}

		$stamp  = md5( $page->post_modified_gmt . '|' . (int) $limit . '|' . ( function_exists( 'clipto_get_toc' ) ? 'toc' : 'raw' ) );
		$key    = 'clipto_guide_sections_' . $page->ID;
		$cached = get_transient( $key );
		if ( is_array( $cached ) && isset( $cached['stamp'], $cached['items'] ) && $stamp === $cached['stamp'] ) {
			return $cached['items'];
		}

		$items = array();
		if ( function_exists( 'clipto_get_toc' ) ) {
			foreach ( (array) clipto_get_toc( $page ) as $entry ) {
				if ( 2 !== (int) $entry['level'] || '' === $entry['text'] ) {
					continue;
				}
				$items[] = array(
					'text'   => (string) $entry['text'],
					'anchor' => (string) $entry['id'],
				);
				if ( count( $items ) >= $limit ) {
					break;
				}
			}
		} elseif ( preg_match_all( '#<h2\b([^>]*)>(.*?)</h2>#is', $page->post_content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$text = trim( (string) preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( $match[2] ), ENT_QUOTES, 'UTF-8' ) ) );
				if ( '' === $text ) {
					continue;
				}
				$anchor = '';
				if ( preg_match( '/\sid\s*=\s*(["\'])([^"\']+)\1/i', ' ' . $match[1], $id ) ) {
					$anchor = html_entity_decode( $id[2], ENT_QUOTES, 'UTF-8' );
				}
				$items[] = array(
					'text'   => $text,
					'anchor' => $anchor,
				);
				if ( count( $items ) >= $limit ) {
					break;
				}
			}
		}

		set_transient(
			$key,
			array(
				'stamp' => $stamp,
				'items' => $items,
			),
			DAY_IN_SECONDS
		);
		return $items;
	}
endif;

if ( ! function_exists( 'clipto_home_more_url' ) ) :
	/**
	 * Where "More stories" leads from the homepage: page 2 of the posts front page, or the
	 * posts page when a static front page is used. '' when neither exists.
	 *
	 * @return string
	 */
	function clipto_home_more_url() {
		if ( 'posts' === get_option( 'show_on_front' ) ) {
			$published = (int) wp_count_posts( 'post' )->publish;
			$per_page  = max( 1, (int) get_option( 'posts_per_page' ) );
			return $published > $per_page ? get_pagenum_link( 2 ) : '';
		}
		$page_for_posts = (int) get_option( 'page_for_posts' );
		if ( $page_for_posts && 'publish' === get_post_status( $page_for_posts ) ) {
			return get_permalink( $page_for_posts );
		}
		return '';
	}
endif;
