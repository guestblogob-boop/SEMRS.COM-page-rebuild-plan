# semrs.com Rebuild — Master Plan

Last updated: 2026-10-11 · Source plan: `SEMRS.COM PAGE BUILD PLAN.md`

**Strategy in one line:** free SEO tools bring Tier 1 traffic (AdSense revenue + trust) → tool pages point to SEMRS services → visitors hire SEMRS.

## Plan documents

| File | What it covers |
| --- | --- |
| `MASTER_PLAN.md` | This file: strategy, theme decision, build order, status |
| `BRAND.md` | Positioning, tone, banned claims |
| `DECISIONS.md` | Approved and open decisions |
| `TOOLS_PLAN.md` | 45 free tools in 9 categories, tool page template |
| `PAGE_INVENTORY_SEO.md` | Every page: URL, keyword, title, schema, ads, index |
| `TECHNICAL_SEO.md` | Sitewide technical SEO, Tier 1 targeting, local SEO, AdSense rules, measurement |
| `RESPONSIVE_SPEC.md` | Mobile/tablet/desktop layouts and test matrix |

## 1. Where the site is today (checked 2026-10-11)

| Item | Current state | Problem |
| --- | --- | --- |
| Positioning | "Grow Your Business with SEMRS" | Generic |
| Services | Content Writing, Guest Blogging, Ranking Solutions | No web design, ads, content management, lead gen pages |
| Pricing | None | Biggest missed differentiator |
| Free tools | None | No reason for repeat visits or links; nothing for AdSense to scale on |
| Testimonials | 3 unverified | Removing (D6) |
| Contact | guestblogob@gmail.com | Replace with admin@semrs.com (D5) |

## 2. Competitors and how we win

| Group | Examples | Their strength | Our angle |
| --- | --- | --- | --- |
| SEO tool companies | Semrush, Ahrefs, Moz, Ubersuggest | Huge free-tool traffic | Our tools need no signup, and a real team can fix what the tool finds |
| Large agencies | WebFX, Thrive, Ignite Visibility, SmartSites | Brand, case studies | Public prices, no "book a call" wall, faster site |
| Small agencies/freelancers | Thousands | Cheap | Tools, transparency, process, proof standards |

Winning angles: public from-prices · free no-signup tools · visible human approval gate · honest AI transparency · speed as proof.

## 3. Theme decision (approved, updated 2026-10-11)

**Own stack, no Astra, no Rank Math, no Ad Inserter:**
- `semrs-pro`: standalone lightweight main theme (≈5.5 KB CSS+JS gzipped), SEO-first, AdSense-ready, its own hook system.
- `semrs-child-seo`: SEMRS brand and agency components.
- `semrs-core` plugin: SEO (titles, schema, sitemap, redirects, 404 log), Ads, Site Layouts, Proof, Lead tracking. Reads existing Rank Math data so the switch loses nothing.
- `semrs-tools` plugin (next): the 45 free tools.
- Packaged in `semrs-site/dist/SEMRS-Assets-v1.0.zip`; migration steps in `dist/INSTALL.md`.

## 4. Build order (one item per session)

Status: ☐ not started · ◐ in progress · ☑ approved on test.semrs.com

### Foundation
- ☑ 1. Positioning (D1)
- ◐ 2. `BRAND.md` (draft, matches D1)
- ◐ 3. Child theme + pattern sheet (incl. tool UI + ad-slot patterns), previewed in WordPress Playground at all 6 test sizes
- ☐ 4. Header/footer spec + Contact Form 7 code
- ☐ 5. `semrs-tools` plugin framework (loader, shared UI, rate limiter, GA4 events) + first tool: SERP Snippet Preview

### Launch set (Phase 1)
- ☐ 6. Home `/`
- ☐ 7. `/tools/` hub + 9 category hubs
- ☐ 8. Wave 1 tools (12, one per session; see `TOOLS_PLAN.md`)
- ☐ 9. Services hub + SEO Services → Technical SEO → SEO Content Writing → SEO Audit → GEO/AI Search → Web Design → Content Management
- ☐ 10. Pricing → How We Work → Get a Quote → Thank You
- ☐ 11. About → Contact
- ☐ 12. Privacy → Terms → Cookie Policy + AdSense GDPR/US-states consent messages
- ☐ 13. Write For Us → Guest Post Submission + Registration Form 301
- ☐ 14. Launch check on test.semrs.com (technical SEO checklist, responsive matrix, Lighthouse) → backup → push live → submit sitemap → inspect Home, `/tools/`, 5 service pages

### Phase 2 (2–3 items per week)
- ☐ 15. Wave 2 tools (19)
- ☐ 16. Local SEO service page + Digital Marketing hub, Social, Paid Ads, Lead Gen, Email & WhatsApp, Website Care
- ☐ 17. FAQ, Editorial Policy, How We Use AI, Disclosure, Founder page, Resources
- ☐ 18. Blog refresh (25 strongest posts, each linking 1 tool + 1 service)

### Phase 3
- ☐ 19. Wave 3 tools (14)
- ☐ 20. Case studies, Ecommerce SEO, industry pages: only when the proof is real

## 5. Workflow per page

Claude Code builds `semrs-site/pages/<slug>/` (page.html, seo.md, schema.json, images.md, links.md, previews at mobile/tablet/desktop) → you review → paste on **test.semrs.com** → you approve → next. Claude Code never logs into or publishes on either site.

## 6. Inputs still needed

| Item | Needed for |
| --- | --- |
| S-monogram logo (SVG preferred) + exact navy/gold hex if you have them | Step 3 |
| `SEMRS_UX_AND_DESIGN_SPEC.md`, `SEMRS_PAGE_WIREFRAMES.md`, `SEMRS_AGENCY_STRATEGY.md` | Steps 3, 6+ |
| `data/pricingCatalog.ts` | From-prices (step 9–10) |
| `wordpress/semrs-lead-bridge.php` | CF7 field names (step 4) |
| Business location + real address (D9) | Organization schema, GBP |
| Founder name + photo | About, author pages |
