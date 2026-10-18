# Listing SEMRS on WordPress.org

What is ready, what to do yourself, and the honest open points before you submit.

## Which products go to WordPress.org

| Product | List on WordPress.org? | Zip to upload | Notes |
|---|---|---|---|
| SEMRS (theme) | Yes, submit first | `dist/wporg/semrs.zip` | Theme review queue; one theme per account in the queue at a time |
| SEMRS Child SEO | Later (optional) | `dist/wporg/semrs-child-seo.zip` | Only after the parent theme is live |
| SEMRS Core | Yes | `dist/wporg/semrs-core.zip` | Plugin review |
| SEMRS Blocks | Yes | `dist/wporg/semrs-blocks.zip` | Plugin review |
| SEMRS Tools | Yes | `dist/wporg/semrs-tools.zip` | Plugin review |
| SEMRS Pro | **No** (paid) | `dist/releases/semrs-pro-x.y.z.zip` | Sold from semrs.com; updates from your update server |
| SEMRS Site | **No** (semrs.com only) | – | Keyword Ideas Generator, photo hero, AI photo |
| SEMRS Update Server | **No** | `dist/server/…` | Runs only on semrs.com |

`node release.mjs` builds the `dist/wporg/` zips without the semrs.com updater and without the `Update URI` header, as WordPress.org requires.

## Already done in the code

- **GPL:** every file is GPLv2-or-later; artwork is original (theme readme → Resources section).
- **No paid code in free zips:** Pro code lives only in SEMRS Pro. Free plugins show what Pro adds with a PRO label and a link (allowed upsell), and the inserter teasers can be switched off in SEMRS → Blocks.
- **No calls home in WordPress.org builds:** the updater is removed by `release.mjs`. The only external script (Google model-viewer) is in SEMRS Pro.
- **Theme is design only:** emoji/head/jQuery Migrate clean-up moved from the theme to SEMRS Core → Speed (theme 1.2.0). SEO, ads, forms, leads and custom post types are in plugins.
- **Prefixes:** `semrs_theme_`, `semrs_core_`/`semrs_seo_`/`semrs_ads_`/`semrs_speed_`, `semrs_blocks_`, `semrs_tools_`.
- **Security:** nonces and capability checks on every form, sanitized input, escaped output; REST contact route validates and rate-limits.
- **Readmes:** WordPress.org format with Description, FAQ, Changelog; theme readme has Copyright and Resources.
- **Screenshots:** real 1200 × 900 `screenshot.png` in both themes (no ad-like text).
- **Plugin icons and banners:** `plugins/semrs-update-server/assets/art/<slug>/` → `icon-128x128.png`, `icon-256x256.png`, `banner-772x250.png`, `banner-1544x500.png`.
- **User manual:** 8 pages in `pages/support/` (install on semrs.com from SEMRS Updates → Support pages).

## Steps for you

1. Create (or use) your WordPress.org account. Use the same account for all SEMRS submissions.
2. Run `node release.mjs` to build fresh zips.
3. Install the **Theme Check** and **Plugin Check** plugins on a staging site, activate each SEMRS product and fix anything they report as an error.
4. Theme: upload `dist/wporg/semrs.zip` at wordpress.org/themes/upload/. Answer reviewer comments in the Trac ticket within 7 days.
5. Plugins: submit each zip at wordpress.org/plugins/developers/add/. Check that the slugs `semrs-core`, `semrs-blocks` and `semrs-tools` are free; if one is taken, the reviewer will offer another slug, and the text domain must then be changed to match.
6. After approval, commit the code to SVN `trunk/`, tag it under `tags/x.y.z/`, and copy the icon and banner files into SVN `assets/`.

## Open points to confirm (honest list)

- **Translation templates:** `.pot` files are not generated yet. Run `wp i18n make-pot` for each product before submitting (reviewers recommend it; it is not a rejection reason).
- **Plugin Check / Theme Check have not been run yet** in this project; expect a few warnings to fix.
- **Social network marks** in the theme are simplified drawings used only for profile links; a reviewer may ask about trademark use. If so, switch to plain text labels or the core Social Icons block.
- **Pro teasers:** allowed when limited and clearly labelled. If a reviewer asks, set the inserter teasers off by default (one line: the `semrs_blocks_show_pro` default) and keep only the SEMRS → Blocks cards.
- **Child theme:** WordPress.org accepts child themes only of themes already in the directory, and only when they add meaningful features; submit it after the parent is live.
- **Contact form email:** depends on the host's mail; the manual tells users to add SMTP if needed.
