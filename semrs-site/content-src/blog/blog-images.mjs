// Builds the blog images as WebP, following WRITING_GUIDE.md section 7:
//   - Free-stock photos (CC0 / free-license photo sites): cropped to 1200x675 and stamped with
//     the word "SEMRS" in plain text. Never the logo.
//   - SEMRS-made graphics (graphics.mjs) and generated images: no stamp, because the graphic
//     already carries the word SEMRS. Add the text stamp only when needed ({ stamp: true }).
// Writes content-src/blog/images/*.webp and images.json (alt, caption, credit).
//
//   node content-src/blog/blog-images.mjs        (needs sharp: npm i --no-save sharp)
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import sharp from 'sharp';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const OUT = path.join(ROOT, 'content-src/blog/images');
const PH = { free: path.join(ROOT, 'plugins/semrs-blocks/templates/photos'), pro: path.join(ROOT, 'plugins/semrs-pro/templates/photos') };
const { GRAPHICS } = await import('file:///' + path.join(ROOT, 'content-src/blog/graphics.mjs').replace(/\\/g, '/'));
fs.mkdirSync(OUT, { recursive: true });

// Text stamp: the word SEMRS, white on a soft dark pill, so it reads on any photo. Never the logo.
const SIZE = 30, SW = Math.round(SIZE * 4.1), SH = Math.round(SIZE * 1.6);
const STAMP = Buffer.from(`<svg xmlns="http://www.w3.org/2000/svg" width="${SW}" height="${SH}"><rect width="${SW}" height="${SH}" rx="${SH / 2}" fill="#0a1650" fill-opacity=".55"/><text x="50%" y="50%" dy=".35em" text-anchor="middle" font-family="Segoe UI, Arial, Helvetica, sans-serif" font-size="${SIZE}" font-weight="800" letter-spacing="2" fill="#ffffff">SEMRS</text></svg>`);

