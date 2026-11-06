---
kind: tool
parent: tools
order: 16
h1: Free Hreflang Tag Generator
title: Free Hreflang Tag Generator – HTML & Sitemap | SEMRS
description: Create correct hreflang tags for every language and country version of a page, with x-default, as HTML, sitemap entries or headers. Free.
focus: hreflang generator
schema: WebApplication
---
This free hreflang tag generator builds the tags that tell Google which language and country version of a page to show each searcher. Add one row for each version of the page, choose an x-default page for everyone else, and copy the result as HTML link tags, XML sitemap entries or an HTTP header. The tool also catches the mistakes that break hreflang most often, such as invalid codes and duplicate versions.

[tool]

[ad]

## What hreflang does

If your website has the same page in several languages, or in one language for several countries, Google needs to know they belong together. Hreflang annotations link the versions and say who each one is for: English for the United States, English for the United Kingdom, Spanish for Mexico and so on. Google then shows the best match in each country's results, and the versions stop competing with each other.

Hreflang does not translate anything and does not move a page up in rankings by itself. It makes sure the right existing page appears for the right audience.

## How hreflang codes work

- **Language first:** a two-letter ISO 639-1 code such as en, es, fr, de, ar or ur.
- **Country optional:** add a two-letter ISO 3166-1 country code after a hyphen, such as en-us, en-gb or es-mx. A country code alone, such as "us", is not valid.
- **x-default:** a special value for the page to show when no version matches, often a language picker or your main international page.
- **Case does not matter:** en-GB and en-gb mean the same thing.

## How to use the hreflang generator

1. Add one row for each version of the page: the hreflang code and the full URL of that version.
2. Use **+ Add version** for more languages or countries, and the ✕ button to remove one.
3. Add an x-default URL for visitors whose language or country you do not cover.
4. Choose the output: HTML tags for the page head, XML sitemap entries, or an HTTP header for PDFs and other files.
5. Fix anything the warnings list, then copy the result and add it to every version of the page.

## The three rules that make hreflang work

- **Every version lists every version,** including itself. If the US page lists the UK page, the UK page must list the US page too. Google ignores annotations without these return links.
- **Each version has a self-referencing canonical.** Do not point the canonical of the UK page to the US page, or Google may drop the UK page.
- **Use one method per page.** HTML tags, sitemap entries or headers all work, but mixing them makes mistakes more likely.

## Worked example

An online course provider has a pricing page in US English, UK English and Spanish for Mexico. The UK page shows prices in pounds, but British searchers keep landing on the US page with dollar prices.

The team adds three rows (en-us, en-gb, es-mx) with each page's URL, plus an x-default pointing to the US page. The generator gives four link tags, which the team adds to all three pages, and each page keeps a canonical to itself. Within a few weeks the UK page appears for British searches and complaints about currency stop.

## Best practices

- Use full, absolute URLs including https.
- Only link pages that are true equivalents. A page in another language should have the same purpose and content.
- Use a country code only when the content really differs, such as prices, spelling, delivery or legal details.
- Keep hreflang in sync when you add, move or delete a version.
- For large sites, put hreflang in the XML sitemap so page HTML stays light.

## Common mistakes

- Using en-uk instead of en-gb. The country code for the United Kingdom is GB.
- Missing return links, so the annotations are ignored.
- Pointing hreflang at URLs that redirect, are blocked by robots.txt or carry a noindex tag.
- Canonicalizing every language version to one page.
- Creating country versions with identical content and calling them different pages.

## Tips for local businesses

If you serve one country in one language, you do not need hreflang. It becomes useful when you serve, for example, English and Spanish speakers in the US, or customers in both Canada and the US with different prices. In those cases, create genuinely localized pages with the right currency, phone numbers and contact details, then link them with hreflang.

Google explains the rules in [Tell Google about localized versions of your page](https://developers.google.com/search/docs/specialty/international/localized-versions).

## Hreflang generator: frequently asked questions

Q: Do I need x-default?
A: It is optional but recommended. It tells Google which page to show when none of your versions match the searcher's language or country.

Q: Is hreflang case-sensitive?
A: No. en-GB and en-gb are the same. The tool writes the common form, with the country in capitals.

Q: Can I use hreflang for regions such as Latin America?
A: Region codes such as es-419 are valid in some systems, but Google supports only language codes and two-letter country codes. Use es for general Spanish, or specific countries such as es-mx and es-ar.

Q: Where should I put the tags?
A: In the head of every version of the page, or in your XML sitemap, or in an HTTP Link header for files such as PDFs. Pick one method.

Q: Does hreflang work on Bing?
A: Bing pays more attention to the page's language and its content-language settings. Hreflang does no harm there, but Google is where it matters most.

## Related free tools

- [On-Page SEO Checker](/tools/on-page-seo-checker/): check each version's canonical, title and indexing.
- [Redirect Checker](/tools/redirect-checker/): make sure every hreflang URL loads directly with a 200 status.
- [SERP Snippet Preview](/tools/serp-snippet-preview/): write a strong title and description for each language.

> Taking your website into new countries or languages? [See SEMRS SEO & GEO services →](/services/seo/)
