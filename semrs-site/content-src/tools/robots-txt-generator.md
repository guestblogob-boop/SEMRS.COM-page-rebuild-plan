---
kind: tool
parent: tools
order: 3
h1: Free Robots.txt Generator
title: Free Robots.txt Generator with AI Crawler Rules | SEMRS
description: Create a correct robots.txt in seconds: WordPress rules, blocked paths, sitemap links and AI crawler settings for GPTBot, ClaudeBot and more. Free download.
focus: robots.txt generator
schema: WebApplication
---
Build a correct robots.txt file in seconds. Choose WordPress-friendly defaults, block private paths, add your sitemap and decide how AI crawlers such as GPTBot, ClaudeBot and PerplexityBot may use your site. Then copy or download the file.

[tool]

[ad]

## What robots.txt does (and what it does not do)

Robots.txt is a small text file at the root of your domain, for example `https://example.com/robots.txt`. Well-behaved crawlers read it before visiting your site to learn which areas they may crawl. It is one of the first files Googlebot requests, so a mistake here can affect your whole site.

Two points cause most of the confusion:

- **Robots.txt controls crawling, not indexing.** Blocking a URL stops compliant crawlers fetching it, but Google can still index the URL without its content if other pages link to it. To keep a page out of search results, let it be crawled and use a `noindex` meta tag instead.
- **Robots.txt is not security.** The file is public and only polite bots follow it. Never rely on it to hide private files; use passwords or server rules for that.

## How the generator works

1. **Default rule.** Choose "Allow all crawlers" for a normal public website. "Block all crawlers" is only for staging or private sites, because it stops Google crawling everything.
2. **WordPress site.** Tick this to block the admin area while still allowing `admin-ajax.php`, which some themes and plugins need on the public site.
3. **Internal search.** Blocking internal search result URLs stops crawlers wasting time on endless combinations of search pages.
4. **Extra paths.** Add any folders that should not be crawled, such as cart or account pages. Each path starts with a slash.
5. **Sitemaps.** Paste the full URL of your XML sitemap so crawlers can find every page you want indexed.
6. **AI crawlers.** Decide whether AI companies may crawl your content. You can allow everything, allow AI search and browsing bots while blocking AI training bots, block them all, or choose bot by bot.

The generator warns you about risky rules, such as blocking CSS or JavaScript files that Google needs to render your pages.

## Understanding the AI crawler options

AI companies use different crawlers for different jobs. Training crawlers collect content to train models. Search and browsing crawlers fetch pages to answer a user's question, often with a link back to the source. Blocking training crawlers keeps your content out of future training data from companies that respect robots.txt. Blocking search crawlers can also stop your site being cited in AI answers, which may cost you visibility. For most businesses that want to be found, "allow AI search, block AI training" is a sensible middle ground. Google-Extended controls use of your content for Google's Gemini models and does not affect Google Search rankings.

## Worked example

A small online shop on WordPress wants Google to crawl products and categories, but not the basket, checkout, account pages or internal search. It also wants to stay visible in AI search answers while opting out of AI training. The generated file:

- allows everything by default,
- blocks `/wp-admin/` but allows `admin-ajax.php`,
- blocks `/cart/`, `/checkout/`, `/my-account/` and internal search,
- lists the training crawlers in their own group with `Disallow: /`,
- ends with the sitemap URL.

The owner uploads it to the site root and checks it in Google Search Console's robots.txt report.

## Best practices

- Keep the file short and readable. Every rule should have a clear reason.
- Always list your sitemap.
- Never block CSS, JavaScript or image folders that your pages need to display.
- After any change, check the robots.txt report in Google Search Console.
- Use `noindex` (not robots.txt) for pages that should stay out of search results, such as thank-you pages.
- Review the file whenever you change platform, theme or URL structure.

## Common mistakes

- Leaving `Disallow: /` live after launching a site that was built on a staging server. This blocks the whole site.
- Blocking a page in robots.txt and adding `noindex` to it. Google cannot see the noindex tag on a page it is not allowed to crawl.
- Adding `Crawl-delay`. Google ignores it; manage crawl rate in Search Console instead.
- Uploading the file to a sub-folder. It must sit at the root of the domain or subdomain.
- Using robots.txt to hide sensitive files. The file itself tells everyone where they are.

## Tips for local businesses

Most small local business websites in the US, UK, Canada and Australia need a very simple robots.txt: allow everything, block the admin area and internal search, and list the sitemap. Make sure your location, service and contact pages are never blocked, because Google uses them to understand where you operate. If you use a booking or quote system on a separate subdomain, that subdomain needs its own robots.txt.

## Frequently asked questions

Q: Where do I put the robots.txt file?
A: In the root folder of your website so it opens at yourdomain.com/robots.txt. On WordPress you can upload it by FTP or your host's file manager, or manage it with an SEO plugin.

Q: Does every website need a robots.txt file?
A: It is not required, but it is good practice. Without one, crawlers assume they may crawl everything. A simple file with your sitemap URL helps them find your pages.

Q: Will blocking AI crawlers hurt my Google rankings?
A: Blocking AI training crawlers, including Google-Extended, does not affect Google Search rankings. Blocking AI search crawlers may reduce how often AI assistants cite and link to your site.

Q: How do I check that my robots.txt works?
A: Open the file in your browser, then use the robots.txt report in Google Search Console to see the version Google fetched and any errors.

Q: Do all bots obey robots.txt?
A: Reputable search engines and many AI companies do. Malicious bots ignore it, so use server-level protection for anything private.

## Related free tools

- [Redirect Checker](/tools/redirect-checker/): make sure old URLs reach the right pages.
- [llms.txt Generator](/tools/llms-txt-generator/): point AI tools to your most useful pages.
- [Schema Markup Generator](/tools/schema-markup-generator/): help search engines understand your content.

> Want a full crawl and indexing check of your website? [See SEMRS Technical SEO →](/services/technical-seo/)
