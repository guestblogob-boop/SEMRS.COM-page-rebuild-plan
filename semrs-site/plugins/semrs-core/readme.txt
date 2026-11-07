=== SEMRS Core ===
Contributors: semrs
Tags: seo, cookie consent, smtp, security, page cache
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.3.53
License: GPLv2 or later

Free SEO, cookie consent, SMTP email, security, page cache, redirects, WebP images and a policy-safe ads manager in one plugin.

== Description ==

Free SEO with an SEO score and site audit, redirects and a 404 log, IndexNow, a page cache and speed optimization, WebP/AVIF image optimization on your own server, a user role editor, review widgets, a policy-safe ads manager, a cookie consent banner with Google Consent Mode v2 and the WP Consent API built in, SMTP email, login and header security, and site tools (maintenance mode, duplicate posts, comments off) for any WordPress site. SEMRS Pro adds multi-location local SEO, image SEO, custom schema, a bulk SEO editor, delayed scripts, stylesheet minification, cache preload, CDN, media library conversion, Pro blocks and more.

== Features screen ==
SEMRS → Features shows every SEMRS feature, free and Pro, as cards with search, filters (Free, Pro, SEO, Speed, Ads, Design, Leads, Privacy & security), on/off switches, a Settings button and a How to use link to the manual. A switched-off feature loads no code. Pro features are marked PRO and stay locked until SEMRS Pro is installed.

== Modules ==

= SEO (SEMRS → SEO, "SEMRS SEO" box in the editor) =
* Titles with templates and variables, meta descriptions (auto from excerpt or content when empty), homepage title/description.
* Robots meta via WordPress's own API: per-post noindex/nofollow, noindex for search, 404, tag/author/date archives (configurable), max-image-preview:large.
* Canonical URLs for posts, pages, archives and paginated pages; custom canonical per post.
* Open Graph + X cards with per-post share image (featured image fallback, site default).
* JSON-LD graph: Organization / LocalBusiness / ProfessionalService / Person (address, area served, social profiles), WebSite with SearchAction, WebPage, BreadcrumbList, BlogPosting/Article/NewsArticle with Person author, Service, WebApplication (free tools), About/Contact/Collection pages, and FAQPage built automatically from visible Details blocks.
* XML sitemap on WordPress's core engine (/wp-sitemap.xml): noindexed posts left out, lastmod dates, tag/author sitemaps follow noindex settings, old /sitemap_index.xml and /sitemap.xml redirect.
* robots.txt extra lines, Google/Bing verification, breadcrumbs (used by SEMRS Pro).
* Editor box: focus keyword, SEO title and description with live counters, Google preview, canonical, schema type, noindex/nofollow.
* SEO score 0–100 with Basic SEO, Content and Readability checks: keyword in title/description/slug/first 100 words/subheadings, keyword density, keyword already used on another page, title and description length, word count, internal and outbound links, image alt text, featured image, single H1, subheadings, sentence and paragraph length, Flesch reading ease (English).
* "Re-check now" analyses unsaved editor content; internal link ideas list related pages not linked yet.
* SEO score column (sortable) on post and page lists.
* SEO Audit (SEMRS → SEO Audit): instant site checks with fix links, and a batched content audit for up to 2,000 pages: scores, missing and duplicate titles/descriptions, thin content, orphan pages, noindex.
* llms.txt for AI assistants (/llms.txt): site summary, pages and latest articles; noindexed pages left out.
* Switching from Rank Math, Yoast SEO or All in One SEO: SEMRS output pauses while any SEO plugin is active, and their titles, descriptions, focus keywords, canonicals, noindex/nofollow and share images are read automatically (title variables converted), so nothing is lost when you switch.

