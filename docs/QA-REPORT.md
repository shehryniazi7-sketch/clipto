# Clipto AI Pro — Real-WordPress QA & Finalization Report

**Scope:** Round 1 (Sections 1–7) is a production QA/finalization pass on the existing Clipto theme and Clipto Core plugin, not a redesign. Round 2 (Section 8) is the **single-post redesign you requested afterwards**. Across both rounds the theme file set, URLs and existing features are unchanged.

**Deliverables**

| File | Contents |
|---|---|
| `dist/clipto-ai-pro-final.zip` | Theme **v1.2.0** (folder `clipto-theme/`), which includes the single-post redesign in Section 8 |
| `dist/clipto-core-plugin-final.zip` | Plugin v1.1.0 (folder `clipto-core-plugin/`) |
| `docs/qa-screenshots/` | Selected final screenshots, plus one "BEFORE" shot. `single-post/` holds the redesign's before/after and element crops |

> The ZIPs keep the original folder names on purpose. WordPress identifies themes and plugins by folder, so uploading them replaces the existing install in place and it stays active. Renaming the folders would create a second theme/plugin.

---

## 1. Test environment (what was actually used)

| Component | Version / detail |
|---|---|
| WordPress | **7.1.2** (official `WordPress/WordPress` release tag. wordpress.org downloads are blocked in this sandbox, so core came via git) |
| Database | **MariaDB 10.11.14** (real server, not SQLite) |
| PHP | 8.4.19, `WP_DEBUG` + `WP_DEBUG_LOG` on, `error_reporting(E_ALL)` |
| Web server | PHP built-in server with a front-controller router (pretty permalinks `/%postname%/`). **Not Apache/nginx.** |
| Browser | Chromium 1194 (Playwright 1.63), headless. **Chromium only. No Firefox/WebKit/Safari testing was done.** |
| Tools | WP-CLI 2.x, axe-core (WCAG 2.0/2.1 A+AA + best-practice), PHPCS + WPCS 3 + PHPCompatibilityWP |

**Seeded content** (a WP-CLI script run against the real DB): 12 posts across the `ai-news`, `earn-with-ai` and `ai-tools` categories. This included a very long title, AI Summary meta on half the posts, posts with and without excerpts and featured images, the `free-ai` tag, and an "updated later" post. There were also 7 AI tools covering all pricing terms, 4 tool categories, ratings, URLs and logos, and one tool with a **very long name and no URL, rating or logo**. The rest was an author with bio/expertise/socials, 3 pages (one using `[clipto_tools_grid]`), primary and footer menus, and an approved comment.

---

## 2. Architecture as found (inspected before any change)

- **Plugin** owns the data layer. It registers the `clipto_tool` CPT (public, `has_archive`, slug `ai-tools`, supports editor/excerpt/thumbnail), the `clipto_pricing` (`/tool-pricing/`) and `clipto_tool_category` (`/tool-category/`) taxonomies, the Tool Details and AI Summary meta boxes, reading time, `[clipto_summary]`, `[clipto_tools_grid]`, author profile fields and `clipto_render_tool_card()`.
- **Theme** owns presentation. It has a deliberately fixed file set (`index/archive/single/page/author/header/footer/functions.php` + `style.css`) with **no** `search.php`, `404.php`, `comments.php` or CPT-specific templates. Shared render helpers live in `functions.php`, and `archive.php` branches for the tools archive. It prints meta/OG/Twitter tags and JSON-LD itself, and backs off when Yoast, Rank Math, AIOSEO or SEOPress is active.
- All JS is one small inline vanilla block in `footer.php`. There's one stylesheet.

I followed the theme's own convention (no new template files, branch inside existing ones) for every fix.

---

## 3. Step 4 — single `clipto_tool` pages (the known scope gap)

**Does the architecture intend single tool pages to show tool data? Yes.**
- The CPT is `public` with its own rewrite slug, so `/ai-tools/{slug}/` exists, is in `wp-sitemap.xml`, and shows up in site search.
- It `supports` the **editor**. That content is displayed nowhere except the single page (cards use the Short Summary/excerpt), so the pages are meant to be read.

