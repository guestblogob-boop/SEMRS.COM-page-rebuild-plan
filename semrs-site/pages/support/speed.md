---
title: SEMRS Speed Optimization Manual
description: Make WordPress faster with the free SEMRS Core speed module: page cache, CSS and script optimization, hover prefetch, database cleanup and a built-in speed check.
order: 6
---
The Speed module is part of the free SEMRS Core plugin. Switch it on in **SEMRS → Features**, then open **SEMRS → Speed**. The screen has four tabs: **Optimize**, **Page cache**, **Database** and **Speed check**. Each option has a short explanation; the safe ones are on by default.

## 1. Optimize

| Option | Default | What it does |
|---|---|---|
| Remove emoji scripts | On | Browsers show emoji themselves; saves a script and styles on every page |
| Remove oEmbed discovery | On | Removes the embed script; your own YouTube and other embeds keep working |
| Dashicons only for logged-in users | On | Visitors never need the admin icon font |
| Remove jQuery Migrate | On | Turn off if an old plugin breaks |
| Clean the head | On | Removes RSD, Windows Live Writer, generator and shortlink tags |
| Slow the Heartbeat API | On | Every 60 seconds; lowers server load, autosave still works |
| Limit revisions | On | Keeps at most 10 revisions per post |
| YouTube click-to-play | On | Shows the thumbnail and loads the player only when clicked |
| Preload the featured image | On | Helps Largest Contentful Paint |
| Preconnect hints | On | Only when ads or Google tags are active |
| Block styles only for blocks on the page | On | Each page gets only the CSS of the blocks it uses |
| Inline small stylesheets | Off | CSS files under 10 KB go straight into the page, saving requests. Best with the page cache |
| Load chosen stylesheets without blocking | Off | For plugin CSS that is not needed at the top of the page. List the style handles |
| Prefetch pages on hover | Off | The next page starts loading when a visitor points at a link, so it opens almost instantly |
| Disable XML-RPC | Off | Leave off if you use Jetpack or the WordPress mobile app |
| Defer front-end scripts | Off | Test your site after switching on; add any script that breaks to the exclusions |

**Finding a style handle:** right-click your page → View Source, find the `<link rel='stylesheet' id='contact-form-7-css'>` tag, and use the id without `-css` (here: `contact-form-7`). Never list the theme's main stylesheet.

## 2. Page cache

The page cache saves each finished page as a file and sends that file to the next visitors, so WordPress does not build the page again. It is **off by default**.

1. Open **SEMRS → Speed → Page cache**.
2. Tick **Cache pages for visitors who are not logged in** and click **Save Changes**.
3. Open your site in a private window. The first visit builds the cached copy; later visits are served from it.

**Never cached:** logged-in users, visitors who left a comment, shoppers with items in a WooCommerce or Easy Digital Downloads cart, search results, 404 pages, previews, feeds, sitemaps, password-protected pages, cart, checkout and account pages, form submissions, and URLs with a query string (tracking tags such as `utm_source`, `gclid` and `fbclid` are ignored, so ad and email traffic still gets the fast page).

**Clearing:** the whole cache clears automatically when you publish or update content, change menus, widgets, the Customizer, theme or SEMRS settings, or when comments are approved. You can also click **Clear cache** in the admin bar. Cached copies are kept for up to 10 hours, so forms on cached pages keep working.

**Phones and computers** get separate copies, so plugins that show different content on phones stay correct.

**Another cache plugin?** If WP Rocket, LiteSpeed Cache, W3 Total Cache, WP Super Cache, WP Fastest Cache, SiteGround Optimizer or a similar plugin is active, the SEMRS page cache pauses itself. Use one page cache only. Host caches (for example on managed WordPress hosting) work alongside SEMRS; if your host already caches pages, you can leave the SEMRS cache off.

**For developers:** `define( 'DONOTCACHEPAGE', true );` stops one page from being cached; `define( 'SEMRS_CACHE_OFF', true );` in wp-config.php switches the cache off. Cached files live in `wp-content/cache/semrs/` and are deleted when SEMRS Core is deactivated.

## 3. Database

Shows how many old revisions (older than 30 days), old auto-drafts, trashed posts, spam and trashed comments, expired transients and orphaned post meta your database holds. Click **Clean** next to an item to remove up to 500 at a time. Back up first if your host does not do it for you; cleaned items cannot be restored.

## 4. Speed check

Click **Run speed check**. SEMRS loads your homepage twice from your own server and reports:

- server response time and whether the page cache served the page
- compression (Gzip or Brotli), page size, render-blocking scripts and stylesheets
- images without width and height (these make the page jump while loading)
- scripts loaded from other sites, oversized image uploads
- PHP version, object cache, autoloaded options and debug mode

Each finding has a plain-language fix. Use **Open PageSpeed Insights** for Google's real-world phone scores. Some hosts block a site from loading itself; the check then says so.

## 5. SEMRS Pro speed extras

- **Delay JavaScript until interaction:** analytics, pixel, chat and heatmap scripts wait for the first scroll, tap or key press, then run in their original order. A list of common services is filled in for you. Ad scripts such as AdSense are never delayed.
- **Cache preload:** after the cache clears, the homepage and your 200 newest pages are rebuilt in the background for phones and computers.
- **CDN:** images, CSS, JavaScript and fonts load from your CDN address.

These settings appear at the bottom of the Optimize tab when SEMRS Pro is active.

## How to test

1. Open your page in [PageSpeed Insights](https://pagespeed.web.dev/) and note the mobile scores.
2. Change one setting at a time and test again.
3. If something stops working, switch the last change off. For deferred scripts, add the script's handle to the exclusion list instead.

Avoid switching on the same feature (for example "defer JavaScript") in two plugins at once.

## Theme tips

The SEMRS theme already loads one stylesheet and one small script, loads core block CSS only for blocks on the page, and gives the first featured image high priority. Use WebP images and keep pages focused.
