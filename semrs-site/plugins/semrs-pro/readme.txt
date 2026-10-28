=== SEMRS Pro ===
Contributors: semrs
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Requires Plugins: semrs-core
Stable tag: 1.4.10
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
* Local SEO (multi-location): Locations with NAP, opening hours, map link, service areas and Google Business Profile link; LocalBusiness schema per location joined to the site graph; [semrs_locations] and [semrs_location id="…"] shortcodes.
* Custom schema: your own JSON-LD on any post or page (Event, Product, Course, HowTo, Recipe, Video starters), checked as JSON on save.
* Image SEO: alt text and titles from clean file names on upload (camera names ignored); missing alt attributes filled on output.
* Bulk SEO editor (SEMRS → Bulk SEO): SEO titles, meta descriptions, focus keywords and noindex for 20 posts per screen, with counters, search and a "missing description" filter; scores update on save.

== Speed Pro (needs the SEMRS Core Speed module) ==
* Delay JavaScript until interaction: analytics, pixel, chat and heatmap scripts (list prefilled) run after the first scroll, tap or key press, in their original order; optional timeout. Ad scripts such as AdSense, JSON-LD and other data scripts are never delayed.
* Cache preload: after the SEMRS page cache clears, the homepage and 200 newest pages are rebuilt in the background for phones and computers.
* CDN: static files from wp-content and wp-includes are served from your CDN address.
* Unused plugin files: Contact Form 7 (with reCAPTCHA and Turnstile) and Spectra load only on pages that use them.
* Minified stylesheets (cached until the file changes), missing image width/height added, lazy-loaded iframes.

== Image library (needs the SEMRS Core Images module) ==
* WebP or AVIF copies of every JPEG and PNG already in the media library, all sizes, made in batches and shown on your pages. Originals stay untouched; "Remove copies" undoes it.

Redirects and the 404 log, and IndexNow, moved to the free SEMRS Core plugin in Core 1.3.0.

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

== Photo credits ==

