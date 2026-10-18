# Responsive Spec — Mobile, Tablet, Desktop

Claude Code is responsible for every page and tool rendering correctly on all three. Each page is screenshot at the test sizes below before it reaches you.

## Breakpoints (aligned with Astra's defaults to avoid conflicts)

| Device | Width | Astra setting |
| --- | --- | --- |
| Mobile | ≤ 544 px | Astra mobile breakpoint 544 |
| Tablet | 545–921 px | Astra tablet breakpoint 921 |
| Desktop | ≥ 922 px | — |
| Wide desktop | ≥ 1200 px | Sticky ad sidebar on tool pages only |

Content max width 1200 px; reading text max 72 characters per line (~720 px).

## Layout per device

| Element | Mobile | Tablet | Desktop |
| --- | --- | --- | --- |
| Header | Logo + hamburger + small "Free Review" button | Logo + hamburger + button | Full menu + dropdowns + button |
| Hero | 1 column, headline 32–36 px | 1 column, 40 px | 2 columns (copy / visual), 48–56 px |
| Service/tool cards | 1 per row | 2 per row | 3–4 per row |
| Tool UI | Input above, result below | Input above, result below (wider) | Input left, result right (50/50) |
| Pricing cards | Stacked, recommended first | 2 per row | 3 in a row |
| Footer | 1 column, accordions for link groups | 2 columns | 4 columns |
| Tool page ads | In-content 300×250/responsive; optional anchor | In-content responsive | In-content + sticky 300×600 sidebar at ≥ 1200 px |
| Tables (guides) | Horizontal scroll inside a wrapper (page itself never scrolls sideways) | Full | Full |

## Rules

- Fluid type with `clamp()`; body text min 16 px (also stops iOS zooming into form fields).
- Tap targets ≥ 48×48 px with ≥ 8 px spacing.
- Buttons full-width on mobile inside forms and tools.
- No horizontal page scroll at 320 px width.
- No hover-only features. Tooltips also open on tap/focus.
- Visible focus outline for keyboard users; WCAG AA contrast.
- Images use `srcset`/`sizes` (WordPress core does this) with set width/height.
- `prefers-reduced-motion` respected; dark mode not offered at launch (keeps brand consistent).
- Sticky elements: only the header (compact, ≤ 64 px on mobile) and mobile anchor ad; never both covering more than 20% of the screen.

## Test matrix (every page, before your review)

| Device class | Viewport | Represents |
| --- | --- | --- |
| Small phone | 360 × 800 | Android (Samsung A-series) |
| Phone | 390 × 844 | iPhone 13–16 |
| Tablet portrait | 768 × 1024 | iPad |
| Tablet landscape | 1180 × 820 | iPad Air landscape |
| Laptop | 1366 × 768 | Most common laptop |
| Desktop | 1920 × 1080 | Monitor |

Checks per size: no overflow, menu works, tool works by touch, form submits, ads don't cover content, CLS ≤ 0.1.
Browsers: Chrome, Safari (iOS), Edge, Firefox, latest versions.
