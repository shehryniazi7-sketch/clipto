<?php
/**
 * Navigation: primary destinations, active-state detection, the AI Tools mega-menu
 * and the styling hooks that make an assigned "Primary" menu render identically to
 * the built-in navigation.
 *
 * Public helpers (safe for other templates, e.g. the footer):
 *   clipto_primary_nav_items()          Normalised top-level items (menu or destinations).
 *   clipto_destination_current( $key )  '' | 'page' | 'true' — aria-current value.
 *   clipto_is_destination_active( $key )
 *   clipto_post_destination_key( $post ) Which destination a post belongs to.
 *   clipto_latest_tool_post()           Latest AI Tools post (prefers one with an image).
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'clipto_post_destination_key' ) ) :
	/**
	 * The destination a post belongs to: its primary category (or that category's
	 * nearest destination ancestor) first, then any other category, then Free AI.
	 *
	 * @param int|WP_Post|null $post Post.
	 * @return string Destination key or ''.
	 */
	function clipto_post_destination_key( $post = null ) {
		$post = get_post( $post );
		if ( ! $post || 'post' !== $post->post_type ) {
			return '';
		}

		$by_term = array();
		foreach ( clipto_destinations() as $key => $dest ) {
			if ( 'category' === $dest['type'] ) {
				$by_term[ (int) $dest['object']->term_id ] = $key;
			}
		}

		if ( $by_term ) {
			$candidates = array();
			$primary    = clipto_primary_category( $post );
			if ( $primary ) {
				$candidates[] = $primary;
			}
			$candidates = array_merge( $candidates, (array) get_the_category( $post->ID ) );

			foreach ( $candidates as $cat ) {
				$chain = array_merge( array( (int) $cat->term_id ), array_map( 'intval', get_ancestors( $cat->term_id, 'category', 'taxonomy' ) ) );
				foreach ( $chain as $term_id ) {
					if ( isset( $by_term[ $term_id ] ) ) {
						return $by_term[ $term_id ];
					}
				}
			}
		}

		$free = clipto_destination( 'free-ai' );
		if ( $free && has_tag( (int) $free['object']->term_id, $post ) ) {
			return 'free-ai';
		}
		return '';
	}
endif;

if ( ! function_exists( 'clipto_destination_current' ) ) :
	/**
	 * The aria-current value for a destination in the current request:
	 * 'page' when viewing the destination itself, 'true' when viewing something
	 * inside it (a subcategory, a post filed in it, a child page), '' otherwise.
	 *
	 * @param string $key Destination key.
	 * @return string
	 */
	function clipto_destination_current( $key ) {
		static $cache = array();
		if ( isset( $cache[ $key ] ) ) {
			return $cache[ $key ];
		}

		$state = '';
		$dest  = clipto_destination( $key );

		if ( $dest ) {
			if ( 'category' === $dest['type'] ) {
				$term = $dest['object'];
				if ( is_category() ) {
					$queried = get_queried_object();
					if ( $queried instanceof WP_Term ) {
						if ( (int) $queried->term_id === (int) $term->term_id ) {
							$state = 'page';
						} elseif ( term_is_ancestor_of( $term, $queried, 'category' ) ) {
							$state = 'true';
						}
					}
				} elseif ( is_singular( 'post' ) && clipto_post_destination_key( get_queried_object_id() ) === $key ) {
					$state = 'true';
				}
			} elseif ( 'tag' === $dest['type'] ) {
				if ( is_tag( (int) $dest['object']->term_id ) ) {
					$state = 'page';
				} elseif ( is_singular( 'post' ) && clipto_post_destination_key( get_queried_object_id() ) === $key ) {
					$state = 'true';
				}
			} elseif ( 'page' === $dest['type'] && is_page() ) {
				$page_id = (int) $dest['object']->ID;
				if ( (int) get_queried_object_id() === $page_id ) {
					$state = 'page';
				} elseif ( in_array( $page_id, array_map( 'intval', get_post_ancestors( get_queried_object_id() ) ), true ) ) {
					$state = 'true';
				}
			}
		}

		$cache[ $key ] = $state;
		return $state;
	}
endif;

