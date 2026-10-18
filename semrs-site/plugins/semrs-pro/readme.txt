=== SEMRS Pro ===
Contributors: semrs
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Requires Plugins: semrs-core
Stable tag: 1.2.0
License: GPLv2 or later

Premium add-on for the free SEMRS theme and the free SEMRS Core plugin. Every module can be switched off under SEMRS → Modules.

== Theme Pro (needs the SEMRS theme) ==
* Sticky header and transparent front-page header.
* Mega menu: add the CSS class "semrs-mega" to a top menu item (2–4 columns).
* Table of contents (posts with 3+ headings), reading progress bar, "Updated on" date.
* Author box with "Reviewed by" line (custom field semrs_reviewed_by), related posts.
* 5 extra section patterns: service cards, 6-step process, pricing, tool section, contact section.
* White label: rename the theme in Appearance → Themes.

== SEO Pro ==
* Redirects: 301/302/307/410, wildcards, loop protection, 404 log with one-click redirect, Rank Math redirect import.
* Local SEO (multi-location): Locations with NAP, opening hours, map link, service areas and Google Business Profile link; LocalBusiness schema per location joined to the site graph; [semrs_locations] and [semrs_location id="…"] shortcodes.
* IndexNow: instant notification to Bing, Yandex, Seznam, Naver and others on publish, update and delete (paused on local/staging sites).
* Image SEO: alt text and titles from clean file names on upload (camera names ignored); missing alt attributes filled on output.
* Bulk SEO editor (SEMRS → Bulk SEO): SEO titles, meta descriptions, focus keywords and noindex for 20 posts per screen, with counters, search and a "missing description" filter; scores update on save.

== Speed Pro (needs the SEMRS Core Speed module) ==
* Delay JavaScript until interaction: analytics, pixel, chat and heatmap scripts (list prefilled) run after the first scroll, tap or key press, in their original order; optional timeout. Ad scripts such as AdSense, JSON-LD and other data scripts are never delayed.
* Cache preload: after the SEMRS page cache clears, the homepage and 200 newest pages are rebuilt in the background for phones and computers.
* CDN: static files from wp-content and wp-includes are served from your CDN address.

== Ads Pro (needs the SEMRS Core Ads module) ==
* Extra placements: repeat in content every N paragraphs (density cap and gap still apply) and between posts on blog and category pages.
* Per-position targeting: only in / never in categories, posts older than N days, start and end dates, hide for logged-in users.
* A/B rotation: a second AdSense slot ID or custom code per position, chosen at random per page view (works with page caching).
* Click protection: after N ad clicks within H hours a visitor gets no ads for D days; ad requests paused (Auto ads included); stored in the visitor's browser.
* Polite, dismissible ad-blocker message; content is never blocked.
* Per-page position switches in the editor.

== Pro Blocks (needs the free SEMRS Blocks plugin) ==
* Slider / carousel: images or posts, 1 to 4 per view, arrows, dots, keyboard and swipe, autoplay that pauses on hover and focus.
* Animated counter: counts up on scroll; the real number is in the HTML for search engines.
* 3D model viewer: .glb/.gltf with rotate, zoom and "view in your room" AR on phones (script loads only where used).
* Scroll animations: fade, zoom and slide on any block (Advanced panel); respects reduced motion.

* Display conditions: show any block only to logged-in or logged-out visitors, certain roles, between two dates, or on certain kinds of page.

== Dark mode & languages ==
* Dark mode switch for the header builder: starts light or follows the device; header, top bar and footer stay navy; no flash on load; remembered in the visitor's browser.
* Languages: switcher (header element or [semrs_languages]), linked translations, hreflang + x-default, html lang and right-to-left support, optional nofollow Google Translate links for untranslated pages. Works with Polylang and WPML.

== Pro starter sites ==
* Seven extra starter sites for the SEMRS templates library: Consultant, Clinic, Travel Agency, Camping & Outdoors, School & Admissions (with registration form), Online Courses (student registration) and AI & LLM Services, with Pro sections (services in tabs, latest-articles slider).

== Popups ==
* SEMRS → Popups: popups built with the block editor; centred box (bottom sheet on phones), bottom bar or corner slide-in.
* Open after a delay, after scrolling, on exit intent (desktop) or when a link to #semrs-popup-ID is clicked. Every view, once per visit or once every N days (local storage, no cookies).
* Device and page targeting. Focus kept inside, Esc to close; ad shortcodes are never rendered inside popups.

The Ads Manager moved to the free SEMRS Core plugin in 1.1.0.

== Site Layouts, Proof, Leads ==
* Site Layouts: block content at any theme position with display rules.
* Case Studies, Portfolio and Testimonials with permission gates.
* Contact Form 7 → GA4 generate_lead → thank-you page.

== License ==
SEMRS → License: paste your key and click Activate (Deactivate frees the site so you can move the key). Updates come from semrs.com (SEMRS Update Server): with a valid key, WordPress shows "Update available" and installs new versions in one click. Features never switch off when a license expires; the license controls updates and support.

== Changelog ==

= 1.2.0 =
* SEO: 0–100 SEO score with readability checks, Re-check and internal link ideas; SEO Audit screen; llms.txt; reads Yoast and AIOSEO data
* Speed: page cache, per-block and non-blocking CSS, hover prefetch, database cleanup and speed check
* Starter-site templates library: 23 sites with header and footer presets (SEMRS Blocks)
* Pro: Ads Pro (repeat and between-posts placements, targeting, A/B rotation, click protection, ad-blocker message), Speed Pro (delay JavaScript, cache preload, CDN), bulk SEO editor, dark mode switch, languages and Pro starter sites
* Translation templates (.pot) included; tested up to WordPress 7.1; Plugin Check and Theme Check fixes

= 1.1.0 =
* Automatic update notices from semrs.com (WordPress 'Update available' + one-click update)
* Logo width controls for desktop and mobile; 512×512 square logos detected automatically
* Footer social icons in brand colours (9 networks)
* Free/Pro split: premium modules moved to the SEMRS Pro plugin
* Pro: license status checked with semrs.com

= 1.0.0 =
* First release: Theme Pro, Redirects, Local SEO, IndexNow, Image SEO, Ads Manager, Site Layouts, Proof, Leads, License screen.
