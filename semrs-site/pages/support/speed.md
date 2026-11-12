---
title: SEMRS Speed Optimization Manual
description: SEMRS speed manual: make WordPress faster with the free page cache, server rules, an early page cache, CSS and script optimization, database cleanup and a speed check.
order: 6
seo_title: SEMRS Speed Manual 2026: Easy Guide to a Faster Site
focus: semrs speed
image: support-speed.webp
---
Fast pages keep visitors happy, and Google likes them too. The SEMRS Speed module makes your WordPress site faster with a few clicks. It is part of the free SEMRS Core plugin. The safe options are already switched on for you.

## Open the SEMRS speed settings

Go to **SEMRS → Features** and switch on **Speed**. Then open **SEMRS → Speed**. You will see five tabs.

![The five SEMRS speed tabs: Optimize, Page cache, Database, Server and Speed check](speed-tabs-v2)

## 1. Optimize: remove extra code

Most options here are already on. Each one removes a little code your visitors do not need.

| Option | Starts | What it does |
|---|---|---|
| Remove emoji scripts | On | Browsers show emoji by themselves |
| YouTube click-to-play | On | Loads the video player only when someone clicks |
| Preload the featured image | On | Shows the main picture sooner |
| Block styles only for blocks on the page | On | Each page loads only the styles it uses |
| Host Google Fonts on your own site | On | Fonts your theme or plugins load from Google come from your site instead, and the main ones are preloaded. Faster, and visitors' IP addresses are not sent to Google (GDPR). |
| Limit revisions | On | Keeps up to 10 old versions of each post |
| Prefetch pages on hover | Off | Starts loading a page when the mouse points at its link |
| Inline small stylesheets | Off | Puts tiny style files straight into the page |
| Defer scripts | Off | Loads scripts after the page. Test your site after you switch it on. |
| Disable XML-RPC | Off | Leave it off if you use Jetpack or the WordPress app |

## 2. Page cache: the biggest speed boost

![SEMRS speed page cache: the first visit builds the page, next visits get the saved copy at once](speed-cache)

The page cache saves a copy of each page. The next visitor gets that copy at once. WordPress does not have to build the page again. It is **off** until you switch it on.

1. Open **SEMRS → Speed → Page cache**.
2. Tick **Cache pages for visitors who are not logged in**.
3. Click **Save Changes**.
4. Open your site in a private window to check it.

**What is never cached:** logged-in users, carts and checkouts, search results, forms and error pages. Phones and computers get their own copies.

**The cache clears itself** when you publish, change a menu or change settings. You can also click **Clear cache** in the top admin bar.

**Using another cache plugin?** Then the SEMRS cache pauses itself. Use only one page cache.

## 3. Database: tidy up

This tab shows old drafts, spam comments and other leftovers. Click **Clean** to remove them.

**Careful:** cleaned items cannot come back. Make a backup first if your host does not do it for you.

## 4. Server: a faster server

![SEMRS speed Server tab in 3 clicks: remove old cache plugin leftovers, add server rules, switch on the early page cache](speed-server)

This tab works on the web server itself. Nothing changes until you click a button. Before each change, SEMRS saves a dated copy of the old file in a protected folder.

1. **Remove leftovers.** Did you use another cache plugin before (WP Rocket, LiteSpeed Cache, W3 Total Cache and others)? Their rules often stay behind after you delete the plugin. SEMRS finds them and removes them in one click.
2. **Add server rules.** Turns on compression (Brotli and gzip) and tells browsers to keep pictures, fonts, styles and scripts for a year. Updates still show at once, because WordPress gives each new file version its own address.
3. **Switch on the early page cache.** Cached pages are sent before any plugin or the theme loads, so your server answers faster. You need the page cache (tab 2) switched on first.

**Uses Nginx?** The tab tells you. Ask your host to switch on compression and browser caching. Click **Show the rules** to see what to set.

**Something wrong?** Click **Remove rules** or **Switch off**. In an emergency, add `define( 'SEMRS_CACHE_OFF', true );` to wp-config.php. If you deactivate SEMRS Core, the early cache file is removed for you.

## 5. Cloudflare

Is your domain on Cloudflare? Open the **Cloudflare** tab and connect it once.

1. In Cloudflare, go to **My Profile → API Tokens → Create Token → Create Custom Token**.
2. Give it these permissions for your domain: **Zone → Read**, **Zone Settings → Edit**, **Cache Purge → Purge** and **Cache Rules → Edit**.
3. Paste the token in SEMRS and click **Connect**. Your domain is found by itself.

**Safe by design:** SEMRS sends nothing unless the text looks like a Cloudflare token or Global API key, and your browser never fills a saved password into this field.

Then:

- **Automatic clearing:** when you update a post, only its pages are cleared at Cloudflare (the post, home page, its categories, tags and author). Theme, menu and settings changes clear everything.
- **Clear Cloudflare cache** and **Development mode** buttons, for when you work on the design.
- **Apply recommended settings:** HTTPS, TLS 1.3, HTTP/3, Early Hints and more in one click. Your old values are saved, and **Put my previous settings back** undoes it.
- **Edge page cache:** your pages are served from Cloudflare's data centers near each visitor, also on the free plan. Logged-in users, carts, checkout, search and the admin are never cached.
- **Real visitor addresses:** comments and security see each visitor's own IP address instead of Cloudflare's.

**Using the Cloudflare plugin or WP Rocket's Cloudflare add-on?** SEMRS automatic clearing steps aside, so the cache is never cleared twice.

## 6. Speed check

Click **Run speed check**. SEMRS tests your home page and lists simple fixes. It checks:

- how fast your server answers
- the page size and compression
- pictures without a set size (they make the page jump)
- scripts that slow the page down

For Google's own phone score, click **Open PageSpeed Insights**.

## How to test safely

![SEMRS speed testing loop: change one setting, test, keep it or undo it](speed-test-loop)

1. Test a page in [PageSpeed Insights](https://pagespeed.web.dev/) and write down the score.
2. Change **one** setting.
3. Test again.
4. Faster? Keep it. Something broken? Switch it back off.

**Tip:** do not switch on the same feature in two plugins. For example, use only one plugin to defer scripts.

## Extra speed in SEMRS Pro

- **Delay scripts:** chat and tracking scripts wait until the visitor scrolls or taps. Ads are never delayed.
- **Remove unused CSS:** each page gets only the styles it uses. The full styles load after the first scroll or tap.
- **Minify styles:** smaller style files.
- **Unused plugin files:** form and block plugin files load only on pages that use them.
- **Image sizes and lazy iframes:** pictures get a set size so the page does not jump, and maps and videos load when the visitor scrolls near them.
- **Lazy rendering:** the browser skips drawing parts far down the page until the visitor gets close.
- **Cache preload:** after the cache clears, your main pages are saved again in the background.
- **CDN:** pictures and files load from your CDN.

## SEMRS speed help and next steps

The SEMRS theme is already light: one small style file and one small script. Use WebP pictures and keep pages focused. Next, check your titles in the [SEO guide](/support/seo/). Something broke? [Contact us](/contact-us/).
