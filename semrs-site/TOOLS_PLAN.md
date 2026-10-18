# Free SEO Tools Hub — Plan

Goal: free, no-signup tools that business owners in Tier 1 countries (US, UK, Canada, Australia, New Zealand, Ireland) actually use and come back to. Each visit earns AdSense on the tool page and steers the visitor toward the matching SEMRS service.

## How the tools are built

- One free WordPress plugin, `semrs-tools`, built by Claude Code. Each tool is a shortcode/block, e.g. `[semrs_tool id="serp-preview"]`, placed inside a normal page you paste like any other.
- **Browser tools** (most of them): plain JavaScript and no server calls, so they cost nothing to run and cope with any traffic spike. The JS for a tool loads only on that tool's page, deferred, under 30 KB.
- **Server tools** (marked ⚙): fetch a URL through a plugin REST endpoint, with a rate limit (e.g. 20 checks per IP per hour), a 10-second timeout, blocks on private/internal IPs (SSRF protection), and a 24h cache.
- No login, no email wall to use a tool. An *optional* "Email me this report" box is the lead capture.
- Each tool page = tool on top, plus 800–1,500 words of genuinely useful guide below it (how to use it, worked example, common mistakes, FAQ). That text is what makes the page rank and keeps it AdSense-compliant: Google rejects ads on "low-value" pages that are only a widget.

## URL structure

- Hub: `/tools/`
- Category hubs: `/tools/<category>/` (e.g. `/tools/technical-seo/`)
- Tool pages: `/tools/<tool-slug>/`. Tool URLs stay flat, so moving a tool to another category never changes its URL; the breadcrumb shows the category.

## Categories and tools

Priority: **W1** = launch wave (build first), **W2** = weeks 3–6, **W3** = weeks 7–12.

### 1. Meta Tags & SERP — `/tools/meta-serp/` → leads to SEO Services
| Tool | Slug | Type | Wave |
| --- | --- | --- | --- |
| SERP Snippet Preview (desktop + mobile, pixel-width) | `serp-snippet-preview` | Browser | W1 |
| Meta Title & Description Checker | `meta-description-checker` | Browser | W1 |
| Meta Tag Generator | `meta-tag-generator` | Browser | W2 |
| Open Graph & X Card Generator + preview | `open-graph-generator` | Browser | W2 |
| URL Slug Generator | `slug-generator` | Browser | W2 |
| Title Case Converter (AP/Chicago/APA) | `title-case-converter` | Browser | W2 |

### 2. Technical SEO — `/tools/technical-seo/` → Technical SEO service
| Tool | Slug | Type | Wave |
| --- | --- | --- | --- |
| Robots.txt Generator | `robots-txt-generator` | Browser | W1 |
| Redirect Checker (chains, 301/302) | `redirect-checker` | ⚙ Server | W1 |
| HTTP Header Checker | `http-header-checker` | ⚙ Server | W2 |
| Hreflang Tag Generator | `hreflang-generator` | Browser | W2 |
| .htaccess Redirect Generator | `htaccess-redirect-generator` | Browser | W2 |
| Canonical Tag Generator | `canonical-tag-generator` | Browser | W3 |
| XML Sitemap Validator (paste or URL) | `sitemap-validator` | ⚙ Server | W3 |
| Page Speed / Core Web Vitals Checker (Google PageSpeed Insights API, free key) | `page-speed-checker` | ⚙ Server | W3 |

### 3. Schema Markup — `/tools/schema/` → Technical SEO
| Tool | Slug | Type | Wave |
| --- | --- | --- | --- |
| Schema Markup Generator (Organization, Article, FAQ, Breadcrumb, Product, Event, Service) | `schema-markup-generator` | Browser | W1 |
| Local Business Schema Generator | `local-business-schema-generator` | Browser | W1 |
| FAQ Schema Generator | `faq-schema-generator` | Browser | W2 |
| Product Schema Generator | `product-schema-generator` | Browser | W3 |

### 4. Content & Keywords — `/tools/content-keywords/` → SEO Content Writing
| Tool | Slug | Type | Wave |
| --- | --- | --- | --- |
| Word Counter (words, characters, reading time) | `word-counter` | Browser | W1 |
| Readability Checker (Flesch, grade level, long sentences) | `readability-checker` | Browser | W2 |
| Keyword Density Checker | `keyword-density-checker` | Browser | W2 |
| Keyword Mixer / Combiner | `keyword-mixer` | Browser | W2 |
| Keyword List Cleaner (dedupe, lowercase, sort) | `keyword-list-cleaner` | Browser | W3 |
| Heading Structure Checker (paste HTML or URL) | `heading-checker` | Browser/⚙ | W3 |

