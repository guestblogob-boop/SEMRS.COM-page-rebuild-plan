---
title: SEMRS Free Tools Guide
description: SEMRS Free Tools guide: put 20 free SEO tools on your WordPress site, switch tools on or off, set limits, and use the Free Tools Pro features.
order: 9
seo_title: SEMRS Free Tools 2026: Set Up 20 SEO Tools on WordPress
focus: semrs free tools
---
The free SEMRS Tools plugin puts 20 SEO and marketing tools on your own WordPress site. Visitors use them without signing up. Each page loads only the script of the tool on it, so tool pages stay fast. Most tools run in the visitor's browser. Three ask your server to read another website, with strict safety checks.

## Put a tool on a page

1. Go to **SEMRS → Free Tools** (or **Tools → SEMRS Free Tools** without SEMRS Core).
2. Copy the shortcode of a tool, for example `[semrs_tool id="readability-checker"]`.
3. Paste it into a Shortcode block on any page.
4. Under the tool, write a real guide: how to use it, an example, common mistakes and a short FAQ. A page with only a tool on it is thin content for Google and AdSense.

**Quick start:** click **Create the missing tool pages as drafts**. It builds the /tools/ hub, a page for each category and a page for each tool. Tools that come with a guide get the full text, SEO title, description and schema. Newer tools get a draft with a reminder to write the guide. Pages you already have are never changed.

**Directory:** `[semrs_tools]` shows every tool by category, and `[semrs_tools category="technical-seo"]` shows one category. A tool joins the directory once its page at /tools/tool-id/ is published, so visitors never land on a missing page.

## The 20 tools

| Category | Tools |
|---|---|
| Meta tags & SERP | SERP Snippet Preview, Meta Title & Description Checker, On-Page SEO Checker, Social Share Preview & Open Graph Generator |
| Technical SEO | Robots.txt Generator, Robots.txt Tester, Redirect Checker, Redirect Rules Generator, Hreflang Tag Generator |
| Schema | Schema Markup Generator, Local Business Schema Generator |
| Content | Word Counter, Readability Checker |
| Local SEO | Google Review Link Generator |
| AI search | llms.txt Generator |
| Ads & marketing | UTM Builder, ROAS & Break-Even Calculator |
| Website | Image Compressor & WebP Converter, Color Contrast Checker |
| Calculators | SEO ROI Calculator |

**Server tools:** the Redirect Checker, On-Page SEO Checker and Robots.txt Tester read the address a visitor types in, from your server. Every step is checked: only public websites on normal ports, never private or internal addresses. Images in the Image Compressor and text in the Readability Checker never leave the visitor's device.

## Settings

Go to **SEMRS → Free Tools → Settings**.

- **Tools on this site:** untick any tool you do not want. It disappears from pages and the directory and stops answering requests.
- **Expert-help box:** a short line under each tool that links to the matching service page. Untick it to hide it.
- **Remember inputs:** keeps what a visitor typed in their own browser for their next visit. Nothing is sent to your server.
- **Analytics events:** sends tool_used, tool_run, tool_copy, tool_download and cta_click to Google Analytics 4 when your tag is on the page and the visitor allowed it.
- **Checks per visitor per hour:** for the server tools. 30 suits most sites; the free maximum is 60.
- **Reuse a result for:** the same address checked again within these minutes is answered from memory. Faster for visitors and kinder to the other website.

## Free Tools Pro

Free Tools Pro is part of the SEO Pro add-on. Switch on **Free Tools Pro** under **SEMRS → Features**, then open **SEMRS → Free Tools → Settings**.

- **Bulk checks:** visitors can check up to 25 addresses at once in the Redirect Checker and On-Page SEO Checker, and download the results as CSV. Each address counts toward the hourly limit.
- **Your own expert-help box:** set your own text, link text and link for each category, for example "Need help with this? Book a call".
- **Usage report:** the **Usage report** tab shows which tools visitors use and how often they run them, copy or download results and click your box, for the last 7 and 30 days. Only totals are stored, never IP addresses or personal data.
- **Higher limits:** up to 500 server checks per visitor per hour.

## Speed and privacy

- The tool stylesheet loads only on pages with a tool. Each tool script is 2–6 KB and loads after the page has painted, so tool pages keep their PageSpeed scores.
- Tools store only functional data in the visitor's own browser and load no trackers.
- Turn an unused tool off to keep the directory focused.

## Common questions

**A tool says it is switched off.** Only editors see that note. Switch the tool on in **Settings**, or remove the shortcode.

**The directory does not show a new tool.** Publish its page at /tools/tool-id/ first.

**Visitors see "Limit reached".** Raise **Checks per visitor per hour** a little, or keep it as it is to protect your server from heavy use.

**Can I import redirects I made with the Redirect Rules Generator?** Yes. Choose **CSV**, copy the result and paste it under **SEMRS → Redirects → Import from CSV**.
