/**
 * Masthead behaviour (template-parts/header/*):
 *   - .is-scrolled on the sticky header after 8px of scroll (passive, rAF-throttled)
 *   - AI Tools mega-menu: disclosure button + hover intent for fine pointers
 *   - mobile menu: native modal <dialog> sheet
 *
 * Also exports the small modal-dialog helpers shared with search.js: scroll lock,
 * animated close (CSS keyframes on .is-closing), backdrop click, Esc, focus return.
 */
import { prefersReducedMotion } from './reveal.js';

const DESKTOP = '(min-width: 64em)';
const FINE_HOVER = '(hover: hover) and (pointer: fine)';
const OPEN_DELAY = 120;
const CLOSE_DELAY = 250;
const SCROLL_OFFSET = 8;

const media = (query) => window.matchMedia(query);

/* ---------------------------------------------------------------------------
 * Modal dialog helpers
 * ------------------------------------------------------------------------ */

let scrollLocks = 0;

function lockScroll() {
	scrollLocks += 1;
	if (scrollLocks > 1) return;
	const root = document.documentElement;
	// Keep the layout still when the scrollbar disappears.
	const gap = window.innerWidth - root.clientWidth;
	root.classList.add('has-modal');
	if (gap > 0) document.body.style.paddingRight = `${gap}px`;
}

function unlockScroll() {
	scrollLocks = Math.max(0, scrollLocks - 1);
	if (scrollLocks) return;
	document.documentElement.classList.remove('has-modal');
	document.body.style.paddingRight = '';
}

/**
 * Wire a <dialog> once: Esc and backdrop clicks close it with the exit animation,
 * [data-dialog-close] buttons close it, and closing restores focus and scroll.
 *
 * @param {HTMLDialogElement} dialog
 * @param {{ onClose?: () => void }} [options]
 */
export function setupDialog(dialog, { onClose } = {}) {
	dialog.addEventListener('cancel', (event) => {
		// Play the exit animation; if the browser refuses the cancel it simply closes.
		if (!event.cancelable) return;
		event.preventDefault();
		closeDialog(dialog);
	});

	// The dialog element itself is the transparent full-viewport layer around the sheet,
	// so a press that starts and ends on it is a backdrop click.
	let pressedBackdrop = false;
	dialog.addEventListener('pointerdown', (event) => {
		pressedBackdrop = event.target === dialog;
	});
	dialog.addEventListener('click', (event) => {
		if (event.target.closest('[data-dialog-close]')) {
			closeDialog(dialog);
		} else if (event.target === dialog && pressedBackdrop) {
			closeDialog(dialog);
		}
		pressedBackdrop = false;
	});

	dialog.addEventListener('close', () => {
		clearTimeout(dialog._closeTimer);
		dialog.classList.remove('is-closing');
		if (dialog._locked) {
			dialog._locked = false;
			unlockScroll();
		}
		const opener = dialog._opener;
		dialog._opener = null;
		if (opener && opener.isConnected && typeof opener.focus === 'function') {
			opener.focus({ preventScroll: true });
		}
		if (onClose) onClose();
	});
}

/**
 * Open a dialog modally. The entry animation is pure CSS ([open] keyframes).
 *
 * @param {HTMLDialogElement} dialog
 * @param {Element|null} [opener] Element to return focus to (defaults to the focused one).
 */
export function openDialog(dialog, opener) {
	if (!dialog) return;
	if (dialog.open) {
		if (!dialog.classList.contains('is-closing')) return;
		// Re-opened mid-exit: cancel the exit and keep it open.
		clearTimeout(dialog._closeTimer);
		dialog.classList.remove('is-closing');
		return;
	}
	dialog._opener = opener || document.activeElement;
	dialog.classList.remove('is-closing');
	if (!dialog._locked) {
		dialog._locked = true;
		lockScroll();
	}
	dialog.showModal();
}

/**
 * Close a dialog after its exit animation (instant when motion is reduced).
 *
 * @param {HTMLDialogElement} dialog
 * @param {{ instant?: boolean }} [options]
 */
export function closeDialog(dialog, { instant = false } = {}) {
	if (!dialog || !dialog.open) return;

	const finish = () => {
		clearTimeout(dialog._closeTimer);
		if (dialog.open) dialog.close();
	};

	if (instant || prefersReducedMotion()) {
		finish();
		return;
	}
	if (dialog.classList.contains('is-closing')) return;

	const sheet = dialog.firstElementChild;
	dialog.classList.add('is-closing');
	const onEnd = (event) => {
		if (event.target !== sheet) return;
		sheet.removeEventListener('animationend', onEnd);
		if (dialog.classList.contains('is-closing')) finish();
	};
	if (sheet) sheet.addEventListener('animationend', onEnd);
	// Safety net if animations are disabled or never fire.
	dialog._closeTimer = setTimeout(() => {
		if (sheet) sheet.removeEventListener('animationend', onEnd);
		if (dialog.classList.contains('is-closing')) finish();
	}, 450);
}

/* ---------------------------------------------------------------------------
 * Scroll state
 * ------------------------------------------------------------------------ */

