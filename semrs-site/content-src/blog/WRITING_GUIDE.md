# SEMRS blog writing guide

The house style and SEO checklist for every semrs.com article. Sections 13 (speed, accessibility and dark mode) and 14 (indexing and search engines) apply to every page and post on the site. The first articles (October 2026) follow it and score 98–100 on the SEMRS SEO check. Use it for every article in the content plan (`keywords/semrs-keywords.xlsx`, sheet "3-Month Plan").

Last updated 2026-10-28, after the site-wide SEO audit (average score 87 across 61 pages), a PageSpeed Insights run on all 63 published pages, and a review of every SEMRS plugin setting.

## 1. Who we write for

- Small and growing business owners in the US, UK, Canada and Australia, plus the industries in our plan (financial advisors, insurance, mortgage, law, dental, med spa, real estate, home services).
- They want a clear answer, a cost or a plan they can act on today.
- Write for a smart reader who is not a marketer: plain English, no jargon without a one-line explanation.

## 2. Voice and language

- **US spelling** (color, optimize, license, honor) and USD prices.
- Short sentences (most under 25 words) and paragraphs under 150 words.
- Active voice, second person ("you"), direct and calm. No hype, no "ultimate", no exclamation marks.
- Never promise rankings, income or results. Say what usually happens and what it depends on.
- Brand name: **SEMRS** (Search Engine Marketing & Ranking Solutions). Never mention or compare with Semrush.
- Every number needs a source. No invented statistics, testimonials, case studies or client names.

## 3. Article front matter (required)

```
---
title: <H1 shown on the page, can be longer>
slug: <lowercase-hyphenated, contains the focus keyword, under 75 characters>
seo_title: <30–60 characters, contains the focus keyword>
focus: <the focus keyword from the plan, lowercase>
description: <120–155 characters, contains the focus keyword, says what the reader gets>
category: <WordPress category ID, see section 10>
keyword: <primary keyword from the plan>
---
```

## 4. Structure (in this order)

1. **Opening paragraph** (2–4 sentences): answer the search question directly. The focus keyword appears in the first 100 words.
2. **Key takeaways** (`## Key takeaways` + 3 bullets): the three most useful points. It is an H2: an H3 straight after the title skips a heading level.
3. **Disclaimer callout** for legal, finance, tax, health or insurance topics (`! This article is general … not legal advice …`).
4. **H2 sections** (one idea each). At least one H2 contains the focus keyword. Use H3 for sub-steps. Never use H1 in the body (the title is the H1).
5. **One data graphic** after the section it illustrates (`@img chart`), see section 7.
6. **A table** wherever you compare options, prices or steps (`| a | b |`).
7. **Numbered steps** for any process, bullets for lists.
8. **Frequently asked questions** (`## Frequently asked questions` + 4–6 `? Question` blocks). These become FAQ schema automatically. Use real questions people search; the last one can mention the relevant SEMRS service.
9. **Closing call to action** as a callout linking to `/get-a-quote/` or the matching service page.
10. **Sources line** (`**Sources:** …`) when the article cites figures.

## 5. Keywords

- **Focus keyword**: in the slug, SEO title, meta description, first 100 words and at least one H2/H3.
- **One focus keyword per URL, never reused.** SEMRS uses it three ways: the SEO check, daily rank tracking (SEO → SEO Performance tracks every published focus keyword automatically) and automatic internal links (section 6). Two pages with the same focus keyword compete with each other and confuse all three. Check the list in SEO Performance before choosing one.
- **Make it specific**: "seo for attorneys", not "seo" or "pricing". Generic focus keywords turn common words into site-wide links.
- **Density** between 0.5% and 2.5% (the SEMRS check: uses × words in the keyword ÷ total words × 100; a 3-word keyword in 1,000 words needs 2–8 uses). Above 2.5% is flagged as keyword stuffing; use synonyms instead.
- **Supporting / LSI keywords**: use the ones listed for the article in the plan naturally in subheadings, the FAQ and body text.
- Answer the search intent shown in the plan (informational, commercial). Commercial articles should include costs, comparisons or "how to choose".

## 6. Links