if ( ! function_exists( 'clipto_is_destination_active' ) ) :
	/**
	 * Whether the current request is the destination or inside it.
	 *
	 * @param string $key Destination key.
	 * @return bool
	 */
	function clipto_is_destination_active( $key ) {
		return '' !== clipto_destination_current( $key );
	}
endif;

if ( ! function_exists( 'clipto_nav_item_destination_key' ) ) :
	/**
	 * Match a nav menu item to a destination (by linked object, then by URL).
	 *
	 * @param WP_Post $item Nav menu item.
	 * @return string Destination key or ''.
	 */
	function clipto_nav_item_destination_key( $item ) {
		if ( ! is_object( $item ) ) {
			return '';
		}
		$object_id = isset( $item->object_id ) ? (int) $item->object_id : 0;
		$object    = isset( $item->object ) ? $item->object : '';
		$url       = isset( $item->url ) ? untrailingslashit( strtok( (string) $item->url, '?#' ) ) : '';

		foreach ( clipto_destinations() as $key => $dest ) {
			if ( 'page' === $dest['type'] && 'page' === $object && $object_id === (int) $dest['object']->ID ) {
				return $key;
			}
			if ( 'category' === $dest['type'] && 'category' === $object && $object_id === (int) $dest['object']->term_id ) {
				return $key;
			}
			if ( 'tag' === $dest['type'] && 'post_tag' === $object && $object_id === (int) $dest['object']->term_id ) {
				return $key;
			}
			if ( $url && untrailingslashit( $dest['url'] ) === $url ) {
				return $key;
			}
		}
		return '';
	}
endif;

if ( ! function_exists( 'clipto_primary_nav_items' ) ) :
	/**
	 * Top-level primary navigation items, normalised. Uses the menu assigned to the
	 * "primary" location when there is one, otherwise the live destinations.
	 *
	 * Each item: key, label, url, current (''|'page'|'true'), count (int|null),
	 * mega (bool — AI Tools with existing subcategories), target, rel.
	 *
	 * @return array[]
	 */
	function clipto_primary_nav_items() {
		static $items = null;
		if ( null !== $items ) {
			return $items;
		}
		$items = array();

		$has_tool_subcats = (bool) clipto_tool_subcategories();
		$locations        = get_nav_menu_locations();

		if ( has_nav_menu( 'primary' ) && ! empty( $locations['primary'] ) ) {
			$menu_items = wp_get_nav_menu_items( $locations['primary'], array( 'update_post_term_cache' => false ) );
			if ( $menu_items ) {
				if ( function_exists( '_wp_menu_item_classes_by_context' ) ) {
					_wp_menu_item_classes_by_context( $menu_items );
				}
				foreach ( $menu_items as $menu_item ) {
					if ( (int) $menu_item->menu_item_parent ) {
						continue;
					}
					$key     = clipto_nav_item_destination_key( $menu_item );
					$current = $key ? clipto_destination_current( $key ) : '';
					if ( ! $current ) {
						if ( ! empty( $menu_item->current ) ) {
							$current = 'page';
						} elseif ( ! empty( $menu_item->current_item_ancestor ) || ! empty( $menu_item->current_item_parent ) ) {
							$current = 'true';
						}
					}
					$dest    = $key ? clipto_destination( $key ) : null;
					$items[] = array(
						'key'     => $key ? $key : 'menu-item-' . (int) $menu_item->ID,
						'label'   => wp_strip_all_tags( $menu_item->title ),
						'url'     => $menu_item->url,
						'current' => $current,
						'count'   => $dest ? $dest['count'] : null,
						'mega'    => 'ai-tools' === $key && $has_tool_subcats,
						'target'  => (string) $menu_item->target,
						'rel'     => (string) $menu_item->xfn,
					);
				}
			}
		} else {
			foreach ( clipto_destinations() as $key => $dest ) {
				$items[] = array(
					'key'     => $key,
					'label'   => $dest['label'],
					'url'     => $dest['url'],
					'current' => clipto_destination_current( $key ),
					'count'   => $dest['count'],
					'mega'    => 'ai-tools' === $key && $has_tool_subcats,
					'target'  => '',
					'rel'     => '',
				);
			}
		}

		$items = apply_filters( 'clipto_primary_nav_items', $items );
		return $items;
	}
endif;

