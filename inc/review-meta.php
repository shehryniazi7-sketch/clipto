<?php
/**
 * AI tool facts: registered post meta (REST-enabled for the block editor) and a classic
 * "Tool facts (optional)" meta box on posts. The front end reads them via
 * clipto_tool_facts() / clipto_tool_logo() (inc/template-tags.php).
 *
 * Meta keys:
 *   _clipto_pricing    free|freemium|free-trial|paid
 *   _clipto_price      Free text, e.g. "From $12/mo"
 *   _clipto_best_for   Free text
 *   _clipto_platforms  Free text, e.g. "Web, iOS, Android"
 *   _clipto_website    URL (http/https)
 *   _clipto_rating     0–5, one decimal. Only shown when filled.
 *   _clipto_verdict    One-sentence verdict
 *   _clipto_logo_id    Attachment ID of the tool logo
 *
 * @package Clipto
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'clipto_tool_fact_fields' ) ) {
	/**
	 * Field definitions (key => type / label).
	 *
	 * @return array
	 */
	function clipto_tool_fact_fields() {
		return array(
			'_clipto_pricing'   => array(
				'type'  => 'string',
				'label' => __( 'Pricing model', 'clipto' ),
			),
			'_clipto_price'     => array(
				'type'  => 'string',
				'label' => __( 'Price', 'clipto' ),
			),
			'_clipto_best_for'  => array(
				'type'  => 'string',
				'label' => __( 'Best for', 'clipto' ),
			),
			'_clipto_platforms' => array(
				'type'  => 'string',
				'label' => __( 'Platforms', 'clipto' ),
			),
			'_clipto_website'   => array(
				'type'  => 'string',
				'label' => __( 'Website', 'clipto' ),
			),
			'_clipto_rating'    => array(
				'type'  => 'number',
				'label' => __( 'Rating', 'clipto' ),
			),
			'_clipto_verdict'   => array(
				'type'  => 'string',
				'label' => __( 'Verdict', 'clipto' ),
			),
			'_clipto_logo_id'   => array(
				'type'  => 'integer',
				'label' => __( 'Tool logo', 'clipto' ),
			),
		);
	}
}

/* -------------------------------------------------------------------------
 * Sanitizers
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'clipto_sanitize_pricing' ) ) {
	/**
	 * Pricing model: one of the known keys, or ''.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	function clipto_sanitize_pricing( $value ) {
		$value = sanitize_key( (string) $value );
		return array_key_exists( $value, clipto_pricing_labels() ) ? $value : '';
	}
}

if ( ! function_exists( 'clipto_sanitize_rating' ) ) {
	/**
	 * Rating: float 0–5 with one decimal, or '' when empty / not numeric.
	 *
	 * @param mixed $value Value.
	 * @return float|string
	 */
	function clipto_sanitize_rating( $value ) {
		if ( is_string( $value ) ) {
			$value = trim( str_replace( ',', '.', $value ) );
		}
		if ( '' === $value || null === $value || ! is_numeric( $value ) ) {
			return '';
		}
		return round( max( 0, min( 5, (float) $value ) ), 1 );
	}
}

if ( ! function_exists( 'clipto_sanitize_tool_website' ) ) {
	/**
	 * Website: an http(s) URL or ''.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	function clipto_sanitize_tool_website( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}
		if ( ! preg_match( '#^[a-z][a-z0-9+.-]*://#i', $value ) ) {
			$value = 'https://' . $value;
		}
		return esc_url_raw( $value, array( 'http', 'https' ) );
	}
}

if ( ! function_exists( 'clipto_sanitize_logo_id' ) ) {
	/**
	 * Logo: an existing image attachment ID, or 0.
	 *
	 * @param mixed $value Value.
	 * @return int
	 */
	function clipto_sanitize_logo_id( $value ) {
		$id = absint( $value );
		return ( $id && wp_attachment_is_image( $id ) ) ? $id : 0;
	}
}

