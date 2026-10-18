# SEMRS Assets — Free + Pro (theme 1.1.0 · Core 1.1.0 · Tools 1.2.0 · Pro 1.1.0)

| Package | Tier | File | Install at |
| --- | --- | --- | --- |
| **SEMRS** theme | Free | `releases/semrs-1.1.0.zip` | Appearance → Themes → Add New → Upload |
| **SEMRS Child SEO** | Free | `releases/semrs-child-seo-1.1.0.zip` | Appearance → Themes → Upload → **Activate this one** (or activate SEMRS directly) |
| **SEMRS Core** (SEO essentials) | Free | `releases/semrs-core-1.1.0.zip` | Plugins → Add New → Upload |
| **SEMRS Tools** (12 tools + 22 pages) | Free | `releases/semrs-tools-1.2.0.zip` | Plugins → Add New → Upload |
| **SEMRS Pro** (premium add-on) | **Premium** | `releases/semrs-pro-1.1.0.zip` | Plugins → Add New → Upload (needs SEMRS Core) |

Zips live in `releases/` (self-hosted, with automatic update notices from semrs.com), `wporg/` (WordPress.org builds) and `server/` (the SEMRS Update Server, for semrs.com only). Serve the free packages, sell SEMRS Pro. How to publish updates: see `RELEASING.md`.

## Fresh install
1. Upload **semrs.zip**, then upload and activate **semrs-child-seo.zip** (or just activate SEMRS).
2. Activate **SEMRS Core**, then (optional) **SEMRS Pro** and **SEMRS Tools**. Settings → Permalinks → Save.
3. Appearance → Customize → **Site Identity**: upload your logo (512 × 512 px recommended).
4. Appearance → Customize → **SEMRS Theme**:
   - **Logo & header:** logo width desktop/mobile, header height, header button (Pro: sticky/transparent header).
   - **Social profiles:** paste your profile URLs; choose brand colours or one colour, and the icon size.
   - Colours, typography, layout, blog, footer.
5. Appearance → Menus: assign Primary and Footer. Pro mega menu: give a top item the CSS class `semrs-mega`.
6. SEMRS → SEO: business details and indexing. Pro: SEMRS → Locations, Redirects, Ads, IndexNow, License.
7. Block inserter → Patterns → **SEMRS sections** for ready-made page sections.

## Moving semrs.com from the earlier "SEMRS Pro" theme build
The theme was renamed (folder `semrs-pro` → `semrs`) so it can be free, with the premium features in the SEMRS Pro plugin. Theme settings were renamed too, so re-enter colours, header button and footer text in the Customizer after switching. Pages, posts, menus, widgets and SEO data are not affected.

## Switching from Astra + Rank Math + Ad Inserter
Follow the 10 staging steps from the previous INSTALL guide, with SEMRS Pro activated for redirects (import from Rank Math) and ads. Keep Rank Math active until you have checked your pages: SEMRS SEO pauses itself and reads Rank Math's data in the meantime.
