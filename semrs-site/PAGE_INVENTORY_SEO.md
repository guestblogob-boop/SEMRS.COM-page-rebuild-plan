# Page Inventory + Per-Page SEO Plan

Every page SEMRS will have, in build order, with its SEO plan. Keywords are **candidates**: validate each in Google Keyword Planner / Search Console (US, UK, CA, AU filters) before writing the page. No search volumes are quoted here because none have been measured yet.

Legend: **Ads** = AdSense on (✓) / off (✗) · **Idx** = index (✓) / noindex (✗)
Schema in every row is *in addition to* the sitewide Organization + WebSite + BreadcrumbList.

## A. Core pages

| # | Page | URL | Primary keyword (candidate) | Intent | Title (≤60) | Schema | Ads | Idx |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | Home | `/` | SEO agency for small business | Commercial | SEO Agency for Small Business + Free SEO Tools \| SEMRS | Organization, WebSite (SearchAction) | ✗ | ✓ |
| 2 | Free SEO Tools hub | `/tools/` | free SEO tools | Informational | 45 Free SEO Tools — No Signup Needed \| SEMRS | CollectionPage, ItemList | ✓ | ✓ |
| 3–11 | 9 tool category hubs | `/tools/<category>/` | free [category] tools | Informational | Free [Category] Tools \| SEMRS | CollectionPage, ItemList | ✓ | ✓ |
| 12+ | 45 tool pages | `/tools/<tool-slug>/` | free [tool name] | Informational/transactional | Free [Tool] — [Benefit] \| SEMRS | WebApplication (offers price 0), FAQPage* | ✓ | ✓ |
| — | Services hub | `/services/` | SEO and digital marketing services | Commercial | SEO, Web Design & Content Services \| SEMRS | ItemList of Service | ✗ | ✓ |
| — | SEO Services | `/services/seo/` | monthly SEO services for small business | Commercial | Monthly SEO Services for Small Business \| SEMRS | Service + Offer (from-price) | ✗ | ✓ |
| — | Technical SEO | `/services/technical-seo/` | technical SEO services | Commercial | Technical SEO Services: Speed, Indexing, Schema \| SEMRS | Service + Offer | ✗ | ✓ |
| — | SEO Content Writing | `/services/seo-content-writing/` | SEO content writing services | Commercial | SEO Content Writing Services \| SEMRS | Service + Offer | ✗ | ✓ |
| — | SEO Audit | `/services/seo-audit/` | SEO audit service | Commercial | SEO Audit Service — Free Review or Full Audit \| SEMRS | Service + Offer | ✗ | ✓ |
| — | GEO / AI Search | `/services/ai-search-optimization/` | AI search optimization / GEO services | Commercial | AI Search Optimization (GEO) Services \| SEMRS | Service + Offer | ✗ | ✓ |
| — | Web Design | `/services/web-design/` | small business web design | Commercial | Small Business Web Design \| SEMRS | Service + Offer | ✗ | ✓ |
| — | Content Management | `/services/content-management/` | content management services | Commercial | Content Management & WordPress Publishing \| SEMRS | Service + Offer | ✗ | ✓ |
| — | Pricing | `/pricing/` | SEO pricing / SEO packages | Commercial | SEO & Web Design Pricing (From-Prices) \| SEMRS | OfferCatalog | ✗ | ✓ |
| — | How We Work | `/how-we-work/` | SEO process | Informational | Our 6-Step SEO Process with Human Approval \| SEMRS | — | ✗ | ✓ |
| — | Get a Quote / Free SEO Review | `/get-a-quote/` | free SEO review | Transactional | Get a Free SEO Review in 48 Hours \| SEMRS | — | ✗ | ✓ |
| — | Thank You | `/thank-you/` | — | — | Thanks — We'll Reply Within 1 Business Day | — | ✗ | ✗ |
| — | About | `/about/` | about SEMRS | Navigational | About SEMRS — The People Behind the Work | AboutPage, Person (founder) | ✗ | ✓ |
| — | Contact | `/contact-us/` | contact SEMRS | Navigational | Contact SEMRS | ContactPage | ✗ | ✓ |
| — | Privacy Policy | `/privacy-policy/` | — | — | Privacy Policy \| SEMRS | — | ✗ | ✓ |
| — | Terms | `/terms-and-conditions/` | — | — | Terms and Conditions \| SEMRS | — | ✗ | ✓ |
| — | Cookie Policy | `/cookie-policy/` | — | — | Cookie Policy \| SEMRS | — | ✗ | ✓ |
| — | Write For Us | `/write-for-us-guidelines/` | write for us SEO / digital marketing | Navigational | Write For Us: SEO & Marketing Guest Posts \| SEMRS | — | ✓ | ✓ |
| — | Guest Post Submission | `/guest-post-submission-form/` | — | — | Submit a Guest Post \| SEMRS | — | ✗ | ✓ |
| — | Registration Form | `/registration-form/` | — | — | 301 → `/contact-us/` | — | — | — |
| — | Feedback | `/feed-back-form/` | — | — | Leave Us a Review \| SEMRS (or 301 → contact) | — | ✗ | ✗ |

