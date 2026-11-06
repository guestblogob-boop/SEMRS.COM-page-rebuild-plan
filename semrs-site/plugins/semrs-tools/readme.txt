=== SEMRS Tools ===
Contributors: semrs
Tags: seo tools, serp preview, schema generator, utm builder, image compressor
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.4.5
License: GPLv2 or later

20 free SEO tools for any WordPress site. Each page loads only its own small script (core ~2 KB + tool 2-6 KB gzipped).

== Tools ==
Meta Tags & SERP: SERP Snippet Preview, Meta Title & Description Checker, On-Page SEO Checker (server), Social Share Preview & Open Graph Generator
Technical SEO: Robots.txt Generator (AI crawler rules), Robots.txt Tester (server: fetches the live file, tests Google and AI crawlers), Redirect Checker (server), Redirect Rules Generator (.htaccess, Nginx, CSV), Hreflang Tag Generator
Schema: Schema Markup Generator (Organization, Article, FAQ, Breadcrumb, Product, Event, Service), Local Business Schema Generator
Content: Word Counter (reading time, keyword density), Readability Checker (reading ease, grade, long sentences, passive voice)
Local SEO: Google Review Link Generator
AI Search: llms.txt Generator
Marketing: UTM Builder (bulk + CSV), ROAS & Break-Even Calculator
Website: Image Compressor & WebP Converter (in the browser; files never upload), Color Contrast Checker (WCAG 2.2)
Calculators: SEO ROI Calculator

Server tools fetch other websites from your server. Every hop is checked: http(s) only, standard ports, public IP addresses only; private and internal addresses are always blocked. Visitors get a set number of checks per hour (Settings) and repeat checks of the same URL are answered from a short cache.

== Settings ==
SEMRS → Free Tools → Settings (or Tools → SEMRS Free Tools without SEMRS Core):
* Switch each tool on or off. Off tools show nothing, leave the directory and stop answering server requests.
* Expert-help box under each tool on or off.
* Remember visitor inputs in their own browser on or off.
* GA4 events on or off.
* Server checks per visitor per hour (up to 60; 500 with Pro) and how long a result is reused.

Free Tools Pro (SEMRS Pro, SEO Pro add-on; switch it on under SEMRS → Features):
* Bulk checks: up to 25 URLs at once with CSV download in the Redirect Checker and On-Page SEO Checker.
* Your own expert-help box per category: text, link text and link.
* Usage report: which tools visitors use, runs, copies, downloads and expert-box clicks (7 and 30 days; totals only, no personal data).
* Higher hourly limit.

== Usage ==
* [semrs_tool id="word-counter"] on any page. IDs are listed under SEMRS → Free Tools (or Tools → SEMRS Free Tools).
* [semrs_tools] for a directory of all tools, [semrs_tools category="technical-seo"] for one category. The directory lists a tool once its page at /tools/<id>/ is published, so visitors never reach a missing page (filter: semrs_tools_listed).
* "Create the missing tool pages as drafts" builds the /tools/ hub, category pages and tool pages as drafts. Tools with a bundled guide get the full text; newer tools get a draft with a reminder to write the guide. Write each tool's guide (800-1,500 words) before publishing; pages that are only a widget are thin content for Google and AdSense.
* With SEMRS Core Ads: put [semrs_ad slot="tool"] under the tool. With SEMRS Core SEO: tool pages get WebApplication schema.
* GA4 events: tool_used, tool_run, tool_copy, tool_download, cta_click.

== External services ==
* **SEMRS update server (semrs.com)**: checks for new versions of the plugin about twice a day and when you open the Updates screen. Sends the plugin version, your site address and the WordPress and PHP versions. No personal data. Not present in WordPress.org builds. [Terms](https://semrs.com/terms-and-conditions/), [Privacy](https://semrs.com/privacy-policy/).
* Server tools (Redirect Checker, On-Page SEO Checker, Robots.txt Tester) fetch only the address a visitor types in, from your server, when they click the button. Nothing is sent to SEMRS.
* Social Share Preview shows the image URL the visitor types in, loaded by their own browser.

== Changelog ==

= 1.4.5 =
* Fix: "Create the missing tool pages" works again (the button's handler was lost in 1.4.0 and the request ended with an empty error)

= 1.4.4 =
* Full guides bundled for the 8 newer tools (On-Page SEO Checker, Open Graph Generator, Robots.txt Tester, Hreflang, Redirect Rules, Readability, ROAS, Color Contrast): "Create the missing tool pages" now builds them with complete text, SEO title, description, focus keyword and schema
* Hub and category pages describe all 20 tools

= 1.4.3 =
* Tool pages preload the tool's scripts from the page head, so the tool appears sooner (better Speed Index)

= 1.4.2 =
* No layout shift when a tool loads: each tool reserves its own height on phones and desktops (filter: semrs_tools_heights)

= 1.4.1 =
* Settings: the first save no longer switches every tool off (WordPress checks a new setting twice)
* Saving settings clears the SEMRS page cache, so tool pages and the directory update at once
* On-Page SEO Checker: skips hidden images without a source, and counts links named by an icon's label
* Color Contrast Checker: suggests the exact nearest passing color

= 1.4.0 =
* 8 new free tools: On-Page SEO Checker, Social Share Preview & Open Graph Generator, Robots.txt Tester, Hreflang Tag Generator, Redirect Rules Generator, Readability Checker, ROAS & Break-Even Calculator, Color Contrast Checker
* Settings screen: tools on/off, expert-help box, remember inputs, GA4 events, hourly limit and result cache for server tools
* Directory lists only tools whose page is published (no links to missing pages)
* Free Tools Pro (with SEMRS Pro): bulk checks with CSV, your own expert-help box per category, usage report, higher limits
* SERP preview: the copied title tag is escaped
* Admin: tabs (Tools, Settings, Usage report) and live counts on the "Create pages" button

= 1.3.6 =
* Tool pages: table of contents, a light step diagram and an official source link

= 1.3.5 =
* Image compressor: the quality slider has an accessible name for screen readers and AI agents.

= 1.3.4 =
* Tool panel headings are H2 (headings follow in order after the page title)
* Image compressor file picker has a label

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
