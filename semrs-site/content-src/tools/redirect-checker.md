---
kind: tool
parent: tools
order: 4
h1: Free Redirect Checker
title: Free Redirect Checker – Trace 301 & 302 Chains | SEMRS
description: Check any URL for redirects. See every hop, status code and response time, spot redirect chains, loops and temporary redirects, and get clear fixes. Free.
focus: redirect checker
schema: WebApplication
---
Enter any public URL to trace exactly what happens when a browser or search engine requests it. You will see every redirect hop, the status code at each step, the response time and the final destination, plus plain-English advice on what to fix.

[tool]

[ad]

## What a redirect is

A redirect tells browsers and search engines that a page has moved and where to find it. When someone visits the old address, the server answers with a 3xx status code and a new location, and the browser follows it automatically. Redirects are essential when you change URLs, move to HTTPS, merge pages or switch domains. Done well, they keep visitors, links and rankings. Done badly, they slow pages down, waste crawl time and can lose search traffic.

## Status codes you will see

- **301 Moved Permanently** and **308 Permanent Redirect**: the page has moved for good. Search engines transfer signals to the new URL. Use these for most site changes.
- **302 Found** and **307 Temporary Redirect**: the move is temporary. Use them only for short-term situations such as a seasonal campaign or maintenance.
- **200 OK**: the final page loaded successfully. This is what you want at the end of the chain.
- **404 Not Found** and **410 Gone**: the page does not exist. A redirect that ends here sends visitors to a dead end.
- **5xx errors**: the server failed. These need urgent attention, especially on important pages.

## How to use the redirect checker

1. Paste the full URL you want to test, such as an old blog post address or your domain without "www".
2. Click **Check redirects**. The tool follows up to 10 hops, the same practical limit Google documents for its crawler.
3. Read the hop table: each row shows the URL requested, the status code returned and how long it took.
4. Review the findings below the table. Green items are fine; amber items are worth reviewing; red items should be fixed.
5. Click **Copy report** to share the result with your developer or web host.

Checks are limited to public websites, and results for the same URL are briefly cached to keep the tool fast for everyone.

## Worked example

A bakery in Melbourne moved its website to HTTPS and later renamed its "menu" page. Testing the old address `http://bakery.example/menu` shows three hops:

1. `http://bakery.example/menu` returns **301** to the HTTPS version.
2. `https://bakery.example/menu` returns **302** to `https://www.bakery.example/menu`.
3. `https://www.bakery.example/menu` returns **301** to `https://www.bakery.example/our-menu/`, which returns **200**.

Two problems stand out. The chain has three redirects where one would do, adding delay on every visit. One hop is a temporary 302 even though the move is permanent. The fix is a single 301 rule sending every old variation straight to `https://www.bakery.example/our-menu/`, and updating internal links to point there directly.

## Best practices

- Redirect each old URL to the most relevant new page, not to the homepage.
- Use one hop wherever possible. Update rules so old URLs point straight at the final destination.
- Use 301 or 308 for permanent changes.
- Update internal links, menus and sitemaps to the final URLs so visitors do not need the redirect at all.
- Keep redirects in place long term. Old links on other websites keep sending visitors for years.
- After a site migration, test a sample of your most visited and most linked old URLs.

## Common problems the tool finds

- **Redirect chains:** several hops, often caused by layering new rules on top of old ones.
- **Redirect loops:** A redirects to B and B redirects back to A, so the page never loads.
- **Temporary redirects used permanently,** which can delay search engines moving signals to the new URL.
- **Redirects ending in a 404,** usually after a second round of URL changes.
- **Mixed versions:** HTTP and HTTPS, or www and non-www, not all pointing to one preferred version.
- **Unexpected noindex headers** (X-Robots-Tag) on the final URL, which keep it out of search results.

## Tips for local businesses

When a local business in the US, UK, Canada or Australia changes its website, the URL on its Google Business Profile, local directories (such as Yelp, Yell or Yellow Pages) and social profiles often still points to the old address. Test those exact links with this tool. If they go through several hops or end at an error, update the link at the source and fix the redirect. Also check that your domain with and without "www", and with "http", all land on the same final page in one step.

## Frequently asked questions

Q: Do redirects hurt SEO?
A: A single, well-chosen permanent redirect passes visitors and search signals to the new page with little or no loss. Problems start with long chains, loops, redirects to irrelevant pages and temporary redirects used for permanent moves.

Q: How many redirects in a chain are too many?
A: Aim for one. Google follows up to 10 hops, but each extra hop slows the page and adds risk. Clean up chains whenever you find them.

Q: What is the difference between 301 and 308?
A: Both are permanent. A 308 also guarantees the request method stays the same, which matters for forms and APIs. For normal web pages, either works.

Q: Should I redirect deleted pages to my homepage?
A: Only if there is no closer match. Redirect to the most relevant replacement page. If nothing is relevant, a 410 or a helpful 404 page is better than an irrelevant redirect.

Q: Why does the tool show a different result from my browser?
A: Browsers cache redirects, especially 301s, so your browser may skip steps it remembers. The checker makes a fresh request each time and shows exactly what the server sends today.

## Related free tools

- [Robots.txt Generator](/tools/robots-txt-generator/): make sure crawlers can reach your new URLs.
- [SERP Snippet Preview](/tools/serp-snippet-preview/): check how your new pages will look in Google.
- [UTM Builder](/tools/utm-builder/): track campaign links so redirects do not hide your traffic sources.

> Planning a redesign or migration? We map and test every redirect. [See SEMRS Technical SEO →](/services/technical-seo/)
