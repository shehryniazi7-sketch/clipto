# Clipto — Design System

> **Clipto is a field guide to AI.** The theme is a premium editorial publication, not a SaaS
> dashboard. Content is always the hero; typography, proportion and rhythm carry the premium feel —
> never decoration.

This document is the single source of truth for the Clipto WordPress theme. Every template, partial,
stylesheet and script in this repository follows it.

---

## 1. Brand concept

**"The field guide / the index."** Clipto organises a noisy field (AI) into a calm, well-edited index.
Three signature devices express this — use them consistently and sparingly:

| Device | What it is | Where it appears |
| --- | --- | --- |
| **Index numerals** | Tabular numerals in the sans face (`01`, `02` …) with a hairline | Section headers (`01 — AI Tools`), ordered story lists, the hero category index, TOC |
| **Crop marks** | Four thin corner brackets (from *clip*: the editor's crop marks) | The logo mark, image hover state (`.clip-frame`), the Key Takeaways component |
| **Hairline rules** | 1px rules in `--c-rule`, 2px ink rules above section headers | Section separation, lists, tables, meta rows |

The accent colour (**Clipto Vermilion**) is a *signal*: kickers, active states, the reading progress bar,
index highlights, key-takeaway markers. It is never used for large fills, gradients or glows.

### What Clipto is NOT
No neon, no giant gradients, no glassmorphism, no glowing borders, no floating dashboard panels, no fake AI
UI screenshots, no heavy drop shadows, no uniformly rounded cards, no constant looping animation.

---

## 2. Colour

Colour tokens live in `src/css/01-tokens.css` as CSS custom properties. Dark mode is a tuned palette,
**not** an inversion. `html[data-theme="dark"]` (explicit) or `prefers-color-scheme: dark` (when no
explicit choice) switches the tokens.

| Token | Light | Dark | Use |
| --- | --- | --- | --- |
| `--c-paper` | `#F6F4EE` | `#0D0F11` | Page background |
| `--c-surface` | `#FFFFFF` | `#15181B` | Raised surfaces (menus, dialogs, inputs) |
| `--c-tint` | `#ECE8DE` | `#1B1F23` | Quiet tinted blocks (callouts, table header, chips) |
| `--c-ink` | `#121417` | `#ECE9E1` | Primary text, strong rules |
| `--c-ink-2` | `#3B4047` | `#BDB8AD` | Secondary text, deks, excerpts |
| `--c-ink-3` | `#62666D` | `#8F8B82` | Metadata, captions (≥ 4.7:1 on all surfaces) |
| `--c-rule` | `#D9D4C7` | `#2A2F35` | Hairlines, borders |
| `--c-rule-strong` | `#121417` | `#ECE9E1` | Section top rules |
| `--c-accent` | `#E2491F` | `#FF6B3D` | Signal: bars, markers, progress, icons (non-text or large text) |
| `--c-accent-text` | `#B3360F` | `#FF8A63` | Accent-coloured text & links (AA on paper/surface/tint) |
| `--c-free` | `#1B7446` | `#4CC38A` | "Free" status dot + label only |
| `--c-invert-bg` / `--c-invert-ink` | `#121417` / `#F6F4EE` | `#ECE9E1` / `#121417` | Contrast band (Earn With AI). Inverts in both modes |

Measured contrast (WCAG): ink/paper 16.8 (L) 15.8 (D); ink-3/tint 4.7 (L) 4.9 (D); accent-text/paper
5.5 (L) 8.3 (D); free/paper 5.3 (L) 8.7 (D).

WordPress preset colours (`theme.json`) map to the same values and are re-pointed in dark mode, so editor
content that uses the palette adapts automatically.

## 3. Typography

Two self-hosted variable families (latin subset, `font-display: swap`, ~105 KB critical):

- **Newsreader** (serif, variable `wght` 200–800, roman + italic) — headlines, article body, deks.
  Gives the publication voice.
- **Schibsted Grotesk** (sans, variable `wght` 400–900) — UI, navigation, kickers, labels, metadata,
  buttons, H3+ inside articles, tables. Designed for a news publisher: clear, neutral, confident.
- Code: system monospace stack (no third web font).

| Token | Size | Use |
| --- | --- | --- |
| `--fs-2xs` | 0.6875rem | Micro labels |
| `--fs-xs` | 0.75rem | Kickers, badges, meta |
| `--fs-sm` | 0.875rem | UI text, captions, lists |
| `--fs-base` | 1rem | UI base |
| `--fs-md` | 1.125rem | Small headlines, lede UI |
| `--fs-lg` | clamp(1.25rem → 1.5rem) | Card headlines |
| `--fs-xl` | clamp(1.5rem → 2rem) | Feature headlines, article H2 |
| `--fs-2xl` | clamp(1.875rem → 2.75rem) | Section titles, lead story |
| `--fs-3xl` | clamp(2.25rem → 3.75rem) | Article H1, archive titles |
| `--fs-4xl` | clamp(2.75rem → 5.5rem) | Homepage hero only |
| `--fs-read` | clamp(1.125rem → 1.25rem) | Article body |

Rules:
- Headlines: Newsreader, weight 500–600, `letter-spacing: -0.015em` to `-0.03em` as size grows,
  `line-height` 1.05–1.2, `text-wrap: balance`.
- Body: `text-wrap: pretty`, line-height 1.65 for reading, 1.5 for UI.
- Kicker: sans 600, `--fs-xs`, uppercase, `letter-spacing: .08em`, colour `--c-accent-text`.
- Numerals in indices/metadata: `font-variant-numeric: tabular-nums`.
- Reading measure: `--measure: 40rem` (≈ 68–72 characters).

## 4. Space, grid, containers

- Base unit 4px. Tokens `--sp-1` (0.25rem) … `--sp-12` (8rem).
- Section rhythm: `--section-gap: clamp(3.5rem, 2rem + 5vw, 7rem)`.
- Containers: `.container` max `82.5rem` (1320px) with gutter `--gutter: clamp(1rem, 3.5vw, 2.5rem)`;
  `.container--narrow` `48rem`; `.container--read` `var(--measure)`.
- Grid: `.grid-12` 12-column CSS grid, gap `--grid-gap: clamp(1rem, 2.2vw, 2rem)`. Compositions collapse
  deliberately at 1024 / 768 / 480 — not by squashing.
- Breakpoints designed for: 320, 375, 390, 430, 768, 1024, 1280, 1440, 1920.

## 5. Shape, depth, borders

- Radius: `--r-xs: 2px` (images), `--r-sm: 4px` (buttons, inputs, badges), `--r-md: 8px`
  (dialogs, menus), `--r-pill: 999px` (chips only).
- Depth comes from rules and surfaces, not shadows. One shadow token exists for floating layers only
  (`--shadow-float`: mega-menu, search dialog).
- Borders: 1px `--c-rule`; section headers carry a 2px `--c-rule-strong` top rule.

## 6. Imagery

- Ratios: **16:9** wide/hero features, **3:2** lead stories & cards, **4:3** Earn With AI feature,
  **1:1** compact list thumbnails. Always set via `aspect-ratio` + `object-fit: cover` → zero CLS.
- Registered sizes: `clipto-wide` 1600×900, `clipto-feature` 1200×800, `clipto-card` 720×480,
  `clipto-thumb` 240×240.
- Hover: image scales to 1.035 over 600ms inside an overflow-hidden frame; crop marks fade in at the
  corners (`.clip-frame`). No overlays unless text sits on the image (it never does by default).
- Missing featured image → a typographic placeholder (`.media-fallback`) using the category name on
  `--c-tint` with crop marks — never a broken box.
- Captions: sans `--fs-xs`, `--c-ink-3`, left aligned, hairline above.
- LCP image (hero feature / article featured image): `loading="eager" fetchpriority="high"`; everything
  else lazy.

## 7. Components

| Component | Class root | Notes |
| --- | --- | --- |
| Section header | `.section-head` | 2px top rule, index numeral + kicker, title (serif), optional "All →" link |
| Kicker | `.kicker` | Category / label above headlines |
| Badge | `.badge` (`--free`, `--paid`, `--freemium`, `--new`) | Small caps sans on tint; Free uses a status dot, never a green fill |
| Chip | `.chip` | Pill links for real taxonomy terms (subcategory navigation) |
| Button | `.btn` (`--primary` ink, `--accent`, `--ghost`, `--link`) | 44px min height, arrow nudges 3px on hover |
| Text link | `.link-u` | Animated underline (background-size), thickness 1px → 2px |
| Story card | `clipto_card()` variants: `lead`, `feature`, `standard`, `compact`, `row`, `tool`, `numbered` | One renderer, many compositions |
| Meta row | `.meta` | Author · date · reading time, separated by thin dots |
| Crop frame | `.clip-frame` | Image wrapper with hover crop marks |
| Key Takeaways | `.is-style-clipto-takeaways` | Crop-mark bracket, numbered accents, serif items |
| Callouts | `.is-style-clipto-note` / `-tip` / `-warning` | Left rule + label, tint surface |
| Tool facts | `.tool-facts` | "At a glance" definition list: pricing, best for, platform, rating (only if set) |
| Pros / Cons | `.is-style-clipto-pros` / `-cons` | Two-column list with +/– markers |
| FAQ | core Details block, `.is-style-clipto-faq` | Native `<details>`, animated where supported |

## 8. Motion

Principles: fast, purposeful, subtle; only `transform` and `opacity` (plus colour on hover); never
animate layout properties; no continuous loops; everything readable with motion off.

| Token | Value | Use |
| --- | --- | --- |
| `--dur-1` | 120ms | Colour/hover feedback |
| `--dur-2` | 200ms | Buttons, underlines, chips |
| `--dur-3` | 320ms | Menus, dialogs, dropdowns |
| `--dur-4` | 600ms | Image scale, scroll reveals |
| `--ease-out` | `cubic-bezier(.2,.7,.2,1)` | Entrances |
| `--ease-in-out` | `cubic-bezier(.65,0,.35,1)` | Toggles |

- Scroll reveals: `[data-reveal]` → opacity 0→1 + translateY 14px→0 once, via IntersectionObserver.
  Hidden state applies only under `html.js` **and** `prefers-reduced-motion: no-preference`, so content is
  never trapped invisible.
- Stagger via `--i` custom property (`transition-delay: calc(var(--i) * 60ms)`), capped at 6 steps.
- Hero entrance runs once on load (CSS keyframes).
- Theme switch: View Transitions cross-fade where supported; otherwise instant.
- `prefers-reduced-motion: reduce` → all durations ≈ 0, reveals shown, smooth scroll off.

## 9. Accessibility

Skip link; landmarks (`header`, `nav[aria-label]`, `main#content`, `aside`, `footer`); one H1 per page and
ordered headings; visible `:focus-visible` ring (2px `--c-accent` + 2px offset); menus use disclosure
buttons with `aria-expanded`/`aria-controls`; search & mobile menu use native `<dialog>` (focus trap, Esc);
TOC marks the current section with `aria-current="true"`; FAQ uses native `<details>`; forms have labels;
touch targets ≥ 44×44px; contrast ≥ 4.5:1 for text.

## 10. Performance budget

- CSS: one stylesheet (`assets/css/main.css`, built & minified). Target < 60 KB raw.
- JS: `assets/js/main.js` (all pages, deferred, < 10 KB) + `assets/js/article.js` (singular only).
  No jQuery, no animation libraries, no third-party scripts.
- Fonts: 3 woff2 files, 2 preloaded. Italic is not preloaded.
- Emoji script/styles, oEmbed discovery JS and unused core block CSS are removed/split.
- All images use core responsive `srcset/sizes`; explicit aspect ratios everywhere → CLS ≈ 0.

## 11. Information architecture (fixed — no new URLs)

| Label | Destination |
| --- | --- |
| AI Tools | `/category/ai-tools/` (mega-menu lists its **existing** child categories, read live) |
| AI News | `/category/ai-news/` |
| Earn With AI | `/category/earn-with-ai/` |
| Free AI | `/tag/free-ai/` |
| AI Guide | `/artificial-intelligence-clipto-org/` (page) |

Destinations are resolved from live taxonomy/page data (`clipto_destinations()`); a destination that does
not exist on an installation is simply not rendered — the theme never prints dead or invented links.
A menu assigned to the *Primary* location in *Appearance → Menus* overrides the default navigation.
