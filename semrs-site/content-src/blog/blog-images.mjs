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
	'personal-injury-lawyer-advertising': ['seo-services-hero.jpg', 'free', 'A man searching on a laptop at a table at home', 'Most injury clients search and call a firm within days, so every ad should lead to a fast, friendly answer.', 'Man Laptop', 'Kristin Hardwick', 'https://stocksnap.io/photo/man-laptop-HYRSHH13TD'],
	'google-ads-for-small-business': ['digital-marketing-post2.jpg', 'free', 'Laptop showing a bar and pie chart report next to a tablet on a desk', 'Judge Google Ads by leads and customers, not by clicks.', 'Business Working', 'Marc Chouinard', 'https://stocksnap.io/photo/business-working-PHE63L27S6'],
	'best-business-credit-cards-for-advertising': ['guest-posting-hero.jpg', 'free', 'Business owner writing figures on paper beside a laptop', 'Total your monthly ad spend before you compare card rewards and fees.', 'Laptop Working', 'Matt Moloney', 'https://stocksnap.io/photo/laptop-working-8GETAUWFBE'],
	'dental-marketing': ['dentist-about.jpg', 'free', 'A clean, empty dental treatment room with a blue patient chair', 'Real photos of your rooms and team help new patients choose your practice.', 'Dental clinic', '', 'https://www.rawpixel.com/image/6020787/dental-clinic-free-public-domain-cc0-photo'],
	'website-design-cost': ['online-courses-post2.jpg', 'pro', 'Paper wireframe sketches of web pages with pens on a desk', 'Count your pages and features first; they decide most of the price.', 'Office Work', 'Jeffrey Betts', 'https://stocksnap.io/photo/office-work-91WZ32J28K'],
	'generative-engine-optimization': ['ai-llm-hero.jpg', 'pro', 'Two colleagues reviewing code on a laptop screen', 'Pages that are easy to crawl and quote are the ones AI answers cite.', 'Developer Discuss', 'Christina Morillo', 'https://stocksnap.io/photo/developer-discuss-OEIJJX36TI'],
	'website-builders-for-small-business': ['guest-posting-post1.jpg', 'free', 'Overhead view of a person planning in a notebook next to a laptop', 'Plan your pages and goals before you pick a website builder.', 'Man Work', 'Burst', 'https://stocksnap.io/photo/man-work-DZ7DC58DSV'],
	'google-ads-agency': ['seo-services-about.jpg', 'free', 'Three colleagues discussing results around a laptop', 'A good agency explains its plan and reports cost per lead in plain words.', 'Business People', 'Direct Media', 'https://stocksnap.io/photo/business-people-DUXISHKTT3'],
	'marketing-automation-software': ['top-ten-post2.jpg', 'free', 'Tablet showing a marketing dashboard next to a cup of coffee', 'Automation reports show which flows bring bookings and sales.', 'Office Work', 'WDnet Studio', 'https://stocksnap.io/photo/office-work-030TCBJQ8C'],
	'keyword-research': ['digital-marketing-about.jpg', 'free', 'Two people writing notes on paper beside a laptop', 'Start keyword research with your customers’ own words, then use tools to expand them.', 'Writing Papers', 'Helloquence', 'https://stocksnap.io/photo/writing-papers-Y01VDYAX63'],
	'business-line-of-credit': ['guest-posting-post2.jpg', 'free', 'Two people shaking hands across a desk with a coffee cup', 'Compare the full cost of a credit line before you agree to it.', 'Work Business', 'Kristin Hardwick', 'https://stocksnap.io/photo/work-business-J5LXKNDREC'],
	'med-spa-marketing': ['clinic-hero.jpg', 'pro', 'A clinician in a white coat holding a tablet', 'Clients choose a med spa on trust: named providers, real results and clear information.', 'Male Doctor', 'Direct Media', 'https://stocksnap.io/photo/male-doctor-KN1OCKC4Y2'],
	'merchant-services-for-small-business': ['top-ten-about.jpg', 'free', 'A woman checking her phone beside a laptop at a white desk', 'Card fees come out of every sale, so compare your effective rate, not the headline rate.', 'Browsing Smartphone', 'Kristin Hardwick', 'https://stocksnap.io/photo/browsing-smartphone-4FRH6CVQJI'],
	'best-wordpress-hosting': ['repairs-hero.jpg', 'free', 'A technician working inside an open computer case', 'Good hosting is the base of a fast, secure WordPress site.', 'Computer repair', '', 'https://www.rawpixel.com/image/6045410/free-public-domain-cc0-photo'],
	'life-insurance-leads': ['local-post3.jpg', 'free', 'The front door of a red brick family home', 'Life events such as a new home or a new baby are when people look for cover.', 'House home', '', 'https://www.rawpixel.com/image/6042788/photo-image-public-domain-house-home'],
	'local-seo-services': ['local-about.jpg', 'free', 'A roofer in a hard hat working on a roof', 'Local service businesses win most new customers from nearby searches.', 'Roofer at work', '', 'https://www.rawpixel.com/image/11515783/photo-image-person-public-domain-man'],
	'how-to-get-more-google-reviews': ['seo-services-post1.jpg', 'free', 'A hand holding a smartphone with a business page open', 'Most reviews are written on a phone, so send a direct link the same day.', 'Mobile Phone', 'Expect Best', 'https://stocksnap.io/photo/mobile-phone-QZ5V5ZZ8UI'],
	'social-media-management-platforms': ['digital-marketing-post1.jpg', 'free', 'Social media app icons on a phone screen', 'A platform helps you post on time; your content decides whether it works.', 'Instagram phone', '', 'https://www.rawpixel.com/image/6111516/photo-image-instagram-phone-public-domain'],
	'real-estate-advertising': ['cleaning-about.jpg', 'free', 'A bright kitchen with a white bay window', 'Describe the home and the area, never the people you think should live there.', 'Farmhouse Kitchen', 'Matt Bango', 'https://stocksnap.io/photo/farmhouse-kitchen-QSX4WH8BDB'],
	'mortgage-marketing': ['agency-post3.jpg', 'free', 'Two people reviewing papers and a planner beside a laptop', 'Referral partners and clear guides bring steady mortgage leads.', 'Notebook', '', 'https://www.rawpixel.com/image/5926528/photo-image-background-public-domain-notebook'],
	'digital-marketing-strategy': ['guest-posting-post3.jpg', 'free', 'A person writing a plan in a ring-bound planner', 'Write the plan down, then review it every month.', 'Writing Hand', 'Kristin Hardwick', 'https://stocksnap.io/photo/writing-hand-2FRRD2PUVA'],
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
	'ai-marketing-tools': ['ai-content-workflow-human-approval', 'Six-step AI content workflow: human brief, AI draft, expert fact check, editor review, human approval, measure and update', 'A safe workflow for using AI tools, with people approving every piece.'],
	'best-crm-for-small-business': ['crm-sales-pipeline-stages', 'Sales pipeline stages in a CRM: new lead, contacted, qualified, proposal sent and won', 'A simple pipeline to set up in a CRM for small business.'],
	'google-business-profile-optimization': ['google-local-ranking-factors', 'Diagram of Google local ranking factors: relevance, distance and prominence overlapping at the map pack', 'Google names relevance, distance and prominence as the main local ranking factors.'],
	'small-business-loans-for-marketing': ['small-business-loan-approval-by-lender', 'Small business loans bar chart: share of applicants fully approved was 54% at small banks, 45% at large banks and 30% at online lenders', 'Full approval rates differ widely by lender type, so where you apply matters.'],
	'email-marketing-platforms': ['email-marketing-platform-checklist', 'Six checks for choosing an email marketing platform: goal, list size, integrations, editor, next-tier price and exporting contacts', 'Six checks to run before you commit to an email marketing platform.'],
	'general-liability-insurance-small-business': ['general-liability-insurance-cost-bands', 'Bar chart: 22% of Insureon small business customers paid under $30 a month for general liability, 41% paid $30 to $60 and 37% paid more', 'Most small businesses in the Insureon data paid between $30 and $60 a month.'],
	'seo-for-small-business': ['seo-for-small-business-roadmap', 'Nine-step SEO for small business roadmap from keyword research to measuring leads', 'The nine steps of SEO for small business covered in this guide.'],
	'personal-injury-lawyer-advertising': ['personal-injury-lawyer-advertising-plan', 'Six-part personal injury lawyer advertising plan: profile and reviews, call tracking and intake, Local Services Ads, Google search ads, SEO and content, bar rules review', 'A simple advertising plan for a small personal injury firm.'],
	'google-ads-for-small-business': ['google-ads-first-campaign-steps', 'Eight steps to a first Google Ads campaign for small business: track conversions, search campaign, location, keywords, negative keywords, ads, matching page, steady budget', 'The eight setup steps from this guide.'],
	'best-business-credit-cards-for-advertising': ['business-credit-card-ad-spend-checks', 'Six checks for choosing among the best business credit cards for ad spend: total spend, pay in full, bonus categories, fee vs rewards, caps, personal guarantee', 'Run these six checks before you apply for a card.'],
	'dental-marketing': ['dental-marketing-new-patients', 'Dental marketing plan in eight steps: Business Profile, reviews, treatment pages, local SEO, Google Ads, easy booking, recall reminders, team on social media', 'Eight ways dental practices win new patients.'],
	'website-design-cost': ['website-design-cost-packages', 'Bar chart of website design cost at SEMRS: Starter $399, Business $799, Growth $1,499, E-commerce Starter $1,799', 'Published one-time website prices from one agency, as an example of how packages differ.'],
	'generative-engine-optimization': ['generative-engine-optimization-signals', 'Diagram of generative engine optimization: findable, clear and trusted pages overlap where AI answers cite a source', 'Pages that are findable, clear and trusted are the ones AI answers cite.'],
	'website-builders-for-small-business': ['website-builders-wordpress-vs-hosted', 'Comparison of website builders for small business: WordPress vs hosted builders on ownership, upkeep, flexibility, SEO control, monthly cost and moving later', 'WordPress and hosted builders side by side.'],
	'google-ads-agency': ['google-ads-agency-questions', 'Nine questions to ask a Google Ads agency: account owner, lead tracking, fees, contract length, manager, first 90 days, report, recommendations, examples', 'Ask these nine questions before you hire an agency.'],
	'marketing-automation-software': ['marketing-automation-first-flows', 'Marketing automation software flows to set up first: welcome series, enquiry follow-up, review request, re-booking reminder, win-back email, monthly review', 'Start with these simple flows before anything complex.'],
	'keyword-research': ['keyword-research-six-steps', 'Keyword research in six steps: seed topics, expand ideas, check intent, judge difficulty, map to pages, track and update', 'The six steps of keyword research in this guide.'],
	'business-line-of-credit': ['line-of-credit-vs-term-loan', 'Comparison of a business line of credit and a term loan: how you get money, interest, repayment, rate, best uses and fees', 'A line of credit and a term loan side by side.'],
	'med-spa-marketing': ['med-spa-marketing-plan', 'Med spa marketing in eight cards: Business Profile, treatment pages, reviews, real results, ads, memberships, videos and following the rules', 'Seven ways to grow a med spa, with the rules that apply to all of them.'],
	'merchant-services-for-small-business': ['merchant-services-fee-on-100-sale', 'Bar chart of merchant services fees on a $100 card sale: Square in person $2.75, Stripe in person $2.75, Stripe online $3.20, Square online $3.60', 'Online card payments usually cost more than in-person ones.'],
	'best-wordpress-hosting': ['wordpress-hosting-checks', 'Checks for the best WordPress hosting: server specs, speed, security, backups, support, staging, renewal price and account ownership', 'Run these checks before you choose a host.'],
	'life-insurance-leads': ['life-insurance-leads-bought-vs-own', 'Comparison of bought life insurance leads and your own leads: speed, cost, exclusivity, trust, consent records and best use', 'Bought leads are fast; your own leads are cheaper and more loyal over time.'],
	'local-seo-services': ['local-seo-services-checklist', 'What local SEO services should include: Business Profile, service area pages, reviews, citations, schema, technical basics, local links and lead reports', 'Ask a provider to put each of these in writing.'],
	'how-to-get-more-google-reviews': ['google-reviews-process', 'A Google reviews routine: job finished, ask in person, send a direct link or QR code, customer reviews, reply to every review', 'A simple routine that gets more reviews within the rules.'],
	'social-media-management-platforms': ['social-media-platform-choice', 'How to choose among social media management platforms: list channels, decide who posts, try free first, check reports, price the next step, plan content', 'Six steps to pick the right platform.'],
	'real-estate-advertising': ['real-estate-advertising-ideas', 'Eight real estate advertising ideas: profile and reviews, area guides, home valuations, video tours, search ads, social ads, direct mail and partners', 'Eight ways agents can own their area.'],
	'mortgage-marketing': ['mortgage-marketing-ideas', 'Mortgage marketing ideas: referral partners, Business Profile, buyer guides, program pages, search ads, client emails, short videos and compliance review', 'Seven mortgage marketing ideas, with compliance review for every one.'],
	'digital-marketing-strategy': ['digital-marketing-strategy-steps', 'A digital marketing strategy in steps: target customer, advantage, goals, channels, budget, content plan, measurement and yearly update', 'The steps of a simple digital marketing strategy.'],
};

const out = {};
for (const [slug, [file, set, alt, caption, title, creator, landing]] of Object.entries(PHOTOS)) {
	const name = `${slug}-featured.webp`;
	let buf = await sharp(path.join(PH[set], file)).resize(1200, 675, { fit: 'cover', position: 'attention' }).modulate({ saturation: 1.05 })
		.composite([{ input: STAMP, left: 1200 - SW - 22, top: 675 - SH - 22 }]).webp({ quality: 80 }).toBuffer();
	// Featured images stay under 100 KB (WRITING_GUIDE section 7): detailed photos get a lower quality.
	if (buf.length > 85000) buf = await sharp(buf).webp({ quality: 66 }).toBuffer();
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
