<?php
/**
 * The template for displaying the footer.
 *
 * All theme JavaScript is intentionally kept here as one small inline
 * block rather than split into separate enqueued files — it is vanilla,
 * defensive, and this keeps the theme to exactly the requested file set
 * while also minimizing HTTP requests.
 *
 * @package Clipto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
</main><!-- #primary -->

<footer class="site-footer" id="colophon">
	<div class="clipto-container site-footer__inner">
		<nav class="footer-nav" aria-label="<?php esc_attr_e( 'Footer', 'clipto' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'clipto-footer',
					'container'      => false,
					'menu_class'     => 'footer-nav-list',
					'fallback_cb'    => false,
					'depth'          => 1,
				)
			);
			?>
		</nav>
		<p class="footer-copyright">
			&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All rights reserved.', 'clipto' ); ?>
		</p>
	</div>
</footer>

<?php wp_footer(); ?>

<script>
(function () {
	"use strict";

	/* ---------------------------------------------------------
	 * Mobile navigation
	 * --------------------------------------------------------- */
	(function () {
		var toggle = document.getElementById( 'clipto-nav-toggle' );
		var menu   = document.getElementById( 'clipto-nav-menu' );
		if ( ! toggle || ! menu ) { return; }

		function closeNav() {
			toggle.setAttribute( 'aria-expanded', 'false' );
			menu.classList.remove( 'is-open' );
		}
		function openNav() {
			toggle.setAttribute( 'aria-expanded', 'true' );
			menu.classList.add( 'is-open' );
		}
		toggle.addEventListener( 'click', function () {
			var expanded = toggle.getAttribute( 'aria-expanded' ) === 'true';
			if ( expanded ) { closeNav(); } else { openNav(); }
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && toggle.getAttribute( 'aria-expanded' ) === 'true' ) {
				closeNav();
				toggle.focus();
			}
		} );
		document.addEventListener( 'click', function ( e ) {
			// contains(), not ===: a tap on the hamburger bars targets the
			// inner icon <span>, which must not count as an "outside" click.
			if ( toggle.getAttribute( 'aria-expanded' ) === 'true' && ! menu.contains( e.target ) && ! toggle.contains( e.target ) ) {
				closeNav();
			}
		} );
	})();

	/* ---------------------------------------------------------
	 * Search toggle
	 * --------------------------------------------------------- */
	(function () {
		var toggle = document.getElementById( 'clipto-search-toggle' );
		var form   = document.getElementById( 'clipto-search-form' );
		if ( ! toggle || ! form ) { return; }
		toggle.addEventListener( 'click', function () {
			var expanded = toggle.getAttribute( 'aria-expanded' ) === 'true';
			toggle.setAttribute( 'aria-expanded', expanded ? 'false' : 'true' );
			form.classList.toggle( 'is-open', ! expanded );
			if ( ! expanded ) {
				var input = form.querySelector( 'input[type="search"]' );
				if ( input ) { input.focus(); }
			}
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && toggle.getAttribute( 'aria-expanded' ) === 'true' ) {
				toggle.setAttribute( 'aria-expanded', 'false' );
				form.classList.remove( 'is-open' );
			}
		} );
	})();

	/* ---------------------------------------------------------
	 * Sticky header state — class toggle only, rAF-throttled,
	 * no layout properties touched (avoids CLS/INP cost).
	 * --------------------------------------------------------- */
	(function () {
		var header = document.getElementById( 'masthead' );
		if ( ! header ) { return; }
		var ticking = false;
		function update() {
			header.classList.toggle( 'is-scrolled', window.scrollY > 8 );
			ticking = false;
		}
		window.addEventListener( 'scroll', function () {
			if ( ! ticking ) {
				window.requestAnimationFrame( update );
				ticking = true;
			}
		}, { passive: true } );
		update();
	})();

	/* ---------------------------------------------------------
	 * Scroll-reveal — progressive enhancement only. If
	 * IntersectionObserver isn't supported, the .js-reveal-ready
	 * class is never added, so CSS never hides anything.
	 * --------------------------------------------------------- */
	(function () {
		if ( ! ( 'IntersectionObserver' in window ) ) { return; }
		var items = document.querySelectorAll( '.clipto-reveal' );
		if ( ! items.length ) { return; }

		document.documentElement.classList.add( 'js-reveal-ready' );

		var observer = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					entry.target.classList.add( 'is-visible' );
					observer.unobserve( entry.target );
				}
			} );
		}, { threshold: 0.01, rootMargin: '0px 0px 400px 0px' } );

		items.forEach( function ( item ) { observer.observe( item ); } );
	})();

	/* ---------------------------------------------------------
	 * Bookmark feature — localStorage only, defensive throughout.
	 * --------------------------------------------------------- */
	(function () {
		var STORAGE_KEY = 'clipto_bookmarks';

		function readStore() {
			try {
				var raw = window.localStorage.getItem( STORAGE_KEY );
				if ( ! raw ) { return {}; }
				var data = JSON.parse( raw );
				return ( data && typeof data === 'object' ) ? data : {};
			} catch ( err ) {
				return {};
			}
		}
		function writeStore( data ) {
			try {
				window.localStorage.setItem( STORAGE_KEY, JSON.stringify( data ) );
				return true;
			} catch ( err ) {
				return false;
			}
		}
		function refreshButton( btn ) {
			var id = btn.getAttribute( 'data-post-id' );
			var store = readStore();
			var saved = !! ( id && store[ id ] );
			btn.setAttribute( 'aria-pressed', saved ? 'true' : 'false' );
			btn.classList.toggle( 'is-saved', saved );
			var label = btn.querySelector( '.clipto-bookmark-label' );
			if ( label ) {
				label.textContent = saved ? btn.getAttribute( 'data-label-saved' ) : btn.getAttribute( 'data-label-save' );
			}
		}

		document.addEventListener( 'DOMContentLoaded', function () {
			var buttons = document.querySelectorAll( '.clipto-bookmark-btn' );
			for ( var i = 0; i < buttons.length; i++ ) { refreshButton( buttons[ i ] ); }
		} );

		document.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest ? e.target.closest( '.clipto-bookmark-btn' ) : null;
			if ( ! btn ) { return; }
			var id = btn.getAttribute( 'data-post-id' );
			if ( ! id ) { return; }
			var store = readStore();
			if ( store[ id ] ) {
				delete store[ id ];
			} else {
				store[ id ] = {
					id: id,
					url: btn.getAttribute( 'data-permalink' ) || '',
					title: btn.getAttribute( 'data-title' ) || '',
					savedAt: Date.now()
				};
			}
			writeStore( store );
			refreshButton( btn );
		} );
	})();

})();
</script>
</body>
</html>
