---
title: SEMRS Blocks Manual: Every Block Explained
description: SEMRS Blocks manual: how to use 50+ blocks, from buttons, maps and FAQs to how-to guides, popups and pricing switches, plus the rules that keep them safe.
order: 2
seo_title: SEMRS Blocks Manual 2026: Easy Guide to Every Block
focus: semrs blocks
image: support-blocks.webp
---
SEMRS Blocks adds new blocks to the WordPress editor. A block is one piece of a page, like a heading, a picture or a button. The blocks work with any theme. Each one loads its code only on pages that use it, so your site stays fast.

## How to add SEMRS Blocks to a page

1. Open a page in the editor.
2. Click the **+** button. Open the **SEMRS** group.
3. Or type `/` and the block name, for example `/tabs`.
4. Click the block to select it. Its settings appear in the right sidebar.

To turn off blocks you never use, go to **SEMRS → Blocks**, switch them off and click **Save changes**.

![SEMRS Blocks at a glance: layout, content, sell and posts blocks are free; movement and control blocks are Pro](blocks-map)

## Content blocks (free)

| Block | Use it for |
|---|---|
| Icon | One icon, with or without a link |
| Icon List | Lists with check marks or other icons |
| Fancy Heading | Headings with typing words, gradients or highlights |
| Posts | A grid or list of your latest posts |
| Countdown Timer | Count down to a launch or an event |
| Pricing Card | Show a plan with its price and features |
| Service Card | Show a service with an icon, text and a link |
| Contact Form | Let people send you a message |
| Buttons | A row of buttons in three styles, with an icon if you like |
| Notice / Alert | An info, tip, warning or important box. Visitors can close it if you allow it. |
| Divider | A line between sections, with text or an icon in the middle |
| Star Rating | Stars with halves, for a score you give |
| Steps / Process | Numbered steps that explain how something works |
| Timeline | Dates and events on a line: your history or a roadmap |
| Progress Bars | Bars with a label and a percentage |
| Comparison Table | Compare features with ticks and crosses. One column can stand out. |

**How to fill in lists:** many blocks use one line per item, with parts split by a `|` sign. For example, Buttons uses `Text | Link | style | icon`. The hint under each box in the sidebar shows the order.

## Business and trust blocks (free)

| Block | Use it for |
|---|---|
| Call to Action | A colored box with a headline and one or two buttons |
| Team Member | Photo, name, role, short bio and profile links |
| Business Hours | Your opening hours. Lines with "Closed" look gray. |
| Map | A Google Map of your address. No API key needed. |
| Social Icons | Links to your profiles: one per line, the network name and then the link |
| Logo Grid | Logos of clients or partners in a neat grid |
| Share Buttons | Let visitors share the page. Plain links, with no tracking code. |

**The map loads only when someone clicks it.** Until then no Google code runs and no Google cookies are set. This keeps the page fast and helps with privacy laws like the GDPR. You can switch this off in the sidebar, but then say in your privacy policy that the page loads Google Maps.

## SEO blocks (free)

| Block | What it does |
|---|---|
| How-To Guide | Steps, time, tools and materials, with HowTo schema |
| Review Box | Your review of a product: score, pros, cons and a verdict, with Review schema |
| Breadcrumbs | Home › Category › Page links at the top of a page |

**How-To schema:** search engines and AI answers can read the steps. Google no longer shows HowTo rich results, so do not expect special looks in Google. The steps still help readers.

**Review schema:** use the Review Box only for your honest review of someone else's product. Google ignores reviews a business writes about itself, so do not use it to review your own company.

**Breadcrumbs schema:** SEMRS Core SEO already adds breadcrumb schema to every page. Keep the block's schema switch off when SEMRS Core is active, so the schema is not added twice.

## Layout blocks (free)

These blocks hold other blocks. Use them to build sections.

| Block | What it does |
|---|---|
| Container | A section, row or grid. Add a background color or image. On phones, rows stack one under another. |
| Tabs | Put content in tabs. Visitors click a tab to see it. |
| Accordion / FAQ | Questions that open and close. They can also become FAQ schema for Google. |
| Gallery | A photo grid. Click a photo to see it large. |
| Testimonial | A quote from a real customer. Use only real reviews you may publish. |
| Table of Contents | A list of the headings on the page, with jump links |
| Website Showcase | Cards with a desktop and phone preview of a website |

**Tip:** in the editor, tabs and accordions show all their parts at once. This makes them easy to edit. On your site they work as real tabs.

