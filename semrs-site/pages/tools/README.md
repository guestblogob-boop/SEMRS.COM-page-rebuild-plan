# SEMRS Free Tools Pages: Publishing Guide

22 publish-ready pages: the `/tools/` hub, 9 category pages and 12 tool pages. 16,000+ words of original content in total.

## Two ways to publish

### A. One click (recommended)
1. Install and activate **semrs-tools.zip** (and SEMRS Core for SEO + Ads).
2. Go to **SEMRS → Free Tools** (or Tools → SEMRS Free Tools) and click **Create all tool pages as drafts**.
3. All 22 pages are created as drafts under `/tools/`, with full content, SEO title, meta description, focus keyword and schema type already filled in. The fields are filled for both SEMRS SEO and Rank Math.
4. Open each draft, read it, add images if you want, and click **Publish**.

Existing pages with the same URL are never overwritten.

### B. Paste manually
Each folder holds `page.html` (block code) and `seo.md` (URL, title, description, focus keyword, schema).
1. Pages → Add New. Title = the H1 from `seo.md`. Slug = the folder name. Parent = "Free SEO Tools" (except the hub itself).
2. ⋮ menu → Code editor → paste `page.html` → Exit code editor.
3. Fill the SEO box from `seo.md`.

## What every page includes (technical SEO)

| Item | Status |
| --- | --- |
| Unique SEO title ≤ 60 characters, keyword first, brand last | ✔ all 22 |
| Unique meta description 120–158 characters with a call to action | ✔ all 22 |
| One focus keyword per page, in title, description, H1 and intro | ✔ |
| Exactly one H1 (page title) and a logical H2 structure | ✔ tested |
| Self-referencing canonical, indexable | ✔ tested |
| Breadcrumbs (visible + BreadcrumbList schema) | ✔ tested |
| WebApplication schema (tool pages, free offer) / CollectionPage (hub + categories) | ✔ tested |
| FAQPage schema built from the visible FAQ section | ✔ tested |
| Internal links: related tools + category + hub + matching service page | ✔ no broken tool links |
| Ad slot below the tool, at least 150 px from the tool buttons (AdSense policy) | ✔ |
| Mobile, tablet and desktop layouts | ✔ tested |

## Thin-content protection (Google + AdSense)

- Tool pages: 911–1,202 words each. Categories: 424–485 words. Hub: 808 words.
- Every tool page: what it is · how to use it (steps) · worked example · best practices · common mistakes · **tips for local businesses (US, UK, CA, AU)** · 5 FAQs · related tools · service CTA.
- All written for these tools specifically. No duplicated paragraphs between pages.
- No invented statistics, testimonials or guarantees (brand rules).

No one can guarantee how Google or AdSense will judge a page. These pages remove the usual thin-content causes: widget-only pages, duplicated text and missing context.

## Local SEO approach
Each page has a section for local businesses in the US, UK, Canada and Australia with local examples (Austin, Leeds, Toronto, Melbourne, Calgary, Brisbane…). There are no fake addresses and no city doorway pages. Your own business details come from SEMRS → SEO (Organization/LocalBusiness schema with areaServed).

## Before publishing
- Links to `/services/…`, `/get-a-quote/` and `/pricing/` point to pages from the main build plan. Publish those pages first, or expect a few broken links until they exist.
- Optional: add a real screenshot of each tool (WebP, with alt text) under the intro.
- After publishing: submit `/wp-sitemap.xml` in Search Console and request indexing for `/tools/` and the 12 tool pages.
