/**
 * Light/dark theme toggle for every [data-theme-toggle] button.
 *
 * The head bootstrap (inc/assets.php) applies a saved choice before first paint by
 * setting html[data-theme]. With no saved choice the OS preference rules (CSS
 * prefers-color-scheme), and this module follows OS changes live.
 *
 * Buttons are labelled with the action they perform ("Switch to dark theme"); an
 * element with [data-theme-label] inside a button receives the same text visibly.
 * Switching cross-fades with the View Transitions API when motion is welcome.
 */

const STORAGE_KEY = 'clipto-theme';
const root = document.documentElement;
const darkQuery = window.matchMedia('(prefers-color-scheme: dark)');
const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

const readStored = () => {
	try {
		const value = window.localStorage.getItem(STORAGE_KEY);
		return value === 'light' || value === 'dark' ? value : '';
	} catch (e) {
		return '';
	}
};

const store = (value) => {
	try {
		window.localStorage.setItem(STORAGE_KEY, value);
	} catch (e) {
		/* Private mode / storage blocked: the choice lasts for this page only. */
	}
};

/** The theme currently on screen: an explicit choice, else the OS preference. */
export const currentTheme = () => {
	const explicit = root.getAttribute('data-theme');
	if (explicit === 'light' || explicit === 'dark') return explicit;
	return darkQuery.matches ? 'dark' : 'light';
};

const strings = () => {
	const i18n = window.cliptoI18n || {};
	return {
		toDark: i18n.toDark || 'Switch to dark theme',
		toLight: i18n.toLight || 'Switch to light theme',
	};
};

/** Browser chrome colour: follow the page surface once the reader has chosen a theme. */
function syncThemeColor() {
	const metas = document.querySelectorAll('meta[name="theme-color"]');
	if (!metas.length) return;
	const explicit = root.hasAttribute('data-theme');
	const paper = explicit ? getComputedStyle(root).getPropertyValue('--c-paper').trim() : '';
	metas.forEach((meta) => {
		if (!meta.dataset.original) meta.dataset.original = meta.getAttribute('content') || '';
		meta.setAttribute('content', paper || meta.dataset.original);
	});
}

function syncButtons() {
	const theme = currentTheme();
	const { toDark, toLight } = strings();
	const label = theme === 'dark' ? toLight : toDark;
	document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
		const visible = button.querySelector('[data-theme-label]');
		if (visible) {
			visible.textContent = label;
			button.removeAttribute('aria-label');
		} else {
			button.setAttribute('aria-label', label);
		}
		button.dataset.currentTheme = theme;
	});
}

function apply(theme) {
	root.setAttribute('data-theme', theme);
	store(theme);
	syncButtons();
	syncThemeColor();
}

function toggle() {
	const next = currentTheme() === 'dark' ? 'light' : 'dark';
	const canTransition = typeof document.startViewTransition === 'function' && !motionQuery.matches;
	if (canTransition) {
		try {
			document.startViewTransition(() => apply(next));
			return;
		} catch (e) {
			/* Fall through to an instant switch. */
		}
	}
	apply(next);
}

export function initTheme() {
	const buttons = document.querySelectorAll('[data-theme-toggle]');

	// Honour a stored choice even if the bootstrap could not run (e.g. cached HTML without it).
	const stored = readStored();
	if (stored && root.getAttribute('data-theme') !== stored) root.setAttribute('data-theme', stored);

	syncButtons();
	syncThemeColor();

	buttons.forEach((button) => {
		if (button.dataset.themeReady) return;
		button.dataset.themeReady = '1';
		button.addEventListener('click', toggle);
	});

	const onSchemeChange = () => {
		if (!readStored()) {
			root.removeAttribute('data-theme');
			syncButtons();
			syncThemeColor();
		}
	};
	if (darkQuery.addEventListener) darkQuery.addEventListener('change', onSchemeChange);
	else if (darkQuery.addListener) darkQuery.addListener(onSchemeChange);

	// Another tab changed the theme.
	window.addEventListener('storage', (event) => {
		if (event.key !== STORAGE_KEY) return;
		const value = event.newValue === 'light' || event.newValue === 'dark' ? event.newValue : '';
		if (value) root.setAttribute('data-theme', value);
		else root.removeAttribute('data-theme');
		syncButtons();
		syncThemeColor();
	});
}