**What the page looked like before** (see `qa-screenshots/BEFORE-tool-single-desktop-light.png`). It fell through to the article layout in `single.php`: "Published … · 1 min read · Save", an author box reading "0 articles published", "Next →" post navigation, and **no pricing, rating or tool URL**. When a tool's `post_author` is 0 (created via REST, import or CLI), the byline and author box linked to a broken `/author/` URL with an empty name, which axe flagged as `link-name`.

**What I implemented** (`single.php` branch → `clipto_render_tool_single()` in `functions.php`):
- Breadcrumb **Home / AI Tools / {Tool}**, with a matching BreadcrumbList schema.
- H1, a lede (Short Summary, falling back to the manual excerpt), then linked category and pricing badges (to their existing taxonomy archives), the editorial rating badge and an "Updated" date.
- A visible disclosure under a rating: *"an editorial score … not an average of user reviews"*.
- **Visit {Tool} ↗** (`rel="nofollow noopener noreferrer"`, new tab, SR text), shown only when a valid http(s) URL exists, plus **All AI Tools**.
- The featured image, the editor content, and **More {Category} tools** (up to 3 tool cards, lazy-loaded).
- No article byline, reading time, bookmark, author box or prev/next.

To make these pages reachable, the tool card **name now links to its detail page**. "View Tool" stays the outbound link to the official site. Without this the new pages would be orphaned, reachable only from search and the sitemap.

The plugin gained `clipto_get_tool_data()` (one validated source of URL, rating, terms and summary) and `clipto_render_tool_badges()`, shared by the card and the single page so they can't drift apart.

**Structured data decision:** tool pages get **BreadcrumbList only**. I did *not* add `SoftwareApplication`/`Review`/`AggregateRating`. The ratings are single editorial scores, not aggregated user reviews, and Google's SoftwareApplication rich result requires `offers` or `aggregateRating`/`review`, which we can't populate truthfully (no price data). Emitting it would either fabricate data or produce Search Console errors.

---

## 4. Bugs found and fixes made

"Pre-existing" means the bug was in the ZIPs as delivered, so it's not a regression from this pass.

