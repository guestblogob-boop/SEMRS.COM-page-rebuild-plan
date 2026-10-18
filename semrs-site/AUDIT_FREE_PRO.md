# SEMRS Theme & Plugins: Audit and Free/Pro Split

Date: 2026-10-11 · Compared against: Astra (free + Pro), GeneratePress (free + Premium), Kadence, Neve, Blocksy; Rank Math, Yoast SEO, All in One SEO; Ad Inserter.

Feature lists for the other products are from their public documentation as generally known. Re-check current versions before using any comparison in marketing.

## 1. The new structure (same model as GeneratePress / Astra)

| Package | Tier | Sold/served as |
| --- | --- | --- |
| **SEMRS** (`semrs`) theme | Free | WordPress.org-style theme; complete on its own |
| **SEMRS Child SEO** | Free | Example/brand child theme |
| **SEMRS Core** plugin | Free | SEO essentials (WordPress.org-style) |
| **SEMRS Tools** plugin | Free | 12 SEO tools + 22 ready pages |
| **SEMRS Pro** plugin | **Premium** | Unlocks Pro modules for the theme, SEO and ads. Requires SEMRS Core |

The free theme and plugins work fully on their own. SEMRS Pro adds modules; it never "unlocks" code hidden inside the free packages. That is the model GeneratePress Premium and Astra Pro use, and the one WordPress.org allows for free listings.

## 2. Theme audit

### Fixed in this round
- **Logo space:** logo width settings for desktop (40–400 px) and mobile (32–260 px), plus header height (56–160 px). The header grows to fit the logo instead of squeezing it. Square logos (512 × 512 recommended) are detected and default to 64 px / 52 px until you change them. All three update live in the Customizer.
- **Footer social icons:** 9 networks (Facebook, X, Instagram, LinkedIn, YouTube, TikTok, Pinterest, WhatsApp, Threads). Each icon fills a round button in the network's brand colour, or one colour if you prefer, with adjustable size (28–64 px). Icons are simplified marks drawn for the theme; swap in each network's official SVG from its brand resources page if you want pixel-exact logos.
- **Components moved into the free theme:** sections, cards, process, pricing, CTA band, FAQ. Free users can now use the patterns.
- **Patterns (the "editor"):** a "SEMRS sections" category in the block inserter. Free: Hero, FAQ, CTA band. Pro: Service cards, 6-step process, Pricing, Tool section, Contact section.

### Feature comparison: theme

| Feature | Astra free | Astra Pro | GP free | GP Premium | **SEMRS free** | **SEMRS Pro** |
| --- | --- | --- | --- | --- | --- | --- |
| No jQuery, light CSS (≈5 KB gz) | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |
| Logo size controls | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |
| Colours / fonts / container width | ✔ | ✔ more | ✔ | ✔ more | ✔ | ✔ |
| Sidebar layouts + per-page override | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |
| Blog grid / list | partly | ✔ | ✔ | ✔ | ✔ | ✔ |
| Full-width + blank canvas templates | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |
| Footer widgets + social icons | ✔ | ✔ | widgets | ✔ | ✔ | ✔ |
| Block patterns | ✔ starter | ✔ | — | ✔ site library | 7 | 12 |
| Sticky / transparent header | — | ✔ | — | ✔ | ✔ (since 1.2) | ✔ |
| Mega menu | — | ✔ | — | ✔ (CSS) | — | ✔ |
| Custom hooks / layouts | — | ✔ | hooks | ✔ Elements | hooks | ✔ Site Layouts |
| Progress bar, author box, related posts | — | partly | — | partly | ✔ (since 1.2) | ✔ |
| Table of contents | — | — | — | — | — | ✔ |
| White label | — | ✔ | — | — | — | ✔ (theme name) |
| Drag-and-drop header/footer builder | ✔ | ✔ | — | — | ✔ (since 1.2) | ✔ |
| **Site library / one-click starter sites** | ✔ | ✔ | — | ✔ | **— (gap)** | **— (gap)** |
| WooCommerce styling | ✔ | ✔ | ✔ | ✔ | **— (gap)** | **— (gap)** |
| Typography per element, Google fonts picker | ✔ | ✔ | ✔ | ✔ | system/Inter only | **— (gap)** |
| Dark mode | — | — | — | — | — | — |

## 3. SEO audit (SEMRS Core free + Pro vs leading SEO plugins)

