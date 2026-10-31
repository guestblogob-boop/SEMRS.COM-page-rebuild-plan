---
kind: tool
parent: tools
order: 10
h1: Free UTM Builder
title: Free UTM Builder – Campaign URL Generator 2026 | SEMRS
description: Build tracked campaign links for Google Analytics with presets for Google Ads, Facebook, email and QR codes. Bulk mode tags many URLs at once. CSV export.
focus: utm builder
schema: WebApplication
---
This free UTM builder adds the tracking tags for you, so every campaign link is consistent. Build tracked campaign links in seconds. Add your landing page, pick a preset such as Google Ads, Facebook ads, newsletter or QR code, name your campaign and copy a clean URL with UTM parameters. Bulk mode tags a whole list of URLs at once and exports them as CSV.

[tool]

[ad]

## What UTM parameters are

UTM parameters are short tags added to the end of a URL that tell analytics tools such as Google Analytics 4 where a visitor came from. When someone clicks `https://example.com/offer/?utm_source=newsletter&utm_medium=email&utm_campaign=spring_sale`, your analytics records that visit as coming from the spring sale email, not just as "direct" or "referral" traffic.

Without UTM tags, a lot of marketing traffic is mislabelled. Clicks from email apps, PDFs, text messages, QR codes and some social apps often arrive with no referrer at all and show up as direct traffic. Tagging your links is the simplest way to see which campaigns actually bring visitors, leads and sales.

## The parameters explained

- **utm_source** (required): where the traffic comes from, such as `google`, `facebook`, `newsletter` or `qr`.
- **utm_medium** (required): the type of channel, such as `cpc` for paid search, `paid_social`, `social`, `email`, `referral` or `offline`.
- **utm_campaign** (required): the campaign name, such as `spring_sale_2026` or `webinar_march`.
- **utm_term** (optional): the paid keyword, mainly used for search ads.
- **utm_content** (optional): which ad, banner or link was clicked, useful for A/B tests.
- **utm_id** (optional): a campaign ID that can link your data to ad platform or cost data.

## How to use the UTM builder

1. Paste the full URL of the page you want people to land on.
2. Choose a **preset** to fill source and medium, or type your own.
3. Enter a clear **campaign name**.
4. Add term, content or campaign ID if you need them.
5. Leave **lowercase and underscores** ticked. UTM values are case-sensitive in analytics, so "Facebook" and "facebook" would be counted separately.
6. Copy your tracked link.
7. For many links, paste one URL per line into **Bulk URLs**. The same parameters are added to each, invalid lines are flagged, and you can copy them all or download a CSV.

## A simple naming convention

Consistency matters more than any particular scheme. Agree a short set of rules and write them down:

- Always lowercase, with underscores instead of spaces.
- A fixed list of mediums: `cpc`, `paid_social`, `social`, `email`, `referral`, `affiliate`, `offline`.
- Sources named after the platform: `google`, `bing`, `facebook`, `instagram`, `linkedin`, `tiktok`, `newsletter`.
- Campaign names that include the topic and date: `black_friday_2026`, `boiler_service_autumn`.

## Worked example

A garden centre in Vancouver runs a spring promotion across three channels. For its email newsletter it uses source `newsletter`, medium `email`, campaign `spring_planting_2026`. For Facebook ads it uses `facebook` / `paid_social` with the same campaign name, and `utm_content` values `carousel` and `video` to compare the two ad formats. For flyers it prints a QR code from a link tagged `qr` / `offline`. A month later, GA4 shows exactly how many visits and online orders came from each channel and which ad format worked better.

## Best practices

- Tag every link you control outside your website: emails, ads, social posts, partner sites, QR codes, PDFs and SMS.
- Never use UTM tags on links between pages of your own site. They overwrite the original source and break your reports.
- Keep campaign names short and readable.
- Check the link before sending: click it and confirm the page loads.
- Use a link shortener or QR code for printed material, but build the tagged URL first.
- Remember that Google Ads can add tracking automatically with auto-tagging; follow your ad account's settings.

## Common mistakes

- Mixing capitals and spelling ("Email", "email", "e-mail"), which splits one channel into several rows.
- Putting the channel type in the source field, such as `utm_source=email`.
- Tagging internal links or navigation.
- Forgetting the required source, medium or campaign values.
- Reusing an old campaign name, which mixes old and new results.

## Tips for local businesses

Local businesses in the US, UK, Canada and Australia often market offline as well as online: flyers, vans, local newspapers, radio, events and shop windows. Give each one its own tagged link and QR code, for example source `flyer_eastside` and medium `offline`, so you can finally see which local promotion brings website visits. Your Google Business Profile website link can also be tagged (for example source `google`, medium `organic_gbp`) so you can measure visits from your profile separately from normal search traffic.

Google Analytics explains each campaign tag in [Collect campaign data with custom URLs](https://support.google.com/analytics/answer/10917952).

## UTM builder: frequently asked questions

Q: Do UTM parameters affect SEO?
A: No, as long as you only use them on external links such as ads, emails and social posts. Do not add them to internal links. Your pages should also have a canonical tag pointing to the clean URL.

Q: Where do I see UTM data in Google Analytics 4?
A: In the traffic acquisition reports, using dimensions such as session source, session medium and session campaign.

Q: Are UTM parameters case-sensitive?
A: Yes. "Facebook" and "facebook" are treated as different sources, which is why the builder can lowercase everything for you.

Q: Should I tag my Google Business Profile link?
A: Many businesses do, so profile visits can be measured separately. Use a consistent source and medium, and keep the link pointing to the right page.

Q: What is the difference between utm_campaign and utm_id?
A: The campaign is a readable name. The ID is a code that can connect your analytics data to cost or campaign data imported from ad platforms.

## Related free tools

- [Redirect Checker](/tools/redirect-checker/): make sure tagged links do not lose their parameters in redirects.
- [SEO ROI Calculator](/tools/seo-roi-calculator/): put a value on your organic traffic.
- [Google Review Link Generator](/tools/google-review-link-generator/): turn campaign customers into reviews.

> Want campaigns that are tracked, measured and improved every month? [See SEMRS Paid Ads Management →](/services/paid-ads/)
