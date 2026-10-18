# SEMRS Site (semrs.com only)

Never give this plugin to clients and never list it on WordPress.org. It holds what only semrs.com uses:

- **Keyword Ideas Generator** (free tool on semrs.com): script in `assets/keyword-ideas-generator.js`, page content built from `content-src/tools/keyword-ideas-generator.md` (front matter `site: semrs`) by `node content-src/build.mjs` into `content/`. Install the page: Tools → semrs.com pages (creates a draft under /tools/).
- **Hero with photo and keyword tool** pattern (`patterns/hero-photo.php`): AI-generated photo `assets/images/hero-photo.jpg` (60 KB) in an arch frame, overlay `hero-photo-overlay.svg` (SEO trend, performance, #1 badge), and the keyword strip that opens the tool with results.
- Styles: `assets/site.css`.

Needs SEMRS Tools (for the tool) and the SEMRS theme (for the hero styles).
