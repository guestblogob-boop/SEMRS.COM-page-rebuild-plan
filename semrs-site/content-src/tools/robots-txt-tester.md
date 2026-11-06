---
kind: tool
parent: tools
order: 15
h1: Free Robots.txt Tester
title: Free Robots.txt Tester – Google & AI Crawlers | SEMRS
description: Fetch any live robots.txt and test if Googlebot, Bingbot or AI crawlers such as GPTBot may open a URL. Edit the rules to test changes first. Free.
focus: robots.txt tester
schema: WebApplication
---
This free robots.txt tester fetches the live robots.txt file of any website and shows whether a crawler may open a specific URL. Enter a page address, choose a crawler such as Googlebot, Bingbot, GPTBot or ClaudeBot, and see at once whether the page is allowed or blocked, which rule decided it, and how twelve common crawlers are treated. You can also edit the rules in the box to test a change before you upload it.

[tool]

[ad]

## What robots.txt does

Robots.txt is a plain text file at the root of a website, such as example.com/robots.txt. It tells crawlers which parts of the site they may visit. Well-behaved crawlers, including Google, Bing and the main AI companies, read it before crawling. It is a set of instructions, not a lock: it does not hide pages from people, and badly behaved bots can ignore it.

Robots.txt controls crawling, not indexing. A blocked page can still appear in Google, usually without a description, if other sites link to it. To keep a page out of search results, allow crawling and use a noindex tag instead.

## How crawlers read the rules

The tester follows the same rules Google documents:

- **Groups:** rules are grouped under one or more User-agent lines. A crawler uses only the group that matches its name most specifically, and falls back to the group for all crawlers (`User-agent: *`) if none matches.
- **Longest match wins:** within the group, the rule with the longest matching path decides. "Allow: /blog/news/" beats "Disallow: /blog/".
- **Ties go to Allow:** if an Allow and a Disallow rule are equally long, the page is allowed.
- **Wildcards:** `*` matches any characters and `$` marks the end of the URL, so `Disallow: /*.pdf$` blocks all PDF files.
- **No rule, no block:** an empty Disallow line, or no matching rule, means the page is allowed.

## How to use the robots.txt tester

1. Enter the full address of the page you want to test, for example a product page or a PDF.
2. Choose the crawler you care about. Googlebot is the most important for search; GPTBot, ClaudeBot and PerplexityBot are common AI crawlers.
3. Click **Fetch robots.txt & test**. The tool loads the site's current file and shows Allowed or Blocked with the matching rule.
4. Check the table to see how all twelve crawlers are treated for the same URL.
5. To try a change, edit the text in the robots.txt box. The result updates as you type. Copy your tested rules into the real file when you are happy.

## Worked example

An online shop notices that its product images have disappeared from Google Images. The owner tests a product photo URL with Googlebot-Image and the tool shows **Blocked** by "Disallow: /wp-content/". That rule was added years ago to hide plugin files, but it also blocks the uploads folder where every product photo lives.

In the box, the owner replaces it with "Disallow: /wp-content/plugins/" and adds "Allow: /wp-content/uploads/". The image URL now shows **Allowed** for Googlebot-Image, and pages that need the theme's CSS still render correctly. The owner uploads the corrected file and asks Google to recrawl a few key pages.

## What the status of robots.txt means

- **200 with rules:** crawlers follow the rules in the file.
- **404 or 410:** there is no robots.txt, so crawlers may visit everything. This is fine if you want everything crawled.
- **5xx server error:** Google treats this as "do not crawl anything" for a while and slows down crawling. Fix server errors on robots.txt quickly.

## Best practices

- Block only what you must: admin areas, internal search results, carts and checkout pages.
- Never block the CSS, JavaScript and image files that pages need to display.
- Add a Sitemap line with the full address of your XML sitemap.
- Decide on AI crawlers on purpose. Blocking AI search crawlers can stop your business being cited in AI answers; blocking training crawlers only affects model training.
- Test the exact URLs that matter after every change.

## Common mistakes

- "Disallow: /" left in place after launching a site from a staging server, which blocks everything.
- Blocking pages that also carry a noindex tag. If Google cannot crawl the page, it never sees the noindex.
- Typos in crawler names, which make the group match nothing.
- Expecting robots.txt to protect private content. Use passwords or server rules for that.

## Tips for local businesses

Most small business websites need only a few rules. If your site runs on WordPress, a typical file allows everything except the admin area and lists your sitemap. Test your homepage, your main service page and your contact page with Googlebot after any website change, and make sure your location and service pages are never blocked.

Google explains exactly how it reads the file in [How Google interprets the robots.txt specification](https://developers.google.com/search/docs/crawling-indexing/robots/robots_txt).

## Robots.txt tester: frequently asked questions

Q: Is the result the same as Google's?
A: The tester follows Google's documented rules for groups, longest match, wildcards and ties, so results match Google in normal cases. Search Console's robots.txt report shows the version of the file Google last fetched.

Q: Does blocking a page remove it from Google?
A: No. It stops crawling, not indexing. Use a noindex tag or remove the page to keep it out of results.

Q: Should I block AI crawlers?
A: It depends on your goals. Blocking training crawlers keeps your content out of model training; blocking AI search crawlers can also stop your pages being cited in AI answers.

Q: Can I test a robots.txt before uploading it?
A: Yes. Edit the text in the box and the results update instantly, without changing the live file.

Q: How big can a robots.txt file be?
A: Google reads up to 500 KiB. Keep it short and simple; most sites need fewer than 20 lines.

## Related free tools

- [Robots.txt Generator](/tools/robots-txt-generator/): build a correct file with sitemap and AI crawler rules.
- [Redirect Checker](/tools/redirect-checker/): make sure your important URLs load in one step.
- [On-Page SEO Checker](/tools/on-page-seo-checker/): check a page for noindex, canonical and other indexing problems.

> Want a full crawl and indexing check of your website? [See SEMRS SEO & GEO services →](/services/seo/)
