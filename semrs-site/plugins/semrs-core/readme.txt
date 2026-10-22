=== SEMRS Core ===
Contributors: semrs
Tags: seo, page cache, redirects, webp, user roles
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv2 or later

Free SEO, redirects, page cache, WebP images, user roles, review widgets and a policy-safe AdSense manager in one plugin.

== Description ==

Free SEO with an SEO score and site audit, redirects and a 404 log, IndexNow, a page cache and speed optimization, WebP/AVIF image optimization on your own server, a user role editor, review widgets and a policy-safe ads manager for any WordPress site. SEMRS Pro adds multi-location local SEO, image SEO, custom schema, a bulk SEO editor, delayed scripts, stylesheet minification, cache preload, CDN, media library conversion, Pro blocks and more.

== Features screen ==
SEMRS → Features shows every SEMRS feature, free and Pro, as cards with search, filters (Free, Pro, SEO, Speed, Ads, Design, Leads), on/off switches and a Settings link. A switched-off feature loads no code. Pro features are marked PRO and stay locked until SEMRS Pro is installed.

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

== External services ==

SEMRS Core works without any outside service. These connections happen only when you use the related feature:

* **Google AdSense** (Ads module, only after you enter your publisher ID and switch ads on): the AdSense script is loaded from pagead2.googlesyndication.com on pages where ads are allowed, and Google serves the ads. Google receives the visitor's IP address, browser details and cookies as described in its policies. [Terms](https://policies.google.com/terms), [Privacy](https://policies.google.com/privacy), [AdSense terms](https://www.google.com/adsense/new/localized-terms).
* **Other ad networks** (Ads module, custom code): only the code you paste is loaded, from the network you choose, under that network's terms.
* **YouTube** (Speed → YouTube click-to-play, on by default for pages that already embed YouTube videos): the video thumbnail is loaded from i.ytimg.com, and the player loads from youtube-nocookie.com only when the visitor clicks Play. [Terms](https://www.youtube.com/t/terms), [Privacy](https://policies.google.com/privacy).
* **IndexNow** (IndexNow module, only after you switch it on under SEMRS → IndexNow): the addresses of public posts you publish, update or delete, with your site's key, are sent to api.indexnow.org, which shares them with participating search engines. [Terms and privacy](https://www.indexnow.org/terms).
* **Trustpilot** (Review widgets, only on pages where you add [semrs_reviews] with your business unit ID): the TrustBox script is loaded from widget.trustpilot.com and Trustpilot shows your reviews. [Terms](https://legal.trustpilot.com/for-everyone/end-user-terms-and-conditions), [Privacy](https://legal.trustpilot.com/for-reviewers/end-user-privacy-terms).
* **Google PageSpeed Insights** (Speed → Speed check): an "Open PageSpeed Insights" link opens pagespeed.web.dev with your homepage address when you click it. Nothing is sent automatically. [Terms](https://policies.google.com/terms), [Privacy](https://policies.google.com/privacy).

The speed check, SEO audit, page cache, llms.txt and all other features run entirely on your own server.

== Changelog ==

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
