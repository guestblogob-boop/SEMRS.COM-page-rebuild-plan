=== SEMRS Shops ===
Contributors: semrs
Tags: shop, digital downloads, printables, crochet patterns, starter sites
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Requires Plugins: semrs-blocks
Stable tag: 1.1.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Ready-made digital-download and crochet-pattern shops for SEMRS Blocks, in four styles: brand, maker, listing and catalog store.

== Description ==

SEMRS Shops adds two complete shops to SEMRS → Templates, each in four storefront styles (8 starter sites). They are kept in this separate plugin so SEMRS Blocks stays light for sites that do not sell.

= Storefront styles =
* Brand store: a big hero, collection tiles and a featured grid.
* Maker shop: an auto-rotating banner slider, a shop header, announcement, sections sidebar beside all listings, about and shop policies.
* Listing store: categories beside listing rows and store information.
* Catalog store: a category bar, product rows per category, a comparison chart and Q&A.

Every style is a complete site: home, shop, category pages, a page for every product, about, policies, FAQ, blog and contact.

= Settings =
SEMRS → Shops (or Tools → SEMRS Shops without SEMRS Core):
* Currency for every shop product (USD, EUR, GBP, CAD, AUD, NZD, INR, PKR, AED, JPY).
* Checkout link for every buy button, with {product} (page slug) and {title} placeholders; the chosen options are added to the link. A link set on a single Product block wins.
* Before a checkout is connected, buy buttons show "Ask about this item" and link to your contact page with the product name, instead of a disabled button.

= Marketplace exports =
SEMRS → Shops → Exports downloads each catalogue as a Shopify product CSV (one row per variant, digital settings, draft status) and an Etsy listing sheet (13 tags per listing, variations, personalisation notes). eBay and Amazon files are not offered because both marketplaces restrict downloadable products such as printables and patterns.

= Prices =
Example prices are rough estimates from typical 2026 marketplace listings: crochet patterns about $3.49–$9.99, SVG and design bundles from $3.99, planners about $8.99, custom pet portraits from $24.99. Check them against current listings before publishing.

= Digital Downloads Shop =
Product pages for Christmas SVG bundles, printable party packs, gift tags, Christmas cards, personalised family shirt designs, crochet patterns, custom family and pet portraits, personalised wall art and name prints, dog breed and cat SVGs, watercolour bird clipart, pet memorial prints, pet party printables, t-shirt, cap and hat, tumbler, mug, wall decal and car decal designs, planner stickers, coloring pages, worksheets, digital workbooks and planners, budget spreadsheets, holiday bundles, nursery wall art, wedding invitations, Canva and Notion templates.

= Crochet Pattern Shop =
Pattern pages for amigurumi animals and birds (bear, bunny, dinosaur, reindeer, cat, puppy, owl, parrot, chicken), pet accessories (dog sweater, bandana, cat toys, pet bed), blankets (chunky, granny square, temperature, baby), wearables (beanie, cardigan, scarf, bucket hat, baby booties), home decor, Christmas and seasonal, bags, car accessories (mirror charm, steering wheel cover, seat belt covers, cup holder coasters), a beginner's guide and a bundle. Includes a Crochet help page with US/UK stitch terms, hook sizes and skill levels.

= Every product page =
* SEMRS Product block with color swatches, sizes, formats, licenses or plan cards, personalisation fields (names, dates), bundle add-ons and bulk prices; live price, savings and total.
* The buy button opens your own checkout (WooCommerce, Shopify, Stripe Payment Links, Lemon Squeezy, Gumroad…) with the chosen options added to the link.
* "What you get", "How it works", license and refund sections, and "You may also like" products.
* Product schema with price and availability only (no ratings).

Shop home, all-products page, category pages, FAQ and contact are included. All pages are also editor patterns. Prices, policies and delivery times are examples: replace them with your own before publishing. Product images are original SEMRS artwork (GPL).

== Installation ==

1. Install and activate the free SEMRS Blocks plugin.
2. Install and activate SEMRS Shops.
3. Go to SEMRS → Templates, choose Digital Downloads Shop or Crochet Pattern Shop and click Import.

== Changelog ==

= 1.1.3 =
* Smaller download: images recompressed (same look)

= 1.1.2 =
* US spelling in labels, help text and readme

= 1.1.1 =
* New Maker shop previews (desktop and phone) with the banner slider

= 1.1.0 =
* Maker shop home opens with an auto-rotating banner slider (shop welcome and featured items); needs SEMRS Blocks 1.3.14
* Settings: shop currency, one checkout link for all products, and "Ask about this item" instead of a disabled button before checkout is connected
* Shops screen with Settings and Exports tabs (under SEMRS, or Tools without SEMRS Core)
* Storefront styles renamed to brand store, maker shop, listing store and catalog store (no company names)
* Example badges and headings say "Featured" instead of "Trending" or "Popular right now", so a new shop makes no claims it cannot back up
* Comparison chart prices use the shop currency

= 1.0.3 =
* Design previews are WebP (about 25% lighter); JPG previews still work

= 1.0.2 =
* Declares WP Consent API support (functional storage only, no trackers)

= 1.0.1 =
* Settings links on the Plugins screen

= 1.0.0 =
* First release: Digital Downloads Shop (35 products) and Crochet Pattern Shop (36 patterns), each in Shopify, Etsy, eBay and Amazon storefront styles (8 starter sites).
* Tools > Shop exports: Shopify product CSV and Etsy listing sheet.
* Prices based on 2026 marketplace medians (estimates to review).