Template photos in templates/photos/ are public domain (CC0 1.0 Universal, https://creativecommons.org/publicdomain/zero/1.0/), found through Openverse. They were cropped and compressed for the templates. No attribution is required; sources are listed for transparency.

* consultant-hero.jpg: Christina Morillo, StockSnap, CC0 1.0 – https://stocksnap.io/photo/business-meeting-SKBXLJOILI
* consultant-about.jpg: Direct Media, StockSnap, CC0 1.0 – https://stocksnap.io/photo/business-office-IHBPBLYUFE
* consultant-post1.jpg: energepic.com, StockSnap, CC0 1.0 – https://stocksnap.io/photo/office-work-42H3JH8QI5
* consultant-post2.jpg: Cathryn Lavery, StockSnap, CC0 1.0 – https://stocksnap.io/photo/journal-notepad-DPKNIIN5X3
* consultant-post3.jpg: Direct Media, StockSnap, CC0 1.0 – https://stocksnap.io/photo/business-presenting-XDAR6MIG75
* clinic-hero.jpg: Direct Media, StockSnap, CC0 1.0 – https://stocksnap.io/photo/male-doctor-KN1OCKC4Y2
* clinic-about.jpg: Direct Media, StockSnap, CC0 1.0 – https://stocksnap.io/photo/doctor-mask-GWE6UQDBTN
* clinic-post1.jpg: Negative Space, StockSnap, CC0 1.0 – https://stocksnap.io/photo/stethoscope-medical-9M1HWW2JFV
* clinic-post2.jpg: Direct Media, StockSnap, CC0 1.0 – https://stocksnap.io/photo/caregiver-nurse-QQEQNVMB0I
* clinic-post3.jpg: Healthy Living, StockSnap, CC0 1.0 – https://stocksnap.io/photo/healthy-food-7NGJXUAFPT
* travel-agency-hero.jpg: Pablo Heimplatz, StockSnap, CC0 1.0 – https://stocksnap.io/photo/grass-highland-3SOTA2KHAI
* travel-agency-about.jpg: Chatree Petjan, StockSnap, CC0 1.0 – https://stocksnap.io/photo/background-travel-HJGIY622S1
* travel-agency-post1.jpg: Matt Moloney, StockSnap, CC0 1.0 – https://stocksnap.io/photo/tropical-beach-4ASM8GQGNN
* travel-agency-post2.jpg: Aukje Leermakers, StockSnap, CC0 1.0 – https://stocksnap.io/photo/mountain-lake-5IGV8IZMBT
* travel-agency-post3.jpg: rawpixel, CC0 1.0 – https://www.rawpixel.com/image/5942830/free-public-domain-cc0-photo
* camping-hero.jpg: Christopher Jolly, StockSnap, CC0 1.0 – https://stocksnap.io/photo/camp-tent-PTERNSNX0F
* camping-about.jpg: Anggoro Sakti, StockSnap, CC0 1.0 – https://stocksnap.io/photo/man-campfire-HRLU7ZESP0
* camping-post1.jpg: Hiking Adventures, StockSnap, CC0 1.0 – https://stocksnap.io/photo/hiking-trail-HTYBXCKX4P
* camping-post2.jpg: Elliott Chau, StockSnap, CC0 1.0 – https://stocksnap.io/photo/camping-tent-OCXLY7U3OG
* camping-post3.jpg: rawpixel, CC0 1.0 – https://www.rawpixel.com/image/5917516/image-public-domain-stars-nature
* school-hero.jpg: rawpixel, CC0 1.0 – https://www.rawpixel.com/image/6111772/students-classroom-africa-unknown-date
* school-about.jpg: Brodie Vissers, StockSnap, CC0 1.0 – https://stocksnap.io/photo/people-girls-Y2AHVPYB51
* school-post1.jpg: Suzy Hazelwood, StockSnap, CC0 1.0 – https://stocksnap.io/photo/books-library-GTWPBBTRRN
* school-post2.jpg: The African Union Mission in Somalia, rawpixel, CC0 1.0 – https://www.rawpixel.com/image/3373064/free-photo-image-teaching-education-africa-black-teacher
* school-post3.jpg: Tamarcus Brown, StockSnap, CC0 1.0 – https://stocksnap.io/photo/student-school-1KOT8HDPAX
* online-courses-hero.jpg: Bench Accounting, StockSnap, CC0 1.0 – https://stocksnap.io/photo/working-typing-0E0M5W9O3V
* online-courses-about.jpg: Matthew Henry, StockSnap, CC0 1.0 – https://stocksnap.io/photo/laptop-code-0K2OHEKTRH
* online-courses-post1.jpg: Michal Kulesza, StockSnap, CC0 1.0 – https://stocksnap.io/photo/beats-headphones-7B62IKGVF4
* online-courses-post2.jpg: Jeffrey Betts, StockSnap, CC0 1.0 – https://stocksnap.io/photo/office-work-91WZ32J28K
* online-courses-post3.jpg: Daniel Frank, StockSnap, CC0 1.0 – https://stocksnap.io/photo/laptop-apple-V6JZXOHZRD
* ai-llm-hero.jpg: Christina Morillo, StockSnap, CC0 1.0 – https://stocksnap.io/photo/developer-discuss-OEIJJX36TI
* ai-llm-about.jpg: Negative Space, StockSnap, CC0 1.0 – https://stocksnap.io/photo/analytics-charts-RCFX768X06
* ai-llm-post1.jpg: One Idea LLC, StockSnap, CC0 1.0 – https://stocksnap.io/photo/programming-code-1STVFMTBJY
* ai-llm-post2.jpg: Alex Knight, StockSnap, CC0 1.0 – https://stocksnap.io/photo/robot-technology-WUBMLJRYEK
* ai-llm-post3.jpg: rawpixel, CC0 1.0 – https://www.rawpixel.com/image/5906639/photo-image-light-desktop-wallpapers-public-domain

== License ==
SEMRS → License: paste your key and click Activate (Deactivate frees the site so you can move the key). Updates come from semrs.com (SEMRS Update Server): with a valid key, WordPress shows "Update available" and installs new versions in one click. Features never switch off when a license expires; the license controls updates and support.

== Changelog ==

= 1.4.10 =
* Post sliders give every image alt text; the caption is hidden from screen readers so the link is not read twice.

= 1.4.9 =
* Images in post lists and sliders now carry the post title as alt text, for image search and screen readers.

= 1.4.8 =
* Link Assistant: automatic internal links (once per page, never stuffed, saved content unchanged) and a background broken link checker with unlink/recheck/redirect actions
* Sitemaps Pro: image, video and Google News sitemaps; VideoObject schema for YouTube, Vimeo and uploaded videos
* SEO Performance: Search Console clicks, impressions, CTR, positions, top queries and pages, keyword rank tracking with 30-day backfill and daily history, weekly email report (via Site Kit by Google)

= 1.4.7 =
* New AI SEO module: titles, meta descriptions, focus keywords, social text and image alt text written by Claude, OpenAI or Gemini with your own API key
* Optional autofill on publish and alt text on upload, in the background
* Bulk tools: fill missing SEO titles/descriptions and image alt text
* Needs a valid SEMRS Pro license

= 1.4.6 =
* Dark mode: light backgrounds set in page content switch to dark surfaces
* Dark mode: readable success, error and warning colours
* Slider markup uses valid ARIA (no role on list items)

= 1.4.5 =
* SEO: copy Rank Math data into SEMRS fields (SEMRS → SEO)
* Speed Pro: remove unused CSS

= 1.4.4 =
* Declares WP Consent API support (functional storage only, no trackers)

= 1.4.3 =
* Ads: rectangle, vertical (half-page) and Multiplex formats

= 1.4.2 =
* Mega menu: column headings with accent underline, accent hover; dark-mode dropdowns use the theme's dark colours.

= 1.4.1 =
* Dark mode: accent text and soft backgrounds follow the accent colour; pagination and mobile bar use the dark header colour.

= 1.4.0 =
* New: Image library conversion (WebP/AVIF copies of existing images, originals kept), Custom schema per page, and Speed Pro stylesheet minification, missing image sizes and lazy iframes. Redirects and IndexNow moved to free SEMRS Core 1.3.0.

= 1.3.3 =
* Speed: new option to load Contact Form 7 (with reCAPTCHA and Turnstile) and Spectra files only on pages that use them.

= 1.3.2 =
* Dark mode: readable button hovers (white with navy text by default)
* Dark mode colour settings: background, cards, text, links, header bar, top bar and buttons

= 1.3.1 =
* Settings links on the Plugins screen

= 1.3.0 =
* Pro starter sites with real CC0 photos, example posts, legal pages, niche colours and footers

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
