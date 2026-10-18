---
kind: tool
parent: tools
order: 5
h1: Free Schema Markup Generator
title: Free Schema Markup Generator (JSON-LD) | SEMRS
description: Generate valid JSON-LD schema for Organization, Article, FAQ, Breadcrumb, Product, Event and Service pages. Copy the code and test it with Google. Free.
focus: schema markup generator
schema: WebApplication
---
Choose a schema type, fill in the form and get valid JSON-LD structured data ready to paste into your page. The generator supports Organization, Article, FAQPage, BreadcrumbList, Product, Event and Service, and links straight to Google's Rich Results Test.

[tool]

[ad]

## What schema markup is

Schema markup is a standard vocabulary, maintained at Schema.org, that describes the content of a page in a form machines understand. Instead of guessing that "Smith & Co" is a business, "4.30pm" is a closing time or "$49" is a price, search engines can read those facts directly. Google recommends the JSON-LD format, a block of code that sits in the page and does not change what visitors see.

Structured data can make a page eligible for rich results, such as product prices, event dates or breadcrumb paths in search results. It also helps search engines and AI systems connect your pages to the right business, author and topic. It does not guarantee a rich result or a higher ranking, but it removes ambiguity, and accurate data is increasingly valuable.

## Which schema type to use

- **Organization:** your business identity, usually on the homepage or About page: name, logo, contact details and social profiles.
- **Article:** blog posts, guides and news. Choose BlogPosting for blog content and NewsArticle for news.
- **FAQPage:** pages with visible questions and answers. Google now shows FAQ rich results only for some well-known authoritative sites, but the markup still describes your content clearly.
- **BreadcrumbList:** the path from your homepage to the current page, which Google can show instead of the full URL.
- **Product:** a single product with a visible price and availability.
- **Event:** a dated event, in person or online.
- **Service:** a service you offer, the provider and the areas you serve.

For a physical local business, use our dedicated Local Business Schema Generator, which adds address, map coordinates and opening hours.

## How to use the generator

1. Choose a schema type from the buttons at the top.
2. Fill in the fields. Leave optional fields empty if they do not apply; empty fields are left out of the code.
3. For FAQ and breadcrumb types, add or remove items with the buttons.
4. Watch the JSON-LD update on the right.
5. Click **Copy code** and paste it into your page. In WordPress, use a Custom HTML block, your theme's header settings or your SEO plugin's schema feature.
6. Click **Test in Google** and paste your page URL or code into the Rich Results Test to confirm there are no errors.

## Worked example

A marketing agency in Toronto publishes a guide called "How to Choose a Web Designer". Using the Article type, it enters the headline, a one-sentence summary, the main image URL, the author's name and profile page, the agency name and logo, the publish date and the page URL. The generator produces a BlogPosting block that ties the guide to a real author and publisher. The agency pastes it into the post, runs the Rich Results Test, sees the article detected with no errors, and adds a BreadcrumbList block so Google shows "Home › Guides › How to Choose a Web Designer" in results.

## Rules to follow

- **Only mark up what visitors can see.** If a price, review or question is in your schema, it must also be visible on the page. Hidden or misleading markup breaks Google's guidelines and can lead to a manual action.
- **Never invent ratings or reviews.** This generator deliberately has no rating fields. Add review markup only for genuine reviews displayed on the page.
- **Be specific and accurate.** Use real URLs, full dates and the exact business name you use everywhere else.
- **One topic per block.** Several blocks on one page are fine, as long as each describes something on that page.
- **Keep it updated.** Change the schema when prices, dates or details change.

## Common mistakes

- Copying another site's schema and leaving its details in.
- Adding Product markup to category pages that list many products.
- Using FAQ markup for sales copy rather than real questions.
- Relative URLs or images that do not load.
- Dates in an unclear format. Use the date picker so the format is correct.
- Duplicate Organization blocks from a theme and a plugin at the same time.

## Tips for local businesses

If you serve customers locally in the US, UK, Canada or Australia, start with Organization or LocalBusiness markup on your homepage, using exactly the same business name, address and phone number as your Google Business Profile. Add Service markup on each service page with the areas you serve, and Event markup for workshops or open days. Structured data supports your local presence; it does not replace a verified Google Business Profile or genuine customer reviews.

## Frequently asked questions

Q: Is JSON-LD better than microdata?
A: Google supports both, but it recommends JSON-LD because it sits in one block, separate from your HTML, which makes it easier to add, read and maintain.

Q: Will schema markup improve my rankings?
A: Structured data is not a direct ranking factor. It helps search engines understand your content and can make pages eligible for rich results, which can improve how many people click.

Q: Why is Google not showing my rich result?
A: Valid markup makes a page eligible but does not guarantee display. Google decides based on quality, relevance and the search. Some types, such as FAQ, are now shown only for selected sites.

Q: Can I add several schema types to one page?
A: Yes. A blog post can have Article and BreadcrumbList markup at the same time, as long as each describes content on that page.

Q: Does my WordPress SEO plugin already add schema?
A: Many do, for example basic Organization, WebPage and Article data. Check your page source or the Rich Results Test before adding more, so you do not create duplicates.

## Related free tools

- [Local Business Schema Generator](/tools/local-business-schema-generator/): address, opening hours and map coordinates.
- [SERP Snippet Preview](/tools/serp-snippet-preview/): see how your result looks in Google.
- [llms.txt Generator](/tools/llms-txt-generator/): help AI tools understand your site.

> Want structured data set up and validated across your whole site? [See SEMRS Technical SEO →](/services/technical-seo/)
