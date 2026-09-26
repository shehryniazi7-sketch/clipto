/**
 * Table of contents: marks the section being read (aria-current="true") in every TOC on
 * the page, glides the rail's vermilion indicator to it (transform only), keeps the
 * active entry visible when the rail itself scrolls, and closes the mobile disclosure
 * after a link is followed. Active-section tracking is IntersectionObserver based; a
 * scrollend/idle pass catches fast jumps that skip past the observation band.
 */
import { prefersReducedMotion } from './reveal.js';

const BAND_BOTTOM = 0.3; // A heading becomes current once it rises above 30% of the viewport.

function headerOffset() {
	const root = document.documentElement;
	const styles = getComputedStyle(root);
	const px = (value) => {
		const probe = document.createElement('div');
		probe.style.cssText = `position:absolute;visibility:hidden;height:${value}`;
		document.body.appendChild(probe);
		const h = probe.getBoundingClientRect().height;
		probe.remove();
		return h;
	};
	const header = document.querySelector('[data-site-header], .site-header');
	if (header) {
		const r = header.getBoundingClientRect();
		if (getComputedStyle(header).position === 'sticky' || getComputedStyle(header).position === 'fixed') {
			return Math.max(0, r.bottom);
		}
	}
	return px(styles.getPropertyValue('--header-h') || '4rem');
}

export function initToc() {
	const navs = Array.from(document.querySelectorAll('[data-toc]'));
	if (!navs.length) return;

	const links = Array.from(document.querySelectorAll('[data-toc-link]'));
	const byId = new Map();
	links.forEach((link) => {
		const id = decodeURIComponent((link.getAttribute('href') || '').slice(1));
		if (!id) return;
		if (!byId.has(id)) byId.set(id, []);
		byId.get(id).push(link);
	});

	const headings = Array.from(byId.keys())
		.map((id) => document.getElementById(id))
		.filter(Boolean);
	if (!headings.length) return;

	const indicators = navs
		.map((nav) => ({ nav, el: nav.querySelector('[data-toc-indicator]') }))
		.filter((x) => x.el);

	let currentId = null;
	let locked = false;
	let lockTimer = 0;

	const moveIndicator = (id) => {
		indicators.forEach(({ nav, el }) => {
			const link = id ? nav.querySelector(`[data-toc-link][href="#${CSS.escape(id)}"]`) : null;
			if (!link || link.offsetParent === null) {
				el.classList.remove('is-visible');
				return;
			}
			const track = el.parentElement;
			const top = link.getBoundingClientRect().top - track.getBoundingClientRect().top;
			const height = link.offsetHeight;
			el.style.transform = `translate3d(0, ${top.toFixed(1)}px, 0) scaleY(${(height / 100).toFixed(3)})`;
			el.classList.add('is-visible');
			if (!el.classList.contains('is-ready')) {
				// First placement is instant; later moves glide.
				requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('is-ready')));
			}
			// Keep the active entry in view when the rail scrolls on its own.
			const scroller = nav.closest('.reading__rail-inner');
			if (scroller && scroller.scrollHeight > scroller.clientHeight) {
				const sr = scroller.getBoundingClientRect();
				const lr = link.getBoundingClientRect();
				if (lr.top < sr.top + 48 || lr.bottom > sr.bottom - 48) {
					scroller.scrollTo({
						top: scroller.scrollTop + (lr.top - sr.top) - sr.height / 3,
						behavior: prefersReducedMotion() ? 'auto' : 'smooth',
					});
				}
			}
		});
	};

	const setActive = (id) => {
		if (id === currentId) return;
		currentId = id;
		links.forEach((link) => {
			const on = id !== null && byId.get(id) && byId.get(id).includes(link);
			if (on) {
				link.setAttribute('aria-current', 'true');
			} else {
				link.removeAttribute('aria-current');
			}
		});
		document.querySelectorAll('.toc__item.is-parent-active').forEach((li) => li.classList.remove('is-parent-active'));
		if (id && byId.get(id)) {
			byId.get(id).forEach((link) => {
				const parent = link.closest('.toc__sub');
				const item = parent ? parent.closest('.toc__item') : null;
				if (item) item.classList.add('is-parent-active');
			});
		}
		moveIndicator(id);
	};

	let offset = headerOffset();

	const compute = () => {
		if (locked) return;
		const line = offset + window.innerHeight * BAND_BOTTOM;
		let active = null;
		for (const h of headings) {
			if (h.getBoundingClientRect().top <= line) {
				active = h.id;
			} else {
				break;
			}
		}
		// At the very end of the page the last sections may never reach the line.
		const doc = document.documentElement;
		if (window.innerHeight + window.scrollY >= doc.scrollHeight - 2) {
			const last = headings[headings.length - 1];
			if (last.getBoundingClientRect().top < window.innerHeight) active = last.id;
		}
		setActive(active);
	};

	let observer = null;
	const observe = () => {
		if (observer) observer.disconnect();
		if (!('IntersectionObserver' in window)) return;
		const bottom = Math.round(window.innerHeight * (1 - BAND_BOTTOM));
		observer = new IntersectionObserver(compute, {
			rootMargin: `-${Math.round(offset)}px 0px -${bottom}px 0px`,
			threshold: [0, 1],
		});
		headings.forEach((h) => observer.observe(h));
	};

	// Safety net for jumps that skip the observation band entirely.
	let idle = 0;
	const onScroll = () => {
		window.clearTimeout(idle);
		idle = window.setTimeout(compute, 140);
	};
	window.addEventListener('scroll', onScroll, { passive: true });

	let resizeTimer = 0;
	window.addEventListener('resize', () => {
		window.clearTimeout(resizeTimer);
		resizeTimer = window.setTimeout(() => {
			offset = headerOffset();
			observe();
			moveIndicator(currentId);
			compute();
		}, 150);
	});

	// Following a link: mark it at once, hold the observer until the scroll settles,
	// and fold the mobile disclosure away.
	const release = () => {
		locked = false;
		window.clearTimeout(lockTimer);
		compute();
	};
	links.forEach((link) => {
		link.addEventListener('click', () => {
			const id = decodeURIComponent((link.getAttribute('href') || '').slice(1));
			if (!document.getElementById(id)) return;
			setActive(id);
			locked = true;
			window.clearTimeout(lockTimer);
			lockTimer = window.setTimeout(release, prefersReducedMotion() ? 80 : 1200);
			if ('onscrollend' in window) {
				window.addEventListener('scrollend', () => window.setTimeout(release, 60), { once: true });
			}
			const details = link.closest('[data-toc-details]');
			if (details) details.open = false;
		});
	});

	observe();
	compute();
}
