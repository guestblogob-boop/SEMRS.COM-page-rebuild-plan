---
kind: tool
parent: tools
order: 14
h1: Free Social Share Preview & Open Graph Generator
title: Free Open Graph Generator & Social Share Preview | SEMRS
description: Preview how your link looks on Facebook, X and LinkedIn, check image size and text length, then copy ready-made Open Graph and Twitter card tags. Free.
focus: open graph generator
schema: WebApplication
---
This free Open Graph generator shows how a link to your page will look when someone shares it on Facebook, X (Twitter) and LinkedIn, then gives you the tags to make it look that way. Type a title, description, page URL and image URL to see three live previews, check the image size and text length, and copy the finished Open Graph and Twitter card tags.

[tool]

[ad]

## What Open Graph tags are

When someone pastes a link into a social network or a messaging app, the app reads a few tags in the page's HTML to build the preview card: the title, a short description, an image and the site name. These are Open Graph tags, a format first created for Facebook and now read by LinkedIn, Pinterest, Slack, WhatsApp, iMessage and many others. X reads its own Twitter card tags first and falls back to Open Graph when they are missing.

Without these tags, apps guess. They may pick your logo, a random image from the footer or no image at all, and the title may be your site name instead of the page's topic. A clear card with a strong image gets noticeably more clicks than a plain link.

## The tags this tool creates

- **og:title** and **og:description:** the headline and short text on the card.
- **og:image** and **og:image:alt:** the picture and its description for screen readers.
- **og:url:** the address the card links to.
- **og:type:** usually "website" for pages and "article" for blog posts.
- **og:site_name:** your brand.
- **twitter:card:** "summary_large_image" for a big picture, or "summary" for a small square.
- **twitter:title**, **twitter:description**, **twitter:image** and an optional **twitter:site** handle.

## How to use the Open Graph generator

1. Type the title you want on the card. Keep it under about 60 characters so it is not cut off.
2. Write one or two sentences for the description that give people a reason to click.
3. Paste the page URL and the full URL of the image you want to show.
4. Add a short description of the image and your site name.
5. Choose the content type and the X card size, then check the three previews.
6. Click **Copy tags** and paste them into the head of your page, or into the social fields of your SEO plugin.

The previews are close approximations. Each network changes its design from time to time, but the tags and the image rules stay the same.

## Image size and format

Use an image of **1200 × 630 pixels** (a ratio of 1.91 to 1). This size looks sharp on Facebook, LinkedIn and X's large card and is cropped sensibly by most other apps. Keep the file under 5 MB, use JPG or PNG (WebP works on most networks but not all), and use an https address. Keep important text and faces away from the edges, because some apps crop the image into a square or a narrower rectangle on phones. The tool shows the real size of your image and warns you if it is smaller than 1200 pixels wide.

## Worked example

A bakery shares its new "Custom Birthday Cakes" page and the card shows the bakery's logo, cropped, with the title "Products – Sweet Crumbs". After using the tool, the bakery sets the title "Custom Birthday Cakes in Brooklyn – Order 3 Days Ahead", a description with the starting price and delivery area, and a 1200 × 630 photo of a decorated cake with the shop's name in the corner. The new card makes the offer clear before anyone clicks.

## Best practices

- Write a social title that is a little more conversational than your SEO title, if it helps.
- Use a real photo or a clean branded graphic, not a stock image that could be anyone's.
- Give every important page its own image and text, not one site-wide default.
- Add alt text for the image so screen-reader users get the same information.
- After changing tags, ask the networks to read the page again with the Facebook Sharing Debugger and LinkedIn Post Inspector, because they cache old previews.

## Common mistakes

- Images that are too small, which networks show as a tiny thumbnail or not at all.
- Relative image paths such as "/images/card.jpg" instead of a full https address.
- Two sets of Open Graph tags on one page, printed by the theme and a plugin, which confuses apps.
- Text in the image so small it cannot be read on a phone.
- Forgetting to update the card after changing the page's offer or price.

## Tips for local businesses

Local customers often share links in messaging apps and community groups, so the card matters. Show your service and town in the title, use a photo of your real work or premises, and include something specific in the description, such as opening hours, a price from or "free quote". Check the card on a phone, where most sharing happens.

The full tag list is documented on the [Open Graph protocol website](https://ogp.me/).

## Open Graph generator: frequently asked questions

Q: Do Open Graph tags help SEO?
A: They are not a Google ranking factor, but better previews earn more clicks and shares from social networks and messaging apps, which brings more visitors.

Q: Why does Facebook still show my old image?
A: Facebook and LinkedIn cache previews. Use the Facebook Sharing Debugger or LinkedIn Post Inspector to fetch the page again after changing the tags.

Q: Do I need both Open Graph and Twitter card tags?
A: X falls back to Open Graph for the title, description and image, but it needs the twitter:card tag to choose the large image layout. Adding both is the safest choice.

Q: What image size should I use?
A: 1200 × 630 pixels, under 5 MB, as JPG or PNG with an https address.

Q: My SEO plugin already adds these tags. Do I need this tool?
A: Use it to plan the text and image and check the result. Then type the values into your plugin's social fields rather than pasting a second set of tags.

## Related free tools

- [SERP Snippet Preview](/tools/serp-snippet-preview/): check how the same page looks in Google search results.
- [Image Compressor & WebP Converter](/tools/image-compressor/): make your share image smaller without losing quality.
- [UTM Builder](/tools/utm-builder/): track visits from the links you share on social networks.

> Want every page on your site to look great when it is shared? [See SEMRS SEO & GEO services →](/services/seo/)