## Hide a block on phones or computers (free)

Select any block. Open **Visibility** in the sidebar. Choose to hide it on phones, tablets or computers. Hidden blocks look faded in the editor, so you can still find them.

## Set up the contact form

![SEMRS Blocks contact form: visitor sends it, spam check, saved in Leads, email to you](blocks-contact-flow)

1. Add the **Contact Form** block to your page.
2. In the sidebar, type the email address that should get the messages.
3. Write a short thank-you message.
4. Publish the page. Send yourself a test message.

Every message is saved under **Leads** in your dashboard. You also get an email. When you click Reply, your answer goes straight to the visitor.

**Spam protection is built in.** Bots that send the form too fast are ignored. Each person can send only a few messages per hour.

**No email arriving?** Many web hosts block normal PHP mail. Install an SMTP plugin and connect your email account. Your messages are still saved in Leads either way.

**Privacy:** the form saves what people type. Say this in your privacy policy, and keep the consent box switched on.

## Pro blocks (need SEMRS Pro)

| Block | What it adds |
|---|---|
| Slider / Carousel | Pictures or posts that slide |
| Animated Counter | Numbers that count up when you scroll to them |
| 3D Model Viewer | 3D models people can turn and zoom |
| Scroll animations | Blocks that fade or slide in |
| Display conditions | Show a block only to some visitors or on some dates |
| Popup / Modal | A popup made from any blocks. It opens with a button or after some seconds. |
| Pricing Table with Switch | Several plans with a monthly / yearly switch |
| Sticky Bar | A slim bar at the top or bottom of the screen with a message and a button |
| Before / After | Drag a handle to compare two pictures |
| Image Hotspots | Points on a picture. Click one to read a short note. |
| Lottie Animation | Light moving pictures from a Lottie .json file |
| Logo / Text Marquee | Logos or words that scroll sideways without end |
| Video Popup | A picture with a play button. The video loads only when clicked. |
| Content Switcher | A switch between two contents, like "For shops / For agencies" |
| Data Table | A table people can sort and search |
| Animated Timeline | A timeline with icons. Each event slides in as you scroll. |
| Reviews Slider | Real customer reviews that slide, with stars and a link to each original |
| Trust Badges | A row of promises, like secure checkout or a refund period |

Without SEMRS Pro, these blocks show a **PRO** badge. You can try them in the editor. They show nothing on your site until Pro is active.

## Rules to follow when you use these blocks

Laws and search engine rules change. These are the main ones today. They are tips, not legal advice.

- **Reviews must be real.** Show only reviews from real customers, with their permission, and link to where they were posted. In the US the FTC has banned fake reviews and testimonials since 2024, and buying or inventing them can lead to fines. The EU has similar rules. This covers the Testimonial, Reviews Slider and Review Box blocks.
- **No made-up numbers.** Counters, ratings and progress bars must show true figures you can prove.
- **Countdowns must be honest.** Do not restart a timer or invent a deadline to rush people. Consumer laws in the US and EU treat fake urgency as a trick.
- **Popups must not block the page.** Google can rank pages lower when a popup covers the main content right after someone arrives from search. Open it with a button or after a delay, keep it small and always give it a close button. Cookie notices and age checks that the law requires are fine.
- **Sticky bars stay slim.** Keep them to one line so they never cover the content on phones.
- **Privacy for maps and videos.** The Map block waits for a click, and the Video Popup uses YouTube's privacy-enhanced mode and Vimeo's "do not track" option. Mention Google Maps, YouTube and Vimeo in your privacy policy.
- **Pictures and files.** Use only images, logos, animations and 3D files you may use. Check each license. Free Media Sites lists sources with their license notes.
- **Everyone can use it.** Fill in the alt text for every picture and the description for Lottie and 3D models. Moving blocks (Marquee, Animated Timeline, Lottie, sliders) stop moving for visitors who choose "reduce motion" in their device settings.

## Quick tips

- Make pictures small before you upload them. Always fill in the alt text.
- Use only one main heading (H1) per page.
- Show a price the same way everywhere: in the pricing blocks, at checkout and in the schema.

## SEMRS Blocks help and next steps

SEMRS Blocks are normal WordPress blocks, so the [block editor guide on WordPress.org](https://wordpress.org/documentation/article/wordpress-block-editor/) works for them too. Want a finished design? See the [templates guide](/support/templates/). Questions? [Contact us](/contact-us/).