- **Internal (3–5)**: the category pillar article, one sibling article, the matching SEMRS service page and, where useful, a free tool (`/tools/...`). Use descriptive anchor text, never "click here".
- **External (1–2)**: official or primary sources that back a specific claim (government, regulator, Google documentation, original research). They open in a new tab automatically.
- No vendor or affiliate links unless the article discloses them. No links to "write for us" or guest-post sites.
- Post URLs sit at the root: link `/slug/`, not `/blog/slug/`.
- **Only link pages that exist.** Service pages: `/services/seo/` (SEO & GEO), `/services/seo-content-writing/`, `/services/content-marketing/`, `/services/content-management/`, `/services/social-media-management/`, `/services/web-design/`, `/services/paid-ads-lead-generation/`. Others: `/pricing/`, `/get-a-quote/`, `/contact-us/`, `/tools/`, `/semrs-pro/`. A guessed URL (the audit found `/services/ai-search-optimization/`) becomes a broken link.
- **Automatic internal links are on** (SEO → Link Assistant): every page's focus keyword links to that page, once per page, at most 3 per page, never in headings or buttons, and never to the page itself. Don't add a manual link for a phrase that is already another page's focus keyword; it would link twice.
- **Broken links**: Link Assistant checks every link weekly. Before you publish, its "Broken links" count must be 0. If an official page (Google help, government) shows as broken but opens in a browser, click "Check again".

## 7. Images

