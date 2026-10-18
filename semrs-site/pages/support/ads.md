---
title: SEMRS Ads Manager & AdSense Policy Guide
description: Place AdSense, Ad Manager or other network ads with the free SEMRS Core ads manager, and keep your site within Google AdSense program policies.
order: 5
---
The Ads manager is part of the free SEMRS Core plugin. It places your ad codes in safe positions and keeps them away from pages where ads break Google's rules or hurt conversions.

## 1. Set up

1. **SEMRS → Features** → switch on **Ads Manager** → **Settings** (or **SEMRS → Ads**).
2. Enter your AdSense publisher ID (`ca-pub-…`). Leave "Load the AdSense script" on unless another plugin already loads it.
3. For each position choose the network (AdSense or custom code), paste the ad slot ID or the full code, pick the format and the devices.
4. Switch on **Enable ads** and save.
5. Optional: switch on **Manage ads.txt** and paste the lines your networks give you. Check that `yoursite.com/ads.txt` opens.

## 2. Positions

| Position | Where it appears |
|---|---|
| Before content | Under the post title |
| After paragraph N | After the paragraph number you choose (default 2) |
| Middle of content | Halfway through long articles |
| End of content | After the article |
| Shortcodes | `[semrs_ad slot="tool"]`, `[semrs_ad slot="sidebar"]` and `[semrs_ad slot="manual"]` anywhere you place them |

In-content ads are inserted only between normal paragraphs, never inside tables, lists, headings or blocks, and only on posts with at least 300 words (you can change this). A maximum number of in-content ads and a minimum gap between them stop overcrowding.

## 3. Built-in safety

- **No ads on conversion and legal pages** by default: services, pricing, contact, quote, thank-you, about, privacy, terms, cookie and disclosure pages, plus the front page and search results. Edit the list in Excluded paths.
- **Turn ads off for one post** in the editor's SEMRS Ads box.
- **Labelled:** every slot shows a small "Advertisement" label (you can change the text).
- **No layout shift:** space is reserved for each ad so content does not jump (good for Core Web Vitals).
- **Lazy loading** for ads below the screen, and ads hidden for logged-in admins so you do not click your own ads by mistake.

## 4. AdSense policy checklist

Google's [AdSense Program policies](https://support.google.com/adsense/answer/48182) apply to every site. In short:

- Never click your own ads or ask anyone to click them. No "click here" or arrows pointing at ads.
- Do not place ads where they can be mistaken for menus, downloads or buttons, or where they cover content.
- No ads on pages without real content: empty pages, thank-you pages, login pages, error pages, or pages that are mostly ads.
- Content must follow the [Google Publisher Policies](https://support.google.com/adsense/answer/10502938): no adult, violent, hateful, dangerous or copyrighted content you do not own.
- Have a privacy policy that explains cookies and advertising, and use a Google-certified consent tool (CMP) for visitors in the EEA, UK and Switzerland.
- Do not use pop-ups or redirects that force visitors to see ads.
- Add `ads.txt` and keep it up to date.

SEMRS defaults follow these rules, but you are responsible for your content and placements. Google can change its policies at any time; check the official pages linked above.

## 5. Ad Manager (AdX) and other networks

Choose **Custom code** for a position and paste the full tag from Google Ad Manager or your network. Put header scripts (for example GPT) in **Head code**. Use only networks you have an account with and add their lines to ads.txt.

## 6. SEMRS Pro ad features

With SEMRS Pro active, a **SEMRS Pro** section appears on **SEMRS → Ads**, and two extra rows appear in the positions table.

**More placements**

- **Pro: repeat in content:** another ad every N paragraphs after the first one. Your "Maximum in-content ads per page" and "Minimum paragraphs between ads" still apply, so long articles never get crowded.
- **Pro: between posts on blog and category pages:** an ad after every N posts in lists, at most 1–5 per page.

**Targeting and A/B rotation (per position)**

- **Only in categories / Never in categories:** for example, show a finance ad unit only in your money articles.
- **Older than N days:** keep new posts ad-free while they collect links and shares, then start earning.
- **From / Until:** run a position between two dates, for example a seasonal sponsor.
- **Hide for logged-in users:** members or customers see fewer ads.
- **Variant B:** a second AdSense slot ID or custom code. Each page view shows A or B at random; compare the two ad units in your AdSense reports and keep the better one.

**Click protection** (on by default with Pro): if a visitor clicks ads 3 times within 24 hours (you can change both numbers), that visitor gets no ads for 7 days. Ad requests are paused for them, including Auto ads. This protects your AdSense account from accidental or deliberate invalid clicks. The count is kept only in the visitor's browser.

**Ad-blocker message** (off by default): a small, dismissible note asking ad-blocker users to support the site. It never blocks or hides content, and once closed it stays away for 7 days.

**Per-page position switches:** in the post editor, the **Ad positions on this page** box lets you turn single positions off for that page, for example keep the sidebar ad but drop in-content ads on a sensitive article. The free **Disable ads on this page** switch still turns everything off.

SEMRS Pro never refreshes ads, never hides ads after they load, and never places ads in popups, which keeps these features within AdSense policies.
