---
title: Policies: Google, AdSense, WordPress.org & Privacy
description: How SEMRS products follow Google Search guidelines, AdSense policies, WordPress.org theme and plugin guidelines, GPL licensing and privacy rules, and what site owners must do.
order: 8
---
SEMRS products are built to follow the rules of Google Search, Google AdSense and WordPress.org. This page explains how, and what you, the site owner, still need to do.

## Google Search

- **Clean, crawlable HTML:** one H1 per page, real text rendered on the server, numbered pagination, no hidden text or links.
- **Honest structured data:** schema describes only what visitors can see. SEMRS never adds fake reviews, ratings or FAQ items.
- **Speed and Core Web Vitals:** light theme, reserved ad space (no layout shift), lazy loading, high-priority hero images, an optional page cache and a built-in speed check.
- **AI assistants:** an optional llms.txt lists only pages you allow search engines to index.
- **Multilingual done right (Pro):** translations are real pages linked with hreflang tags and the correct html lang; machine-translation links are nofollow so only reviewed translations are indexed.
- **No intrusive popups:** SEMRS Pro popups open as a bottom sheet on phones instead of covering the page, always have a close button, close with Esc, and never show ads.
- **Your part:** publish helpful, original content written for people, and follow [Google Search Essentials](https://developers.google.com/search/docs/essentials).

## Google AdSense

See the full [Ads manager and AdSense guide](/support/ads/). By default SEMRS keeps ads off conversion, legal and empty pages, labels every ad, limits in-content ads and never places ads where they can be mistaken for navigation.

**Your part:** never click your own ads, keep content within the Google Publisher Policies, keep ads.txt current, and use a Google-certified consent tool for visitors in the EEA, UK and Switzerland.

## WordPress.org guidelines

The free SEMRS theme and plugins are built to the [Theme Review requirements](https://make.wordpress.org/themes/handbook/review/required/) and the [Plugin Directory guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/):

- **GPL licence:** all code, images and icons are GPLv2 or later, or original work released under it.
- **No locked code in free versions:** free plugins contain no paid-only code. Pro features live in the separate SEMRS Pro plugin. Free plugins only show what Pro adds, with a clear PRO label.
- **No tracking or phoning home:** the versions on WordPress.org make no requests to semrs.com. The update checker is included only in versions downloaded from semrs.com.
- **No surprise changes:** nothing is switched on without your choice except safe defaults, which are explained on each settings screen. Upgrade notices are limited to SEMRS screens.
- **Theme vs plugin:** the theme handles design only. SEO, speed tweaks, ads, forms and custom post types live in plugins, so switching themes never loses your content or SEO.
- **Security:** settings are checked with nonces and capabilities, input is sanitized and output is escaped.
- **Accessibility:** skip link, keyboard-usable menus and blocks, visible focus styles, ARIA labels, reduced-motion support.

## Privacy and personal data

| Feature | What is stored | Where |
|---|---|---|
| Contact Form block | Name, email, phone, country, website, service, budget, message, page address, time | Leads in your dashboard and the email sent to you |
| Rate limit (contact form) | A one-way hash of the visitor's IP address for one hour | Temporary database entry |
| Ads manager | Nothing; ad networks set their own cookies | – |
| Popups (Pro) | When a popup was last shown, to respect "once per visit" or "once every N days" | The visitor's own browser (local or session storage); nothing is sent to your server |
| Page cache | Copies of public pages exactly as logged-out visitors see them; never pages for logged-in users, carts or forms | Files in wp-content/cache/semrs/ on your server |
| Ads click protection (Pro) | Times of the visitor's recent ad clicks and, if the limit is reached, when ads may show again | The visitor's own browser (local storage); nothing is sent to your server |
| Dark mode switch (Pro) | Whether the visitor chose light or dark | The visitor's own browser (local storage) |
| SEMRS Pro license | License key, site address, plugin version | Sent to semrs.com for updates |

**Your part:**

1. Add the contact form and advertising details to your privacy policy (Settings → Privacy helps you create one).
2. Keep the consent box on the contact form.
3. Delete leads you no longer need under Leads.
4. Use a consent tool if you show personalised ads or use analytics cookies where the law requires it.

## Trademarks

Google, AdSense and other names are trademarks of their owners. SEMRS is independent and is not affiliated with Google, Semrush, WordPress.org or any theme or plugin company mentioned on this site.
