<?php
/**
 * Plugin Name: Clipto Core
 * Description: Core functionality for Clipto.org — the AI Tools directory (custom post type + taxonomies), the AI Summary editorial fields, canonical reading-time calculation, and the [clipto_summary] / [clipto_tools_grid heading="h2|h3|h4"] shortcodes. Safe to activate alongside the Clipto theme or any other theme, and safe to deactivate: the theme keeps working without it.
 * Version:     1.1.0
 * Author:      Clipto
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: clipto-core
 *
 * @package Clipto_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CLIPTO_CORE_VERSION', '1.1.0' );

/**
 * ------------------------------------------------------------------
 * 1. CUSTOM POST TYPE: AI TOOLS
 * ------------------------------------------------------------------
 */
function clipto_register_tool_post_type() {
	$labels = array(
		'name'               => __( 'AI Tools', 'clipto-core' ),
		'singular_name'      => __( 'AI Tool', 'clipto-core' ),
		'add_new'            => __( 'Add New', 'clipto-core' ),
		'add_new_item'       => __( 'Add New AI Tool', 'clipto-core' ),
		'edit_item'          => __( 'Edit AI Tool', 'clipto-core' ),
		'new_item'           => __( 'New AI Tool', 'clipto-core' ),
		'view_item'          => __( 'View AI Tool', 'clipto-core' ),
		'view_items'         => __( 'View AI Tools', 'clipto-core' ),
		'search_items'       => __( 'Search AI Tools', 'clipto-core' ),
		'not_found'          => __( 'No AI tools found', 'clipto-core' ),
		'not_found_in_trash' => __( 'No AI tools found in Trash', 'clipto-core' ),
		'all_items'          => __( 'All AI Tools', 'clipto-core' ),
		'menu_name'          => __( 'AI Tools', 'clipto-core' ),
		'name_admin_bar'     => __( 'AI Tool', 'clipto-core' ),
	);

	register_post_type(
		'clipto_tool',
		array(
			'labels'        => $labels,
			'public'        => true,
			'has_archive'   => true,
			'show_in_rest'  => true,
			'menu_icon'     => 'dashicons-admin-generic',
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ),
			'rewrite'       => array( 'slug' => 'ai-tools' ),
			'capability_type' => 'post',
			'map_meta_cap'  => true,
		)
	);
}
add_action( 'init', 'clipto_register_tool_post_type' );

function clipto_register_tool_taxonomies() {
	register_taxonomy(
		'clipto_pricing',
		'clipto_tool',
		array(
			'labels'            => array(
				'name'          => __( 'Pricing', 'clipto-core' ),
				'singular_name' => __( 'Pricing', 'clipto-core' ),
				'menu_name'     => __( 'Pricing', 'clipto-core' ),
			),
			'hierarchical'      => false,
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'tool-pricing' ),
		)
	);

	register_taxonomy(
		'clipto_tool_category',
		'clipto_tool',
		array(
			'labels'            => array(
				'name'          => __( 'Tool Categories', 'clipto-core' ),
				'singular_name' => __( 'Tool Category', 'clipto-core' ),
				'menu_name'     => __( 'Tool Categories', 'clipto-core' ),
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'tool-category' ),
		)
	);
}
add_action( 'init', 'clipto_register_tool_taxonomies' );

/**
 * Activation only sets up what's genuinely needed once: the post type and
 * taxonomies (so rewrite rules include them), three starter pricing terms,
 * and a rewrite flush. Nothing here runs on every request.
 */
function clipto_core_activate() {
	clipto_register_tool_post_type();
	clipto_register_tool_taxonomies();

	$defaults = array(
		'free'     => __( 'Free', 'clipto-core' ),
		'freemium' => __( 'Freemium', 'clipto-core' ),
		'paid'     => __( 'Paid', 'clipto-core' ),
	);
	foreach ( $defaults as $slug => $name ) {
		if ( ! term_exists( $slug, 'clipto_pricing' ) ) {
			wp_insert_term( $name, 'clipto_pricing', array( 'slug' => $slug ) );
		}
	}

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'clipto_core_activate' );

