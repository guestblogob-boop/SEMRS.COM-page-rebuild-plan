=== SEMRS Tools ===
Contributors: semrs
Tags: seo tools, serp preview, schema generator, utm builder, image compressor
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.3.3
License: GPLv2 or later

12 free SEO tools for any WordPress site. Each page loads only its own small script (core ~2 KB + tool 2-6 KB gzipped).

== Tools ==
Meta Tags & SERP: SERP Snippet Preview, Meta Title & Description Checker
Technical SEO: Robots.txt Generator (AI crawler rules), Redirect Checker (server, rate-limited, private/internal addresses blocked)
Schema: Schema Markup Generator (Organization, Article, FAQ, Breadcrumb, Product, Event, Service), Local Business Schema Generator
Content: Word Counter (reading time, keyword density)
Local SEO: Google Review Link Generator
AI Search: llms.txt Generator
Marketing: UTM Builder (bulk + CSV)
Website: Image Compressor & WebP Converter (in the browser; files never upload)
Calculators: SEO ROI Calculator

== Usage ==
* [semrs_tool id="word-counter"] on any page. IDs are listed under SEMRS → Free Tools (or Tools → SEMRS Free Tools).
* [semrs_tools] for a directory of all tools, [semrs_tools category="technical-seo"] for one category.
* "Create draft pages" builds the /tools/ hub, 9 category pages and 12 tool pages as drafts. Write each tool's guide (800-1,500 words) before publishing; pages that are only a widget are thin content for Google and AdSense.
* With SEMRS Core Ads: put [semrs_ad slot="tool"] under the tool. With SEMRS Core SEO: tool pages get WebApplication schema.
* GA4 events: tool_used, tool_run, tool_copy, tool_download, cta_click.

== Changelog ==

= 1.3.3 =
* Service box, image drop zone, tool tabs and tool cards follow dark mode
* Tool tab hover uses the theme button hover colours
* ROI calculator and redirect checker status colours readable in dark mode

= 1.3.2 =
* Declares WP Consent API support (functional storage only, no trackers)

= 1.3.1 =
* Settings links on the Plugins screen

= 1.3.0 =
* SEO: 0–100 SEO score with readability checks, Re-check and internal link ideas; SEO Audit screen; llms.txt; reads Yoast and AIOSEO data
* Speed: page cache, per-block and non-blocking CSS, hover prefetch, database cleanup and speed check
* Starter-site templates library: 23 sites with header and footer presets (SEMRS Blocks)
* Pro: Ads Pro (repeat and between-posts placements, targeting, A/B rotation, click protection, ad-blocker message), Speed Pro (delay JavaScript, cache preload, CDN), bulk SEO editor, dark mode switch, languages and Pro starter sites
* Translation templates (.pot) included; tested up to WordPress 7.1; Plugin Check and Theme Check fixes

= 1.2.0 =
* Automatic update notices from semrs.com (WordPress 'Update available' + one-click update)
* Logo width controls for desktop and mobile; 512×512 square logos detected automatically
* Footer social icons in brand colours (9 networks)
* Free/Pro split: premium modules moved to the SEMRS Pro plugin
* Pro: license status checked with semrs.com

= 1.1.0 =
* 22 publish-ready pages bundled: hub, 9 categories, 12 tools with full guides, SEO fields and schema. "Create all tool pages as drafts" installs them.

= 1.0.0 =
* First release: 12 tools.
