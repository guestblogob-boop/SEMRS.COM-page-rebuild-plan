# SEMRS blog writing guide

The house style and SEO checklist for every semrs.com article. The first 12 articles (October 2026) follow it and score 98–100 on the SEMRS SEO check. Use it for every article in the content plan (`keywords/semrs-keywords.xlsx`, sheet "3-Month Plan").

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
2. **Key takeaways** (`### Key takeaways` + 3 bullets): the three most useful points.
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
- **Density** between 0.5% and 3% (a 3-word keyword in 1,000 words needs 2–10 uses). Do not stuff.
- **Supporting / LSI keywords**: use the ones listed for the article in the plan naturally in subheadings, the FAQ and body text.
- Answer the search intent shown in the plan (informational, commercial). Commercial articles should include costs, comparisons or "how to choose".

## 6. Links

- **Internal (3–5)**: the category pillar article, one sibling article, the matching SEMRS service page and, where useful, a free tool (`/tools/...`). Use descriptive anchor text, never "click here".
- **External (1–2)**: official or primary sources that back a specific claim (government, regulator, Google documentation, original research). They open in a new tab automatically.
- No vendor or affiliate links unless the article discloses them. No links to "write for us" or guest-post sites.
- Post URLs sit at the root: link `/slug/`, not `/blog/slug/`.

## 7. Images

| Image | Rule |
|---|---|
| Featured image | One photo, 1200 × 675, WebP, under 100 KB, SEMRS logo badge, file name `{slug}-featured.webp`. Own photos or CC0 / commercial-use license; record the credit in the media description. |
| Data graphic | One per article, in the body, drawn in SEMRS colors (blue #0b2fd1, orange #f7941d) with `content-src/blog/graphics.mjs`. Use only cited figures or the article's own steps. Label examples as examples. |
| Alt text | Describe what the image actually shows; include the topic naturally, never stuff keywords. |
| Caption | One sentence that adds meaning (what the reader should take from it). |
| No GIFs | Heavy files hurt Core Web Vitals. Use a still graphic instead. |

Build images with the scratchpad script `blog-images.mjs` (photos are badged and cropped, graphics rendered to WebP).

## 8. Technical SEO checklist

- 900–1,500+ words (aim for 1,200–1,800 in new articles); depth over length.
- One H1 (the title), logical H2 → H3 order, short slug.
- SEO title 30–60 characters, meta description 120–155 characters.
- Featured image set; every in-body image has alt text and a caption.
- FAQ section present (FAQPage schema is added by SEMRS automatically).
- Article, BreadcrumbList and Organization schema come from SEMRS; nothing to add by hand.
- Run `node content-src/blog/build-blog.mjs` from the `semrs-site` folder: every article must print `ok`.
- After publishing, the SEMRS SEO score must be 98 or higher.

## 9. Local SEO requirements

- Use real US, UK, Canadian or Australian cities and services in examples ("emergency plumber in Denver").
- For local-service topics, link the [Google Business Profile guide](/google-business-profile-optimization/) and mention reviews, categories and service areas.
- Mention local rules where they matter (state bar rules, state insurance departments, FINRA/SEC, ADA).
- Keep the SEMRS name, email and website identical everywhere (NAP consistency).

## 10. Categories (WordPress IDs)

| ID | Category |
|---|---|
| 1 | Digital Marketing |
| 24 | Web Design & WordPress |
| 25 | Industry Marketing |
| 26 | Local SEO |
| 27 | PPC & Paid Ads |
| 292 | Marketing Costs & ROI |
| 293 | SEO & AI Search |
| 294 | Content & Social Media |
| 296 | AI Marketing |
| 297 | Business Finance |

## 11. Google and AdSense content rules

- Helpful, original, first-hand content; no thin, copied or mass-produced pages.
- Finance, legal, health and insurance (YMYL) articles need a qualified reviewer and a disclaimer.
- No ads on contact, quote or thank-you pages (SEMRS Ads enforces this).
- Business finance articles (loans, credit cards, insurance) describe options and trade-offs; they never give personal financial advice and must disclose any affiliate relationship.

## 12. Publishing workflow

1. Pick the next article in the 3-Month Plan sheet; copy the article template sheet into `content-src/blog/articles/NN-slug.md`.
2. Write it to this guide; add the graphic to `graphics.mjs` and the photo to `blog-images.mjs`.
3. Build and check (`build-blog.mjs` prints `ok`).
4. Upload to staging, set the featured image, check the SEMRS score (98+), then a person approves it before it goes live.
5. Mark it "Written" in the plan.
