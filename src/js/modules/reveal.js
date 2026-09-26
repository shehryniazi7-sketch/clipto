/**
 * Scroll reveal: adds .is-revealed to [data-reveal] elements once, as they enter
 * the viewport. CSS only hides elements once this module has started (html.reveal-ready,
 * screen, motion allowed), so a blocked, delayed or failing script can never leave
 * content invisible. Whatever is already on screen when it starts stays as painted.
 */
export const prefersReducedMotion = () =>
	window.matchMedia('(prefers-reduced-motion: reduce)').matches;

let focusWired = false;

export function initReveal(root = document) {
	const items = root.querySelectorAll('[data-reveal]:not(.is-revealed)');
	if (!items.length) return;

	if (prefersReducedMotion() || !('IntersectionObserver' in window)) {
		items.forEach((el) => el.classList.add('is-revealed'));
		return;
	}

	const io = new IntersectionObserver(
		(entries) => {
			entries.forEach((entry) => {
				if (entry.isIntersecting) {
					entry.target.classList.add('is-revealed');
					io.unobserve(entry.target);
				}
			});
		},
		{ rootMargin: '0px 0px -8% 0px', threshold: 0.08 }
	);

	// Anything on screen or above it was painted visible before this ran: keep it so (no
	// flash of hidden content). Only what is still below the fold waits for its reveal.
	const fold = window.innerHeight || document.documentElement.clientHeight;
	items.forEach((el) => {
		if (el.getBoundingClientRect().top < fold) {
			el.classList.add('is-revealed');
		} else {
			io.observe(el);
		}
	});
	document.documentElement.classList.add('reveal-ready');

	// Keyboard focus can land on a link the observer has not revealed yet (the browser
	// scrolls it into the bottom band the observer ignores): show it, and every
	// unrevealed [data-reveal] around it, at once so the focused element is never invisible.
	if (focusWired) return;
	focusWired = true;
	document.addEventListener('focusin', (event) => {
		let el = event.target instanceof Element ? event.target.closest('[data-reveal]') : null;
		while (el) {
			if (!el.classList.contains('is-revealed')) {
				el.classList.add('is-revealed');
				io.unobserve(el);
			}
			el = el.parentElement ? el.parentElement.closest('[data-reveal]') : null;
		}
	});
}
