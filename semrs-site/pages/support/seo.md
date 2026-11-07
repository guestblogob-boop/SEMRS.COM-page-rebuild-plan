---
title: SEMRS Core SEO Manual: Titles, Schema, Sitemaps
description: SEMRS SEO manual: titles and meta, the SEO score, share previews, redirects, link settings, site audit, schema, sitemaps, llms.txt and switching from another SEO plugin.
order: 4
seo_title: SEMRS SEO Manual 2026: Easy Guide to Titles & Schema
focus: semrs seo
image: support-seo.webp
---
SEMRS SEO helps people find your site on Google. It writes the hidden page details that search engines read. It also checks each page and tells you what to fix, in plain words. It is part of the free SEMRS Core plugin.

## Set up SEMRS SEO in 4 steps

![SEMRS SEO setup: switch it on, add site details, fill in each page, check the score](seo-setup)

1. Go to **SEMRS → Features** and switch on **SEO**.
2. Open **SEMRS → SEO**. Add your business name, logo, address and social profiles.
3. Write a title and a short description for your home page.
4. Paste your Google Search Console code under **Verification**.

**Tip:** your home page title should be under 60 characters. The description should be under 160.

## Fill in each page

When you edit a page or post, open the **SEMRS SEO** panel. Fill in three things:

- **Focus keyword:** the main words people type into Google to find this page.
- **SEO title:** the blue link people see in Google.
- **Description:** the short text under the link.

You see a preview of how the page will look in Google. The counters show when your text is too long or too short.

**Not sure which keyword to pick?** Under the keyword box, click **Check interest in Google Trends**. With more than one keyword, it compares them side by side.

**Share previews:** the **Social** tab shows how the page will look when someone shares it on Facebook and X. Change the title, text or picture there. With no picture of its own, the page uses its featured image.

## The SEO score

![SEMRS SEO score: red under 50, orange 50 to 79, green 80 and up](seo-score)

Every page gets a score from 0 to 100. The checks are in three groups:

- **Basic SEO:** is the keyword in the title, the description, the address and the first lines?
- **Content:** does the page have enough words, links, picture alt text and subheadings?
- **Readability:** are the sentences and paragraphs short?

Click **Re-check** to test your changes before you publish. The score also shows in the **SEO** column of your Pages and Posts lists. Click the column name to sort by score.

**Internal link ideas:** after a re-check, SEMRS lists your other pages on the same topic. Link to them to help readers and Google.

## Check your whole site

Go to **SEMRS → SEO Audit**.

- **Site checks** run at once. Each problem has a **Fix** link.
- Click **Run the content audit** to score every page. You see missing descriptions, repeated titles, short pages and pages nobody links to.

**Tip:** pages that no other page links to are hard for Google to find. Link to them from related posts.

## Redirects and 404 errors

![SEMRS redirect types: exact page, whole folder, regex pattern and automatic redirects when a page address changes](seo-redirects)

Open **SEMRS → Redirects**. A redirect sends visitors and Google from an old address to a new one, so you keep your traffic and links.

- **Exact page:** `/old-page/` to `/new-page/`.
- **Whole folder:** `/old-folder/*` sends every page below it. End the target with `*` to keep the rest of the address.
- **Regex:** tick **Regex** for patterns, like `^/blog/(\d{4})/(.+)$` to `/news/$2/`.
- **Automatic:** change the address of a published page and SEMRS adds the 301 for you.
- **410 Gone** and **451** tell Google a page was removed on purpose.

Each rule shows how often it was used. The **404 log** lists addresses that were not found. Click **Redirect** next to one to fix it. You can also import redirects from Rank Math or a CSV file, and export them.

**Tip:** adding the same old address again updates its rule. You never get two rules for one address.

## Links, addresses and feeds

Open **SEMRS → SEO → Links, URLs & feeds**.

- **External links:** add nofollow or open them in a new tab. Most sites leave both off. Links you marked sponsored keep their mark.
- **Remove /category/:** `/category/news/` becomes `/news/`. Old addresses redirect for you. Do not use it if a page has the same name as a category.
- **RSS credit line:** sites that copy your feed then link back to your post. It is on by default.

Your **Pages** and **Posts** lists get a **Links** column. It shows the links inside each page and how many other pages link to it. A red **0** means no page links there yet.

## Schema, sitemap and llms.txt

These run on their own. You do not need to set them up.

- **Schema** tells Google what your page is about: your business, an article, a service or FAQs. It only describes what visitors can really see.
- **Sitemap** is a list of your pages for Google, at `/wp-sitemap.xml`. Send it to Google in Search Console → Sitemaps.
- **llms.txt** is a short map of your site for AI assistants. You can switch it off in **SEMRS → SEO → Indexing**.

## Moving from another SEO plugin

![Moving to SEMRS SEO from another SEO plugin in 4 steps](seo-switch)

SEMRS reads the titles and descriptions you saved in Rank Math, Yoast SEO or All in One SEO. Nothing is lost.

1. Install SEMRS Core. Look at a few pages in the SEMRS SEO panel.
2. Deactivate your old SEO plugin.
3. Open a page and check that it still has its title and description.
4. Send your sitemap to Google Search Console again.

While another SEO plugin is active, SEMRS waits. You never get the same tags twice.

## Extra SEO in SEMRS Pro

Pro adds automatic internal links and a broken link checker, AI-written titles and descriptions, automatic share images, image, video and news sitemaps, custom schema, local SEO for many locations, automatic alt text and a bulk editor for titles and descriptions. See the [Pro guide](/support/pro-license/).

## SEMRS SEO help and next steps

Write for people first. Our checks follow [Google Search Essentials](https://developers.google.com/search/docs/essentials), the rules Google shares for sites that want to rank. For faster pages, read the [speed guide](/support/speed/). Questions? [Contact us](/contact-us/).
