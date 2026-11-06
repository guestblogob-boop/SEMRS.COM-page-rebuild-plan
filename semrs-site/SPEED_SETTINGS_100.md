# SEMRS Speed settings that scored 100 / 100 / 100 / 100 (PageSpeed Insights, 25 Oct 2026)

Mobile: FCP 1.1 s, LCP 1.1 s, TBT 0 ms, CLS 0, SI 2.0 s. Desktop: FCP 0.3 s, LCP 0.3 s, TBT 60 ms, CLS 0.002, SI 0.9 s. Agentic Browsing 3/3.
Keep these on. Re-check after every release (Core 1.3.41 / Pro 1.5.9 live 5 Nov 2026; new modules are off by default and do not change these).

## SEMRS → Speed (Core)
- ON: emojis, embeds, dashicons, jQuery Migrate, head cleanup, heartbeat, revisions, YouTube click-to-play, preload first image, resource hints, per-block CSS, inline small CSS, hover prefetch, defer scripts
- OFF: async (non-blocking) CSS, XML-RPC removal
- Defer exclude: `jquery`, `jquery-core`

## Speed Pro
- Delay scripts until interaction: ON, timeout 5 s. List: googletagmanager.com, google-analytics.com, gtag(, connect.facebook.net, fbq(, clarity.ms, static.hotjar.com, analytics.tiktok.com, snap.licdn.com, embed.tawk.to, client.crisp.chat, widget.intercom.io, js.hs-scripts.com, widget.trustpilot.com, trustpilot-reviews, trustbox
- Unload unused plugin files: ON
- Remove unused CSS: ON, safelist starts: is-*, has-*, sp-scrolled, sp-submenu*, semrs-pfv*, semrs-lightbox*, semrs-cf*, semrs-cd*, semrs-anim*, semrs-ad*, adsbygoogle, active, open, show, visible, toggled, current*, focus* … (rest not captured; read the live screen before changing)

## Warnings from the 25 Oct report (status 6 Nov 2026)
- Unused preconnect to www.googletagmanager.com: gone (only a dns-prefetch hint remains).
- Non-composited chart animation: fixed in theme 1.3.20 (transform + opacity instead of stroke-dashoffset).
- Desktop CLS 0.002 in the header top bar: unscored, still 100; re-check with PageSpeed after each release.
- LCP render delay of the hero paragraph: within the 100 score (LCP 1.1 s mobile, 0.3 s desktop).

## New settings switched on (6 Nov 2026)
- Security: limit log-ins (5 tries / 15 min), generic log-in error, hide WordPress version, security headers (also on cached pages from Core 1.3.43), file editor off. Off: HSTS, hide usernames, SVG.
- Privacy: cookie banner on, opt-in, Consent Mode v2 + ads data redaction, consent log, cookie settings button. Banner and Consent Mode scripts carry data-no-delay so Speed Pro never delays them.
- Social images: on, but inactive until a .ttf font is set (the server has none).