function clipto_core_deactivate() {
	// The post type and taxonomies are still registered during this request
	// (they were added on init), so they must be unregistered before the
	// flush — otherwise the /ai-tools/, /tool-pricing/ and /tool-category/
	// rewrite rules survive deactivation and /ai-tools/ keeps answering 200
	// with a copy of the homepage instead of a 404.
	unregister_taxonomy( 'clipto_pricing' );
	unregister_taxonomy( 'clipto_tool_category' );
	unregister_post_type( 'clipto_tool' );
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'clipto_core_deactivate' );

/**
 * ------------------------------------------------------------------
 * 2. AI SUMMARY META BOX (Dashboard > Posts > Edit Post)
 * ------------------------------------------------------------------
 */
function clipto_register_summary_meta_box() {
	add_meta_box(
		'clipto_ai_summary_box',
		__( 'AI Summary', 'clipto-core' ),
		'clipto_render_summary_meta_box',
		'post',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'clipto_register_summary_meta_box' );

function clipto_render_summary_meta_box( $post ) {
	wp_nonce_field( 'clipto_save_summary', 'clipto_summary_nonce' );
	$summary  = get_post_meta( $post->ID, '_clipto_ai_summary', true );
	$takeaway = get_post_meta( $post->ID, '_clipto_ai_takeaway', true );
	?>
	<p>
		<label for="clipto_ai_summary"><strong><?php esc_html_e( 'Concise summary', 'clipto-core' ); ?></strong></label><br />
		<textarea id="clipto_ai_summary" name="clipto_ai_summary" rows="4" style="width:100%;"><?php echo esc_textarea( $summary ); ?></textarea>
		<span class="description">
			<?php esc_html_e( 'A short, editorially-reviewed summary shown in the article\'s AI Summary box and by the [clipto_summary] shortcode. If left blank, the post excerpt is used instead. This is never generated by calling an external AI service — it only displays what you write here.', 'clipto-core' ); ?>
		</span>
	</p>
	<p>
		<label for="clipto_ai_takeaway"><strong><?php esc_html_e( 'Key takeaway', 'clipto-core' ); ?></strong></label><br />
		<input type="text" id="clipto_ai_takeaway" name="clipto_ai_takeaway" value="<?php echo esc_attr( $takeaway ); ?>" style="width:100%;" />
	</p>
	<?php
}

function clipto_save_summary_meta_box( $post_id ) {
	if ( ! isset( $_POST['clipto_summary_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['clipto_summary_nonce'] ) ), 'clipto_save_summary' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['clipto_ai_summary'] ) ) {
		update_post_meta( $post_id, '_clipto_ai_summary', wp_kses_post( wp_unslash( $_POST['clipto_ai_summary'] ) ) );
	}
	if ( isset( $_POST['clipto_ai_takeaway'] ) ) {
		update_post_meta( $post_id, '_clipto_ai_takeaway', sanitize_text_field( wp_unslash( $_POST['clipto_ai_takeaway'] ) ) );
	}
}
add_action( 'save_post_post', 'clipto_save_summary_meta_box' );

/**
 * ------------------------------------------------------------------
 * 3. TOOL DETAILS META BOX (Dashboard > AI Tools > Edit Tool)
 * ------------------------------------------------------------------
 */
function clipto_register_tool_meta_box() {
	add_meta_box(
		'clipto_tool_details',
		__( 'Tool Details', 'clipto-core' ),
		'clipto_render_tool_meta_box',
		'clipto_tool',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'clipto_register_tool_meta_box' );

function clipto_render_tool_meta_box( $post ) {
	wp_nonce_field( 'clipto_save_tool', 'clipto_tool_nonce' );
	$url     = get_post_meta( $post->ID, '_clipto_tool_url', true );
	$summary = get_post_meta( $post->ID, '_clipto_tool_summary', true );
	$notes   = get_post_meta( $post->ID, '_clipto_tool_notes', true );
	$rating  = get_post_meta( $post->ID, '_clipto_tool_rating', true );
	?>
	<p>
		<label for="clipto_tool_url"><strong><?php esc_html_e( 'Official Tool Website URL', 'clipto-core' ); ?></strong></label><br />
		<input type="url" id="clipto_tool_url" name="clipto_tool_url" value="<?php echo esc_attr( $url ); ?>" placeholder="https://" style="width:100%;" />
		<span class="description"><?php esc_html_e( 'Shown as the "View Tool" button on the tools grid and the "Visit" button on the tool\'s own page. Leave blank to hide the button for this tool.', 'clipto-core' ); ?></span>
	</p>
	<p>
		<label for="clipto_tool_summary"><strong><?php esc_html_e( 'Short Summary', 'clipto-core' ); ?></strong></label><br />
		<textarea id="clipto_tool_summary" name="clipto_tool_summary" rows="3" style="width:100%;"><?php echo esc_textarea( $summary ); ?></textarea>
		<span class="description"><?php esc_html_e( 'Shown on the tool card. If left blank, the tool excerpt is used instead.', 'clipto-core' ); ?></span>
	</p>
	<p>
		<label for="clipto_tool_notes"><strong><?php esc_html_e( 'Editor Notes', 'clipto-core' ); ?></strong></label><br />
		<textarea id="clipto_tool_notes" name="clipto_tool_notes" rows="3" style="width:100%;"><?php echo esc_textarea( $notes ); ?></textarea>
		<span class="description"><?php esc_html_e( 'Internal notes — not displayed on the front end.', 'clipto-core' ); ?></span>
	</p>
	<p>
		<label for="clipto_tool_rating"><strong><?php esc_html_e( 'Editorial Rating (0–5, optional)', 'clipto-core' ); ?></strong></label><br />
		<input type="number" id="clipto_tool_rating" name="clipto_tool_rating" value="<?php echo esc_attr( $rating ); ?>" min="0" max="5" step="0.1" />
	</p>
	<?php
}

function clipto_save_tool_meta_box( $post_id ) {
	if ( ! isset( $_POST['clipto_tool_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['clipto_tool_nonce'] ) ), 'clipto_save_tool' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['clipto_tool_url'] ) ) {
		$url = esc_url_raw( trim( wp_unslash( $_POST['clipto_tool_url'] ) ) );
		// Belt-and-suspenders: esc_url_raw() already strips javascript:/data:
		// and other unsafe schemes via WordPress's allowed-protocol list, but
		// we additionally require a genuine, resolvable http(s) URL before
		// storing it — an empty or malformed value is stored as empty rather
		// than silently keeping something unsafe.
		$url = ( '' === $url || wp_http_validate_url( $url ) ) ? $url : '';
		update_post_meta( $post_id, '_clipto_tool_url', $url );
	}
	if ( isset( $_POST['clipto_tool_summary'] ) ) {
		update_post_meta( $post_id, '_clipto_tool_summary', sanitize_textarea_field( wp_unslash( $_POST['clipto_tool_summary'] ) ) );
	}
	if ( isset( $_POST['clipto_tool_notes'] ) ) {
		update_post_meta( $post_id, '_clipto_tool_notes', wp_kses_post( wp_unslash( $_POST['clipto_tool_notes'] ) ) );
	}
	if ( isset( $_POST['clipto_tool_rating'] ) && '' !== $_POST['clipto_tool_rating'] ) {
		$rating = (float) $_POST['clipto_tool_rating'];
		$rating = max( 0, min( 5, $rating ) );
		update_post_meta( $post_id, '_clipto_tool_rating', $rating );
	} elseif ( isset( $_POST['clipto_tool_rating'] ) ) {
		delete_post_meta( $post_id, '_clipto_tool_rating' );
	}
}
add_action( 'save_post_clipto_tool', 'clipto_save_tool_meta_box' );

/**
 * ------------------------------------------------------------------
 * 4. READING TIME (canonical calculation — the theme falls back to its
 *    own local copy if this plugin is inactive)
 * ------------------------------------------------------------------
 */
function clipto_calculate_reading_time( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return 1;
	}
	$content = strip_shortcodes( $post->post_content );
	$content = wp_strip_all_tags( $content );
	$words   = str_word_count( $content );
	$wpm     = (int) apply_filters( 'clipto_reading_time_wpm', 225 );
	$wpm     = max( 100, $wpm );
	$minutes = (int) ceil( $words / $wpm );
	return max( 1, $minutes );
}

/**
 * ------------------------------------------------------------------
 * 5. SHORTCODE: [clipto_summary]
 * ------------------------------------------------------------------
 */
function clipto_summary_shortcode( $atts ) {
	static $rendered = array();

	$atts    = shortcode_atts( array( 'id' => 0 ), $atts, 'clipto_summary' );
	$post_id = $atts['id'] ? (int) $atts['id'] : get_the_ID();

	if ( ! $post_id || isset( $rendered[ $post_id ] ) ) {
		return '';
	}
	$rendered[ $post_id ] = true;

	$summary = get_post_meta( $post_id, '_clipto_ai_summary', true );
	if ( ! $summary ) {
		$summary = get_the_excerpt( $post_id );
	}
	if ( ! $summary ) {
		return '';
	}

	return '<div class="clipto-ai-summary clipto-ai-summary--shortcode"><h2>'
		. esc_html__( 'AI Summary', 'clipto-core' )
		. '</h2><div class="clipto-ai-summary__body">'
		. wp_kses_post( $summary )
		. '</div></div>';
}
add_shortcode( 'clipto_summary', 'clipto_summary_shortcode' );

/**
 * Collects one AI Tool's display data in one place, so the tool card,
 * the single tool page and the theme's structured data all read the
 * same validated values. The URL is only returned if it is a genuine
 * http(s) URL, and the rating only if it is numeric — nothing is ever
 * invented when a field is left blank in the editor.
 */
function clipto_get_tool_data( $tool_id = null ) {
	$tool_id = $tool_id ? (int) $tool_id : get_the_ID();
	if ( ! $tool_id || 'clipto_tool' !== get_post_type( $tool_id ) ) {
		return null;
	}

	$url      = get_post_meta( $tool_id, '_clipto_tool_url', true );
	$rating   = get_post_meta( $tool_id, '_clipto_tool_rating', true );
	$pricing  = get_the_terms( $tool_id, 'clipto_pricing' );
	$category = get_the_terms( $tool_id, 'clipto_tool_category' );

	return array(
		'id'       => $tool_id,
		'url'      => ( $url && wp_http_validate_url( $url ) ) ? $url : '',
		'summary'  => (string) get_post_meta( $tool_id, '_clipto_tool_summary', true ),
		'rating'   => ( '' !== $rating && is_numeric( $rating ) ) ? max( 0, min( 5, (float) $rating ) ) : null,
		'pricing'  => ( $pricing && ! is_wp_error( $pricing ) ) ? $pricing[0] : null,
		'category' => ( $category && ! is_wp_error( $category ) ) ? $category[0] : null,
	);
}

/**
 * Category / pricing / editorial-rating badges. Shared by the tool card
 * and the single tool page so both stay visually identical. On the
 * single page ($link_terms = true) the category and pricing badges link
 * to their existing taxonomy archives.
 */
function clipto_render_tool_badges( $data, $link_terms = false ) {
	if ( ! $data ) {
		return;
	}
	?>
	<div class="clipto-tool-card__badges">
		<?php if ( $data['category'] ) : ?>
			<?php if ( $link_terms ) : ?>
				<a class="clipto-badge clipto-badge--category" href="<?php echo esc_url( get_term_link( $data['category'] ) ); ?>"><?php echo esc_html( $data['category']->name ); ?></a>
			<?php else : ?>
				<span class="clipto-badge clipto-badge--category"><?php echo esc_html( $data['category']->name ); ?></span>
			<?php endif; ?>
		<?php endif; ?>
		<?php if ( $data['pricing'] ) : ?>
			<?php if ( $link_terms ) : ?>
				<a class="clipto-pricing-badge clipto-pricing-badge--<?php echo esc_attr( $data['pricing']->slug ); ?>" href="<?php echo esc_url( get_term_link( $data['pricing'] ) ); ?>"><?php echo esc_html( $data['pricing']->name ); ?></a>
			<?php else : ?>
				<span class="clipto-pricing-badge clipto-pricing-badge--<?php echo esc_attr( $data['pricing']->slug ); ?>"><?php echo esc_html( $data['pricing']->name ); ?></span>
			<?php endif; ?>
		<?php endif; ?>
		<?php if ( null !== $data['rating'] ) : ?>
			<span class="clipto-rating-badge" title="<?php echo esc_attr__( 'Editorial rating — assigned by our editors, not aggregated from user reviews', 'clipto-core' ); ?>">
				<svg aria-hidden="true" viewBox="0 0 20 20" width="13" height="13"><path fill="currentColor" d="M10 1l2.6 5.9 6.4.6-4.8 4.3 1.4 6.2L10 14.9 4.4 18l1.4-6.2L1 7.5l6.4-.6z"/></svg>
				<?php echo esc_html( number_format_i18n( $data['rating'], 1 ) ); ?>/5
				<span class="screen-reader-text"><?php esc_html_e( '(editorial rating, not a user review average)', 'clipto-core' ); ?></span>
			</span>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Renders one AI Tool card. Shared by the [clipto_tools_grid] shortcode
 * and, when this plugin is active, by the theme's archive.php for the
 * actual clipto_tool post-type archive — so the real /ai-tools/ URL gets
 * the same premium card treatment as the shortcode, not a generic post
 * card. Takes an explicit ID (rather than relying on the global $post)
 * so it works correctly regardless of which loop calls it.
 *
 * The tool name links to the tool's own detail page (/ai-tools/{slug}/);
 * "View Tool" remains the outbound link to the official website.
 *
 * $loading: '' lets WordPress core choose eager/lazy for the logo (it keeps
 * the first, likely above-the-fold images eager); 'lazy' forces lazy for
 * sections known to be below the fold.
 */
function clipto_render_tool_card( $tool_id = null, $heading_tag = 'h3', $loading = '' ) {
	$data = clipto_get_tool_data( $tool_id );
	if ( ! $data ) {
		return;
	}
	$tool_id     = $data['id'];
	$heading_tag = in_array( $heading_tag, array( 'h2', 'h3', 'h4' ), true ) ? $heading_tag : 'h3';
	?>
	<article class="clipto-tool-card">
		<?php if ( has_post_thumbnail( $tool_id ) ) : ?>
			<div class="clipto-tool-card__logo">
				<?php echo get_the_post_thumbnail( $tool_id, 'clipto-card', array_filter( array( 'loading' => 'lazy' === $loading ? 'lazy' : '', 'decoding' => 'async', 'sizes' => '(max-width: 640px) calc(100vw - 32px), (max-width: 940px) 50vw, (max-width: 1240px) 33vw, 300px', 'alt' => wp_strip_all_tags( get_the_title( $tool_id ) ) ) ) ); ?>
			</div>
		<?php endif; ?>
		<div class="clipto-tool-card__body">
			<<?php echo esc_html( $heading_tag ); ?> class="clipto-tool-card__name"><a href="<?php echo esc_url( get_permalink( $tool_id ) ); ?>"><?php echo esc_html( get_the_title( $tool_id ) ); ?></a></<?php echo esc_html( $heading_tag ); ?>>
			<?php clipto_render_tool_badges( $data ); ?>
			<p class="clipto-tool-card__desc"><?php echo esc_html( $data['summary'] ? $data['summary'] : wp_trim_words( get_the_excerpt( $tool_id ), 18 ) ); ?></p>
			<?php if ( $data['url'] ) : ?>
				<a class="clipto-tool-card__cta" href="<?php echo esc_url( $data['url'] ); ?>" target="_blank" rel="nofollow noopener noreferrer">
					<?php esc_html_e( 'View Tool', 'clipto-core' ); ?>
					<span class="screen-reader-text">
						<?php
						/* translators: %s: tool name */
						echo esc_html( sprintf( __( '%s (official website, opens in a new tab)', 'clipto-core' ), get_the_title( $tool_id ) ) );
						?>
					</span>
				</a>
			<?php endif; ?>
		</div>
	</article>
	<?php
}

/**
 * ------------------------------------------------------------------
 * 6. SHORTCODE: [clipto_tools_grid]
 * ------------------------------------------------------------------
 */
function clipto_tools_grid_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'category' => '',
			'pricing'  => '',
			'count'    => 12,
			'order'    => 'DESC',
			'orderby'  => 'date',
			'heading'  => 'h3',
		),
		$atts,
		'clipto_tools_grid'
	);

	$count            = max( 1, min( 50, (int) $atts['count'] ) );
	$order            = 'ASC' === strtoupper( $atts['order'] ) ? 'ASC' : 'DESC';
	$allowed_orderby  = array( 'date', 'title', 'rand', 'menu_order' );
	$orderby          = in_array( $atts['orderby'], $allowed_orderby, true ) ? $atts['orderby'] : 'date';
	$heading          = in_array( $atts['heading'], array( 'h2', 'h3', 'h4' ), true ) ? $atts['heading'] : 'h3';

	$query_args = array(
		'post_type'           => 'clipto_tool',
		'post_status'         => 'publish',
		'posts_per_page'      => $count,
		'orderby'             => $orderby,
		'order'               => $order,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);

	$tax_query = array();
	if ( $atts['category'] && term_exists( sanitize_title( $atts['category'] ), 'clipto_tool_category' ) ) {
		$tax_query[] = array(
			'taxonomy' => 'clipto_tool_category',
			'field'    => 'slug',
			'terms'    => sanitize_title( $atts['category'] ),
		);
	}
	if ( $atts['pricing'] && term_exists( sanitize_title( $atts['pricing'] ), 'clipto_pricing' ) ) {
		$tax_query[] = array(
			'taxonomy' => 'clipto_pricing',
			'field'    => 'slug',
			'terms'    => sanitize_title( $atts['pricing'] ),
		);
	}
	if ( ! empty( $tax_query ) ) {
		$query_args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery -- bounded, term-validated above.
	}

	$query = new WP_Query( $query_args );

	ob_start();

	if ( ! $query->have_posts() ) {
		echo '<div class="clipto-empty-state"><p>' . esc_html__( 'No AI tools found yet.', 'clipto-core' ) . '</p></div>';
		return ob_get_clean();
	}

	echo '<div class="clipto-grid clipto-grid--tools">';
	while ( $query->have_posts() ) {
		$query->the_post();
		clipto_render_tool_card( get_the_ID(), $heading );
	}
	echo '</div>';
	wp_reset_postdata();

	return ob_get_clean();
}
add_shortcode( 'clipto_tools_grid', 'clipto_tools_grid_shortcode' );

/**
 * ------------------------------------------------------------------
 * 7. AUTHOR PROFILE FIELDS (Dashboard > Users > Profile)
 * ------------------------------------------------------------------
 * Author metadata (expertise level, social links) is data, not
 * presentation, so the editing screen lives here rather than in the
 * theme — it should survive a theme switch. Expertise is stored as
 * its human-readable label text directly (validated against the
 * fixed list below at save time), so the theme's read-only display
 * helper needs no matching lookup table and works independently of
 * whether this plugin is active.
 */
function clipto_core_expertise_levels() {
	return array(
		__( 'Contributor', 'clipto-core' ),
		__( 'Writer', 'clipto-core' ),
		__( 'Specialist', 'clipto-core' ),
		__( 'Expert', 'clipto-core' ),
		__( 'Senior Expert', 'clipto-core' ),
		__( 'Editor', 'clipto-core' ),
	);
}

function clipto_core_social_fields() {
	return array(
		'clipto_linkedin'  => __( 'LinkedIn URL', 'clipto-core' ),
		'clipto_twitter'   => __( 'X / Twitter URL', 'clipto-core' ),
		'clipto_youtube'   => __( 'YouTube URL', 'clipto-core' ),
		'clipto_instagram' => __( 'Instagram URL', 'clipto-core' ),
		'clipto_facebook'  => __( 'Facebook URL', 'clipto-core' ),
		'clipto_website'   => __( 'Personal Website URL', 'clipto-core' ),
	);
}

function clipto_core_add_user_profile_fields( $user ) {
	$expertise = get_user_meta( $user->ID, 'clipto_expertise_level', true );
	wp_nonce_field( 'clipto_core_save_user_profile', 'clipto_core_user_profile_nonce' );
	?>
	<h2><?php esc_html_e( 'Clipto Author Profile', 'clipto-core' ); ?></h2>
	<table class="form-table">
		<tr>
			<th><label for="clipto_expertise_level"><?php esc_html_e( 'Expertise Level', 'clipto-core' ); ?></label></th>
			<td>
				<select name="clipto_expertise_level" id="clipto_expertise_level">
					<option value=""><?php esc_html_e( '— Not set —', 'clipto-core' ); ?></option>
					<?php foreach ( clipto_core_expertise_levels() as $label ) : ?>
						<option value="<?php echo esc_attr( $label ); ?>" <?php selected( $expertise, $label ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'Editorial trust label shown on this author\'s articles and author page.', 'clipto-core' ); ?></p>
			</td>
		</tr>
		<?php
		foreach ( clipto_core_social_fields() as $key => $label ) :
			$value = get_user_meta( $user->ID, $key, true );
			?>
			<tr>
				<th><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
				<td><input type="url" class="regular-text" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="https://" /></td>
			</tr>
		<?php endforeach; ?>
	</table>
	<?php
}
add_action( 'show_user_profile', 'clipto_core_add_user_profile_fields' );
add_action( 'edit_user_profile', 'clipto_core_add_user_profile_fields' );

function clipto_core_save_user_profile_fields( $user_id ) {
	if ( ! isset( $_POST['clipto_core_user_profile_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['clipto_core_user_profile_nonce'] ) ), 'clipto_core_save_user_profile' ) ) {
		return false;
	}
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return false;
	}

	if ( isset( $_POST['clipto_expertise_level'] ) ) {
		$levels = clipto_core_expertise_levels();
		$value  = sanitize_text_field( wp_unslash( $_POST['clipto_expertise_level'] ) );
		if ( '' === $value || in_array( $value, $levels, true ) ) {
			update_user_meta( $user_id, 'clipto_expertise_level', $value );
		}
	}

	foreach ( array_keys( clipto_core_social_fields() ) as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			$url = esc_url_raw( trim( wp_unslash( $_POST[ $key ] ) ) );
			$url = ( '' === $url || wp_http_validate_url( $url ) ) ? $url : '';
			update_user_meta( $user_id, $key, $url );
		}
	}

	return true;
}
add_action( 'personal_options_update', 'clipto_core_save_user_profile_fields' );
add_action( 'edit_user_profile_update', 'clipto_core_save_user_profile_fields' );
