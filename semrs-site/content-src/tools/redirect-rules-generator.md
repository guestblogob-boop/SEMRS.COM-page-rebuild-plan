---
kind: tool
parent: tools
order: 17
h1: Free Redirect Rules Generator
title: Free Redirect Generator – .htaccess, Nginx, CSV | SEMRS
description: Turn old and new URL lists into 301 redirect rules for Apache .htaccess, Nginx or a CSV for redirect plugins. Spots loops and chains. Free.
focus: redirect generator
schema: WebApplication
---
This free redirect rules generator turns a list of old and new URLs into ready-to-use redirect rules. Paste your URL pairs straight from a spreadsheet, choose Apache (.htaccess), Nginx or CSV, pick the redirect type, and copy or download the result. While you type, the tool warns you about redirect loops, chains and duplicate rules, the mistakes that cause most redirect problems after a website change.

[tool]

[ad]

## When you need redirects

Every time a page's address changes, the old address should send visitors and search engines to the new one. Common reasons include a website redesign, a new URL structure, moving from HTTP to HTTPS, merging two similar pages, renaming products or categories, and moving to a new domain. Without redirects, old links, bookmarks and search results lead to error pages, and the search signals the old page earned are lost.

## Which redirect type to choose

- **301 Permanent:** the page has moved for good. Use this for almost all website changes.
- **308 Permanent:** like 301, but forms and other requests keep their method. Useful for apps and APIs.
- **302 Temporary:** the move is short-term, such as a seasonal page or maintenance.
- **410 Gone:** the page was removed on purpose and has no replacement. Search engines drop it faster than a 404.

## How to use the redirect rules generator

1. Paste one redirect per line: the old URL, then the new URL. Separate them with a space, tab, comma or an arrow.
2. Use paths such as /old-page/ or full URLs. Query strings such as ?id=5 are supported.
3. Choose your server: Apache for most shared hosting, Nginx for many cloud servers, or CSV to import into a redirect plugin.
4. Pick the redirect type, usually 301.
5. Read the warnings and fix any loops, chains or duplicates.
6. Copy or download the rules and add them to your server or plugin, then test a few old URLs.

## Where to put the rules

- **Apache (.htaccess):** add the rules near the top of the .htaccess file in your site's root folder, above the WordPress block. Keep a backup first; one typo can take the site offline.
- **Nginx:** add the rules inside the server block of your site's configuration and reload Nginx. Your host may need to do this for you.
- **CSV:** import the file into your redirect plugin. On WordPress with SEMRS Core, paste it under SEMRS → Redirects → Import from CSV.

## Worked example

A garden center is moving from an old shop to a new website. Its spreadsheet has 120 old product URLs in column A and the new ones in column B. The owner pastes both columns into the tool and chooses CSV.

The tool reports two problems. /roses/ points to /plants/roses/, but /plants/roses/ also appears as an old URL pointing to /flowers/roses/, which is a chain. And one line points /gift-cards/ to itself. The owner points /roses/ straight to /flowers/roses/, removes the self-redirect, imports the CSV into the redirect plugin, and tests ten old URLs with the Redirect Checker. All land on the right page in one step.

## Best practices

- Redirect each old URL to its closest equivalent, not to the homepage.
- Point every redirect straight to the final URL. Update old rules when you change URLs again.
- Keep redirects in place for at least a year; for important pages, keep them indefinitely.
- Update internal links and your sitemap to the new URLs, so visitors do not rely on redirects.
- Test a sample of old URLs, including ones with query strings, after publishing the rules.

## Common mistakes

- Loops, where two rules send a URL back and forth.
- Chains created by adding new rules on top of old ones.
- Redirecting all old pages to the homepage, which search engines often treat like a 404.
- Using 302 for a permanent move.
- Mixing up trailing slashes, so /page and /page/ behave differently.

## Tips for local businesses

When you change your website, the address on your Google Business Profile, local directories and social profiles often stays the same. Include those old URLs in your list, especially service and location pages. After the move, update the links at the source as well, so customers reach the new page directly.

Google explains how redirects affect search in [Redirects and Google Search](https://developers.google.com/search/docs/crawling-indexing/301-redirects).

## Redirect rules generator: frequently asked questions

Q: Should I use .htaccess or a plugin?
A: A plugin is easier to manage and safer for most site owners. Server rules are faster and work for every file, which helps on large sites and during migrations.

Q: Do redirects slow down my site?
A: A single redirect adds a small delay. Chains of several hops add more, so always point straight to the final URL.

Q: How many redirects can I have?
A: Thousands are fine in a plugin or server configuration. Very long .htaccess files can slow Apache down, so move large lists into the main server configuration or a plugin.

Q: What about pages I deleted with no replacement?
A: Use 410 Gone if the page was removed on purpose, or let it return a helpful 404 page. Do not redirect it to an unrelated page.

Q: Why does my browser still go to the old place?
A: Browsers cache permanent redirects. Test in a private window or with the Redirect Checker, which makes a fresh request every time.

## Related free tools

- [Redirect Checker](/tools/redirect-checker/): test that each old URL lands on the new page in one step.
- [Robots.txt Tester](/tools/robots-txt-tester/): make sure crawlers may reach your new URLs.
- [On-Page SEO Checker](/tools/on-page-seo-checker/): check the new pages for titles, canonicals and noindex.

> Planning a redesign or migration? We map and test every redirect. [See SEMRS SEO & GEO services →](/services/seo/)