if ( ! function_exists( 'clipto_tool_fact_sanitizer' ) ) {
	/**
	 * Sanitizer callback for a meta key.
	 *
	 * @param string $key Meta key.
	 * @return callable
	 */
	function clipto_tool_fact_sanitizer( $key ) {
		switch ( $key ) {
			case '_clipto_pricing':
				return 'clipto_sanitize_pricing';
			case '_clipto_website':
				return 'clipto_sanitize_tool_website';
			case '_clipto_rating':
				return 'clipto_sanitize_rating';
			case '_clipto_logo_id':
				return 'clipto_sanitize_logo_id';
			case '_clipto_verdict':
				return 'sanitize_textarea_field';
			default:
				return 'sanitize_text_field';
		}
	}
}

/* -------------------------------------------------------------------------
 * Registration
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'clipto_register_tool_meta' ) ) {
	/**
	 * Register the tool-facts meta for posts (REST-enabled, editable by post editors).
	 */
	function clipto_register_tool_meta() {
		$auth = static function ( $allowed, $meta_key, $object_id ) {
			return current_user_can( 'edit_post', (int) $object_id );
		};

		foreach ( clipto_tool_fact_fields() as $key => $field ) {
			$schema = array( 'type' => $field['type'] );
			if ( '_clipto_pricing' === $key ) {
				$schema['enum'] = array_merge( array( '' ), array_keys( clipto_pricing_labels() ) );
			} elseif ( '_clipto_rating' === $key ) {
				$schema['minimum'] = 0;
				$schema['maximum'] = 5;
			} elseif ( '_clipto_website' === $key ) {
				$schema['format'] = 'uri';
			}

			$args = array(
				'type'              => $field['type'],
				'description'       => $field['label'],
				'single'            => true,
				'sanitize_callback' => clipto_tool_fact_sanitizer( $key ),
				'auth_callback'     => $auth,
				'show_in_rest'      => array( 'schema' => $schema ),
			);
			if ( '_clipto_website' === $key ) {
				// An empty URL must stay valid in REST requests.
				unset( $args['show_in_rest']['schema']['format'] );
			}
			register_post_meta( 'post', $key, $args );
		}
	}
}
add_action( 'init', 'clipto_register_tool_meta' );

/* -------------------------------------------------------------------------
 * Meta box
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'clipto_add_tool_facts_box' ) ) {
	/**
	 * Add the meta box on posts (shown below the block editor canvas too).
	 */
	function clipto_add_tool_facts_box() {
		add_meta_box(
			'clipto-tool-facts',
			__( 'Tool facts (optional)', 'clipto' ),
			'clipto_render_tool_facts_box',
			'post',
			'normal',
			'default'
		);
	}
}
add_action( 'add_meta_boxes', 'clipto_add_tool_facts_box' );

