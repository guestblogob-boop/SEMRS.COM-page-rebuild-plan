=== SEMRS ===
Contributors: semrs
Tags: blog, portfolio, grid-layout, full-width-template, block-patterns
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.3.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight, SEO-first theme for businesses, agencies and blogs, with a free drag-and-drop header and footer builder.

== Description ==

SEMRS is fast by design: one stylesheet, one small script, no jQuery, no icon or web fonts by default. Everything below is free.

= Header & footer builder =
* Customizer → SEMRS Theme → Header builder: drag elements into the left, centre and right of three rows (top bar, main header, below header).
* Elements: logo, primary menu, secondary menu, two buttons, search, social icons, phone & email, two text/HTML areas.
* Sticky header with optional shrink, transparent header (front page or all pages, light text option), top bar colours, header background.
* Phones: logo and menu icon with a full-width menu panel; optional header button and top bar.
* Footer builder: copyright, social icons, footer menu, phone & email and a text area in the bottom bar; up to 4 widget columns above it.

= Per-page options =
Hide the title, header or footer, turn the transparent header on or off, and choose the sidebar for any page or post.

= Blog =
Card grid or list archives, reading progress bar, "Updated on" date, author box (with optional "Reviewed by" from the semrs_reviewed_by custom field), related posts, previous/next links, back-to-top button, numbered pagination.

= Design =
* Logo width for desktop and mobile (512 × 512 px recommended; square logos detected), header height, colours, font size and container width.
* Footer social icons for 9 networks in brand colours or one colour.
* 7 section patterns: Hero, Hero with illustration, Services, How it works, Pricing, FAQ, Call to action.
* Right / left / no sidebar for posts, pages and archives. Templates: Full width and Blank canvas.

= SEO & accessibility =
Semantic HTML5, one H1 per page, landmarks, skip link, visible focus styles, breadcrumbs when an SEO plugin provides them, high-priority loading for the first featured image.

= Works with =
The free SEMRS Core plugin (SEO, speed, ads manager) and SEMRS Blocks plugin (extra blocks). Both are optional.

= Hooks =
wp_body_open, semrs_before_header, semrs_header_actions, semrs_after_header, semrs_before_content, semrs_content_top, semrs_before_entry_content, semrs_after_entry_content, semrs_after_entry, semrs_content_bottom, semrs_sidebar_top, semrs_sidebar_bottom, semrs_after_content, semrs_before_footer, semrs_footer_bottom, semrs_after_footer. Filters: semrs_theme_layout, semrs_theme_settings, semrs_theme_header_elements, semrs_theme_footer_elements.

== Frequently Asked Questions ==

= Where is the header builder? =
Appearance → Customize → SEMRS Theme → Header builder.

= How do I make the header transparent on one page only? =
Edit the page, open "Page options" in the sidebar and set Transparent header to On.

== Changelog ==

= 1.3.1 =
* Hero with cover image pattern now uses a photo in an arch frame
* New theme screenshot

= 1.3.0 =
* SEO: 0–100 SEO score with readability checks, Re-check and internal link ideas; SEO Audit screen; llms.txt; reads Yoast and AIOSEO data
* Speed: page cache, per-block and non-blocking CSS, hover prefetch, database cleanup and speed check
* Starter-site templates library: 23 sites with header and footer presets (SEMRS Blocks)
* Pro: Ads Pro (repeat and between-posts placements, targeting, A/B rotation, click protection, ad-blocker message), Speed Pro (delay JavaScript, cache preload, CDN), bulk SEO editor, dark mode switch, languages and Pro starter sites
* Translation templates (.pot) included; tested up to WordPress 7.1; Plugin Check and Theme Check fixes

= 1.2.0 =
* New: drag-and-drop header and footer builder (top bar, main header, below header; 10 header elements, 5 footer elements).
* New free features: sticky header with shrink, transparent header, header search, reading progress bar, "Updated on" date, author box and related posts.
* New: hide header / footer and transparent header per page.
* New patterns: Hero with illustration, Services, How it works, Pricing.
* Script and head clean-up moved to the SEMRS Core plugin (Speed), where it belongs.

= 1.1.0 =
* Logo width controls, footer social icons in brand colours, automatic updates from semrs.com.

= 1.0.0 =
* First release.

== Copyright ==

SEMRS WordPress Theme, Copyright 2026 SEMRS.
SEMRS is distributed under the terms of the GNU GPL v2 or later.

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

== Resources ==

* assets/images/hero-illustration.svg, hero-cover.svg, hero-cover-2.svg: original artwork by SEMRS, GPLv2 or later.
* assets/images/hero-photo.jpg: photo owned by SEMRS (AI-generated for this theme), GPLv2 or later.
* Social network marks in inc/customizer.php: simplified shapes drawn for this theme; the names and logos are trademarks of their owners and are used only to link to the site owner's profiles.
* UI icons (search, phone, email): original artwork by SEMRS, GPLv2 or later.
* screenshot.png: screenshot of this theme with its own patterns and artwork, GPLv2 or later.
