---
title: SEMRS Core SEO Manual: Titles, Schema, Sitemaps
description: Set up SEO with SEMRS Core: titles and meta, SEO score and readability checks, site audit, schema, XML sitemaps, llms.txt and switching from Rank Math, Yoast or AIOSEO.
order: 4
---
The SEO module is part of the free SEMRS Core plugin. Switch it on in **SEMRS → Features**, then open **SEMRS → SEO**.

## 1. Site-wide settings (SEMRS → SEO)

1. **Titles:** choose a separator and title templates. Variables such as `%title%`, `%site%` and `%sep%` are filled in for each page.
2. **Homepage:** write the homepage title (under 60 characters) and description (under 160 characters).
3. **Organisation or person:** your business name, type (LocalBusiness, ProfessionalService, Organization or Person), logo, address, area served and social profiles. This becomes your site's JSON-LD schema.
4. **Robots:** search results and 404 pages are always kept out of Google; choose whether tag, author and date archives are indexed (tags and dates are noindexed by default).
5. **Verification:** paste the Google Search Console and Bing Webmaster codes.

## 2. Each post and page (SEMRS SEO box in the editor)

- **Focus keyword**, **SEO title** and **meta description** with live length counters and a Google preview.
- **Canonical URL** (leave empty unless the page is a copy of another).
- **Schema type**, **noindex** and **nofollow**.
- **SEO score (0–100)** with checks in three groups:
  - **Basic SEO:** focus keyword in the title, description, URL and first 100 words; the keyword not already used on another page (pages competing for the same keyword); title 30–60 and description 120–155 characters; a short URL.
  - **Content:** enough words (300 for posts, 200 for pages); internal links; a link to a useful outside source; image alt text; a featured image; no extra H1; subheadings that break up long text; the keyword in a subheading; keyword density between 0.5% and 3%.
  - **Readability:** short sentences, comfortable paragraph length and, for English sites, the Flesch reading-ease score.
- **Re-check now** analyses your unsaved changes, so you can improve the score before you publish.
- **Internal link ideas:** after a re-check, SEMRS lists your published pages on the same topic that this page does not link to yet, with a Copy link button.

The score also appears in an **SEO** column on the Posts and Pages lists (green 80+, orange 50–79, red under 50). Click the column title to sort by score.

Empty descriptions are filled from the excerpt or the first paragraph, so no page is left without one.

## 3. SEO Audit (SEMRS → SEO Audit)

**Site checks** run instantly: search engines allowed, readable permalinks, HTTPS, your own tagline, a homepage description, the sitemap, a logo for search results, a site icon, robots.txt problems, a second SEO plugin, Search Console verification and leftover WordPress sample content. Each problem has a **Fix** link.

**Content audit:** click **Run the content audit**. SEMRS scores every published post and page (up to 2,000) in small steps, then shows:

- average score and pages scoring under 50
- missing meta descriptions, duplicate SEO titles and duplicate descriptions
- thin content and noindexed pages
- **orphan pages**: pages no other post or page links to (menus are not counted). Link to them from related articles so visitors and Google can find them.

Click a number to filter the table; each row lists what to fix, with an edit link.

## 4. Schema

SEMRS Core outputs one connected JSON-LD graph: Organization or LocalBusiness, WebSite with search, WebPage, BreadcrumbList, Article/BlogPosting with the author, Service, WebApplication for tool pages, and FAQPage built automatically from visible FAQ (Details) blocks. Schema must describe what visitors can see on the page; never add reviews or ratings that are not shown.

## 5. XML sitemap, robots.txt and llms.txt

The sitemap uses WordPress's own engine at `/wp-sitemap.xml`; noindexed pages are left out and old `/sitemap_index.xml` addresses redirect to it. Submit it in Google Search Console → Sitemaps. Extra robots.txt lines can be added in SEMRS → SEO.

**llms.txt** (on by default) is a short plain-text map of your site at `/llms.txt` for AI assistants such as ChatGPT, Perplexity and Claude: your site summary, main pages and latest articles with their descriptions. Noindexed and password-protected pages are left out, and it updates when you publish. A real llms.txt file in your site root always takes priority. Switch it off under SEMRS → SEO → Indexing.

## 6. Switching from another SEO plugin

While Rank Math, Yoast, All in One SEO, SEOPress or The SEO Framework is active, SEMRS SEO pauses its output so you never get duplicate tags. Titles, descriptions, focus keywords, canonicals, noindex settings and share images saved by **Rank Math**, **Yoast SEO** or **All in One SEO** (post settings) are read automatically, including their title variables, so nothing is lost when you switch.

1. Install SEMRS Core and check a few pages in the SEMRS SEO box.
2. Deactivate the old SEO plugin.
3. Open a few pages, right-click → View Source, and confirm there is one `<title>`, one meta description and one canonical tag.
4. Re-submit your sitemap in Search Console.

## 7. SEMRS Pro SEO extras

Redirects with a 404 monitor, multi-location local SEO, IndexNow instant indexing, automatic image alt text, and the **Bulk SEO editor** (SEMRS → Bulk SEO): edit SEO titles, descriptions, focus keywords and noindex for 20 posts at a time, with length counters and a "missing description" filter. See [SEMRS Pro](/support/pro/).

## Good practice

Write titles and descriptions for people, not for keyword counts. Google's guidelines ask for helpful, original content; tools can only make that content easier to find.
