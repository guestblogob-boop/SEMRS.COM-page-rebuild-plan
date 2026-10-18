---
title: SEMRS Blocks Manual: Every Block Explained
description: How to use the free SEMRS Blocks plugin: containers, tabs, accordions, galleries, testimonials, table of contents, icons, post grids, pricing, contact form, visibility, and the Pro blocks, popups and display conditions.
order: 2
---
SEMRS Blocks adds blocks to the WordPress editor. They work in any theme. Each block is drawn on the server, so visitors and search engines get real HTML, and its CSS or script loads only on pages that use it.

## Find and manage blocks

- In the editor click **+** and open the **SEMRS** category, or type `/` followed by the block name.
- Select a block to see its settings in the right sidebar. The preview updates as you change settings.
- **SEMRS → Blocks** lists every block with a short description. Use the switches to turn off blocks you never use, then click **Save changes**. Use the search box and the Free / Pro / category filters to find a block quickly.

## Free blocks

| Block | Use it for | Key settings |
|---|---|---|
| Icon | A single icon, optionally linked | 48 icons, size, colour, circle or rounded background, accessible label |
| Icon List | Feature lists and checklists | One item per line, icon, colour, 1–4 columns |
| Fancy Heading | Eye-catching headings | Typing (rotating words), gradient, marker highlight, curved underline, outline; heading level |
| Posts | Blog sections on any page | Grid, list, masonry, overlay, compact; post type, category, number, order, offset; image shape; what to show |
| Countdown Timer | Launches, offers, events | End date (your site time zone), boxes or inline, message when finished |
| Pricing Card | Plans and packages | Price, period, features (start a line with - for "not included"), button, highlight, badge |
| Service / Feature Card | Services and features | Icon or image, title, text, price line, link, three styles |
| Contact Form | Enquiries and quotes | See below |
| Free Media Sites | A resource list for your visitors | Photos, video, illustrations & icons, animations, 3D; each site has a licence note |

## Layout blocks (free)

| Block | Use it for | How it works |
|---|---|---|
| Container (Section / Row / Grid) | Full-width sections, rows and grids | Holds any blocks. Arrange them as a stack, a row that wraps on phones, or a grid (1–6 columns on desktop, 1–4 on tablets, 1 on phones). Background colour, ready-made gradients or an image with an overlay, padding, minimum height, rounded corners, content width (full, site container or reading width) and the HTML element (section, header, aside…). |
| Tabs | Packages, features, before/after | Add a Tab for each tab and put any blocks inside it. Styles: underline, pills, boxed, vertical. Visitors can use the arrow keys, and search engines see the content of every tab. |
| Accordion / FAQ | Questions and answers, long details | Add an Accordion item for each question. Opens and closes without JavaScript. Optional "only one open at a time". With SEMRS Core SEO the items become FAQ schema; switch "Use as FAQ schema" off for content that is not a real FAQ. |
| Gallery & Lightbox | Portfolios, photo sets | Grid (same size) or masonry, 1–6 columns, image shape, rounded corners, captions. The lightbox works with the keyboard and swipe and returns focus to the image when it closes. |
| Testimonial | Customer quotes | Quote, name, role, photo, optional stars and a link to the original review. Use only genuine testimonials you have permission to publish. No review schema is added: Google does not show stars for reviews a business publishes about itself. |
| Table of Contents | Long guides | Lists the H2, H3 (and optionally H4) headings of the page with jump links and gives the headings matching ids. Save the page to refresh the list in the editor. |

In the editor, tabs and accordion items are shown one under another as cards so you can edit everything at once; on the site they turn into real tabs and accordions.

### Website Showcase

Cards like the WordPress.org theme directory: a large preview image with a **Desktop / Mobile** switch, a name, a badge (Free, Pro, New, Installed), a short description and two buttons. Add a **Showcase card** for each theme, template or client website, choose a desktop screenshot and, optionally, a phone screenshot (390 × 844 px or the same shape works best). Use 1–4 columns; set the block to Wide or Full width for the best look.

## Visibility (free, on every block)

Select any block, open **Visibility** in the sidebar and hide it on phones (under 782 px), tablets (782–1023 px) or desktops (1024 px and wider). It works with page caching because it uses CSS. Hidden blocks look faded in the editor so you can still edit them.

## Contact form

The form has name, email, phone with a **country picker** (flag and dialling code), website, service and budget choices, project requirements, a WhatsApp opt-in and a consent box.

1. Insert the **Contact Form** block.
2. In the sidebar set: the recipient email (empty = the site admin email), email subject, success message, optional redirect to a thank-you page, default country, service and budget options, and the consent text.
3. Publish and send a test message.

Every message is saved under **Leads** in the dashboard and emailed to you with Reply-To set to the visitor. If you use Google Analytics 4, a `generate_lead` event is sent.

**Spam protection is built in:** a hidden honeypot field, a signed time check (bots that submit instantly are ignored), a limit of 5 messages per hour per connection, and no more than three links per message.

**Email delivery:** many hosts block PHP mail. If test messages do not arrive, install an SMTP plugin and connect your email provider; the leads are still saved in the dashboard either way.

**Privacy:** the form stores what visitors type plus the page address. Mention this in your privacy policy and keep the consent box on. See [Policies](/support/policies/).

## Pro blocks (need SEMRS Pro)

| Block | What it adds |
|---|---|
| Slider / Carousel | Image slider or post carousel, 1–4 slides per view, arrows, dots, keyboard and swipe, autoplay that pauses on hover and for visitors who prefer reduced motion |
| Animated Counter | Numbers that count up when scrolled into view; the real number is in the HTML for search engines |
| 3D Model Viewer | .glb / .gltf models with rotate, zoom and "view in your room" AR on phones |
| Scroll animations | Fade, zoom or slide on any block: block sidebar → Advanced → Scroll animation |
| Display conditions | Show any block only to logged-in or logged-out visitors, certain roles, between two dates, or only on the front page, posts, pages, archives, search or 404 pages: block sidebar → Display conditions |
| Popups | SEMRS → Popups: build a popup with any blocks, choose centred, bottom bar or corner slide-in, and open it after a delay, after scrolling, on exit intent or when a link to `#semrs-popup-ID` is clicked |

Without SEMRS Pro these appear with a **PRO** badge. You can insert them to try the layout, but they print nothing on your site until Pro is active. To hide them, untick "Show SEMRS Pro blocks in the editor" in SEMRS → Blocks.

## Tips

- Images: compress before uploading and always fill in the alt text.
- Use one H1 per page. Fancy Heading defaults to H2; switch to H1 only for the page's main heading.
- Countdown timers must be honest: do not reset them to create false urgency.