if ( ! function_exists( 'clipto_latest_tool_post' ) ) :
	/**
	 * Latest published AI Tools post, preferring one that has a featured image.
	 * Never marks the post as "shown" (the homepage de-duplication is untouched).
	 *
	 * @return WP_Post|null
	 */
	function clipto_latest_tool_post() {
		static $post = false;
		if ( false !== $post ) {
			return $post;
		}
		$post  = null;
		$tools = clipto_destination( 'ai-tools' );
		if ( ! $tools ) {
			return $post;
		}
		$base = array(
			'posts_per_page'         => 1,
			'cat'                    => (int) $tools['object']->term_id,
			'update_post_meta_cache' => true,
		);
		$found = clipto_posts( array_merge( $base, array( 'meta_key' => '_thumbnail_id' ) ), false ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- single row, indexed key.
		if ( ! $found ) {
			$found = clipto_posts( $base, false );
		}
		$post = $found ? $found[0] : null;
		return $post;
	}
endif;

if ( ! function_exists( 'clipto_mega_toggle' ) ) :
	/**
	 * Disclosure button that opens the AI Tools panel (sits next to the real link).
	 *
	 * @param string $panel_id Panel element id.
	 * @param string $label    Visible item label (for the accessible name).
	 * @return string
	 */
	function clipto_mega_toggle( $panel_id, $label ) {
		return sprintf(
			'<button type="button" class="site-nav__toggle" aria-expanded="false" aria-controls="%1$s" data-mega-toggle><span class="screen-reader-text">%2$s</span>%3$s</button>',
			esc_attr( $panel_id ),
			/* translators: %s: navigation item label, e.g. "AI Tools". */
			esc_html( sprintf( __( '%s categories', 'clipto' ), $label ) ),
			clipto_icon( 'chevron-down' )
		);
	}
endif;

if ( ! function_exists( 'clipto_mega_menu' ) ) :
	/**
	 * The AI Tools mega-menu panel markup ('' when there are no subcategories).
	 *
	 * @param string $panel_id Panel element id.
	 * @return string
	 */
	function clipto_mega_menu( $panel_id = 'mega-ai-tools' ) {
		$subcats = clipto_tool_subcategories();
		$tools   = clipto_destination( 'ai-tools' );
		if ( ! $subcats || ! $tools ) {
			return '';
		}
		ob_start();
		get_template_part(
			'template-parts/header/mega-menu',
			null,
			array(
				'id'      => $panel_id,
				'subcats' => $subcats,
				'tools'   => $tools,
				'latest'  => clipto_latest_tool_post(),
			)
		);
		return (string) ob_get_clean();
	}
endif;

if ( ! function_exists( 'clipto_primary_nav' ) ) :
	/**
	 * Desktop primary navigation list. An assigned "Primary" menu renders through
	 * wp_nav_menu() (so menu plugins and the Customizer keep working) and is styled
	 * identically via the filters below; otherwise the live destinations are used.
	 */
	function clipto_primary_nav() {
		if ( has_nav_menu( 'primary' ) ) {
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'site-nav__list',
					'menu_id'        => 'primary-menu',
					'items_wrap'     => '<ul id="%1$s" class="%2$s" role="list">%3$s</ul>',
					'depth'          => 1,
					'fallback_cb'    => false,
					'clipto_nav'     => 'desktop',
				)
			);
			return;
		}

		$items = clipto_primary_nav_items();
		if ( ! $items ) {
			return;
		}
		echo '<ul class="site-nav__list" role="list">';
		foreach ( $items as $item ) {
			$classes = 'site-nav__item site-nav__item--' . sanitize_html_class( $item['key'] );
			if ( $item['mega'] ) {
				$classes .= ' site-nav__item--has-mega';
			}
			printf(
				'<li class="%1$s"%2$s><a class="site-nav__link" href="%3$s"%4$s>%5$s</a>',
				esc_attr( $classes ),
				$item['mega'] ? ' data-mega' : '',
				esc_url( $item['url'] ),
				$item['current'] ? ' aria-current="' . esc_attr( $item['current'] ) . '"' : '',
				esc_html( $item['label'] )
			);
			if ( $item['mega'] ) {
				echo clipto_mega_toggle( 'mega-ai-tools', $item['label'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper.
				echo clipto_mega_menu( 'mega-ai-tools' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in template.
			}
			echo '</li>';
		}
		echo '</ul>';
	}
endif;

/* -------------------------------------------------------------------------
 * wp_nav_menu() styling hooks (desktop primary menu only)
 * ---------------------------------------------------------------------- */

/**
 * Whether wp_nav_menu() args belong to the Clipto desktop primary navigation.
 *
 * @param stdClass|array $args Menu args.
 * @return bool
 */
function clipto_is_desktop_nav_args( $args ) {
	$args = (object) $args;
	return isset( $args->clipto_nav ) && 'desktop' === $args->clipto_nav;
}

/**
 * Item classes: site-nav__item (+ --has-mega on AI Tools when it has subcategories).
 *
 * @param string[] $classes Classes.
 * @param WP_Post  $item    Item.
 * @param stdClass $args    Args.
 * @param int      $depth   Depth.
 * @return string[]
 */
function clipto_nav_menu_css_class( $classes, $item, $args, $depth = 0 ) {
	if ( ! clipto_is_desktop_nav_args( $args ) || $depth ) {
		return $classes;
	}
	$classes[] = 'site-nav__item';
	if ( 'ai-tools' === clipto_nav_item_destination_key( $item ) && clipto_tool_subcategories() ) {
		$classes[] = 'site-nav__item--has-mega';
	}
	return $classes;
}
add_filter( 'nav_menu_css_class', 'clipto_nav_menu_css_class', 10, 4 );

/**
 * Link attributes: site-nav__link class and destination-aware aria-current.
 *
 * @param array    $atts  Attributes.
 * @param WP_Post  $item  Item.
 * @param stdClass $args  Args.
 * @param int      $depth Depth.
 * @return array
 */
function clipto_nav_menu_link_attributes( $atts, $item, $args, $depth = 0 ) {
	if ( ! clipto_is_desktop_nav_args( $args ) || $depth ) {
		return $atts;
	}
	$atts['class'] = trim( ( isset( $atts['class'] ) ? $atts['class'] . ' ' : '' ) . 'site-nav__link' );

	$key     = clipto_nav_item_destination_key( $item );
	$current = $key ? clipto_destination_current( $key ) : '';
	if ( ! $current && ! empty( $item->current ) ) {
		$current = 'page';
	} elseif ( ! $current && ( ! empty( $item->current_item_ancestor ) || ! empty( $item->current_item_parent ) ) ) {
		$current = 'true';
	}
	if ( $current ) {
		$atts['aria-current'] = $current;
	} else {
		unset( $atts['aria-current'] );
	}
	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'clipto_nav_menu_link_attributes', 10, 4 );

/**
 * Mark the AI Tools <li> as a mega-menu host (data-mega) for the header script.
 *
 * @param array    $atts  Attributes.
 * @param WP_Post  $item  Item.
 * @param stdClass $args  Args.
 * @param int      $depth Depth.
 * @return array
 */
function clipto_nav_menu_item_attributes( $atts, $item, $args, $depth = 0 ) {
	if ( clipto_is_desktop_nav_args( $args ) && ! $depth && 'ai-tools' === clipto_nav_item_destination_key( $item ) && clipto_tool_subcategories() ) {
		$atts['data-mega'] = 'true'; // Empty values are dropped by the walker.
	}
	return $atts;
}
add_filter( 'nav_menu_item_attributes', 'clipto_nav_menu_item_attributes', 10, 4 );

/**
 * Append the disclosure button and panel after the AI Tools link.
 *
 * @param string   $output Item output.
 * @param WP_Post  $item   Item.
 * @param int      $depth  Depth.
 * @param stdClass $args   Args.
 * @return string
 */
function clipto_nav_menu_start_el( $output, $item, $depth, $args ) {
	if ( ! clipto_is_desktop_nav_args( $args ) || $depth || 'ai-tools' !== clipto_nav_item_destination_key( $item ) ) {
		return $output;
	}
	$panel = clipto_mega_menu( 'mega-ai-tools' );
	if ( ! $panel ) {
		return $output;
	}
	return $output . clipto_mega_toggle( 'mega-ai-tools', wp_strip_all_tags( $item->title ) ) . $panel;
}
add_filter( 'walker_nav_menu_start_el', 'clipto_nav_menu_start_el', 10, 4 );
