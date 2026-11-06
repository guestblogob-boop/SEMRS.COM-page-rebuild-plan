---
kind: tool
parent: tools
order: 20
h1: Free Color Contrast Checker
title: Free Color Contrast Checker – WCAG AA & AAA | SEMRS
description: Check text and background color contrast against WCAG 2.2 AA and AAA for text, large text and buttons, and get the nearest passing color. Free.
focus: color contrast checker
schema: WebApplication
---
This free color contrast checker tells you whether text on your website is easy to read for everyone. Enter a text color and a background color as hex codes, RGB values or color names, or pick them with the color pickers. You see the contrast ratio, a live sample, a pass or fail for each WCAG 2.2 level, and, when a color fails, the nearest shade that passes.

[tool]

[ad]

## Why contrast matters

Low-contrast text, such as light gray on white, is hard to read for people with low vision or color blindness, for older readers and for anyone using a phone in bright sunlight. Good contrast makes your site easier for everyone to use.

It also matters for your business. Accessibility laws such as the Americans with Disabilities Act in the US and the European Accessibility Act in the EU apply to many websites, low contrast is one of the most common accessibility errors, and tools such as Lighthouse and PageSpeed Insights lower the accessibility score when they find it.

## The WCAG contrast levels

The Web Content Accessibility Guidelines (WCAG) 2.2 set minimum contrast ratios between text and its background:

- **Normal text, level AA:** at least 4.5:1. This is the level most laws and policies refer to.
- **Large text, level AA:** at least 3:1. Large means about 24 pixels (18 point) and larger, or about 19 pixels (14 point) bold and larger.
- **Normal text, level AAA:** at least 7:1, the enhanced level.
- **Large text, level AAA:** at least 4.5:1.
- **Buttons, icons and form borders:** at least 3:1 against what is around them (WCAG 1.4.11, non-text contrast).

The ratio runs from 1:1 (no contrast, the same color) to 21:1 (black on white).

## How to use the color contrast checker

1. Enter the text color: a hex code such as #5b6575, an RGB value such as rgb(91, 101, 117) or a color name. Or use the color picker.
2. Enter the background color in the same way.
3. Read the contrast ratio and the level it reaches for normal text.
4. Check the table for large text and for buttons and icons.
5. If a level fails, use the suggested color, which keeps your shade but darkens or lightens it just enough to pass.
6. Use **Swap** to test light text on a dark background the other way around.

## Worked example

A yoga studio's website uses light gray text (#999999) on white for its class descriptions. The checker shows 2.84:1, which fails AA for normal text. The suggested color is #767676, which reaches 4.54:1 and still looks like the same soft gray. The studio updates the color in its theme settings, and the PageSpeed accessibility warning about low contrast disappears.

## Best practices

- Check every text and background pair in your brand palette once and save the passing combinations.
- Pay attention to text on colored buttons, links on colored backgrounds and placeholder text in forms.
- Check text placed over photos at its weakest point, or add a dark overlay behind it.
- Test both light and dark mode if your website offers both.
- Do not rely on color alone to show meaning. Add a label, an icon or an underline to links and errors.

## Common mistakes

- Light gray body text chosen because it "looks elegant".
- White text on bright yellow, orange or light green buttons.
- Checking only the main text color and forgetting links, captions and footers.
- Assuming a font is large enough to count as "large text" when it is not.

## Tips for local businesses

Many local customers find you on a phone, sometimes outdoors. Make your phone number, address and call-to-action buttons high contrast so they are easy to read in any light. If your logo colors are pale, use a darker shade of the same color for text and buttons instead of changing your brand.

The rule is explained in detail in [Understanding Success Criterion 1.4.3: Contrast (Minimum)](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html).

## Color contrast checker: frequently asked questions

Q: What contrast ratio do I need?
A: At least 4.5:1 for normal text and 3:1 for large text and for buttons, icons and form borders. That is WCAG level AA, the usual legal and policy standard.

Q: Is AAA required?
A: Usually not. AAA (7:1 for normal text) is an enhanced level. Aim for AA everywhere and AAA for long reading content where you can.

Q: How is the ratio calculated?
A: WCAG compares the relative luminance of the two colors: (lighter + 0.05) ÷ (darker + 0.05). The checker uses the same formula.

Q: Does contrast affect SEO?
A: Not directly, but it affects accessibility scores in Lighthouse and PageSpeed Insights and how easily visitors can read and act on your page.

Q: Can I check transparent colors?
A: The checker works with solid colors. For semi-transparent text or backgrounds, check the final visible color as it appears on the page.

## Related free tools

- [Image Compressor & WebP Converter](/tools/image-compressor/): make images lighter for faster pages.
- [On-Page SEO Checker](/tools/on-page-seo-checker/): check images for alt text and other page basics.
- [Readability Checker](/tools/readability-checker/): make the words as easy to read as the colors.

> Want a fast, accessible website that turns visitors into customers? [See SEMRS Website Design →](/services/web-design/)
