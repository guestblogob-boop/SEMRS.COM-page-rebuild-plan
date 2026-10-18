# Premium Features Map (all free, built in-house)

Astra Pro features plus the agency features SEMRS needs, built in our own code. Astra Pro code is not copied; each feature is written from scratch.

**Where each feature lives**
- **Child theme** `semrs-astra-child`: how things look and behave (header, menus, blog layout, conversion widgets, performance).
- **Companion plugin** `semrs-core`: anything that stores content (custom layouts, case studies, portfolio, testimonials) plus lead tracking. Content survives a theme change.
- **Plugin** `semrs-tools`: the 45 free tools (step 5).
- **Existing free plugins** stay where they're best: Rank Math (SEO, schema, breadcrumbs data, redirects, sitemap), WP Rocket (cache), CF7 + Flamingo (forms), Imagify (WebP). We don't rebuild those. Ads now run on our own `semrs-ads` plugin (replaces Ad Inserter).

## Astra Pro features → our version

| Astra Pro feature | Our version | Where | Status |
| --- | --- | --- | --- |
| Sticky header (+ shrink on scroll) | Customizer toggle; header shrinks after 80px | Child theme | Built v0.2 |
| Transparent header | Customizer toggle for the front page | Child theme | Built v0.2 |
| Mega menu | Add CSS class `semrs-mega` to a top menu item → multi-column dropdown; columns = its sub-items; mobile falls back to accordion | Child theme | Built v0.2 |
| Custom Layouts / Hooks | "Site Layouts" post type: write any blocks, choose an Astra hook + display rules (everywhere, front, posts, pages, `/tools/`, `/services/`, IDs), exclusions, priority | semrs-core | Built v0.2 |
| Blog layouts (grid) | 3/2/1-column card grid on archives | Child theme | Built v0.2 |
| Scroll to top | Button after 600px scroll | Child theme | Built v0.2 |
| Breadcrumbs | Rank Math breadcrumbs output above content on all pages except Home | Child theme | Built v0.2 |
| Site layouts (boxed/padded/full) | Section classes `semrs-section`, full-width groups + Astra free container settings | Child theme | Built v0.1 |
| Typography & colour presets | Design tokens + `theme.json` palette/sizes | Child theme | Built v0.1 |
| Footer widgets / builder | Astra free Footer Builder (4 columns) + "Site Layouts" for anything extra | Astra free + semrs-core | Spec in step 4 |
| Page headers / title bars | Hero pattern + archive header styling | Child theme | Built v0.1 |
| Infinite scroll | Skipped: it hurts SEO crawling and AdSense viewability; we use numbered pagination | — | Not planned |
| WooCommerce / LearnDash / LifterLMS modules | Not needed (no shop or courses) | — | Not planned |
| White label | Not needed | — | Not planned |

## Agency features

| Feature | Purpose | Where | Status |
| --- | --- | --- | --- |
| Customizer "SEMRS Settings" panel | Phone, WhatsApp, CTA text/link, feature toggles, footer disclaimer | Child theme | Built v0.2 |
| Sticky mobile CTA bar (Call · WhatsApp · Free review) | Mobile conversions; hidden on `/tools/` pages so it never clashes with the mobile anchor ad | Child theme | Built v0.2 |
| WhatsApp chat button (desktop) | Quick enquiries | Child theme | Built v0.2 |
| Reading progress bar | Engagement on posts | Child theme | Built v0.2 |
| Auto table of contents | Posts with 3+ H2/H3 headings (skipped if a Rank Math TOC block exists) | Child theme | Built v0.2 |
| Author box + "Updated on" + reviewer | E-E-A-T on posts | Child theme | Built v0.2 |
| Related posts (same category) | More page views per session = more AdSense | Child theme | Built v0.2 |
| Case Studies post type `/case-studies/` | Proof, with a **permission gate**: can't be published unless "written client permission on file" is ticked | semrs-core | Built v0.2 |
| Portfolio post type `/portfolio/` | Web design work, same permission gate | semrs-core | Built v0.2 |
| Testimonials post type | Shown via `[semrs_testimonials]` **only** when permission is ticked and a source link (e.g. Google review) exists | semrs-core | Built v0.2 |
| Lead tracking | CF7 success → GA4 `generate_lead` event → redirect to `/thank-you/` | semrs-core | Built v0.2 |
| Performance clean-up | Removes emoji scripts, oEmbed discovery, RSD/WLW links, jQuery Migrate on the front end | Child theme | Built v0.2 |
| Free tools framework | 45 tools | semrs-tools | Step 5 |
| Client portal / reports | Lives in SEMRS OS (os.semrs.com), linked from the header for clients | External | Link only |
