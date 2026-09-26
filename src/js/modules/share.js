/**
 * Share actions. Copy link (Clipboard API with a legacy fallback) toggles .is-copied —
 * the CSS swaps the link icon for a check and "Copy link" for "Copied" inside a fixed-width
 * cell, so nothing around the button moves — and announces "Link copied" through a polite
 * live region; it resets after a moment. On touch devices that support the Web Share API an
 * extra button opens the native share sheet. X / LinkedIn are plain links.
 */
const RESET_AFTER = 2400;

async function copyText(text) {
	try {
		if (navigator.clipboard && window.isSecureContext) {
			await navigator.clipboard.writeText(text);
			return true;
		}
	} catch (e) {
		// Fall through to the legacy path.
	}
	const field = document.createElement('textarea');
	field.value = text;
	field.setAttribute('readonly', '');
	field.style.position = 'fixed';
	field.style.top = '0';
	field.style.left = '0';
	field.style.opacity = '0';
	document.body.appendChild(field);
	field.select();
	let ok = false;
	try {
		ok = document.execCommand('copy');
	} catch (e) {
		ok = false;
	}
	field.remove();
	return ok;
}

function announce(status, message) {
	if (!status) return;
	// Clear first so repeated identical messages are announced again.
	status.textContent = '';
	window.setTimeout(() => {
		status.textContent = message;
	}, 60);
}

export function initShare() {
	const groups = document.querySelectorAll('[data-share]');
	if (!groups.length) return;

	const canNativeShare =
		typeof navigator.share === 'function' && window.matchMedia('(pointer: coarse)').matches;

	groups.forEach((group) => {
		const url = group.dataset.shareUrl || window.location.href.split('#')[0];
		const title = group.dataset.shareTitle || document.title;
		const status = group.querySelector('[data-share-status]');

		const native = group.querySelector('[data-share-native]');
		if (native && canNativeShare) {
			native.hidden = false;
			native.addEventListener('click', () => {
				navigator.share({ title, url }).catch(() => {});
			});
		}

		const copy = group.querySelector('[data-share-copy]');
		if (!copy) return;
		let timer = 0;

		copy.addEventListener('click', async () => {
			const ok = await copyText(url);
			const message = ok ? copy.dataset.labelCopied : copy.dataset.labelFailed;
			window.clearTimeout(timer);
			copy.classList.toggle('is-copied', ok);
			announce(status, message || '');
			timer = window.setTimeout(() => {
				copy.classList.remove('is-copied');
				if (status) status.textContent = '';
			}, RESET_AFTER);
		});
	});
}
