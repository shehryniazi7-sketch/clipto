/**
 * Clipto — reading experience (singular views only): reading progress, table of
 * contents, share actions, FAQ disclosure.
 */
import { initProgress } from './modules/progress.js';
import { initToc } from './modules/toc.js';
import { initShare } from './modules/share.js';
import { initFaq } from './modules/faq.js';

const boot = () => {
	initProgress();
	initToc();
	initShare();
	initFaq();
};

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
	boot();
}