*FAQPage: Google shows FAQ rich results only for well-known government/health sites since 2023, so we add it only for content visible on the page (it still helps AI answers), never for rich-result hopes.
*WebApplication: no `aggregateRating` until real ratings exist. Fake ratings are a manual-action risk.

## B. Phase 2 pages

| Page | URL | Primary keyword (candidate) | Schema | Ads |
| --- | --- | --- | --- | --- |
| Digital Marketing hub | `/services/digital-marketing/` | digital marketing services for small business | ItemList | ✗ |
| Social Media Management | `/services/social-media-management/` | social media management services | Service + Offer | ✗ |
| Paid Ads Management | `/services/paid-ads/` | Google Ads management for small business | Service + Offer | ✗ |
| Lead Generation | `/services/lead-generation/` | lead generation services | Service + Offer | ✗ |
| Email & WhatsApp Marketing | `/services/email-whatsapp-marketing/` | email marketing services | Service + Offer | ✗ |
| Website Care Plans | `/services/website-care-plans/` | WordPress maintenance plans | Service + Offer | ✗ |
| Local SEO (moved up: local tools feed it) | `/services/local-seo/` | local SEO services | Service + Offer, areaServed US/UK/CA/AU | ✗ |
| FAQ | `/faq/` | SEO agency FAQ | FAQPage | ✗ |
| Editorial Policy | `/editorial-policy/` | — | — | ✗ |
| How We Use AI | `/how-we-use-ai/` | AI in SEO agency | — | ✗ |
| Disclosure | `/disclosure/` | — | — | ✗ |
| Founder author page | `/author/<founder>/` | [founder name] | ProfilePage, Person | ✗ |
| Resources | `/resources/` | SEO guides for small business | CollectionPage | ✓ |
| Blog posts (refresh 25) | existing URLs | per post | Article, Person author | ✓ |

## C. Phase 3 pages (only when the condition is real)

| Page | URL | Condition |
| --- | --- | --- |
| Case Studies | `/case-studies/` + `/case-studies/<client>/` | Real results + written permission |
| Ecommerce SEO | `/services/ecommerce-seo/` | Delivery capability exists |
| Editorial Outreach | `/services/guest-posting/` | Decision D2 |
| Industry pages | `/industries/<industry>/` | Real work in that industry; never mass-generated |
| Country pages | `/seo-services-usa/` etc. | Only with real local clients/proof per country; otherwise they are doorway pages that Google penalizes |

## D. Internal linking rules

- Every tool page → its category hub, 3–6 related tools, and **one** matching service page (CTA box).
- Every category hub → all its tools + the matching service page.
- Every service page → 2–3 related free tools ("Try it yourself first") + Pricing + Get a Quote.
- Home → `/tools/`, top 6 tools, 3 core services, Pricing, How We Work.
- Blog posts → 1 relevant tool + 1 relevant service, contextual anchor text.
- Header nav: Services ▾ · Free Tools ▾ · Pricing · How We Work · Blog · About · [Get a Free SEO Review]