if ( ! function_exists( 'clipto_render_tool_facts_box' ) ) {
	/**
	 * Meta box markup.
	 *
	 * @param WP_Post $post Post.
	 */
	function clipto_render_tool_facts_box( $post ) {
		wp_nonce_field( 'clipto_tool_facts_save', 'clipto_tool_facts_nonce' );

		$v = array();
		foreach ( array_keys( clipto_tool_fact_fields() ) as $key ) {
			$v[ $key ] = get_post_meta( $post->ID, $key, true );
		}
		$logo_id  = clipto_sanitize_logo_id( $v['_clipto_logo_id'] );
		$logo_img = $logo_id ? wp_get_attachment_image( $logo_id, 'thumbnail', false, array( 'alt' => '' ) ) : '';
		$rating   = '' === $v['_clipto_rating'] ? '' : clipto_sanitize_rating( $v['_clipto_rating'] );
		?>
		<style>
			.clipto-facts { display: grid; gap: 14px 20px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin: 6px 0 4px; }
			.clipto-facts__field label { display: block; font-weight: 600; margin-bottom: 4px; }
			.clipto-facts__field input[type="text"], .clipto-facts__field input[type="url"], .clipto-facts__field input[type="number"], .clipto-facts__field select, .clipto-facts__field textarea { width: 100%; max-width: none; }
			.clipto-facts__field--wide { grid-column: 1 / -1; }
			.clipto-facts__help { color: #646970; margin: 4px 0 0; font-size: 12px; }
			.clipto-facts__intro { margin: 0 0 10px; color: #3c434a; }
			.clipto-facts__logo { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
			.clipto-facts__preview { width: 56px; height: 56px; border: 1px solid #dcdcde; border-radius: 4px; display: grid; place-items: center; overflow: hidden; background: #fff; }
			.clipto-facts__preview img { width: 100%; height: 100%; object-fit: contain; }
			.clipto-facts__preview:empty::before { content: "—"; color: #8c8f94; }
		</style>
		<p class="clipto-facts__intro">
			<?php esc_html_e( 'Fill these in for AI tool reviews. They appear in an "At a glance" box above the article and on tool cards. Leave everything empty for regular articles — nothing is shown.', 'clipto' ); ?>
		</p>
		<div class="clipto-facts">
			<div class="clipto-facts__field">
				<label for="clipto-pricing"><?php esc_html_e( 'Pricing model', 'clipto' ); ?></label>
				<select id="clipto-pricing" name="clipto_facts[_clipto_pricing]">
					<option value=""><?php esc_html_e( '— Not set —', 'clipto' ); ?></option>
					<?php foreach ( clipto_pricing_labels() as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $v['_clipto_pricing'], $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="clipto-facts__field">
				<label for="clipto-price"><?php esc_html_e( 'Price', 'clipto' ); ?></label>
				<input type="text" id="clipto-price" name="clipto_facts[_clipto_price]" value="<?php echo esc_attr( $v['_clipto_price'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. From $12/mo', 'clipto' ); ?>">
			</div>
			<div class="clipto-facts__field">
				<label for="clipto-best-for"><?php esc_html_e( 'Best for', 'clipto' ); ?></label>
				<input type="text" id="clipto-best-for" name="clipto_facts[_clipto_best_for]" value="<?php echo esc_attr( $v['_clipto_best_for'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. Long-form drafts', 'clipto' ); ?>">
			</div>
			<div class="clipto-facts__field">
				<label for="clipto-platforms"><?php esc_html_e( 'Platforms', 'clipto' ); ?></label>
				<input type="text" id="clipto-platforms" name="clipto_facts[_clipto_platforms]" value="<?php echo esc_attr( $v['_clipto_platforms'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. Web, iOS, Android', 'clipto' ); ?>">
			</div>
			<div class="clipto-facts__field">
				<label for="clipto-website"><?php esc_html_e( 'Website', 'clipto' ); ?></label>
				<input type="url" id="clipto-website" name="clipto_facts[_clipto_website]" value="<?php echo esc_attr( $v['_clipto_website'] ); ?>" placeholder="https://" inputmode="url">
				<p class="clipto-facts__help"><?php esc_html_e( 'Opens in a new tab and is marked nofollow.', 'clipto' ); ?></p>
			</div>
			<div class="clipto-facts__field">
				<label for="clipto-rating"><?php esc_html_e( 'Rating (0–5)', 'clipto' ); ?></label>
				<input type="number" id="clipto-rating" name="clipto_facts[_clipto_rating]" value="<?php echo esc_attr( (string) $rating ); ?>" min="0" max="5" step="0.1" inputmode="decimal">
				<p class="clipto-facts__help"><?php esc_html_e( 'Optional. The rating is only shown on the site when you fill it in — leave it empty if you have not scored the tool.', 'clipto' ); ?></p>
			</div>
			<div class="clipto-facts__field clipto-facts__field--wide">
				<label for="clipto-verdict"><?php esc_html_e( 'Verdict', 'clipto' ); ?></label>
				<textarea id="clipto-verdict" name="clipto_facts[_clipto_verdict]" rows="2" placeholder="<?php esc_attr_e( 'One sentence: who should use it and why.', 'clipto' ); ?>"><?php echo esc_textarea( $v['_clipto_verdict'] ); ?></textarea>
			</div>
			<div class="clipto-facts__field clipto-facts__field--wide">
				<span class="clipto-facts__label" id="clipto-logo-label"><strong><?php esc_html_e( 'Tool logo', 'clipto' ); ?></strong></span>
				<div class="clipto-facts__logo" data-clipto-logo>
					<div class="clipto-facts__preview" data-clipto-logo-preview><?php echo $logo_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped image markup. ?></div>
					<input type="hidden" name="clipto_facts[_clipto_logo_id]" value="<?php echo esc_attr( $logo_id ? (string) $logo_id : '' ); ?>" data-clipto-logo-input>
					<button type="button" class="button" data-clipto-logo-select aria-describedby="clipto-logo-label"><?php echo esc_html( $logo_id ? __( 'Replace logo', 'clipto' ) : __( 'Choose logo', 'clipto' ) ); ?></button>
					<button type="button" class="button-link button-link-delete" data-clipto-logo-remove<?php echo $logo_id ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove', 'clipto' ); ?></button>
				</div>
				<p class="clipto-facts__help"><?php esc_html_e( 'Square image, ideally SVG or PNG on a transparent background. Used on tool cards and in the "At a glance" box.', 'clipto' ); ?></p>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'clipto_save_tool_facts' ) ) {
	/**
	 * Save handler: nonce, autosave/revision guard, capability check, per-field sanitizing.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	function clipto_save_tool_facts( $post_id, $post ) {
		if ( ! isset( $_POST['clipto_tool_facts_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['clipto_tool_facts_nonce'] ) ), 'clipto_tool_facts_save' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( ! $post instanceof WP_Post || 'post' !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Values are sanitized per key below.
		$input = isset( $_POST['clipto_facts'] ) && is_array( $_POST['clipto_facts'] ) ? wp_unslash( $_POST['clipto_facts'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		foreach ( array_keys( clipto_tool_fact_fields() ) as $key ) {
			$raw   = isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) ? (string) $input[ $key ] : '';
			$value = call_user_func( clipto_tool_fact_sanitizer( $key ), $raw );

			if ( '' === $value || null === $value || ( '_clipto_logo_id' === $key && ! $value ) ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $value );
			}
		}
	}
}
add_action( 'save_post_post', 'clipto_save_tool_facts', 10, 2 );

if ( ! function_exists( 'clipto_tool_facts_admin_assets' ) ) {
	/**
	 * Media library picker for the logo field (post edit screens only).
	 *
	 * @param string $hook Admin page hook.
	 */
	function clipto_tool_facts_admin_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || 'post' !== $screen->post_type || ! current_user_can( 'upload_files' ) ) {
			return;
		}
		wp_enqueue_media();

		$i18n = array(
			'title'   => __( 'Choose the tool logo', 'clipto' ),
			'button'  => __( 'Use this logo', 'clipto' ),
			'replace' => __( 'Replace logo', 'clipto' ),
			'choose'  => __( 'Choose logo', 'clipto' ),
		);

		$script = '(function(){var t=' . wp_json_encode( $i18n ) . ';var frame=null;'
			. 'document.addEventListener("click",function(e){'
			. 'var sel=e.target.closest("[data-clipto-logo-select]"),rem=e.target.closest("[data-clipto-logo-remove]");'
			. 'if(!sel&&!rem){return;}e.preventDefault();'
			. 'var box=(sel||rem).closest("[data-clipto-logo]");if(!box){return;}'
			. 'var input=box.querySelector("[data-clipto-logo-input]"),preview=box.querySelector("[data-clipto-logo-preview]"),'
			. 'choose=box.querySelector("[data-clipto-logo-select]"),remove=box.querySelector("[data-clipto-logo-remove]");'
			. 'if(rem){input.value="";preview.textContent="";remove.hidden=true;choose.textContent=t.choose;choose.focus();return;}'
			. 'if(!window.wp||!wp.media){return;}'
			. 'if(!frame){frame=wp.media({title:t.title,button:{text:t.button},library:{type:"image"},multiple:false});'
			. 'frame.on("select",function(){var a=frame.state().get("selection").first().toJSON();'
			. 'var src=(a.sizes&&a.sizes.thumbnail)?a.sizes.thumbnail.url:a.url;'
			. 'input.value=String(a.id);var img=document.createElement("img");img.src=src;img.alt="";'
			. 'preview.textContent="";preview.appendChild(img);remove.hidden=false;choose.textContent=t.replace;});}'
			. 'frame.open();});})();';

		wp_add_inline_script( 'media-editor', $script, 'after' );
	}
}
add_action( 'admin_enqueue_scripts', 'clipto_tool_facts_admin_assets' );
