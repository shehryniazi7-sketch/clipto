/**
 * Clipto build: concatenates src/css/*.css (in filename order) into assets/css/main.css and
 * bundles src/js entry points into assets/js/*.js. Output is committed so the theme works without
 * a build step on the server.
 *
 *   npm run build          production build (minified)
 *   npm run watch          rebuild on change
 */
import { readFileSync, readdirSync, writeFileSync, watch } from 'node:fs';
import { join } from 'node:path';
import { transform, browserslistToTargets } from 'lightningcss';
import * as esbuild from 'esbuild';

const ROOT = new URL('.', import.meta.url).pathname;
const CSS_SRC = join(ROOT, 'src/css');
const targets = {
	chrome: 100 << 16,
	firefox: 100 << 16,
	safari: (15 << 16) | (4 << 8),
};

function buildCss() {
	const files = readdirSync(CSS_SRC).filter((f) => f.endsWith('.css')).sort();
	const source = files
		.map((f) => `/* ${f} */\n` + readFileSync(join(CSS_SRC, f), 'utf8'))
		.join('\n');
	const { code, warnings } = transform({
		filename: 'main.css',
		code: Buffer.from(source),
		minify: true,
		targets,
		drafts: { customMedia: true },
	});
	warnings.forEach((w) => console.warn('[css]', w.message, w.loc));
	writeFileSync(join(ROOT, 'assets/css/main.css'), code);
	console.log(`css  ${files.length} partials → assets/css/main.css (${(code.length / 1024).toFixed(1)} KB)`);

	// Editor styles: tokens + article content + editorial block styles. Content partials scope
	// their selectors to `.entry-content`; in the editor that scope is the canvas itself, so it is
	// rewritten to `body`, which WordPress maps to `.editor-styles-wrapper`.
	const editorParts = ['01-tokens.css', '31-content.css', '35-blocks.css'].filter((f) => files.includes(f));
	const editorSource = editorParts
		.map((f) => readFileSync(join(CSS_SRC, f), 'utf8'))
		.join('\n')
		.replace(/\.entry-content(?![\w-])/g, 'body');
	const editor = transform({ filename: 'editor.css', code: Buffer.from(editorSource), minify: true, targets });
	writeFileSync(join(ROOT, 'assets/css/editor.css'), editor.code);
	console.log(`css  editor (${editorParts.join(', ')}) → assets/css/editor.css (${(editor.code.length / 1024).toFixed(1)} KB)`);
}

async function buildJs() {
	const result = await esbuild.build({
		entryPoints: {
			main: join(ROOT, 'src/js/main.js'),
			article: join(ROOT, 'src/js/article.js'),
		},
		outdir: join(ROOT, 'assets/js'),
		bundle: true,
		minify: true,
		format: 'iife',
		target: ['es2020'],
		metafile: true,
		logLevel: 'warning',
	});
	for (const [file, info] of Object.entries(result.metafile.outputs)) {
		console.log(`js   ${file.replace(ROOT, '')} (${(info.bytes / 1024).toFixed(1)} KB)`);
	}
}

async function buildAll() {
	try {
		buildCss();
		await buildJs();
	} catch (e) {
		console.error(e.message || e);
		process.exitCode = 1;
	}
}

await buildAll();

if (process.argv.includes('--watch')) {
	let t;
	const again = () => { clearTimeout(t); t = setTimeout(buildAll, 80); };
	watch(join(ROOT, 'src'), { recursive: true }, again);
	console.log('watching src/ …');
}