| # | Severity | Bug (how found) | Fix |
|---|---|---|---|
| 1 | **High** | **Mobile menu showed only its first item.** `backdrop-filter` on `.site-header` makes the header the containing block for `position:fixed` children, so the full-screen `.clipto-nav-menu` was clipped to a **64px** strip and 4 of 5 links couldn't be tapped. This happens in every modern engine. *(Found in a Playwright screenshot, confirmed with `elementFromPoint`.)* Pre-existing. | Moved the frosted background and `backdrop-filter` to `.site-header::before`. The look is unchanged and the menu is full height. |
| 2 | **High** | **Hamburger tap on the bars opened then instantly closed the menu.** The outside-click handler compared `e.target !== toggle`, but a tap on the bars targets the inner icon `<span>`. *(Found by the interaction test.)* Pre-existing. | `! toggle.contains( e.target )` |
| 3 | **High** | **Search results had no H1 and the wrong empty state.** With no `search.php`, WP used `index.php` (0 H1s, "No articles published yet"). The search branches in `archive.php` were dead code. *(Found in the URL sweep.)* Pre-existing. | `search_template` filter routes search to `archive.php`. A child theme's `search.php` still wins. |
| 4 | **High** | **Single tool pages** used the article layout with no tool data, plus a broken author link when `post_author=0` (Section 3). | New tool presentation, tool breadcrumbs, card→detail link. |
| 5 | **High** | **Comments never rendered.** `comment-reply.js` was enqueued and comments are open by default, but `single.php` never output the list or form, so readers couldn't see or submit comments. Pre-existing. | `clipto_render_comments()` renders the list and form inline. There's no `comments.php` to keep the file set fixed, and calling `comments_template()` without one loads WP's deprecated theme-compat file. It mirrors core's rule so commenters see their own pending comment, and it's hidden on password-protected posts. |
| 6 | Medium | **Plugin deactivation left stale rewrite rules.** `flush_rewrite_rules()` ran while the CPT was still registered, so after deactivation `/ai-tools/` returned **200 with a copy of the homepage** (duplicate content) instead of 404. *(Found by testing theme-without-plugin.)* Pre-existing. | Unregister the CPT and taxonomies before flushing (the WP handbook pattern). `/ai-tools/*` now 404s when the plugin is inactive. |
| 7 | Medium | **Skip link invisible when focused.** It also carries `.screen-reader-text`, whose `clip` stayed in effect (WCAG 2.4.7). Pre-existing. | Reset `clip`/size/overflow on `.skip-link:focus`. |
| 8 | Medium | **Closed mobile menu and search panel stayed in the tab order.** They were hidden with `opacity` only, so keyboard users tabbed into invisible links and inputs. Pre-existing. | `visibility:hidden` when closed (with a delayed transition so the fade-out is kept). |
| 9 | Medium | **Tool taxonomy archives** (`/tool-pricing/*`, `/tool-category/*`) used generic post cards, the same bug previously fixed for `/ai-tools/`. Pre-existing. | `clipto_is_tools_listing()` covers the CPT archive and both tool taxonomies. Tools in search results also get tool cards. |
| 10 | Medium | **In-page search form unstyled** (404 page, empty search). In dark mode it was light text on a native white input, **1.13:1** contrast. Pre-existing. | Scoped `.site-main .search-form` styles. |
| 11 | Medium | **Badge contrast 4.48:1 (category) and 4.35:1 (expertise)** in light mode, below AA 4.5:1, on every card. *(axe.)* Pre-existing. | Badge text color nudged toward the text color via `color-mix`, keeping the same hue. Dark mode was already passing and is unchanged. |
| 12 | Medium | **Performance: hero images.** Phones downloaded the 1280px hero for a 358px slot because no other 16:9 size existed for `srcset`, and `page.php` lazy-loaded its above-the-fold image. **Card images:** the theme hard-coded `loading="lazy"` on every card, including the LCP image on archives. Pre-existing. | Added the `clipto-hero-md` (768×432) size and accurate `sizes` for hero and cards. Core now decides eager/lazy (first image gets `fetchpriority="high"`, threshold 4 to match the 4-column grid), and below-the-fold sections (Related Articles, More tools) are forced lazy. |
| 13 | Low | **Heading hierarchy.** On the homepage, card titles were H2 under the "Latest Articles" H2, and Related Articles cards were H2 under the "Related Articles" H2. Pre-existing. | `clipto_render_post_card()` takes a heading level. Those two contexts now use H3, and archives keep H2 under the H1. |
| 14 | Low | **JSON-LD contained literal HTML entities.** For example `"description":"… Model&hellip;"`. Pre-existing. | `clipto_schema_text()` strips tags and decodes entities for every JSON-LD string. |
| 15 | Low | **Duplicate "search" landmarks.** On the 404 page, the header form and the in-page form had identical accessible names (axe `landmark-unique`). Pre-existing. | `clipto_search_form( $label )` passes distinct `aria_label`s. |
| 16 | Low | **Author-box avatar link had no accessible name** (it's a duplicate of the name link beside it). Pre-existing. | `tabindex="-1" aria-hidden="true"`, the same pattern the theme already uses for card thumbnails. |
| 17 | Low | **`og:url` on archives** was `home_url( $_SERVER['REQUEST_URI'] )`, which doubles the path on subdirectory installs (`/blog/blog/…`). Pre-existing, found by code review. **This one was not reproduced:** the test install is at the web root. | `get_pagenum_link()`, which resolves against the real home path. |
| 18 | Low | **"Archives: AI Tools"** H1 on `/ai-tools/` (core's default prefix). | The prefix is removed for the tools archive only. Category/Tag prefixes are unchanged. |
| 19 | Low | **Search results indexable** (thin, near-infinite URL space). | `noindex, follow` via core's `wp_robots` API, only when no SEO plugin is active. |
| 20 | Enh. | The shortcode's cards were always H3, so `[clipto_tools_grid]` placed directly under a page H1 skipped a level (axe `heading-order`). | New, backward-compatible `heading="h2|h3|h4"` attribute (default `h3`). Invalid values fall back to `h3`. |

---

## 5. Changed files

| File | Change summary |
|---|---|
| `clipto-theme/functions.php` | Search routing. Tools-listing helper. Archive title prefix. Search form labels. Breadcrumb data function + BreadcrumbList JSON-LD (the visible breadcrumb output is identical). Tool breadcrumbs. `clipto_schema_text()`. Meta description uses the tool summary. `og:url` fix. Search `noindex`. Hero/card `sizes` + eager threshold. Post-card heading/loading args. `clipto_render_tool_single()`. `clipto_render_comments()`. Author avatar a11y. Version 1.1.0. |
| `clipto-theme/single.php` | Tool branch. Related cards H3 + lazy. Comments. Hero `sizes`. |
| `clipto-theme/archive.php` | Tool taxonomies + tools in search use tool cards. Labelled search form. Docblock. |
| `clipto-theme/index.php` | Homepage card titles H3. Labelled 404 search form. |
| `clipto-theme/page.php` | Hero no longer forced lazy. `sizes`. |
| `clipto-theme/header.php` | Labelled header search form. |
| `clipto-theme/footer.php` | Hamburger outside-click fix. |
| `clipto-theme/style.css` | Header backdrop layer. Skip-link focus. Hidden panels `visibility`. Badge contrast. In-page search form. Tool-card name link. Single tool page. Comments. Version 1.1.0, Tested up to 7.1. |
| `clipto-core-plugin/clipto-core-plugin.php` | `clipto_get_tool_data()`, `clipto_render_tool_badges()`. Card name → detail page, SR text on "View Tool", optional `$loading`, `sizes`. Shortcode `heading` attribute. Deactivation unregisters before flush. Meta-box help text. Version 1.1.0. |

No files were added to or removed from the theme or plugin. `author.php` is unchanged.

---

## 6. Tests actually performed (final regression, run after the last code change)

All run against the real WordPress 7.1.2 + MariaDB install, with the debug log **cleared first**.

1. **PHP lint:** `php -l` on all 9 PHP files, 0 errors.
2. **PHP runtime errors:** `debug.log` was **empty** after the whole regression run (with `E_ALL`, including notices and deprecations).
3. **URL / template-hierarchy sweep** (status, template actually chosen, H1 count):
   `/` index.php · `/page/2/` · `/ai-tools/` archive.php · `/ai-tools/chatgpt/` single.php · long-name tool · `/category/ai-tools/` · `/category/ai-news/` · `/category/earn-with-ai/` · `/tag/free-ai/` · `/tool-pricing/free/` · `/tool-category/image/` · 2 posts · `/about/` page.php · shortcode page · `/author/maya/` author.php · `/?s=ai` & `/?s=zzzqqq` archive.php · `/this-does-not-exist/` **404** · `/feed/` · `/wp-sitemap.xml`. All returned the expected status with exactly 1 H1 on every HTML page. No URL was added or renamed.
4. **Visual + accessibility matrix:** 17 pages × {1366×900 desktop, 390×844 @2x mobile} × {light, dark} = **68 full-page renders**. Every render had exactly 1 H1, no horizontal overflow, **CLS 0.0000**, no JS errors, all reveal content visible, and **0 axe violations** (WCAG 2.0/2.1 A+AA + best-practice). The unmodified baseline, run through the same matrix, had failures in 46 of 68 renders: 56 axe violations and 8 renders without exactly one H1. I inspected the screenshots myself.
5. **Interaction suite (38 checks, all pass):**
   - skip link visible on focus
   - closed menu and search panel not focusable
   - hamburger tap opens the menu, every menu link is hittable, Escape closes it and returns focus, the link navigates (light + dark)
   - search panel focuses its input and submits; the results H1 contains the query; tools appear as tool cards
   - visible 2px focus ring
   - bookmark toggles and persists
   - **real guest comment submission:** the commenter sees it "awaiting moderation" and other visitors don't; `comment-reply.js` moves the form under the comment for threaded replies
   - reduced motion: all content visible, animations neutralised
   - reveal content above the fold becomes visible without scrolling
   - pagination to `/page/2/`
6. **Other configurations:**
   - static front page + posts page (1 H1 each)
   - `/ai-tools/` pagination at `posts_per_page=4`
   - password-protected post (body and comments hidden, form shown)
   - comments-closed post
   - **theme with plugin deactivated** (no fatals; `/ai-tools/*` 404s, "Browse AI Tools" CTA hidden)
   - **plugin with Twenty Twenty-Five** (archive, single tool and shortcode work, no errors)
7. **Admin save path:** logged into wp-admin, set hostile values in the Tool Details meta box and saved through the block editor's real `meta-box-loader` request. Result: `javascript:alert(1)` → stored empty (Visit button hidden), rating `9` → `5`, `<script>` stripped from the summary.
8. **Static analysis:** PHPCS (WPCS Security/I18n/Prefix/Globals + PHPCompatibilityWP `7.4-`) found **0 issues in new code**. The 3 remaining findings are identical to the baseline and are false positives: `$post_id` in a template runs in `load_template()` scope, and two `$_POST` values are sanitized via `esc_url_raw( trim( wp_unslash() ) )`, which the sniff doesn't recognize. The new code uses no PHP 8-only syntax, and the new core APIs it relies on are all older than the declared WP 6.0 minimum.
9. **Performance probe** (Playwright resource timing, not Lighthouse):
   - 0 KB theme JS (2.9 KB is core `comment-reply` on posts with open comments)
   - 1 theme stylesheet (43 KB uncompressed)
   - at most 259 DOM nodes
   - every image has `width`/`height`
   - LCP image eager with `fetchpriority="high"`
   - no oversized images except the seed-data case noted below
10. **Packaging:** installed the two ZIPs in place of the working copies, confirmed both activate as v1.1.0, and re-ran the URL sweep.

---

## 7. Remaining limitations / not done (honest list)

- **No Lighthouse/PageSpeed score was run.** No lab or field CWV numbers are claimed. Timings from a local PHP dev server aren't representative.
- **Chromium only.** No Firefox, WebKit/Safari or real-device testing. The mobile tests use Chromium mobile emulation.
- **Web server** was PHP's built-in server, not Apache/nginx, so `.htaccess`, gzip/brotli and caching headers weren't tested.
- **Gravatar and other external hosts are blocked** by this sandbox, so avatars render as broken images in screenshots and produce console network errors. This is environment-only.
- **No SEO plugin installed.** Coexistence with Yoast, Rank Math, AIOSEO or SEOPress (where the theme suppresses its own meta and schema) was verified by code reading only.
- **Subdirectory-install `og:url` fix** was verified by reasoning and code, not on an actual subdirectory install.
- **Existing media needs thumbnail regeneration** for the new `clipto-hero-md` size (for example with the "Regenerate Thumbnails" plugin or `wp media regenerate --only-missing`). New uploads get it automatically, and until regeneration old images still use the 1280 px hero.
- A source image narrower than 1280 px (like the seeded 1200 px tool logo) can't produce a true 16:9 hero set, so that one image stays oversized on phones. That's a content issue, not a code issue.
- **Archive pages have no `rel=canonical`.** WordPress core only prints it on singular pages, and SEO plugins normally add it.
- The **CPT archive, search and 404 meta descriptions** fall back to the site tagline. That's harmless, and an SEO plugin would normally set these.
- **Not changed, flagged for a product decision:**
  - The AI Summary box sits *after* the article body.
  - If an author also places `[clipto_summary]` in the content, the summary appears twice.
  - Pages in search results show "1 min read" like posts.
  - The unused `clipto-secondary` menu location is registered but never rendered.


---

## 8. Round 2 — Single-post redesign and tool verification

### 8.1 Development environment (each item actually checked)

| Tool | Status | How it was verified |
|---|---|---|
| **UI UX Pro Max** | **NOT AVAILABLE** | Not in this session's skill list or `~/.claude/skills` (only `session-start-hook` + the synced Anthropic skills); `find /` finds no file by that name. Not used. |
| **21st.dev MCP** | **NOT AVAILABLE** | ToolSearch found no matching tool; there's no `.mcp.json` in the repo, and `~/.claude.json` lists no MCP servers. Not used. |
| **Chrome DevTools MCP** | **NOT AVAILABLE** | ToolSearch for DevTools/browser tools returns only `WebFetch`. Not used. |
| **Playwright** | **USED** | Playwright 1.63 + the preinstalled Chromium 1194 (headless). |
| **Real WordPress** | **USED** | The same WordPress 7.1.2 + MariaDB 10.11 install as Round 1. The container had restarted, so both services were brought back up and the data was intact. |

The design work followed the brief and the theme's existing tokens. **No UI UX Pro Max or 21st.dev output was used.**

**Font caveat:** the only sans-serif in the sandbox is DejaVu Sans, which is wider and heavier than the theme's `system-ui` stack as real visitors get it (SF Pro, Segoe UI, Roboto). Weight 650 also renders as full bold here. So production headings will look slightly lighter than these screenshots. I deliberately did not add a webfont, to keep the theme lightweight.

**QA-only fixtures** (outside the theme and plugin, not shipped):
- a full-element demo article at `/ai-agents-in-production/`: image with caption, nested and ordered lists, H2–H4, blockquote, pullquote, separator, inline and block code, a 5-column table, a callout, tags, AI Summary
- an mu-plugin that serves a local avatar, because Gravatar is blocked here

### 8.2 What changed on the single post

**Header.** The hierarchy now runs: category eyebrow (accent rule plus label) → H1 → dek (the manual excerpt) → a byline with avatar, author name, expertise, and "published · updated · reading time" on one line → Share and Save chips → featured image.
- The **H1** now uses `clamp(1.65rem, 1.25rem + 1.8vw, 2.55rem)` (it was 3.2rem), with a **21ch max measure**, `text-wrap: balance`, line-height 1.14, and weight 650. Measured: **596px wide on desktop against a 720px text column**, where before it filled the whole column. There are no forced line breaks.
- The **featured image** breaks out to 1040px, but only at viewports of 1120px or wider, where it fits without overflow. It has an accurate `sizes` attribute, shows the attachment caption when one exists, and settles in with a subtle entrance.

**Reading measure and body.**
- The body text column is **720px** (measured), inside your 680–760px target.
- Body text is 18px on desktop and 17px on phones, with line-height 1.75 on desktop (1.72 on phones) and 1.35em between paragraphs.
- Body text uses a softer ink colour (`--clipto-prose`), and bold is limited to `<strong>`.
- **Drop cap:** only the first letter of the first paragraph gets it, in an accent serif spanning about two lines. The paragraph is a `flow-root`, so a short first paragraph can't collide with the next block. Sites can turn it off with the `clipto_enable_drop_cap` filter. WordPress's own "Drop cap" paragraph option gets the same styling.
- **Heading scale** (measured): H1 40.8px → H2 28.8px → H3 20.8px → H4 18.9px, with body text at 18px.
  - H2 is weight 650 with a small gradient accent bar and a 32ch max width.
  - H3 is weight 600 with a 40ch max width.
  - H4 is compact but never smaller than body text.
  - H5 and H6 are muted labels.

**Content elements.**
- **Links** have a thin, tinted underline that turns solid and 2px on hover and focus. They always stay visibly links.
- **Lists** have accent markers, tabular numbers in ordered lists, and readable nesting.
- **Blockquotes** use a serif italic with a gradient left rule and a soft tonal wash, with no box. Citations have their own style.
- **Pullquotes** are centred and balanced.
- **Separators** are a hairline that fades out at both ends.
- **Tables** scroll inside their own container, never the page. On phones, columns keep a minimum width, and a CSS-only edge shadow shows when there's more to scroll. Tables have a tinted header row and hover highlight on rows.
- **Code blocks** sit on a dark surface in both color schemes, with a thin gradient top edge, mono type at 0.86rem, and horizontal scrolling. Core's default code-wrapping is disabled so lines don't wrap. **Inline code** gets a tinted chip.
- **Images** have rounded corners and captions.
- **Callout / Notice:** two new block styles on the core Group block, registered in PHP only.

**AI Summary.** It moved to directly after the featured image, before the body. It has a sparkle icon, a subtle accent glow and tint, and a dashed divider before a labelled "Key takeaway". It reveals on scroll, and its icon animates once.

**End of article.** In order:
1. tag chips
2. a share row: X, LinkedIn and Email intent links built from the permalink, plus Copy link (no social SDKs)
3. the **author trust box**: avatar ring, "Written by", name, expertise badge, bio, article count, social links, and "All articles by …"
4. **Related Articles** at 1040px wide, as cards with image, category, title, date and reading time, with hover lift
5. comments
6. **previous / next** cards: WordPress-generated URLs with `rel="prev"`/`rel="next"`, hover lift and arrow nudge

**Reading progress.** A 3px gradient bar fixed at the top. It updates through a single `transform: scaleX()` from a rAF-throttled passive scroll listener, so there are no layout shifts. It's `aria-hidden` (purely decorative) and respects the admin-bar offset.

**Share and Bookmark.**
- The header **Share** button opens the native Web Share sheet where available and otherwise copies the permalink. Copy results are announced through an `aria-live` status.
- **Bookmark** keeps the existing localStorage behaviour. Its icon now fills when saved, and a one-shot pop animation plays only on click (it used to replay on every page load for already-saved posts). `aria-pressed` is kept.

**Design-system consistency.**
- One refined heading scale is now shared sitewide (weights 650/600 with `text-wrap: balance`).
- Archive H1s are capped at 24ch.
- Card titles use weight 650.
- The homepage hero keeps its brand size explicitly, so the homepage is visually unchanged apart from the refined section-heading scale.
- Article chips, badges, radii, shadows and motion all reuse the existing tokens.

**Animations.** All CSS-driven and run once:
- header children rise in with a stagger
- the featured image settles
- the AI Summary and author box reveal on scroll
- the AI Summary sparkle plays once
- the bookmark pops on save
- links, eyebrow, author link and "All articles" link have micro-interactions
- related cards and prev/next cards lift on hover

The article body never animates continuously. Under `prefers-reduced-motion` everything resolves immediately.

### 8.3 Bugs found and fixed during this round

- **Regression caught by the axe pass.** My new article link style also matched links inside embedded `[clipto_tools_grid]` cards, which turned the "View Tool" button text blue on blue (**1.17:1**). Article element selectors are now wrapped in `:where()` (zero specificity), so embedded components always keep their own styles. Class-qualified overrides of core block CSS keep normal specificity.
- **Horizontally scrolling code blocks and tables weren't keyboard-reachable** (axe `scrollable-region-focusable`). A small script now adds `tabindex="0"` only to regions that actually overflow, re-checked on resize, and they show a focus ring.
- **H4 rendered smaller than body text** (16.3px against 18px). Fixed.
- **Tool-card logos inside article content got rounded bottom corners.** Pre-existing, now fixed.
- **Cards from 16:9 uploads had only a 640px `srcset` candidate.** Added a `clipto-card-sm` size (320×200). Existing media needs "Regenerate Thumbnails" to get it.
- **A long title wrapped the breadcrumb onto a second line.** The current crumb now truncates with an ellipsis.
- Several layout issues in my own first pass, found in screenshots and fixed before the final run:
  - Share/Save wrapping below the byline
  - a dangling "·" at the start of a wrapped meta line
  - an oversized pullquote citation

### 8.4 Tests actually run after the last code change

| Check | Result |
|---|---|
| `php -l`, all 9 PHP files | Clean |
| URL sweep (21 URLs + demo article) | Expected status and template, 1 H1 on every HTML page |
| Visual + axe matrix: 18 pages × desktop/mobile × light/dark = **72 renders** | **0 axe violations**, CLS 0.0000, no overflow, max 340 DOM nodes |
| Responsive sweep at **320 / 375 / 390 / 414 / 480 / 768 / 1024 / 1280 / 1440** × 6 page types × 2 schemes = **108 combinations** | **0 with horizontal overflow**, plus H1 and body measure recorded per width |
| Interaction suite | **50/50 pass**. Adds 13 redesign checks: progress bar 0 → 0.41 → 1.00; copy-link writes the real permalink; the aria-live announcement; share → copy fallback; bookmark has no pop on load, pops once on click, and fills when saved; drop cap on the first paragraph only; 720px measure; H1 narrower than the column; H1 > H2 > H3 > H4 ≥ body |
| WPCS security + PHPCompatibility (7.4+) | Same as the baseline: only the pre-existing `$post_id` false positive, and 0 escaping findings in new code |
| PHP `debug.log` (E_ALL) | **Empty** after the final run |
| Theme ZIP install as the active theme | Loads v1.2.0 |

The screenshots I inspected are in `docs/qa-screenshots/single-post/`:
- article, desktop and mobile × light and dark, plus two full-page renders
- element crops: header, AI Summary, drop-cap intro, lists, blockquote, code, table, callout, pullquote, footer/author box, post nav
- homepage, desktop and mobile
- the shortcode tool grid
- the "BEFORE" shots of the old article

**Performance cost, stated honestly:** the theme `style.css` grew from 44KB to **65.6KB raw (13.4KB gzipped)**. Theme JavaScript is still the one inline script with no libraries. The new blocks (share, progress, scroll regions) are about 3KB unminified.

**Still not done:** Lighthouse/PageSpeed, non-Chromium browsers, real devices, and a real (non-DejaVu) font rendering.
