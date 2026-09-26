/**
 * Newsletter band — progressive enhancement for template-parts/newsletter.php.
 *
 * Form action mode: client-side email check with an accessible live message
 * (aria-invalid + aria-describedby), then a "submitting" state on the button while the
 * browser performs the normal POST to the provider. There is no AJAX and no invented
 * success: the confirmation only appears when the provider redirects back with
 * ?clipto_subscribed=1.
 *
 * Confirmation: brings the message into view (when the redirect has no #newsletter),
 * focuses it for screen readers and removes the flag from the address bar.
 */

// Deliberately permissive: one @, something before it, a dotted domain after it.
const EMAIL = /^[^\s@]+@[^\s@.]+(\.[^\s@.]+)+$/;

const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function enhanceForm(form) {
	if (!form || form.dataset.enhanced) return;
	const input = form.querySelector('input[type="email"]');
	const button = form.querySelector('[type="submit"]');
	const msg = form.querySelector('[data-newsletter-msg]');
	if (!input || !button || !msg) return;

	form.dataset.enhanced = '1';
	// Native validation still guards the no-JS path; with JS we give better feedback.
	form.noValidate = true;

	const label = button.querySelector('.btn__label');
	const idleLabel = label ? label.textContent : '';
	const text = {
		empty: form.dataset.msgEmpty || '',
		invalid: form.dataset.msgInvalid || '',
		submitting: form.dataset.msgSubmitting || '',
	};
	let touched = false;
	let busy = false;

	const problem = () => {
		const value = input.value.trim();
		if (!value) return 'empty';
		return EMAIL.test(value) ? '' : 'invalid';
	};

	const render = (state) => {
		form.dataset.state = state || '';
		if (state === 'empty' || state === 'invalid') {
			input.setAttribute('aria-invalid', 'true');
			msg.textContent = text[state];
		} else {
			input.removeAttribute('aria-invalid');
			msg.textContent = state === 'submitting' ? text.submitting : '';
		}
	};

	const reset = () => {
		busy = false;
		form.removeAttribute('aria-busy');
		button.removeAttribute('aria-disabled');
		if (label) label.textContent = idleLabel;
		render('');
	};

	// Reward early, punish late: judge on blur, then re-check live once flagged.
	input.addEventListener('blur', () => {
		if (!input.value.trim()) {
			if (form.dataset.state === 'empty') render('');
			return;
		}
		touched = true;
		render(problem());
	});

	input.addEventListener('input', () => {
		if (!touched && !input.hasAttribute('aria-invalid')) return;
		const p = problem();
		// While typing, only clear errors (or keep the current one) — never nag mid-word.
		render(p && input.hasAttribute('aria-invalid') ? p : '');
	});

	form.addEventListener('submit', (event) => {
		if (busy) {
			event.preventDefault();
			return;
		}
		touched = true;
		const p = problem();
		if (p) {
			event.preventDefault();
			render(p);
			input.focus();
			return;
		}
		// Let the browser submit to the provider; just reflect that it is happening.
		input.value = input.value.trim();
		busy = true;
		form.setAttribute('aria-busy', 'true');
		button.setAttribute('aria-disabled', 'true');
		if (label && text.submitting) label.textContent = text.submitting;
		render('submitting');
	});

	// Returning via the back button restores the page from bfcache mid-"submitting".
	window.addEventListener('pageshow', (event) => {
		if (event.persisted) reset();
	});
}

function confirmSubscription(el) {
	if (!el || el.dataset.enhanced) return;
	el.dataset.enhanced = '1';

	try {
		const url = new URL(window.location.href);
		if (url.searchParams.has('clipto_subscribed')) {
			url.searchParams.delete('clipto_subscribed');
			const hash = url.hash || '#newsletter';
			window.history.replaceState(window.history.state, '', url.pathname + url.search + hash);
		}
	} catch (e) {
		/* URL API unavailable — leave the address alone. */
	}

	const reveal = () => {
		const rect = el.getBoundingClientRect();
		const inView = rect.top >= 0 && rect.bottom <= window.innerHeight;
		if (!inView) {
			// Jump (not glide) so the confirmation animation is seen, not scrolled past.
			const root = document.documentElement;
			const previous = root.style.scrollBehavior;
			root.style.scrollBehavior = 'auto';
			el.scrollIntoView({ block: reducedMotion() ? 'start' : 'center' });
			root.style.scrollBehavior = previous;
		}
		el.focus({ preventScroll: true });
	};

	// The browser's own scroll-to-#newsletter runs around load and resets focus to the
	// viewport, so wait for it before moving focus to the confirmation.
	const settle = () => requestAnimationFrame(() => setTimeout(reveal, 0));
	if (document.readyState === 'complete') {
		settle();
	} else {
		window.addEventListener('load', settle, { once: true });
	}
}

export function initNewsletter(root = document) {
	const band = root.querySelector('[data-newsletter]');
	if (band) {
		enhanceForm(band.querySelector('form[data-newsletter-form]'));
		confirmSubscription(band.querySelector('[data-newsletter-success]'));
	}

	// Customizer preview: after selective refresh re-renders the band or the footer, show
	// their scroll-reveal elements (the reveal observer only runs on page load) and
	// re-enhance the form.
	const wp = window.wp;
	if (root === document && wp && wp.customize && wp.customize.selectiveRefresh && !initNewsletter.bound) {
		initNewsletter.bound = true;
		wp.customize.selectiveRefresh.bind('partial-content-rendered', (placement) => {
			const id = placement && placement.partial ? placement.partial.id : '';
			if (id !== 'clipto_newsletter' && id !== 'clipto_footer') return;
			document
				.querySelectorAll('[data-clipto-newsletter] [data-reveal], .site-footer [data-reveal]')
				.forEach((el) => el.classList.add('is-revealed'));
			if (id === 'clipto_newsletter') initNewsletter();
		});
	}
}
