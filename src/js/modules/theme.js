/**
 * Light/dark theme toggle for every [data-theme-toggle] button.
 *
 * The head bootstrap (inc/assets.php) applies a saved choice before first paint by
 * setting html[data-theme]. With no saved choice the OS preference rules (CSS
 * prefers-color-scheme), and this module follows OS changes live — and choices made
 * in other tabs.
 *
 * Buttons are labelled with the action they perform ("Switch to dark theme"); an
 * element with [data-theme-label] inside a button receives the same text visibly.
 * Switching cross-fades with the View Transitions API when motion is welcome.
 */
import { prefersReducedMotion } from './reveal.js';

const KEY = 'clipto-theme';
const root = document.documentElement;
const darkQuery = window.matchMedia('(prefers-color-scheme: dark)');

const readStored = () => {
	try {
		const value = window.localStorage.getItem(KEY);
		return value === 'light' || value === 'dark' ? value : '';
	} catch (e) {
		return '';
	}
};

/** The theme currently on screen: an explicit choice, else the OS preference. */
const currentTheme = () => root.getAttribute('data-theme') || (darkQuery.matches ? 'dark' : 'light');

/** Label every toggle with its action and let the browser chrome follow an explicit choice. */
function sync() {
	const i18n = window.cliptoI18n || {};
	const label = currentTheme() === 'dark'
		? i18n.toLight || 'Switch to light theme'
		: i18n.toDark || 'Switch to dark theme';
	document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
		const visible = button.querySelector('[data-theme-label]');
		if (visible) visible.textContent = label;
		else button.setAttribute('aria-label', label);
	});

	const paper = root.hasAttribute('data-theme') ? getComputedStyle(root).getPropertyValue('--c-paper').trim() : '';
	document.querySelectorAll('meta[name="theme-color"]').forEach((meta) => {
		if (!meta.dataset.original) meta.dataset.original = meta.content;
		meta.content = paper || meta.dataset.original;
	});
}

/** Apply 'light' | 'dark', or '' to follow the OS again. */
function set(theme) {
	if (theme) root.setAttribute('data-theme', theme);
	else root.removeAttribute('data-theme');
	sync();
}

function toggle() {
	const next = currentTheme() === 'dark' ? 'light' : 'dark';
	const apply = () => {
		set(next);
		try {
			window.localStorage.setItem(KEY, next);
		} catch (e) {
			/* Storage blocked: the choice lasts for this page only. */
		}
	};
	if (document.startViewTransition && !prefersReducedMotion()) {
		try {
			document.startViewTransition(apply);
			return;
		} catch (e) {
			/* Fall through to an instant switch. */
		}
	}
	apply();
}

export function initTheme() {
	sync();
	document.querySelectorAll('[data-theme-toggle]').forEach((button) => button.addEventListener('click', toggle));
	darkQuery.addEventListener('change', () => {
		if (!readStored()) set('');
	});
	window.addEventListener('storage', (event) => {
		if (event.key === KEY) set(readStored());
	});
}
