=== SEMRS Pro ===
Contributors: semrs
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Lightweight, SEO-first, AdSense-ready theme for agencies, tool sites and blogs.

== Description ==

Speed
* One stylesheet and one ~2 KB deferred script. No jQuery, no icon fonts, no web fonts by default (system fonts; optional self-hosted Inter).
* Core block CSS loads only for the blocks a page uses. Emoji, oEmbed discovery, jQuery Migrate and dashicons (for visitors) are removed.
* Featured image on single posts loads with fetchpriority="high" (LCP); others lazy-load.
* Customizer CSS is printed only for settings you change.

SEO
* Clean semantic HTML5: one H1 per page, landmarks, skip link, breadcrumbs (SEMRS Core SEO or Rank Math).
* Table of contents with heading anchors, "Updated on" dates, author box with reviewer line (custom field semrs_reviewed_by), related posts.
* Numbered pagination (no infinite scroll, which hurts crawling and ad viewability).

AdSense ready
* Styled, labelled, height-reserved ad spots for the SEMRS Core Ads module. Unfilled AdSense units collapse.
* Sticky sidebar for a high-viewability sidebar ad. Theme hooks for any ad network.

Design & layout
* Sticky header, transparent front-page header, mega menu (add CSS class "semrs-mega" to a top menu item), menu item descriptions, header button.
* Right / left / no sidebar per posts, pages and archives, plus a per-page override. Card grid or list archives.
* Templates: Full width (landing/service pages) and Blank canvas (no header/footer).
* Up to 4 footer widget columns, footer menu, copyright with {year}/{site}, disclaimer line, back-to-top button.
* Colour, font, font size and container width controls. WCAG AA default colours. Responsive at 544 / 921 / 1200 px.

Hooks
wp_body_open, semrs_before_header, semrs_header_actions, semrs_after_header, semrs_before_content, semrs_content_top, semrs_before_entry_content, semrs_after_entry_content, semrs_after_entry, semrs_content_bottom, semrs_sidebar_top, semrs_sidebar_bottom, semrs_after_content, semrs_before_footer, semrs_footer_bottom, semrs_after_footer, semrs_404_after. Filter: semrs_pro_layout.

== Changelog ==
= 1.0.0 =
* First release.
