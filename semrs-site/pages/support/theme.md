---
title: SEMRS Theme Manual: Header Builder, Layouts & Page Options
description: Step-by-step guide to the free SEMRS theme: logo, header and footer builder, sticky and transparent header, colours, layouts, blog features, patterns and per-page options.
order: 1
---
All theme settings live in **Appearance → Customize → SEMRS Theme**. Changes preview live and go public only when you click **Publish**.

## 1. Logo and site identity

1. Customize → Site Identity → Select logo. A square 512 × 512 px PNG or WebP works best; wide logos should be at least 320 px wide.
2. Customize → SEMRS Theme → Logo & header button → set **Logo width on desktop** and **on mobile**. Square logos get compact sizes automatically.
3. Header height controls the main header row.

## 2. Header builder

Customize → SEMRS Theme → **Header builder**.

The header has three rows: **Top bar**, **Main header** and **Below header**. Each row has a Left, Centre and Right zone.

- **Drag** an element from one zone to another, or use **+ Add element** in a zone.
- Click **×** to remove an element. Each element can be used once.
- A row with no elements is not printed at all (no extra code on your site).

| Element | What it shows | Where to set it |
|---|---|---|
| Logo / site title | Your logo, or the site title if there is no logo | Site Identity |
| Primary menu | The main menu (becomes the phone menu panel) | Appearance → Menus → Primary menu |
| Secondary menu | Small one-level links, ideal in the top bar | Appearance → Menus → Secondary menu (top bar) |
| Button / Second button | Call-to-action buttons | Logo & header button / Header builder |
| Search | A search icon that opens a search box | – |
| Social icons | Your social profiles in brand colours | Social profiles |
| Phone & email | Click-to-call and click-to-email links | Header builder → Phone / Email |
| Text / HTML 1 and 2 | Short text, such as opening hours or an offer | Header builder |

**SEMRS Pro adds** two header elements: a dark / light switch and a language switcher (see [SEMRS Pro](/support/pro/)).

**Header options in the same section:** sticky header, shrink after scrolling, transparent header (Off, Front page only, All pages), white text while transparent, header background, top bar colours, show the top bar on phones, show the header button next to the menu icon on phones.

### Phones and tablets

Below 922 px wide the header shows the logo, search (if added) and the menu icon. The menu opens as a full-width panel with your header button at the bottom. Turn on **Show the top bar on phones** if you want contact details there too.

## 3. Footer builder

Customize → SEMRS Theme → **Footer builder** arranges the bottom bar: copyright, social icons, footer menu, phone & email, and a text area. Widget columns above it are set in **Footer** (1 to 4 columns) and filled in Appearance → Widgets.

Copyright text accepts `{year}` and `{site}`.

## 4. Colours, typography and layout

- **Colours:** primary, accent, text on accent, body text, links, background. Defaults pass WCAG AA contrast; check contrast again if you change them.
- **Typography & width:** system fonts (fastest) or self-hosted Inter, base size, container width.
- **Layout & sidebar:** right, left or no sidebar for posts, pages and archives; card grid or list archives; sticky sidebar.

## 5. Blog features (all free)

Customize → SEMRS Theme → Blog & posts: featured image on posts, previous/next links, reading progress bar, "Updated on" date, author box and related posts.

The author box shows the author's name, avatar and biographical info from Users → Profile. To add "Reviewed by", add a custom field named `semrs_reviewed_by` to the post.

## 6. Page options (per page or post)

In the editor sidebar open **Page options**:

- Hide the page title (your content must then contain its own H1).
- Hide the header or the footer on this page (useful for landing pages).
- Transparent header: Default, On or Off.
- Sidebar: Default, Right, Left or None.

Templates: **Full width** for landing and service pages, **Blank canvas** for a page with no header or footer at all.

## 7. Section patterns

In the editor click **+** → Patterns → **SEMRS sections**: Hero, Hero with illustration, Services, How it works, Pricing, FAQ and Call to action. Insert one, then replace the text with your own. Prices and claims in patterns are examples: change them to your real offers.

## 8. Child theme

SEMRS Child SEO adds brand colours, a Call / WhatsApp bar on phones and a WhatsApp button. Set the numbers in Customize → SEMRS Theme → Contact & conversion. Put your own CSS in the child theme so theme updates never overwrite it.
