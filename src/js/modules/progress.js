/**
 * Reading progress bar. The bar maps the reading of .entry-content: empty while the
 * article's top is below the header, full when its last line reaches the bottom of the
 * viewport. Where CSS scroll-driven animations (view timelines) are supported the
 * stylesheet does all the work and this module steps aside; otherwise a passive,
 * rAF-throttled scroll handler writes one transform per frame (no layout writes).
 */
export const CSS_DRIVEN = '(animation-timeline: view()) and (timeline-scope: --a)';

export function initProgress() {
	const root = document.querySelector('[data-reading-progress]');
	if (!root) return;

	if (window.CSS && CSS.supports && CSS.supports(CSS_DRIVEN)) return;

	const bar = root.firstElementChild;
	const article = root.closest('article') || document;
	const target = article.querySelector('.entry-content');
	if (!bar || !target) return;

	let queued = false;
	let last = -1;

	const offsetTop = () => {
		const rect = root.getBoundingClientRect();
		return Math.max(0, rect.top);
	};

	const update = () => {
		queued = false;
		const top = offsetTop();
		const rect = target.getBoundingClientRect();
		const view = window.innerHeight - top;
		const range = rect.height - view;
		let p;
		if (range > 0) {
			p = (top - rect.top) / range;
		} else {
			// Shorter than the viewport: full once it is entirely visible.
			p = rect.bottom <= window.innerHeight ? 1 : 0;
		}
		p = Math.min(1, Math.max(0, p));
		// Skip sub-pixel changes to avoid needless style writes.
		if (Math.abs(p - last) < 0.001) return;
		last = p;
		bar.style.transform = `scaleX(${p.toFixed(4)})`;
	};

	const schedule = () => {
		if (queued) return;
		queued = true;
		requestAnimationFrame(update);
	};

	window.addEventListener('scroll', schedule, { passive: true });
	window.addEventListener('resize', schedule, { passive: true });
	window.addEventListener('load', schedule, { once: true });
	schedule();
}