// slug: [photo file, photo set, alt, caption, credit, creator, landing]
const PHOTOS = {
	'financial-advisor-marketing': ['consultant-about.jpg', 'pro', 'Business advisors meeting around a laptop to review a client plan', 'Advisors who choose a clear niche can plan every channel around one audience.', 'Business Office', 'Direct Media', 'https://stocksnap.io/photo/business-office-IHBPBLYUFE'],
	'insurance-agency-marketing': ['clinic-post2.jpg', 'pro', 'A professional talking with an older client at home about her plans', 'Insurance is bought around life events, so marketing should meet people at those moments.', 'Caregiver Nurse', 'Direct Media', 'https://stocksnap.io/photo/caregiver-nurse-QQEQNVMB0I'],
	'seo-for-attorneys': ['consultant-hero.jpg', 'pro', 'A client consultation across a desk in a professional office', 'Most law firm cases start with a search and end with a consultation like this one.', 'Business Meeting', 'Christina Morillo', 'https://stocksnap.io/photo/business-meeting-SKBXLJOILI'],
	'how-much-does-seo-cost': ['agency-post1.jpg', 'free', 'Website analytics chart on a laptop screen showing organic traffic', 'SEO is priced mostly as a monthly retainer and judged by traffic and leads over months.', 'Google Analytics', 'Negative Space', 'https://stocksnap.io/photo/google-analytics-89AZTB8E5H'],
	'google-ads-cost': ['seo-services-post2.jpg', 'free', 'Advertising performance dashboard with charts open on a laptop', 'Tracking cost per lead, not just cost per click, shows what Google Ads really costs.', 'Analytics on MacBook Laptop', '', 'https://www.rawpixel.com/image/5968621/analytics-macbook-laptop'],
	'how-much-does-digital-marketing-cost': ['content-marketing-post2.jpg', 'free', 'Laptop, coffee and printed marketing charts on a wooden desk', 'Set a marketing budget from your numbers, then fund one or two channels properly.', 'Laptop Coffee Charts Desk Wood', '', 'https://www.rawpixel.com/image/5966850/laptop-coffee-charts-desk-wood'],
	'local-services-ads-vs-google-ads': ['local-post2.jpg', 'free', 'Red water valve on a pipe, the kind of job home service businesses take calls for', 'Home and professional services can pay per lead with Local Services Ads where eligible.', 'Sprinkler system', '', 'https://www.rawpixel.com/image/5907868/sprinkler-system-free-public-domain-cc0-image'],
	'ada-website-compliance': ['seo-services-post3.jpg', 'free', 'Hands typing on a laptop keyboard', 'Every task on an accessible website can be completed with a keyboard alone.', 'Macbook Laptop', 'Skitter Photo', 'https://stocksnap.io/photo/macbook-laptop-CJSRRVR4JE'],
	'ai-marketing-tools': ['ai-llm-post2.jpg', 'pro', 'A humanoid robot standing in front of a screen', 'AI speeds up drafts and analysis; people still decide what is accurate and what gets published.', 'Robot Technology', 'Alex Knight', 'https://stocksnap.io/photo/robot-technology-WUBMLJRYEK'],
	'best-crm-for-small-business': ['agency-about.jpg', 'free', 'A small sales team in a meeting with a laptop open on the table', 'The best CRM is the one your whole team uses every day.', 'Business Meeting', 'Direct Media', 'https://stocksnap.io/photo/business-meeting-Z1ZIMB54UM'],
	'google-business-profile-optimization': ['maintenance-hero.jpg', 'free', 'Small business owner in a work apron with tools in the pocket', 'A complete Google Business Profile is often the first thing local customers see.', 'Handyman wearing apron with tools', '', 'https://www.rawpixel.com/image/11515749/handyman-wearing-apron-with-tools'],
	'small-business-loans-for-marketing': ['consultant-post2.jpg', 'pro', 'Hands writing a plan in an open notebook beside a cup of coffee', 'Write down what the marketing will cost and earn before you apply for a loan.', 'Journal Notepad', 'Cathryn Lavery', 'https://stocksnap.io/photo/journal-notepad-DPKNIIN5X3'],
	'email-marketing-platforms': ['repairs-post2.jpg', 'free', 'Person reading on a smartphone while holding a takeaway coffee cup', 'Most people read marketing emails on their phone, so test every email on mobile.', 'Mobile Smartphone', 'JESHOOTS.com', 'https://stocksnap.io/photo/mobile-smartphone-DLITZEAVJJ'],
	'general-liability-insurance-small-business': ['maintenance-post1.jpg', 'free', 'Painter in blue overalls holding a paint roller in front of a striped yellow wall', 'Trades that work in clients’ homes are often asked to show proof of general liability cover.', 'Guy Man', 'Michal Jarmoluk', 'https://stocksnap.io/photo/guy-man-Q67DPDQSC1'],
	'seo-for-small-business': ['agency-post2.jpg', 'free', 'Small business owner typing on a laptop next to a coffee and a phone', 'Small businesses can do the SEO basics themselves and grow from there.', 'Laptop Typing', 'Kristin Hardwick', 'https://stocksnap.io/photo/laptop-typing-XJMBMTYYN2'],
};
// slug: [graphic key, alt, caption, { stamp: true } only when a text stamp is needed]
const CHARTS = {
	'financial-advisor-marketing': ['financial-advisor-marketing-plan', '6-step financial advisor marketing plan: niche, website, local SEO and content, Google Ads, referrals, compliance review', 'The six steps of the financial advisor marketing plan in this guide.'],
	'insurance-agency-marketing': ['insurance-agency-90-day-marketing-plan', '90-day insurance agency marketing plan: foundations in weeks 1–4, paid search in weeks 5–8, owned lead sources in weeks 9–12', 'A 90-day plan for insurance agency marketing.'],
	'seo-for-attorneys': ['google-ads-legal-cpc-cpl-2025', 'Bar chart: Google Ads cost per click $5.26 across industries vs $8.58 for legal; cost per lead $70.11 vs $131.63', 'Legal is the most expensive Google Ads category in the 2025 benchmarks, which makes SEO for attorneys more valuable.'],
	'how-much-does-seo-cost': ['seo-retainer-cost-by-provider', 'Bar chart of average monthly SEO retainers: freelancers $1,348, local SEO $1,557, agencies $3,209, consultancies $3,250', 'Average monthly SEO retainers by provider type, from the Ahrefs survey.'],
	'google-ads-cost': ['google-ads-budget-to-customers', 'Funnel example: $500 Google Ads budget buys 100 clicks at $5, giving 5 leads at 5% and about 1 customer at 25%', 'Work out your Google Ads budget backwards from the value of a customer.'],
	'how-much-does-digital-marketing-cost': ['digital-marketing-pricing-models', 'Four ways digital marketing agencies charge: monthly retainer, project fee, hourly and percentage of ad spend', 'The four pricing models you will see in digital marketing quotes.'],
	'local-services-ads-vs-google-ads': ['local-services-ads-vs-google-ads-comparison', 'Comparison table of Local Services Ads and Google Ads: payment, placement, badge, keyword control, landing page and best use', 'Local Services Ads and Google Ads side by side.'],
	'ada-website-compliance': ['wcag-pour-principles', 'The four WCAG principles: perceivable, operable, understandable and robust, with examples of each', 'Most ADA website compliance fixes map to one of the four WCAG principles.'],
	'ai-marketing-tools': ['ai-content-workflow-human-approval', 'Six-step AI content workflow: human brief, AI draft, expert fact check, editor review, human approval, measure and update', 'A safe workflow for using AI marketing tools, with people approving every piece.'],
	'best-crm-for-small-business': ['crm-sales-pipeline-stages', 'Sales pipeline stages in a CRM: new lead, contacted, qualified, proposal sent and won', 'A simple pipeline to set up in a CRM for small business.'],
	'google-business-profile-optimization': ['google-local-ranking-factors', 'Diagram of Google local ranking factors: relevance, distance and prominence overlapping at the map pack', 'Google names relevance, distance and prominence as the main local ranking factors.'],
	'small-business-loans-for-marketing': ['small-business-loan-approval-by-lender', 'Bar chart: share of small business loan applicants fully approved was 54% at small banks, 45% at large banks and 30% at online lenders', 'Full approval rates differ widely by lender type, so where you apply matters.'],
	'email-marketing-platforms': ['email-marketing-platform-checklist', 'Six checks for choosing an email marketing platform: goal, list size, integrations, editor, next-tier price and exporting contacts', 'Six checks to run before you commit to an email marketing platform.'],
	'general-liability-insurance-small-business': ['general-liability-insurance-cost-bands', 'Bar chart: 22% of Insureon small business customers paid under $30 a month for general liability, 41% paid $30 to $60 and 37% paid more', 'Most small businesses in the Insureon data paid between $30 and $60 a month.'],
	'seo-for-small-business': ['seo-for-small-business-roadmap', 'Nine-step SEO for small business roadmap from keyword research to measuring leads', 'The nine steps of SEO for small business covered in this guide.'],
};

