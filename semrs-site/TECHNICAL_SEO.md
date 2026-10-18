# Technical SEO, Local SEO & AdSense Rules (sitewide)

Applies to every page Claude Code builds. Each page's `seo.md` confirms these items before you paste it.

## 1. Tier 1 targeting

| Item | Setting |
| --- | --- |
| Target countries | US (primary), UK, Canada, Australia, New Zealand, Ireland |
| Language | `en-US` in `<html lang>`. One English site, so **no hreflang** (it's for alternate-language versions only) |
| Spelling | US English (D10) |
| Currency | USD from-prices; one line "Prices in USD; we invoice in GBP, CAD, AUD on request" (only if true) |
| Examples in copy | Tier 1 businesses, places and platforms (Google, Yelp, Bing Places, Yell, Yellow Pages AU/CA) |
| Hosting/CDN | Hostinger with CDN on, so US/UK/AU visitors get fast responses. Check TTFB from US/UK/AU in PageSpeed Insights |
| Search Console | Track Tier 1 country filters in Performance reports; build a "Tier 1 clicks" saved comparison |

## 2. Technical SEO checklist (every page)

**Indexing**
- [ ] One canonical URL (Rank Math self-referencing canonical), trailing slash consistent
- [ ] `index,follow` except Thank You, Feedback, search results, tag archives, author archives of non-authors, attachment pages (Rank Math: redirect attachments to parent)
- [ ] In the XML sitemap (Rank Math) if indexable; tools in their own `tools-sitemap` group
- [ ] `robots.txt` allows all public paths, blocks `/wp-admin/` (except admin-ajax), `/?s=`, and lists the sitemap
- [ ] Old URLs changed only through the 301 list (Rank Math Redirections)

**On-page**
- [ ] Title ≤ 60 chars, keyword near the start, brand at the end
- [ ] Meta description ≤ 155 chars, includes benefit + CTA
- [ ] Exactly one H1; logical H2/H3 order; no skipped levels
- [ ] Short, keyword slug; no dates/stop words
- [ ] First 100 words state what the page does
- [ ] Every image: descriptive file name, alt text, width/height set, WebP (Imagify), lazy-load below the fold, hero image `fetchpriority="high"` and NOT lazy
- [ ] Internal links per `PAGE_INVENTORY_SEO.md` §D; descriptive anchors (never "click here")
- [ ] External links to authoritative sources open normally; sponsored/affiliate use `rel="sponsored"`, user content `rel="ugc"`
- [ ] Open Graph + X card image (1200×630) per page
- [ ] Author/reviewer line + "Last updated" on tools, guides, posts (E-E-A-T)

**Structured data**
- [ ] Only describe what is visible on the page
- [ ] Sitewide: Organization (name, logo, url, email, sameAs social profiles, areaServed), WebSite
- [ ] BreadcrumbList on every page except Home
- [ ] Page-type schema per `PAGE_INVENTORY_SEO.md`
- [ ] Validate in Google Rich Results Test + Schema.org validator before Update

**Performance (Core Web Vitals, measured on mobile)**
| Metric | Target |
| --- | --- |
| LCP | ≤ 2.5 s |
| INP | ≤ 200 ms |
| CLS | ≤ 0.1 (ad slots must reserve height) |
| Lighthouse mobile | Service/core pages ≥ 90 · Tool pages with ads ≥ 80 |
| Page weight | ≤ 1 MB on first load excluding ads |

- [ ] Inter self-hosted, `font-display: swap`, preload one weight
- [ ] Tool JS only on its own page, `defer`, no jQuery dependency
- [ ] WP Rocket: page cache, delay JS (exclude the tool script on its own page), remove unused CSS tested on staging
- [ ] No sliders, no autoplay video, no icon fonts (inline SVG)

**Security & hygiene**
- [ ] HTTPS everywhere, HSTS via host
- [ ] Server tools: rate limit, SSRF guard, nonce, timeouts
- [ ] Custom 404 page with search + links to Tools and Services
- [ ] Contact Form 7 with honeypot/Turnstile (free) to stop spam

## 3. Local SEO

### For SEMRS itself
- **Google Business Profile** at SEMRS's real registered address (D9). If SEMRS has no public office, use a service-area profile with the address hidden. Never use a virtual/fake Tier 1 address; Google suspends those.
- **NAP** (Name, Address, Phone) identical on footer, Contact, About, GBP, Bing Places, LinkedIn, Facebook, Clutch, GoodFirms, DesignRush.
- **Organization schema** with `address` (real), `areaServed`: ["US","GB","CA","AU","NZ","IE"].
- **Reviews**: after every finished project, send the Google review link (built with our own Review Link Generator tool). Never buy or gate reviews.
- **Tier 1 trust without offices**: client time-zone hours (e.g. "Available 8am–6pm US Eastern"), USD pricing, US-English copy, Tier 1 client examples once real.

### For clients (what our pages and tools teach/sell)
- Local tools category (see `TOOLS_PLAN.md` §5) brings in Tier 1 local business owners searching for exactly these fixes.
- The Local SEO service page (Phase 2) links every local tool. Local tools link back to it.
- Country/city landing pages only with real local work (see Phase 3 rules). Mass-produced "SEO in [City]" pages are doorway pages.

## 4. AdSense rules (protect approval + maximise Tier 1 RPM)

**Where ads go**
| Page type | Ads | Placement |
| --- | --- | --- |
| Tool pages, category hubs, `/tools/` | ✓ | Below tool result · in-guide after 2nd H2 · end of FAQ · desktop sticky sidebar 300×600 (≥ 1200 px wide only) · mobile anchor ad optional |
| Blog posts, Resources, Write For Us | ✓ | After intro · mid-content · end |
| Home, services, pricing, quote, contact, about, legal, thank-you | ✗ | SEMRS Ads excluded paths (default list) |

**Policy rules (every tool page)**
- Never place an ad between a tool's input and its button, or directly next to "Copy"/"Download" buttons. Keep ≥ 150 px of space so clicks can't be accidental.
- Label ad areas "Advertisement".
- Every ad page has substantial original content (the guide). No ads on empty or thin pages.
- Reserve ad slot height in CSS (`min-height`) to keep CLS ≤ 0.1.
- Lazy-load ads below the fold.
- Max 3 in-content ads + 1 sidebar + 1 anchor per page.

**Consent (legally required)**
- UK, Ireland and EU visitors: Google requires a Google-certified CMP. Turn on the free **AdSense → Privacy & messaging** GDPR message the day the new Privacy Policy goes live.
- US state privacy laws (e.g. California): enable the US states message in the same panel.
- `ads.txt` stays at `semrs.com/ads.txt`. Check it in AdSense after every push.

**RPM levers**
- Tier 1 traffic share (tools + US-English content aimed at US/UK/CA/AU queries)
- Time on page (tools + guides): more page views per session through related-tools links
- Speed: faster pages → more viewable impressions
- Commercial-intent tool topics (ads, local business, website cost) attract higher-paying advertisers

## 5. Measurement

- GA4 events: `tool_used` (tool id), `tool_copy`, `tool_email_report`, `cta_click` (destination), `generate_lead` (form submit → Thank You)
- Search Console: weekly check of Tier 1 clicks, Core Web Vitals report, indexing of new tool pages
- AdSense: page-level RPM by URL channel `/tools/`; compare tool categories monthly and build more of what earns
- Monthly report uses real data only (brand rule)
