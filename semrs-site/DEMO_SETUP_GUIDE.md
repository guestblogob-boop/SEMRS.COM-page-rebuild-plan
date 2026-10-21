# SEMRS demo sites and portfolio hub: setup guide

Goal: every SEMRS starter site runs as a live demo on its own subdomain (for example `pets.demo.semrs.com`), and a portfolio hub (`portfolio.semrs.com`) shows them all with "Live demo" and "Get this website" buttons.

You need:
- Hosting with cPanel (or similar) for semrs.com, and access to the semrs.com DNS.
- The zips from `dist/releases/`: `semrs-1.3.7.zip` (theme), `semrs-blocks-1.3.0.zip`, `semrs-pro-1.3.0.zip`, `semrs-core-1.2.0.zip`. Also `dist/server/semrs-site-1.2.0.zip` for the hub.
- A SEMRS Pro key that allows enough sites (the unlimited plan covers all demos).
- An inbox for demo contact forms, for example `demos@semrs.com`.

Time: about 1 hour for the network, then about 10 minutes per demo site.

---

## Part 1. Choose the setup

**Recommended: one WordPress Multisite network at `demo.semrs.com`.** Every demo is a site in the same installation (`pets.demo.semrs.com`, `dental.demo.semrs.com` …). You update the theme and plugins once for all demos, and there is one database and one backup.

Not recommended: 23 separate WordPress installs. That means 23 sets of updates, logins and backups.

> **Cloudflare warning.** If semrs.com uses Cloudflare with the orange-cloud proxy, the free certificate only covers `*.semrs.com`, **not** `*.demo.semrs.com`. Pick one:
> - set the `demo` and `*.demo` DNS records to **DNS only** (grey cloud) and let cPanel AutoSSL issue the certificate (Part 2), or
> - buy Cloudflare Advanced Certificate Manager for `*.demo.semrs.com`, or
> - use one-level names such as `pets-demo.semrs.com` (see "Option B" at the end).

---

## Part 2. DNS and SSL

1. **cPanel → Domains → Create a new domain**
   - Domain: `demo.semrs.com`
   - Document root: `public_html/demo` (or any new empty folder)
2. **Create the wildcard:** again Create a new domain, Domain: `*.demo.semrs.com`, **same document root** as above (`public_html/demo`).
3. **DNS** (cPanel Zone Editor, or Cloudflare if your DNS is there). Add two A records pointing to your server IP (the same IP that semrs.com uses):

   | Type | Name | Value |
   |---|---|---|
   | A | `demo` | your server IP |
   | A | `*.demo` | your server IP |

4. **SSL:** cPanel → SSL/TLS Status → **Run AutoSSL**. Check that both `demo.semrs.com` and `*.demo.semrs.com` show a valid certificate. Wildcard AutoSSL needs the domain's DNS on cPanel (or DNS-only records on Cloudflare). If your host can't issue a wildcard, ask its support for a wildcard certificate for `*.demo.semrs.com`.
5. **Test:** open `https://demo.semrs.com` and `https://anything.demo.semrs.com`. Both should load (an empty folder or "Index of" page is fine) with a padlock.

---

## Part 3. Install WordPress and turn on Multisite

1. **Install WordPress** at `https://demo.semrs.com` (cPanel → Softaculous / WordPress Manager → Install, directory left empty).
   - Site title: `SEMRS Demos`
   - Admin username: something other than "admin"; use a strong password and a password manager.
2. **Allow Multisite:** cPanel → File Manager → open `public_html/demo/wp-config.php`. Above the line `/* That's all, stop editing! */` add:

   ```php
   define( 'WP_ALLOW_MULTISITE', true );
   ```

3. **Deactivate all plugins** the installer added (Plugins → select all → Deactivate).
4. **Tools → Network Setup** → choose **Sub-domains** → Network title `SEMRS Demos` → **Install**.
5. WordPress shows two code blocks. Copy the first into `wp-config.php` (same place as step 2). Replace the WordPress rules in `public_html/demo/.htaccess` with the second block. It looks like this (use the one WordPress gives you):

   ```php
   define( 'MULTISITE', true );
   define( 'SUBDOMAIN_INSTALL', true );
   define( 'DOMAIN_CURRENT_SITE', 'demo.semrs.com' );
   define( 'PATH_CURRENT_SITE', '/' );
   define( 'SITE_ID_CURRENT_SITE', 1 );
   define( 'BLOG_ID_CURRENT_SITE', 1 );
   ```

