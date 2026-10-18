# SEMRS Child SEO

Brand layer for the free **SEMRS** theme (parent). Requires the SEMRS theme installed.

## What it adds
- SEMRS navy/gold brand tokens mapped onto the SEMRS theme's colour variables (rebrand = edit section 1 of `style.css`).
- The section components below now ship in the parent theme; this child only sets the SEMRS brand colours. Add a class under Block → Advanced → Additional CSS class:

| Class | Block | Purpose |
| --- | --- | --- |
| `semrs-section`, `semrs-section--muted`, `semrs-section--navy` | Group | Full-width page sections |
| `semrs-hero`, `semrs-eyebrow`, `semrs-lead` | Group / Paragraph | Hero area, small label, intro text |
| `semrs-card-grid` | Columns | Card grid (3 → 2 → 1 columns) |
| `semrs-badge`, `semrs-card-link` | inline / link | Label pill, "See more →" link |
| `semrs-process` + item `is-gate` | List | Numbered process with highlighted human approval step |
| `semrs-pricing-grid`, `semrs-pricing-card` (+ `is-recommended`), `semrs-price` | Columns / Column / Paragraph | Pricing cards |
| `semrs-cta-band`, `semrs-service-cta` | Group | Call-to-action band, soft service CTA box |
| `semrs-faq` | Group of Details blocks | FAQ accordion (SEMRS Core builds FAQ schema from it) |
| `semrs-tool…` | (semrs-tools plugin) | Free tool UI |

- Customizer → SEMRS Theme → **Contact & conversion**: phone, WhatsApp, sticky mobile CTA bar (hidden on `/tools/`), desktop WhatsApp button.

## Contrast (WCAG AA ≥ 4.5:1)
ink/white 14.68 · navy/white 16.52 · link/white 11.34 · muted/white 5.89 · gold-700/white 5.02 · navy on gold 7.36 · white on navy 16.52 · white on WhatsApp green #128c4b checked.
