---
title: SEMRS Pro: Install, License Key & Updates
description: How to install SEMRS Pro, activate or move your license key, get one-click updates, and what happens when a license expires.
order: 7
---
SEMRS Pro is an add-on plugin. It needs the free SEMRS Core plugin, and its Pro blocks need the free SEMRS Blocks plugin.

## Install

1. Download the `semrs-pro.zip` file from your purchase email or account.
2. Plugins → Add New Plugin → **Upload Plugin** → choose the zip → Install Now → Activate.
3. Go to **SEMRS → License**, paste your key and click **Activate license**.

The status changes to **Active** and locked features in SEMRS → Features and SEMRS → Blocks unlock immediately.

## What Pro unlocks

| Area | Pro features |
|---|---|
| Blocks | Slider / carousel, animated counter, 3D model viewer, scroll animations and display conditions on every block |
| Marketing | Popups: centred, bottom bar or slide-in; delay, scroll, exit-intent or click triggers; frequency and page targeting |
| SEO | Redirects and 404 monitor, multi-location local SEO, IndexNow, image SEO, bulk SEO editor |
| Speed | Delay analytics and chat scripts until interaction, cache preload, CDN |
| Ads | Repeat and between-posts placements, category/post-age/date targeting, A/B rotation, click protection, ad-blocker message, per-page position switches |
| Theme | Dark mode switch, mega menu, automatic table of contents, extra patterns, white label |
| Languages | Language switcher, linked translations with hreflang tags and the right html lang, optional Google Translate for untranslated pages |
| Site | Site layouts (custom hooks), case studies and portfolio, lead tracking |

## Dark mode switch

1. Customize → SEMRS Theme → Header builder → **Dark mode switch**: choose "Switch, starts light" or "Switch, starts with the visitor's device setting".
2. In the same section, add the **Dark / light switch** element to the top bar or the main header.
3. Publish. Visitors' choice is remembered in their own browser. The header, top bar and footer stay navy in both modes, and there is no white flash when a page loads.

## Languages and translations

1. **SEMRS → Languages**: list your languages, one per line, for example "en | English", "fr | Français", "ur | اردو". The first line is your main language.
2. Add the **Language switcher** element in the Header builder (or use the [semrs_languages] shortcode anywhere). On phones the top bar is hidden unless you switch on "Show the top bar on phones", so put the switcher in the main header if phone visitors need it.
3. To translate a page: create a new page with the translated text, open **Language & translations** in the sidebar, choose its language and pick the original page. Save. Both pages are now linked.

Each linked page gets hreflang tags and the correct html lang attribute (and right-to-left for Arabic, Urdu, Hebrew and Persian), which is how Google shows the right language in search. Translations are real pages with their own address and SEO fields, so they are indexed on their own.

**Machine translation:** for a language with no translation of the current page, the switcher can link to Google Translate, marked "(machine translation)" and nofollow so search engines only index your real translations. It works only on public sites. Google Translate pages are not reviewed by you; for important pages, publish a real translation.

**Polylang or WPML:** if one is active, the switcher shows its languages and SEMRS leaves hreflang to that plugin.

## Moving a key to another site

Click **Deactivate license** on the old site first; this frees the site slot. Then activate the key on the new site. Local and staging sites (localhost, .local, .test, staging. and dev. addresses) do not use a slot.

## Updates

With an active key, WordPress shows "Update available" for SEMRS Pro on Dashboard → Updates and the Plugins screen, and updates in one click like any other plugin. Free SEMRS products update the same way.

## When a license expires

Pro features keep working. An active license gives you updates and support; renew to receive new versions and fixes.

## Your data

Activation sends only your license key, your site address and the plugin version to semrs.com, to check the license and offer updates. No content, user or visitor data is sent.
