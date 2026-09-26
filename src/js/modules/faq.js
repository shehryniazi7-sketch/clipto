/**
 * Disclosure motion for native <details> (FAQ items, plain Details blocks, the mobile
 * "In this article" TOC). Opening eases the content in with opacity + a few pixels of
 * translate — never height; closing is instant. The animation starts from the summary's
 * click (before the element renders open), so there is no flash of final state.
 *
 * FAQ items styled by 35-blocks.css already animate in CSS; they are left alone here.
 * Also opens a closed <details> when the URL fragment points inside it.
 */
import { prefersReducedMotion } from './reveal.js';
import { fragmentId } from './fragment.js';

const SELECTOR = 'details.wp-block-details, .entry-content details, details[data-animate-open]';
const CSS_ANIMATED = '.is-style-clipto-faq';

function animateOpen(details) {
	const kids = Array.from(details.children).filter((el) => el.tagName !== 'SUMMARY');
	kids.slice(0, 8).forEach((el, i) => {
		el.animate(
			[
				{ opacity: 0, transform: 'translate3d(0, -6px, 0)' },
				{ opacity: 1, transform: 'none' },
			],
			{
				duration: 280,
				delay: Math.min(i, 3) * 40,
				easing: 'cubic-bezier(.2,.7,.2,1)',
				fill: 'backwards',
			}
		);
	});
}

function openFromHash() {
	const id = fragmentId(window.location.hash.slice(1));
	if (!id) return;
	const target = document.getElementById(id);
	if (!target) return;
	let parent = target.closest('details:not([open])');
	while (parent) {
		parent.open = true;
		parent = parent.parentElement ? parent.parentElement.closest('details:not([open])') : null;
	}
}

export function initFaq() {
	const items = Array.from(document.querySelectorAll(SELECTOR)).filter(
		(d) => d.tagName === 'DETAILS'
	);

	openFromHash();
	window.addEventListener('hashchange', openFromHash);

	if (!items.length || prefersReducedMotion() || !Element.prototype.animate) return;

	items
		.filter((d) => !d.matches(CSS_ANIMATED))
		.forEach((details) => {
			const summary = details.querySelector(':scope > summary');
			let viaClick = false;

			if (summary) {
				summary.addEventListener('click', () => {
					if (details.open) return; // Closing: instant.
					viaClick = true;
					animateOpen(details);
				});
			}

			// Programmatic opens (find-in-page, script): animate on toggle instead.
			details.addEventListener('toggle', () => {
				if (details.open && !viaClick) animateOpen(details);
				viaClick = false;
			});
		});
}