function initScrollState(header) {
	let queued = false;
	let scrolled = null;

	const update = () => {
		queued = false;
		const next = window.scrollY > SCROLL_OFFSET;
		if (next !== scrolled) {
			scrolled = next;
			header.classList.toggle('is-scrolled', next);
		}
	};

	window.addEventListener(
		'scroll',
		() => {
			if (queued) return;
			queued = true;
			window.requestAnimationFrame(update);
		},
		{ passive: true }
	);
	update();
}

/* ---------------------------------------------------------------------------
 * AI Tools mega-menu
 * ------------------------------------------------------------------------ */

function initMega(button) {
	const panel = document.getElementById(button.getAttribute('aria-controls') || '');
	const item = button.closest('li');
	if (!panel || !item) return;

	const desktop = media(DESKTOP);
	const fineHover = media(FINE_HOVER);
	let openTimer = 0;
	let closeTimer = 0;
	let openedBy = '';
	let openedAt = 0;

	const isOpen = () => button.getAttribute('aria-expanded') === 'true';
	const clearTimers = () => {
		clearTimeout(openTimer);
		clearTimeout(closeTimer);
	};

	const onKeydown = (event) => {
		if (event.key !== 'Escape' || !isOpen()) return;
		const focusInside = item.contains(document.activeElement);
		close();
		if (focusInside) {
			event.preventDefault();
			button.focus();
		}
	};

	const onPointerdown = (event) => {
		if (!item.contains(event.target)) close();
	};

	function open(source) {
		clearTimers();
		if (isOpen()) return;
		openedBy = source;
		openedAt = Date.now();
		button.setAttribute('aria-expanded', 'true');
		item.classList.add('is-open');
		document.addEventListener('keydown', onKeydown);
		document.addEventListener('pointerdown', onPointerdown);
	}

	function close() {
		clearTimers();
		if (!isOpen()) return;
		openedBy = '';
		button.setAttribute('aria-expanded', 'false');
		item.classList.remove('is-open');
		document.removeEventListener('keydown', onKeydown);
		document.removeEventListener('pointerdown', onPointerdown);
	}

	button.addEventListener('click', (event) => {
		const source = event.detail === 0 ? 'keyboard' : 'pointer';
		if (!isOpen()) {
			open(source);
			return;
		}
		// Hover opened it a moment ago: the click means "keep it open", not "close".
		if (openedBy === 'hover' && Date.now() - openedAt < 700) {
			openedBy = source;
			return;
		}
		close();
	});

	button.addEventListener('keydown', (event) => {
		if (event.key !== 'ArrowDown') return;
		event.preventDefault();
		open('keyboard');
		const first = panel.querySelector('a[href]');
		if (first) first.focus();
	});

	// Tabbing out of the item closes it (clicks elsewhere are handled on pointerdown).
	item.addEventListener('focusout', (event) => {
		if (event.relatedTarget && !item.contains(event.relatedTarget)) close();
	});

	// Shift+Tab straight back into the panel while it is still fading out: keep it open,
	// or focus would sit inside a panel that is about to become hidden.
	panel.addEventListener('focusin', () => {
		if (!isOpen()) open('keyboard');
	});

	item.addEventListener('pointerenter', (event) => {
		if (event.pointerType !== 'mouse' || !fineHover.matches || !desktop.matches) return;
		clearTimeout(closeTimer);
		if (!isOpen()) openTimer = setTimeout(() => open('hover'), OPEN_DELAY);
	});

	item.addEventListener('pointerleave', (event) => {
		if (event.pointerType !== 'mouse') return;
		clearTimeout(openTimer);
		if (isOpen() && openedBy !== 'keyboard') closeTimer = setTimeout(close, CLOSE_DELAY);
	});

	desktop.addEventListener('change', () => close());
}

/* ---------------------------------------------------------------------------
 * Mobile menu
 * ------------------------------------------------------------------------ */

function initMobileMenu() {
	const dialog = document.getElementById('mobile-menu');
	const openers = Array.from(document.querySelectorAll('[data-menu-open]'));
	if (!dialog || !openers.length) return;

	const setExpanded = (expanded) => {
		openers.forEach((btn) => btn.setAttribute('aria-expanded', String(expanded)));
	};

	setupDialog(dialog, { onClose: () => setExpanded(false) });

	openers.forEach((btn) => {
		btn.addEventListener('click', () => {
			openDialog(dialog, btn);
			setExpanded(true);
		});
	});

	// Following an in-page link from the sheet (e.g. a #newsletter menu item) should
	// reveal the page, not the sheet.
	dialog.addEventListener('click', (event) => {
		const link = event.target.closest('a[href*="#"]');
		if (link && link.pathname === location.pathname && link.search === location.search) {
			// Focus follows the fragment (the browser moves the Tab starting point to the
			// target); returning it to the Menu button would send the next Tab back up the page.
			dialog._opener = null;
			closeDialog(dialog, { instant: true });
		}
	});

	// Growing past the breakpoint (rotation, resize) leaves no visible way to close it.
	media(DESKTOP).addEventListener('change', (event) => {
		if (event.matches && dialog.open) closeDialog(dialog, { instant: true });
	});
}

/* ---------------------------------------------------------------------------
 * Boot
 * ------------------------------------------------------------------------ */

export function initHeader() {
	const header = document.querySelector('[data-site-header]');
	if (header) initScrollState(header);
	document.querySelectorAll('[data-mega-toggle]').forEach(initMega);
	initMobileMenu();
}