const out = {};
for (const [slug, [file, set, alt, caption, title, creator, landing]] of Object.entries(PHOTOS)) {
	const name = `${slug}-featured.webp`;
	const buf = await sharp(path.join(PH[set], file)).resize(1200, 675, { fit: 'cover', position: 'attention' }).modulate({ saturation: 1.05 })
		.composite([{ input: STAMP, left: 1200 - SW - 22, top: 675 - SH - 22 }]).webp({ quality: 80 }).toBuffer();
	fs.writeFileSync(path.join(OUT, name), buf);
	out[slug] = { featured: { file: name, alt, caption, width: 1200, height: 675, credit: `Photo: ${title}${creator ? ' by ' + creator : ''}, CC0 (free for commercial use)`, source: landing, license: 'CC0 1.0' } };
}
for (const [slug, [key, alt, caption, opts = {}]] of Object.entries(CHARTS)) {
	const name = `${key}.webp`;
	let img = sharp(Buffer.from(GRAPHICS[key]()));
	if (opts.stamp) { img = img.composite([{ input: STAMP, left: 1200 - SW - 30, top: 26 }]); }
	fs.writeFileSync(path.join(OUT, name), await img.webp({ quality: 90 }).toBuffer());
	out[slug].chart = { file: name, alt, caption, width: 1200, height: 675, credit: 'Graphic: SEMRS', license: 'SEMRS original' };
}
fs.writeFileSync(path.join(OUT, 'images.json'), JSON.stringify(out, null, 1));
for (const f of fs.readdirSync(OUT).filter((f) => f.endsWith('.webp'))) console.log(String(Math.round(fs.statSync(path.join(OUT, f)).size / 1024)).padStart(4) + 'KB', f);