6. **Log in again.** You now have **My Sites → Network Admin** in the top bar.

---

## Part 4. Theme and plugins (once, for the whole network)

In **Network Admin**:

1. **Themes → Add New → Upload** `semrs-1.3.7.zip` → Install → **Network Enable**.
2. **Plugins → Add New → Upload**, one at a time: `semrs-blocks-1.3.0.zip`, `semrs-pro-1.3.0.zip`, `semrs-core-1.2.0.zip`.
3. **Network Activate** SEMRS Blocks and SEMRS Core.
4. **SEMRS Pro:** activate it on **one site first** (Part 5), enter the key, and check that the Pro templates appear and updates work. Only then activate it on the other Pro demo sites, or Network Activate it.
5. **Settings → Network Settings:**
   - Registration: **Registration is disabled** (nobody can sign up).
   - Upload space: leave the default or raise it to 200 MB per site.

---

## Part 5. Create each demo site

Repeat this for every design in the table at the end of this guide.

### 5.1 Add the site
**Network Admin → Sites → Add New**
- Site Address: the subdomain from the table (for example `pets`)
- Site Title: the demo name (for example `Happy Paws Pet Care`)
- Admin Email: your own email (you stay the only admin)

### 5.2 Set the site up
Open the new site's dashboard (**My Sites → [site] → Dashboard**):
1. **Appearance → Themes:** activate **SEMRS** (not the child theme).
2. **Settings → Permalinks:** **Post name** → Save.
3. **Settings → General:** time zone and date format.
4. **Settings → Reading:** tick **Discourage search engines from indexing this site** → Save. Demo sites share similar example text; keeping them out of Google protects semrs.com.
5. **Posts:** move "Hello world!" to the bin. **Pages:** move "Sample Page" to the bin.

### 5.3 Import the design
**SEMRS → Templates** → find the design → **Preview site** to check it → **Import**. Fill in the form:

| Field | Value |
|---|---|
| Business or site name | the demo name from the table |
| Phone | `+1 555 010 0200` (a fictional number format) |
| Email | `demos@semrs.com` (contact forms send here) |
| City or area | `Austin` (or any city) |
| Country code | `US` |
| Status | **Publish now** |
| Set the home page | ✔ |
| Create the main menu and the footer menu | ✔ |
| Use the business name as the site title | ✔ |
| Add 3 example blog posts with photos | ✔ |
| Apply the header, footer columns, button and contact details | ✔ |
| Use the template colours | ✔ |

Open **Demo and portfolio sites (optional)**:

| Field | Value |
|---|---|
| Show a "Demo website" notice in the top bar | ✔ |
| Footer credit text | `Website designed by SEMRS` |
| Footer credit link | `https://semrs.com/` |

Click **Import starter site**.

### 5.4 Check
- Open the site on a computer and a phone: home, services, about, blog, contact, privacy policy, terms.
- The top bar shows "Demo website: example business, prices and content."
- The footer shows "Website designed by SEMRS".
- Send a test message from the contact form and check it arrives at `demos@semrs.com`.
- **No ads:** do not set up SEMRS Ads or AdSense on demo sites.

---

## Part 6. The portfolio hub (`portfolio.semrs.com`)

**Simplest option: put the hub on semrs.com and point the subdomain to it.**

1. On **semrs.com**: Plugins → upload `semrs-site-1.2.0.zip` → **Replace current**.
2. **Tools → semrs.com pages → Portfolio hub**:
   - Demo address pattern: `https://{sub}.demo.semrs.com/`
   - Quote / contact page: `https://semrs.com/contact/`
   - Click **Create portfolio page as draft**, review the page, then **Publish** (address `semrs.com/portfolio/`).
3. **Point the subdomain:** cPanel → Domains → create `portfolio.semrs.com`, then cPanel → **Redirects** → Permanent (301) from `portfolio.semrs.com` to `https://semrs.com/portfolio/`. Add an A record `portfolio` → your server IP if it isn't created automatically.
4. Add **Portfolio** to the semrs.com main menu.

**Go live with each demo:** when a demo site from Part 5 is finished, go to Tools → semrs.com pages → Portfolio hub, tick it under **Live demos** → **Save portfolio settings**. Its card on the hub now shows **Live demo** instead of "View full design".

---

## Part 7. Looking after the demos

