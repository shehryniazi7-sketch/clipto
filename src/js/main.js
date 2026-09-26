/**
 * Clipto — site-wide behaviour (deferred). Each module is self-contained and
 * no-ops when its markup is absent. No dependencies.
 */
import { initReveal } from './modules/reveal.js';
import { initTheme } from './modules/theme.js';
import { initHeader } from './modules/header.js';
import { initSearch } from './modules/search.js';
import { initNewsletter } from './modules/newsletter.js';

// Reveal first (it un-hides content), and each module in isolation: one failing must not
// disable the others.
const boot = () => {
	[initReveal, initTheme, initHeader, initSearch, initNewsletter].forEach((init) => {
		try {
			init();
		} catch (error) {
			console.error(error);
		}
	});
};

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
	boot();
}
