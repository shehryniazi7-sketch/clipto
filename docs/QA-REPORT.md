# Clipto AI Pro — Real-WordPress QA & Finalization Report

**Scope:** production QA/finalization pass on the existing Clipto theme and Clipto Core plugin. Not a redesign. The visual direction, file set, URLs and features are unchanged except where a bug fix required a change.

**Deliverables**

| File | Contents |
|---|---|
| `dist/clipto-ai-pro-final.zip` | Theme v1.1.0 (folder `clipto-theme/`) |
| `dist/clipto-core-plugin-final.zip` | Plugin v1.1.0 (folder `clipto-core-plugin/`) |
| `docs/qa-screenshots/` | Selected final screenshots, plus one "BEFORE" shot |

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
