/**
 * Search overlay (template-parts/header/search-dialog.php): a native modal <dialog>
 * opened by any [data-search-open] element, by "/" and by Ctrl/Cmd+K — never while the
 * reader is typing in a field. Esc, the close button or a backdrop click closes it and
 * focus returns to where it was. Without JS the header shows a plain search link.
 */
import { setupDialog, openDialog, closeDialog } from './header.js';

const isTyping = (el) => {
	if (!el || el === document.body) return false;
	if (el.isContentEditable) return true;
	const tag = el.tagName;
	if (tag === 'TEXTAREA' || tag === 'SELECT') return true;
	if (tag !== 'INPUT') return false;
	const type = (el.getAttribute('type') || 'text').toLowerCase();
	return !['button', 'submit', 'reset', 'checkbox', 'radio', 'range', 'color', 'file', 'image'].includes(type);
};

export function initSearch() {
	const dialog = document.getElementById('search-dialog');
	if (!dialog) return;
	const input = dialog.querySelector('input[type="search"]');
	const form = input ? input.form : null;

	setupDialog(dialog);

	const open = (opener) => {
		openDialog(dialog, opener);
		if (input) {
			input.focus({ preventScroll: true });
			// A query carried over from the results page is selected, ready to replace.
			if (input.value) input.select();
		}
	};

	document.addEventListener('click', (event) => {
		const trigger = event.target.closest('[data-search-open]');
		if (!trigger) return;
		event.preventDefault();
		open(trigger);
	});

	document.addEventListener('keydown', (event) => {
		if (event.defaultPrevented || event.isComposing || event.repeat) return;
		const key = event.key;
		const slash = key === '/' && !event.ctrlKey && !event.metaKey && !event.altKey;
		const commandK = (key === 'k' || key === 'K') && (event.ctrlKey || event.metaKey) && !event.altKey && !event.shiftKey;
		if (!slash && !commandK) return;
		if (isTyping(event.target) || isTyping(document.activeElement)) return;
		// Another modal (the mobile menu) owns the screen.
		const other = document.querySelector('dialog[open]');
		if (other && other !== dialog) return;
		event.preventDefault();
		if (dialog.open) {
			if (input) input.focus();
			return;
		}
		open(null);
	});

	if (form && input) {
		// Esc closes the overlay in one press (a search field would first clear itself).
		input.addEventListener('keydown', (event) => {
			if (event.key !== 'Escape' || event.isComposing) return;
			event.preventDefault();
			closeDialog(dialog);
		});

		// An empty search would only list everything: keep the reader in the field instead.
		form.addEventListener('submit', (event) => {
			if (!input.value.trim()) {
				event.preventDefault();
				input.value = '';
				input.focus();
			}
		});
	}
}