### 5. Local SEO — `/tools/local-seo/` → Local SEO (Phase 3 service) / SEO Services
Aimed squarely at Tier 1 local businesses (plumbers, dentists, lawyers, trades).
| Tool | Slug | Type | Wave |
| --- | --- | --- | --- |
| Google Review Link Generator (from Place ID) | `google-review-link-generator` | Browser | W1 |
| Local Keyword Generator (service × city/suburb) | `local-keyword-generator` | Browser | W2 |
| NAP Consistency Checker (paste listings, highlights mismatches) | `nap-consistency-checker` | Browser | W2 |
| Google Business Profile Post Preview + character counter | `gbp-post-preview` | Browser | W2 |
| Local Citation Checklist by country (US, UK, CA, AU, NZ, IE) | `local-citation-checklist` | Browser | W3 |

### 6. AI Search / GEO — `/tools/ai-search/` → GEO / AI Search Optimisation
| Tool | Slug | Type | Wave |
| --- | --- | --- | --- |
| llms.txt Generator | `llms-txt-generator` | Browser | W1 |
| AI Crawler Robots.txt Rules (GPTBot, ClaudeBot, PerplexityBot, Google-Extended…) | `ai-crawler-robots-generator` | Browser | W2 |
| Answer-Block Formatter (turns copy into concise Q&A for AI answers) | `answer-block-formatter` | Browser | W3 |

### 7. Ads & Marketing — `/tools/marketing/` → Paid Ads / Email & WhatsApp
| Tool | Slug | Type | Wave |
| --- | --- | --- | --- |
| UTM Builder (+ bulk mode) | `utm-builder` | Browser | W1 |
| ROAS / Break-even ROAS Calculator | `roas-calculator` | Browser | W2 |
| Google Ads Preview + character counter | `google-ads-preview` | Browser | W2 |
| CPC / CPM / CPA Calculator | `cpc-cpm-calculator` | Browser | W2 |
| Email Subject Line Checker (length, spam words) | `email-subject-line-checker` | Browser | W3 |

### 8. Website & Design — `/tools/website/` → Web Design / Website Care
| Tool | Slug | Type | Wave |
| --- | --- | --- | --- |
| Image Compressor & WebP Converter (in-browser, files never uploaded) | `image-compressor` | Browser | W2 |
| Colour Contrast Checker (WCAG AA/AAA) | `color-contrast-checker` | Browser | W2 |
| Favicon Generator | `favicon-generator` | Browser | W3 |

### 9. Business Calculators — `/tools/calculators/` → Pricing / Get a Quote
| Tool | Slug | Type | Wave |
| --- | --- | --- | --- |
| SEO ROI Calculator | `seo-roi-calculator` | Browser | W2 |
| Lead Value / Conversion Rate Calculator | `conversion-rate-calculator` | Browser | W2 |
| Website & SEO Cost Estimator (uses SEMRS catalog from-prices) | `seo-cost-estimator` | Browser | W3 |

**Total:** 45 tools in 9 categories. **W1 = 12 tools** across all 9 categories, so every category hub launches with at least one live tool.

## Tool page template (every tool page)

1. Breadcrumb: Home › Tools › Category › Tool
2. H1 = tool name + benefit ("Free SERP Snippet Preview Tool — See Your Google Result")
3. One-line intro (≤ 25 words)
4. **The tool** (input → instant result). No ad between the input and its button.
5. Result actions: copy, download, "Email me this" (optional lead)
6. Soft CTA box: "Want us to fix this for you? SEO Services from $X/month →"
7. *Ad slot 1* (below the tool, clearly separated, reserved height)
8. Guide: How to use · Worked example · Best practices · Common mistakes
9. *Ad slot 2* (in-content, after 2nd H2)
10. FAQ (5–8 questions, `<details>` blocks)
11. Related tools (3–6 from same/other categories)
12. Author/reviewer line + "Last updated" date
13. Footer CTA band: Free SEO Review

## Why visitors return (and share)

- Results stay in the browser (localStorage) so a returning user sees their last input.
- "Copy as code" for every generator output.
- Bulk modes (UTM, keyword mixer, redirects) are what agencies bookmark.
- Embed/attribution: "Link to this tool" snippet on each page → natural backlinks.