| Image | Rule |
|---|---|
| Featured image | One photo, 1200 × 675, WebP, under 100 KB, file name `{slug}-featured.webp`. Own photos or CC0 / commercial-use license; record the credit in the media description. |
| Data graphic | One per article, in the body, drawn in SEMRS colors (blue #0b2fd1, orange #f7941d) with `content-src/blog/graphics.mjs`. Use only cited figures or the article's own steps. Label examples as examples. |
| Alt text | Describe what the image actually shows; include the topic naturally, never stuff keywords. |
| Caption | One sentence that adds meaning (what the reader should take from it). |
| No GIFs | Heavy files hurt Core Web Vitals. Use a still graphic instead. |

### SEMRS stamp on images

| Image source | Stamp |
|---|---|
| Photos from free image sites (CC0 / free-license stock: StockSnap, Rawpixel, Pixabay, Unsplash, Pexels and similar) | Always: the word **SEMRS** in plain text, white on a soft dark pill, bottom-right corner. |
| Generated images (AI-made images, SEMRS data graphics) | Only when necessary, for example an image likely to be shared or copied on its own. Then the word **SEMRS** in plain text. SEMRS data graphics already print "SEMRS" in their footer, so they get no extra stamp. |
| Own photos, client photos, screenshots | No stamp. |

- Write the word **SEMRS** only: never the SEMRS logo, emblem or a badge, and no other words (no URL, slogan or "©").
- Keep the stamp small and in a corner, away from faces, products and text in the image.
- A stamp never replaces the credit: record the photo credit and license in the media description as before.

Build images with `node content-src/blog/blog-images.mjs` from the `semrs-site` folder (needs `npm i --no-save sharp`). It crops and stamps free-stock photos and renders the graphics to WebP. In its `CHARTS` list, add `{ stamp: true }` to an item only when a generated image needs the stamp.

## 8. Technical SEO checklist

- 900–1,500+ words (aim for 1,200–1,800 in new articles); depth over length.
- One H1 (the title), logical H2 → H3 order, short slug that contains the focus keyword (the SEO audit flags pages without it).
- SEO title 30–60 characters, meta description 120–155 characters, both unique across the site (the SEO audit lists duplicates).
- Noindex pages (thank-you, forms, My Licenses) still get a meta description; PageSpeed flags a missing one even on noindex pages. Their PageSpeed SEO score of 54–63 comes from the noindex tag itself and is expected.
- Featured image set; every in-body image has alt text and a caption.
- Free-stock photos carry the plain-text **SEMRS** stamp (never the logo); see section 7.
- FAQ section present (FAQPage schema is added by SEMRS automatically).
- Article, BreadcrumbList and Organization schema come from SEMRS; nothing to add by hand.
- Run `node content-src/blog/build-blog.mjs` from the `semrs-site` folder: every article must print `ok`.
- After publishing, the SEMRS SEO score must be 98 or higher, with every **Speed & accessibility** check green.
- PageSpeed Insights 100 / 100 / 100 / 100 and Agentic Browsing 3/3, mobile and desktop: follow section 13.

## 9. Local SEO requirements

- Use real US, UK, Canadian or Australian cities and services in examples ("emergency plumber in Denver").
- For local-service topics, link the [Google Business Profile guide](/google-business-profile-optimization/) and mention reviews, categories and service areas.
- Mention local rules where they matter (state bar rules, state insurance departments, FINRA/SEC, ADA).
- Keep the SEMRS name, email and website identical everywhere (NAP consistency).

## 10. Categories (WordPress IDs)

Each category page has its own description, SEO title, meta description and focus keyword (Posts → Categories → Edit → SEMRS SEO). Category pages with no posts are noindexed automatically until the first post is published. Don't target a category's focus keyword in an article.

| ID | Category | Workbook sheet | Category focus keyword |
|---|---|---|---|
| 1 | Digital Marketing | Digital Marketing | small business marketing |
| 293 | SEO & AI Search | SEO & AI Search | seo and ai search |
| 26 | Local SEO | Local SEO | local seo |
| 27 | PPC & Paid Ads | PPC & Paid Ads | ppc advertising |
| 24 | Web Design & WordPress | Web Design & WordPress | wordpress website design |
| 294 | Content & Social Media | Content & Social Media | content and social media marketing |
| 296 | AI Marketing | AI Marketing | ai marketing |
| 292 | Marketing Costs & ROI | Marketing Costs & ROI | marketing costs |
| 25 | Finance & Insurance Marketing | Industry Marketing (Finance & Insurance) | financial services marketing |
| 298 | Legal & Healthcare Marketing | Industry Marketing (Legal & Health) | legal and healthcare marketing |
| 297 | Business Finance | Business Finance (loans, credit, merchant services) | small business finance |
| 299 | Business Insurance | Business Finance (keywords containing "insurance") | small business insurance |

The old `/category/industry-marketing/` URL redirects to Finance & Insurance Marketing.

## 11. Google and AdSense content rules

- Helpful, original, first-hand content; no thin, copied or mass-produced pages.
- Finance, legal, health and insurance (YMYL) articles need a qualified reviewer and a disclaimer.
- No ads on contact, quote, thank-you, pricing or sales pages. SEMRS Ads skips the paths listed under SEMRS → Ads → Excluded paths (services, pricing, contact, quote, thank-you, about, how we work, legal pages, portfolio, SEMRS Pro, My Licenses and others). **When you publish a new sales or conversion page, add its path there**: an ad on it costs conversions and PageSpeed points (the SEMRS Pro page went from 81 to 100 once excluded).
- Business finance articles (loans, credit cards, insurance) describe options and trade-offs; they never give personal financial advice and must disclose any affiliate relationship.

## 12. Publishing workflow

1. Pick the next article in the 3-Month Plan sheet; copy the article template sheet into `content-src/blog/articles/NN-slug.md`.
2. Write it to this guide; add the graphic to `graphics.mjs` and the photo to `blog-images.mjs`, then run it (free-stock photos get the SEMRS text stamp; generated images only if necessary).
3. Build and check (`build-blog.mjs` prints `ok`).
4. Upload to staging, set the featured image, check the SEMRS score (98+) with every "Speed & accessibility" check green, then a person approves it before it goes live.
5. Schedule it in SEMRS → Content Calendar (or publish). On publish, auto-indexing sends the URL to Bing and the other IndexNow engines straight away and updates the sitemap for Google.
6. After it goes live, run [PageSpeed Insights](https://pagespeed.web.dev/) on the URL (mobile and desktop) and meet the targets in section 13.
7. Indexing (section 14): the **Index status** column in Posts and Pages shows each URL's state (Live & indexed, Live: waiting for Google, Live, not indexed yet, Redirects, Not live, Excluded: noindex). Click its **Request indexing** link (or use SEMRS → Index Status) and request indexing in Search Console.
8. Share it with the one-click share buttons in the post's SEMRS SEO box (or the Content Calendar).
9. Mark it "Written" in the plan. Two weeks later, check its focus keyword in SEO → SEO Performance and its row in Index Status ("Live & indexed").

## 13. Speed, accessibility and dark mode (PageSpeed 100)

These rules apply to **every page and post** on semrs.com, not only articles. The SEMRS SEO box checks most of them automatically in its **Speed & accessibility** group.

**Targets (PageSpeed Insights, mobile and desktop, measured 2026-10-28 on all 63 pages):**

| Page type | Performance | Accessibility, Best Practices, SEO | Agentic Browsing | CLS |
|---|---|---|---|---|
| Ad-free pages (home, services, pricing, about, contact, sales, legal) | 100 | 100 | 3/3 | under 0.01 |
| Pages with ads (blog posts, tools) | 85+ mobile, 95+ desktop | 100 | 3/3 | under 0.1 |
| Noindex pages | 100 | 100 (SEO 54–63 from the noindex tag) | 3/3 | under 0.01 |

The AdSense script itself costs ad pages 5–15 Performance points on PageSpeed Insights; that is expected and not a content problem. Anything else below target is: fix it before moving on. (Local Lighthouse runs block ad scripts and show 100 for ad pages too; always judge ad pages on PageSpeed Insights.)

**Layout: nothing may jump (Cumulative Layout Shift 0)**
- Every image has a `width` and `height` (WordPress adds them for Media Library images; never paste images by URL without them).
- Animated or typing text must not change the height of the page. The SEMRS Fancy Heading reserves the space of its longest word; don't build moving text any other way.
- Don't insert banners, notices or embeds above content after the page loads. Ads go only in the SEMRS ad slots, which reserve their space: a slot for another screen size never shows, and an empty ad keeps its space while it is on screen (SEMRS Core 1.3.23+). Never paste raw ad code into content.

**Images and media**
- WebP (or AVIF/SVG), sized to how they show: 1200 px wide at most for full-width images. Featured images under 100 KB, any image under 150 KB.
- No GIFs. Use a still image or a short, muted video.
- Videos and maps are embedded with lazy loading (the default WordPress embed blocks do this).

**Structure that screen readers and AI agents understand**
- One H1 (the title); headings in order (H2 → H3 → H4), never skipping a level.
- Lists contain only list items. Don't put a role on a list item, and don't put `role="img"` on a figure that has a caption; put the role on the image or SVG inside.
- Every link and button has readable text (or an `aria-label` for icon-only buttons). No "click here".
- Every form field has a visible label, including sliders and other controls built by scripts (an unlabeled slider cost the Image Compressor tool its accessibility 100 and Agentic 3/3).

**Colour and dark mode**
- Use the theme's colours (block colour settings from the SEMRS palette). Don't type fixed light backgrounds such as `#fff`, `#f6f7f9` or `#fbf4e0` into blocks: they turn into light boxes with unreadable white text in dark mode.
- Text must stay readable in **both** light and dark mode, including on hover: links and outline buttons on blue bands are white, and button hovers use the theme's button hover colours.
- Check new pages in dark mode (moon icon in the header) before publishing.

**Scripts and caching (site setup, not per article)**
- No extra tracking or chat scripts in content. Third-party scripts (Google tags) load through Site Kit and are delayed until the visitor interacts.
- CSS and JS are versioned (`?ver=`) and cached for a year (the `# BEGIN SEMRS cache` block in `.htaccess`); images for four months. Keep that block when editing `.htaccess`.

## 14. Indexing and search engines (Google, Bing)

Site-wide settings, checked and set on 2026-10-28. Keep them this way; content work depends on them.

**SEMRS settings (already on)**

| Where | Setting | Why |
|---|---|---|
| SEO → Indexing & sitemap | XML sitemap `/wp-sitemap.xml` on; image and video sitemaps on (Sitemaps Pro) | Google and Bing find every page and image. The news sitemap stays off: it is only for Google News publishers. |
| SEO → Indexing & sitemap | Noindex tag, author and date archives; attachment pages redirect to their post | Thin duplicate pages stay out of the index (semrs.com has one author, so the author archive repeats the blog). |
| SEO → Indexing & sitemap | llms.txt on | AI assistants get a clean map of the important pages. |
| Index Status | Auto-indexing on | Every new, updated or deleted page goes to IndexNow (Bing, Yandex, Seznam, Naver) at once. |
| IndexNow | On, key file at the site root | Required by Bing for IndexNow. |
| Link Assistant | Automatic internal links on (focus keywords, 3 per page); broken link checker on | Internal links help pages get found and ranked; broken links waste crawl budget. |
| Speed | Page cache, deferred and delayed scripts, unused CSS removal, WebP images | Core Web Vitals are a ranking signal. |
| SEO Performance | Daily rank tracking of every focus keyword, Search Console data via Site Kit | Shows what ranks and what needs work. |

**Google**
- Search Console is connected through Site Kit (verified). Make sure `/wp-sitemap.xml` is listed under Search Console → Sitemaps; add it if it is missing.
- Google does not use IndexNow and has no public "request indexing" API. For each new or important page, use the **Request indexing** link in SEMRS → Index Status (one click per page in Search Console).
- Pages in "Live, not indexed yet" for more than four weeks need more internal links from indexed pages and a content check (unique, helpful, not thin).

**Bing**
- Bing Webmaster Tools: sign in with the site owner's account and choose **Import from Google Search Console** (fastest), or paste Bing's verification code in SEO → Social, verification & breadcrumbs → Bing verification code. Then submit `https://semrs.com/wp-sitemap.xml`.
- Bing also reads IndexNow, which SEMRS sends automatically.

**Still to fill in (only with real details)**
- SEO → Business: phone, street address, city and country, only if SEMRS has a real public address (never a virtual office).
- SEO → Default share image (1200 × 630) for pages without a featured image.
- AI SEO: an API key of your own (Claude, OpenAI or Gemini) if you want AI-written titles and descriptions; a person always reviews them.