- **Updates:** upload new SEMRS zips once in Network Admin; every demo updates together.
- **Backups:** back up `public_html/demo` and its database weekly (cPanel → Backup, or JetBackup if your host has it).
- **Contact forms:** visitors may mistake a demo for a real business. Reply politely from `demos@semrs.com` and point them to SEMRS web design.
- **Reset a demo:** to start a demo again, Network Admin → Sites → delete the site, then repeat Part 5. Deleting removes its pages and posts permanently, so only do it when you mean to.
- **AdSense:** never on demo sites (example businesses and prices). Ads belong only on real content sites with original articles (see the subdomain plan: `guides`, `learn`, `travel`).

---

## Demo list

Subdomain = **Site Address** in Part 5.1. Demo name = Site Title and "Business or site name".

| Subdomain | Address | Design (SEMRS → Templates) | Demo name | Plugins |
|---|---|---|---|---|
| agency | agency.demo.semrs.com | Agency | Northwind Digital | Free |
| local | local.demo.semrs.com | Local Business | CityFix Services | Free |
| blog | blog.demo.semrs.com | Blog | Everyday Notes | Free |
| seo | seo.demo.semrs.com | SEO Services | RankWise SEO | Free |
| marketing | marketing.demo.semrs.com | Digital Marketing | Brightpath Marketing | Free |
| content | content.demo.semrs.com | Content Marketing | Wordcraft Studio | Free |
| social | social.demo.semrs.com | Social Media Management | Socially Studio | Free |
| outreach | outreach.demo.semrs.com | Guest Blogging | Outreach House | Free |
| cleaning | cleaning.demo.semrs.com | Cleaning Service | Sparkle Home Cleaning | Free |
| handyman | handyman.demo.semrs.com | Handyman & Maintenance | HandyPro Maintenance | Free |
| repair | repair.demo.semrs.com | Repair Shop | QuickFix Repairs | Free |
| garden | garden.demo.semrs.com | Gardening & Landscaping | Green Leaf Gardens | Free |
| pets | pets.demo.semrs.com | Pet Care & Grooming | Happy Paws Pet Care | Free |
| dental | dental.demo.semrs.com | Dentist | Bright Smile Dental | Free |
| top10 | top10.demo.semrs.com | Top 10 Lists & Reviews | TopPicks Guide | Free |
| travel-blog | travel-blog.demo.semrs.com | Travel Blog | Wander Notes | Free |
| consultant | consultant.demo.semrs.com | Consultant | Clear Path Consulting | Pro |
| clinic | clinic.demo.semrs.com | Clinic | Riverside Clinic | Pro |
| travel-agency | travel-agency.demo.semrs.com | Travel Agency | Horizon Travel | Pro |
| camping | camping.demo.semrs.com | Camping & Outdoors | Pine Ridge Camping | Pro |
| school | school.demo.semrs.com | School & Admissions | Oakwood School | Pro |
| courses | courses.demo.semrs.com | Online Courses | SkillUp Academy | Pro |
| ai | ai.demo.semrs.com | AI & LLM Services | Northstar AI | Pro |

Suggested order: start with 3 strong designs (Pet Care, Dentist, Agency), put them live on the hub, then add the rest a few at a time.

Before you use a demo name in public, do a quick web search for it. The names are generic examples, and the "Demo website" notice makes clear they are not real businesses; pick a different name if it matches a real local company.

---

## Option B: one-level names (if wildcard SSL for `*.demo` is not possible)

Use names such as `pets-demo.semrs.com`, which the normal `*.semrs.com` certificate covers (including Cloudflare's free one):

1. Set up the network as in Parts 2–3, but install it at `demos.semrs.com` with **Sub-directories** in Network Setup. Sites are then `demos.semrs.com/pets/` …
2. For each demo, create a domain in cPanel (`pets-demo.semrs.com`, same document root as the network) and an A record `pets-demo` → server IP.
3. Network Admin → Sites → Edit the site → **Site Address (URL)** `https://pets-demo.semrs.com` → Save. WordPress maps the domain itself; no plugin needed.
4. In the portfolio settings use the pattern `https://{sub}-demo.semrs.com/`.

---

## Quick checklist per demo

- [ ] Site added (Network Admin → Sites → Add New)
- [ ] SEMRS theme active, permalinks set to Post name
- [ ] Search engines discouraged
- [ ] "Hello world!" and "Sample Page" binned
- [ ] Design imported with Publish now, title, posts, theme and colours ticked
- [ ] Demo notice on, footer credit set
- [ ] Checked on desktop and phone; contact form test received
- [ ] No ads
- [ ] Ticked under Live demos on the portfolio hub
