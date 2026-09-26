# Clipto — WordPress theme for clipto.org

A premium editorial theme for Clipto, the field guide to AI: tool reviews, AI news, guides and Earn With AI.

## Install (no coding needed)

1. In WordPress, go to **Appearance → Themes → Add New Theme → Upload Theme**.
2. Choose `clipto.zip` and click **Install Now**.
3. Click **Activate**.

Your posts, pages, categories and tags are not changed — only the design is.

## After activating (optional, 5 minutes)

The theme works immediately. These settings make it complete:

| Setting | Where | What it does |
| --- | --- | --- |
| **Logo** | Appearance → Customize → Site Identity | Upload a logo, or leave empty to use the built-in Clipto wordmark. |
| **Tagline** | Settings → General | Shown in the footer (e.g. “The field guide to AI”). |
| **Newsletter** | Appearance → Customize → Clipto: Newsletter | Connect your email provider (form action URL, or a newsletter plugin shortcode). The newsletter section only appears once it is connected — the theme never shows a form that doesn't work. |
| **Menus** (optional) | Appearance → Menus | Not required: the header already links AI Tools, AI News, Earn With AI, Free AI and the AI Guide. Assign a menu to *Primary* only if you want to change that. |
| **Tool facts** | Edit any review post → “Tool facts (optional)” box | Pricing, price, best for, platforms, rating, website, verdict and logo. Filled facts appear as the “At a glance” panel and on tool cards. Ratings only show when you enter one. |
| **Review components** | Block editor → Patterns → “Clipto editorial” | Key takeaways, callouts, pros & cons, pricing and comparison tables, FAQ, verdict and more. |

### How the site structure is used

The theme reads your existing structure — nothing is invented:

- **AI Tools** → category `ai-tools`; its sub-categories appear in the header mega-menu, homepage index and chips.
- **AI News** → category `ai-news` · **Earn With AI** → category `earn-with-ai` · **Free AI** → tag `free-ai`
- **AI Guide** → the page `artificial-intelligence-clipto-org`

If one of these doesn't exist, its link and homepage section are simply hidden.

## For developers

- Design system: [`DESIGN.md`](DESIGN.md).
- Styles live in `src/css/`, scripts in `src/js/`. Built files in `assets/` are committed, so no build step is needed to use the theme.
- Rebuild after editing sources: `npm install && npm run build` (or `npm run watch`).
- Requirements: WordPress 6.4+, PHP 7.4+.

Fonts: Newsreader and Schibsted Grotesk, self-hosted, SIL Open Font License (see `assets/fonts/`).