| Feature | Rank Math free | Yoast free | AIOSEO free | **SEMRS Core free** | **SEMRS Pro** |
| --- | --- | --- | --- | --- | --- |
| Titles, descriptions, templates | ✔ | ✔ | ✔ | ✔ | ✔ |
| Robots, canonical, Open Graph, X cards | ✔ | ✔ | ✔ | ✔ | ✔ |
| Schema graph (Org/WebSite/WebPage/Article/Breadcrumb) | ✔ | ✔ | ✔ | ✔ | ✔ |
| FAQ schema from visible FAQ blocks, automatically | block | block | block | ✔ (any Details block) | ✔ |
| Service / WebApplication / Local business schema | ✔ | Local = paid | ✔ partly | ✔ (types) | ✔ + multi-location |
| XML sitemap | ✔ | ✔ | ✔ | ✔ (core engine) | ✔ |
| Breadcrumbs | ✔ | ✔ | ✔ | ✔ | ✔ |
| Content checks in editor | ✔ | ✔ | ✔ | ✔ (10 checks) | ✔ |
| Reads data from another SEO plugin | import | import | import | ✔ live fallback, no import needed | ✔ |
| Redirects + 404 monitor | ✔ | paid | paid | — | ✔ + Rank Math import |
| Multi-location local SEO | paid | paid add-on | paid | — | ✔ |
| IndexNow | ✔ | ✔ | ✔ | — | ✔ |
| Image alt automation | paid | — | paid | — | ✔ |
| **Google Search Console / Analytics dashboard** | ✔ | — | ✔ | **— (gap)** | **— (gap)** |
| **News / video / image sitemaps** | paid | paid | paid | **— (gap)** | **— (gap)** |
| **Internal link suggestions** | paid | paid | paid | **— (gap)** | **— (gap)** |
| **Keyword rank tracking** | paid | — | — | **— (gap)** | **— (gap)** |
| **WooCommerce product SEO** | ✔ | paid | ✔ | **— (gap)** | **— (gap)** |
| Setup wizard | ✔ | ✔ | ✔ | — | — |

**Honest summary:** on core on-page SEO, SEMRS is already competitive with the free tiers of Rank Math, Yoast and AIOSEO. Pro includes several features those plugins charge for (redirects, multi-location local SEO, image alt automation). It is **not yet "better than Rank Math" overall**: Search Console integration, extra sitemap types, internal-link suggestions, WooCommerce SEO and a setup wizard are missing. Claim superiority only after those ship and you have tested side by side.

## 4. Ads audit (SEMRS Pro Ads Manager vs Ad Inserter)

| Feature | Ad Inserter free | Ad Inserter Pro | **SEMRS Pro Ads** |
| --- | --- | --- | --- |
| AdSense + any network code | ✔ | ✔ | ✔ |
| Auto placement in content | ✔ (many positions) | ✔ | ✔ (before, after paragraph N, middle, end) |
| Safe insertion (never inside tables, lists, forms, tools, CTAs) | manual | manual | ✔ automatic |
| Thin-page protection (minimum words) | ✔ | ✔ | ✔ |
| Default exclusions for conversion/legal/noindex pages | manual | manual | ✔ built in |
| Density cap + minimum gap | partly | ✔ | ✔ |
| Device targeting without hidden ads | CSS | ✔ | ✔ (JS before load) |
| Lazy loading | ✔ | ✔ | ✔ |
| ads.txt management | ✔ | ✔ | ✔ |
| Hide for admins + admin-bar status | ✔ | ✔ | ✔ |
| **Number of blocks / positions** | 16 | 64 | **7 (gap)** |
| **A/B testing, rotation, scheduling** | partly | ✔ | **— (gap)** |
| **Ad-block detection** | ✔ | ✔ | **— (gap)** |
| **Statistics / click tracking** | — | ✔ | **— (gap)** |
| **Geo-targeting by country** | — | ✔ | **— (gap)** |

**Summary:** SEMRS Ads is simpler and safer by default for AdSense policy. Ad Inserter Pro has more positions, testing, statistics and geo-targeting.

## 5. Roadmap to "best in class" (priority order)

| # | Item | Package | Why |
| --- | --- | --- | --- |
| 1 | Licensing + updates (Freemius or EDD Software Licensing) | Pro | Required before selling |
| 2 | Setup wizard (business details, social, sitemap, import from Rank Math/Yoast) | Core | First-run experience |
| 3 | Search Console connection (clicks, impressions, top queries per page) | Pro | Biggest gap vs Rank Math |
| 4 | Header/footer builder (rows, elements, drag and drop in the Customizer) | Pro | Biggest gap vs Astra |
| 5 | Internal link suggestions in the editor | Pro | High value, rare in free tiers |
| 6 | Ads: more positions, A/B rotation, country targeting, simple click stats | Pro | Gap vs Ad Inserter Pro |
| 7 | Google fonts picker (self-hosted download) + per-element typography | Pro | Gap vs Astra/GP |
| 8 | Starter site library (agency, local business, blog) | Pro | Sales driver |
| 9 | WooCommerce styling + product schema | Free/Pro | Shops |
| 10 | Video/news/image sitemaps | Pro | Publishers |

## 6. Before selling

- WordPress.org review for the free theme and plugins (escaping, prefixing, no "upsell nags" beyond one dismissible notice).
- A licensing provider (item 1). The License screen and `semrs_pro_license_status` filter are ready to connect.
- Real speed tests: PageSpeed Insights on a demo site with each competitor theme, same content and hosting. Publish only measured numbers.
- Trademark check on the "SEMRS" name before selling (decision D3).
- Privacy policy and refund terms for the store.
