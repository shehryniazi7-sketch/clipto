/**
 * Scroll reveal: adds .is-revealed to [data-reveal] elements once, as they enter
 * the viewport. CSS only hides elements under html.js + motion allowed, so this
 * module can never leave content invisible.
 */
export const prefersReducedMotion = () =>
	window.matchMedia('(prefers-reduced-motion: reduce)').matches;

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

	items.forEach((el) => {
		// Anything already above the fold on load reveals immediately (no flash of hidden content on reload mid-page).
		const r = el.getBoundingClientRect();
		if (r.bottom < 0) {
			el.classList.add('is-revealed');
		} else {
			io.observe(el);
		}
	});
}