= Redirects & 404 log (SEMRS → Redirects) =
* 301, 302, 307 and 410 redirects, wildcards (/old-folder/*), loop protection.
* 404 log (last 200 addresses, scanner noise skipped) with a one-click redirect.
* Imports Rank Math redirects.

= IndexNow (SEMRS → IndexNow) =
* Off until you switch it on. Then new, updated and deleted public URLs are sent to IndexNow search engines (Bing, Yandex, Seznam, Naver…). Paused on local and staging sites.

= Images (SEMRS → Images) =
* New JPEG and PNG uploads are resized to a maximum side, compressed and saved as WebP or AVIF with WordPress's own image editor. No account, no quota, nothing leaves your server.
* Compression quality for every image size WordPress creates.

= User roles (Users → Roles) =
* Change what each role can do, add a role copied from another, add custom capabilities, delete roles created here (their users get the default role).
* Give a user extra roles next to their main role. The Administrator role is protected.

= Review widgets (SEMRS → Reviews) =
* [semrs_reviews] shows your real Trustpilot TrustBox (four free styles) or a button to your Google reviews. Trustpilot's script loads only on pages that use it.

= Ads (SEMRS → Ads) =
* AdSense / Ad Manager (AdX) and other network codes at policy-safe positions: after the title, inside content (after paragraph N, safe insertion that skips tables, lists and headings), after content, sidebar and shortcode.
* Off by default on contact, quote, thank-you, login and search pages; density cap; device targeting; space reserved to prevent layout shift (CLS); "Advertisement" label; lazy loading; ads.txt editor.
* Developer hooks: semrs_ads_positions, semrs_ads_slot, semrs_ads_unit_templates, semrs_ads_incontent_extra, semrs_ads_block_reason, semrs_ads_settings_after.

= Speed (SEMRS → Speed) =
* Removes emoji scripts, oEmbed, Dashicons for visitors, jQuery Migrate and extra head tags; slows the Heartbeat; limits revisions.
* YouTube click-to-play facade, preload of the first content image, preconnect hints for ads and Analytics.
* Block CSS only for blocks on the page; optional inlining of small stylesheets and non-blocking loading of chosen stylesheets.
* Optional: defer scripts (with an exclude list), prefetch pages on hover (Speculation Rules), turn off XML-RPC.
* Page cache (off by default): static HTML for logged-out visitors served before the theme loads, separate phone copies, automatic clearing on content/menu/widget/Customizer/settings changes, admin-bar Clear cache, exclusions; pauses when another cache plugin is active. Never caches logged-in users, carts, searches, 404s, previews or query strings.
* Database cleanup: old revisions and auto-drafts, trash, spam, expired transients, orphaned post meta.
* Speed check: server response, cache, compression, page weight, render-blocking files, images without sizes, oversized uploads, PHP, object cache, autoload size, with fixes.

= Privacy & consent (SEMRS → Privacy) =
* Cookie banner (off until you switch it on): Accept, Reject and Preferences with equal prominence; categories Necessary, Preferences, Statistics and Marketing; bar or corner box; light and dark; a cookie settings button, [semrs_cookie_settings] shortcode or any link to #cookie-settings to change the choice.
* Opt-in (GDPR, UK) or opt-out (US states) consent; choice remembered for a set number of days; "ask everyone again" after a policy change.
* Google Consent Mode v2: defaults before any tag, updates after the choice, ads data redaction and optional URL passthrough. Works with Site Kit, Google tag and Tag Manager.
* WP Consent API built in: the same functions, cookies and events as the WP Consent API plugin, so plugins that support it read the visitor's choice without the extra plugin.
* Header and footer scripts per category: statistics and marketing codes stay inactive until the visitor agrees.
* Consent log as proof of consent: random visitor ID, choices, policy version and a shortened IP hash, with CSV export and automatic deletion.
* Note: AdSense and Ad Manager in the EEA, UK and Switzerland need a Google-certified CMP (IAB TCF); use Google's free Privacy & messaging there for ads.

= Email (SMTP) (SEMRS → Email) =
* Send WordPress mail through any SMTP server (host, Google Workspace, Microsoft 365, Zoho, Brevo, Mailgun, SendGrid, Amazon SES). Password saved encrypted, or set in wp-config.php.
* From address and name, test email, and a log of the last 50 sends (recipient, subject, result; never the message).

= Security (SEMRS → Security) =
* Limit log-in attempts with an allow list and a lockout log; one generic log-in error.
* Hide usernames (author scans, REST users list, users sitemap) and the WordPress version.
* Security headers (nosniff, SAMEORIGIN, Referrer-Policy, Permissions-Policy) and optional HSTS.
* Safe SVG uploads for administrators, cleaned of scripts and outside links.
* Turn off the theme and plugin file editor. Emergency switch: SEMRS_SECURITY_OFF.

= Site tools (SEMRS → Site tools) =
* Maintenance mode (503 with Retry-After, so rankings are kept) or coming-soon page; logged-in editors see the site; optional reopening date.
* Duplicate any post, page or custom post as a draft with terms, featured image and custom fields.
* Comments off everywhere: forms, existing comments, menus, feeds and the REST endpoint.

== External services ==

SEMRS Core works without any outside service. These connections happen only when you use the related feature:

* **SEMRS update server (semrs.com)**: checks for new versions of the plugin about twice a day and when you open the Updates screen. Sends the plugin version, your site address and the WordPress and PHP versions. No personal data. [Terms](https://semrs.com/terms-and-conditions/), [Privacy](https://semrs.com/privacy-policy/).
* **Your SMTP server** (Email module, only after you enter a server and switch it on): every email WordPress sends (recipient, subject, message) goes to the SMTP server you choose, under that provider's terms.
* **Google AdSense** (Ads module, only after you enter your publisher ID and switch ads on): the AdSense script is loaded from pagead2.googlesyndication.com on pages where ads are allowed, and Google serves the ads. Google receives the visitor's IP address, browser details and cookies as described in its policies. [Terms](https://policies.google.com/terms), [Privacy](https://policies.google.com/privacy), [AdSense terms](https://www.google.com/adsense/new/localized-terms).
* **Other ad networks** (Ads module, custom code): only the code you paste is loaded, from the network you choose, under that network's terms.
* **YouTube** (Speed → YouTube click-to-play, on by default for pages that already embed YouTube videos): the video thumbnail is loaded from i.ytimg.com, and the player loads from youtube-nocookie.com only when the visitor clicks Play. [Terms](https://www.youtube.com/t/terms), [Privacy](https://policies.google.com/privacy).
* **IndexNow** (IndexNow module, only after you switch it on under SEMRS → IndexNow): the addresses of public posts you publish, update or delete, with your site's key, are sent to api.indexnow.org, which shares them with participating search engines. [Terms and privacy](https://www.indexnow.org/terms).
* **Trustpilot** (Review widgets, only on pages where you add [semrs_reviews] with your business unit ID): the TrustBox script is loaded from widget.trustpilot.com and Trustpilot shows your reviews. [Terms](https://legal.trustpilot.com/for-everyone/end-user-terms-and-conditions), [Privacy](https://legal.trustpilot.com/for-reviewers/end-user-privacy-terms).
* **Google PageSpeed Insights** (Speed → Speed check): an "Open PageSpeed Insights" link opens pagespeed.web.dev with your homepage address when you click it. Nothing is sent automatically. [Terms](https://policies.google.com/terms), [Privacy](https://policies.google.com/privacy).

The speed check, SEO audit, page cache, llms.txt, cookie banner, consent log, security options and all other features run entirely on your own server. Google Consent Mode only passes the visitor's choice to Google tags you already use; it does not load anything itself.

== Changelog ==

= 1.3.53 =
* SEO: the blog page shares its own image, and archives without a site-wide default share image use the homepage image instead of none

= 1.3.52 =
* SEO sidebar: the share previews show the automatic social image (SEMRS Pro) when the post has no featured image
* SEO: the RSS credit line is on by default (best practice: copies of your feed link back to you)

= 1.3.51 =
* Redirects: regex rules with $1 placeholders, 451 (unavailable for legal reasons), hit counts with the last hit, and automatic 301s when a published page, post or product gets a new address (slug or parent change). Adding the same address again updates its rule instead of adding a duplicate; the Rank Math import skips addresses already redirected
* SEO → Links, URLs & feeds (new): nofollow and/or new tab for external links (with an exception list), remove /category/ from category addresses (old addresses 301), and an RSS credit line linking back to each post
* Post lists: new Links column with internal and external links per post and how many posts link to it (orphans in red); older posts are counted in the background
* SEO sidebar: Facebook and X share previews, and a Google Trends link to compare the focus keywords

= 1.3.50 =
* Speed → Server: the tab now shows the server rules as On after they are added (WordPress adds its own comment lines inside the block)

= 1.3.49 =
* Speed → Server (new tab): finds and removes the .htaccess rules and advanced-cache.php left behind by cache plugins that are no longer active (WP Rocket, W3 Total Cache, WP Super Cache, WP Fastest Cache, LiteSpeed Cache, Hummingbird, Breeze, SiteGround Optimizer), with a dated backup
* Speed → Server: one-click server rules for Apache and LiteSpeed (Brotli and gzip compression, a year of browser caching for versioned files, no ETags, font CORS, AVIF/WebP/WOFF2 types)
* Speed → Server: early page cache (advanced-cache.php) serves cached pages before plugins and the theme load, for a faster server response; removed automatically when SEMRS Core is deactivated

= 1.3.48 =
* Index Status: suggested robots.txt sitemap lines leave out the video sitemap until a page has a video (an empty sitemap is reported in Search Console)

= 1.3.47 =
* Speed: no early connection to the AdSense server when Ads → Lazy-load is on (the tag loads after the first interaction, so the preconnect sat unused)

= 1.3.46 =
* Ads (lazy-load on): the AdSense tag loads on the visitor's first scroll, tap or key press, or after 5 seconds, instead of in the page head. Faster first paint on pages with ads; Auto ads still run.

= 1.3.45 =
* Redirects: import redirects from CSV (source,target,code) and export them as CSV. URLs already in the list are skipped.
* Site tools: settings renamed internally so they can never clash with the SEMRS Tools plugin (saved settings carry over).
* Features screen: Free Tools Pro card.

= 1.3.44 =
* Folding on every SEMRS screen: long tables (activity log, consent log, mail log, redirects, 404 log, roles…) show 10 rows with a Show all button; settings screens with many sections get fold buttons on each heading and Fold all / Unfold all. Remembered per screen.

= 1.3.43 =
* Site tools screen moved to its own address (SEMRS → Site tools), so it no longer clashes with the SEMRS Tools plugin.
* Display conditions and Custom schema cards open the Pages list, where both are used in the editor.

= 1.3.42 =
* Security headers are also sent on pages served from the SEMRS page cache.
* The cookie banner and Google Consent Mode defaults are never delayed by Speed Pro, so the banner shows at once and consent defaults run before any Google tag.

= 1.3.41 =
* New: Privacy & consent – cookie banner, Google Consent Mode v2, the WP Consent API built in (no extra plugin needed), consent-aware header and footer scripts and a consent log.
* New: Email (SMTP) with test email and mail log; Security (log-in limits, security headers, safe SVG, hidden usernames and version, no file editor); Site tools (maintenance / coming soon, duplicate, comments off).
* Features screen: every card has a button to its screen, a How to use link to the manual and a "Switched off" note; new Privacy & security filter; Pro cards for login security, activity log, search & replace and social images.
* SEO: semrs_seo_auto_image filter for automatic share images.

= 1.3.38 =
* Speed: main image first is now on by default. The featured image preload uses the image's own sizes, only the first content image loads eagerly, and unused early connections to the ad server and Tag Manager are dropped (articles: mobile PageSpeed average 92 to 95, lowest 88 to 94)

= 1.3.37 =
* Speed test switch (?semrs_lcp=1) also drops the unused early connections to the ad server and Tag Manager

= 1.3.36 =
* Speed: test switch for 'main image first' (?semrs_lcp=1): featured image preload before ad tags, preload sizes match the shown image, only the first content image loads eagerly

= 1.3.35 =
* SEO score: text in neighbouring elements (buttons, cards, table cells) is no longer glued into one long word, which made readability scores too low

= 1.3.34 =
* SEO score: headings, list items, table cells and buttons end a sentence, so price lists and feature lists are not read as one long sentence
* SEO score: the blog page is scored with its intro text and the latest posts, as visitors see it

= 1.3.33 =
* SEO score: pages whose template hides the title need exactly one H1 in the content
* SEO score: labeled inline SVG pictures count as images, and their label counts as alt text

= 1.3.32 =
* Index status: Check again next to every not-indexed status asks Google about that page now and updates the row; not-indexed pages are re-checked with Google every hour (requested pages first); a page shown in Google results counts as indexed.

= 1.3.31 =
* A page marked requested that is still not indexed after 14 days goes back to Request indexing, with a red note (Requested Oct 15 · still not indexed, request again), and returns to Request these today.

= 1.3.30 =
* Indexing steps in the Posts and Pages lists and in Request these today: Request indexing and Mark requested side by side; after Copy URL or Request indexing, Mark requested is highlighted as the next step; marking saves instantly and shows a green Requested badge for 14 days.

= 1.3.29 =
* Copy URL link under each published title in the Posts and Pages lists, and in Request these today: paste it into Search Console's URL Inspection without opening the page.

= 1.3.28 =
* Index Status shows each sitemap's state in Google Search Console (submitted, last read, errors), submits missing sitemaps every day and re-submits after you publish (at most every 6 hours): the supported way to tell Google about new pages.

= 1.3.27 =
* Ask Google about every page now runs in small steps with a progress counter (no gateway time-outs).
* Index Status asks Google itself (Search Console URL Inspection through Site Kit) every day: indexed, crawled but not indexed, discovered, or unknown to Google, with the last crawl date.
* New Request these today list: the 10 pages that most need an indexing request, within Search Console's daily limit.

= 1.3.25 =
* Posts and Pages lists also show a Status column (published, draft or scheduled, and whether the page loads) and a Traffic column (Google clicks, impressions and average position, last 90 days).

= 1.3.24 =
* Posts and Pages lists show an Index status column (live & indexed, live but not indexed yet, waiting for Google, redirect, not live, noindex) with a Request indexing link.

= 1.3.23 =
* Ads: no layout shift. Boxes for another screen size never show, and an unfilled ad keeps its space unless it is below the screen.
* Ads: /semrs-pro/ and /my-licenses/ are excluded by default.

= 1.3.22 =
* Features screen: with SEMRS Pro 1.5, modules that are not in your license's add-ons show which add-on they belong to, with an Add button. Their on/off setting is kept for when they unlock.

= 1.3.20 =
* The SEO score of the blog (posts) page now checks the post list visitors see, not its empty page text.

= 1.3.19 =
* Focus keyword in URL: passes on the homepage and ignores the site name
* robots.txt editor saves clean line endings

= 1.3.18 =
* Index Status: live and indexed status with remarks for every page, indexing tasks (Request indexing in Google, IndexNow), auto-indexing switch, robots.txt editor
* Content Calendar: month calendar with Previous/Today/Next, scheduled posts with Add to Google Calendar and .ics
* One-click sharing to Facebook, X, LinkedIn, Pinterest, Reddit, WhatsApp, Telegram and email (post lists, calendar, SEO sidebar); auto-post on publish through a webhook (Pro)
* SEO Audit: recognises a robots.txt file with your sitemap and Site Kit verification; menu and footer links count as internal links; the posts page and noindex pages don't lower the average
* Focus keyword in URL check reads the full page address

= 1.3.17 =
* Pro items work on the front end and in background jobs too (sitemaps, video schema, auto-links, advanced robots, X tags), not only in wp-admin

= 1.3.16 =
* Features list: Link Assistant, Sitemaps Pro and SEO Performance (Pro)

= 1.3.15 =
* Fix with AI on failing title, description and focus keyword checks
* Secondary keywords check (Pro): each used once, never more
* Clear keyword-stuffing warning when the keyword density is too high

= 1.3.14 =
* SEMRS SEO sidebar: Write with AI buttons for focus keyword, SEO title, meta description and Facebook text (with SEMRS Pro AI SEO)
* AI SEO in the Features list

= 1.3.13 =
* SEMRS SEO sidebar in the block editor, like Rank Math: score in the toolbar; General (Edit Snippet with title/permalink/description counters and pixel widths, focus keywords, Pillar Content, checks), Advanced (robots meta, canonical, breadcrumb title), Schema and Social (Facebook, X) tabs
* Rank Math checks: Basic SEO, Additional, Title readability and Content readability groups
* Variables in SEO titles and descriptions (%title% %sep% %sitename% %excerpt% %focuskw%…)
* Pro items (more keywords, advanced robots, X details) unlock with a SEMRS Pro license

= 1.3.12 =
* SEO box: new Speed & accessibility checks (image size and format, no GIFs, lazy embeds, valid lists and ARIA roles, link text, heading order, theme colours for dark mode) to keep pages at PageSpeed 100

= 1.3.11 =
* Redirects: the list of active redirects folds; click its heading to show or hide it

= 1.3.10 =
* SEO: Rank Math copy counter resets on each run

= 1.3.9 =
* SEO: copy Rank Math data into SEMRS fields (SEMRS → SEO)
* Speed Pro: remove unused CSS

= 1.3.8 =
* Declares WP Consent API support (functional storage only, no trackers)

= 1.3.7 =
* Ads: rectangle, vertical (half-page) and Multiplex formats

= 1.3.6 =
* Ads: fixed the AdSense tag check
* Ads: blocks Site Kit's duplicate AdSense tag and keeps it off ad-free pages
* Ads: minimum words between ads, no ad above a free tool

= 1.3.5 =
* Maintenance release.

= 1.3.4 =
* Maintenance release.

= 1.3.3 =
* SEO title, meta description and focus keyword fields for categories and tags (also over the REST API); empty category archives are noindexed until they have posts.

= 1.3.2 =
* SEO title, meta description, focus keyword and score are available over the REST API (block editor and site tools).

= 1.3.1 =
* Redirects: addresses with non-English (percent-encoded) characters are saved correctly.

= 1.3.0 =
* New free modules: Redirects & 404 log and IndexNow (moved from Pro; IndexNow is now opt-in), Image Optimization (WebP/AVIF, resize and compression on upload), User Roles editor with extra roles per user, and Review Widgets (Trustpilot TrustBox and Google reviews link).

= 1.2.2 =
* Rank Math bridge: Service, WebApplication and FAQPage structured data are added to Rank Math's graph, and pages where Rank Math prints none get the SEMRS graph. Schema type is editable over the REST API.

= 1.2.1 =
* The Settings link on the Plugins screen opens the SEMRS dashboard

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
* Free/Pro split: SEO essentials stay in Core; redirects, ads, layouts, proof and leads moved to SEMRS Pro. Extensible module loader.

= 0.3.0 =
* New SEO module (titles, meta, robots, canonical, social, schema, sitemaps, redirects, 404 log, breadcrumbs, checks, Rank Math compatibility).
* Ads module merged in (formerly the SEMRS Ads plugin).
* Module switches and a SEMRS admin menu.
* Site Layouts now work with SEMRS Pro and Astra.

= 0.2.0 =
* First release.
