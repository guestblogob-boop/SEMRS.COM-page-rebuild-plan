---
title: SEMRS Privacy, Email & Security Guide
description: SEMRS privacy and security guide: set up the cookie banner with Google Consent Mode, SMTP email, login limits, security headers and Pro login security.
order: 7
seo_title: SEMRS Privacy & Security 2026: Simple Setup Guide
focus: semrs privacy security
---
SEMRS Core has four free tools that most sites need: a cookie banner, reliable email, light security and site tools. SEMRS Pro adds two-factor log-in, an activity log, search and replace, and automatic share images. Everything starts switched off, so nothing changes for your visitors until you choose it.

## Cookie consent

Go to **SEMRS → Privacy** and tick **Show the banner**.

1. Choose **Opt-in** if you have visitors from Europe or the UK. Nothing that tracks runs until they agree.
2. Keep the four categories: Necessary, Preferences, Statistics and Marketing.
3. Leave **Google Consent Mode** on. Google Analytics, Site Kit and Google Ads then follow the visitor's choice.
4. Click **Save Changes** and open your site in a private window to see the banner.

**You do not need the WP Consent API plugin.** SEMRS has the same functions built in, so plugins that support it, such as Site Kit and WooCommerce, read the choice from SEMRS. You can deactivate and delete that plugin.

**Tracking codes:** paste Google Analytics, Clarity, Meta pixel or chat codes into the matching box under **Header and footer scripts**. Statistics and marketing codes wait until the visitor agrees. Do not paste a Google tag that Site Kit already adds.

**Proof of consent:** keep **Consent log** on. It saves a random visitor ID, the choices and a shortened IP hash, never names. Download it as CSV at the bottom of the screen.

**Ads in Europe:** AdSense and Ad Manager need a Google-certified consent message for visitors from the EEA, the UK and Switzerland. Turn on Google's free **Privacy & messaging** message in AdSense for that. The SEMRS banner then covers everything else.

**Speed:** the banner is a few kilobytes of code in the page, with no extra file to download, and it never moves the page content.

## Email (SMTP)

Many hosts send WordPress email in a way that fails or lands in spam. Send it through your email provider instead.

1. Go to **SEMRS → Email** and tick **Use SMTP**.
2. Enter the SMTP server, port and encryption your provider gives you (usually TLS and port 587).
3. Enter the username and password. For Gmail and Microsoft 365, create an app password.
4. Use a **From email** on your own domain.
5. Click **Save Changes**, then **Send test**.

The password is saved encrypted. Safer still, add `define( 'SEMRS_SMTP_PASSWORD', '…' );` to wp-config.php. The log shows the last 50 emails with their result, never the message itself.

## Security

Go to **SEMRS → Security**. The safe protections are already on when you install SEMRS Core:

| Setting | What it does | Default |
|---|---|---|
| Limit log-in attempts | Locks out an IP after 5 wrong passwords for 15 minutes | On |
| Same error for wrong user or password | Bots cannot learn which usernames exist | On |
| Hide the WordPress version | Removes the version number from the page code | On |
| Security headers | Adds browser protections against clickjacking and file sniffing | On |
| Hide usernames | Blocks author scans and the public users list | On |
| Turn off the file editor | Removes the theme and plugin code editors | Off: turn on when nobody edits code in WordPress |
| HSTS | Forces HTTPS for a year | Off: turn on only when every page and subdomain uses HTTPS |
| Safe SVG uploads | Lets administrators upload cleaned SVG files | Off: turn on when you need SVG logos or icons |

**Using Wordfence or another security plugin?** The log-in limit steps aside automatically, so a wrong password is never counted twice. Sites behind Cloudflare see each visitor's real address.

**Locked out?** Add `define( 'SEMRS_SECURITY_OFF', true );` to wp-config.php, log in, fix the setting and remove the line.

## Site tools

Go to **SEMRS → Site tools**.

- **Maintenance page:** for short work on a live site. Google is told the site is "temporarily unavailable", so your rankings stay. You and your editors still see the site when logged in. Set a reopening time if you know it.
- **Coming soon page:** for a new site that is not launched yet.
- **Duplicate:** a "Duplicate" link under every post and page makes a draft copy with its categories, featured image and SEO settings.
- **Comments off:** turns comments off everywhere and stops comment spam.

## Login security (Pro)

Included with every SEMRS Pro key. Go to **SEMRS → Login security** and click **Set up two-factor for my account**.

1. Install an authenticator app on your phone, such as Google Authenticator, Microsoft Authenticator or 1Password.
2. Add an account with **Enter a setup key** and type the key shown in your profile.
3. Type the 6-digit code from the app and click **Update Profile**.
4. Save the 10 backup codes somewhere safe. Each works once.

From then on, the log-in form asks for the code after your password. An administrator can turn two-factor off for a user who lost their phone. If you lock yourself out, add `define( 'SEMRS_2FA_OFF', true );` to wp-config.php.

**Custom log-in address:** bots that try wp-login.php get "not found". Bookmark the new address before you save. To undo it, add `define( 'SEMRS_LOGIN_SLUG_OFF', true );` to wp-config.php.

## Activity log (Pro)

Included with every SEMRS Pro key. **SEMRS → Activity log** shows who published, changed or deleted content, changed settings, plugins, themes or users, and who logged in or failed to. Filter by person or event, export to CSV, and choose after how many days old entries are deleted. IP addresses are stored shortened.

## Search and replace (Pro)

Included with every SEMRS Pro key. Go to **Tools → Search & replace**.

1. Make a backup first.
2. Type what to find and what to replace it with, for example an old phone number or `http://` links.
3. Keep **Dry run** ticked and click **Run**. Check the count and the examples.
4. Untick **Dry run** and run it again.

If the result is wrong, click **Undo last change**. The site address, plugins, user roles and security keys are never changed.

## SEMRS Assets Protector (Pro)

Included with every SEMRS Pro key. Go to **SEMRS → Assets Protector**. Everything is on with safe settings from the start; the **Overview** tab shows a checklist of what protects your site.

- **Firewall:** stops SQL injection, cross-site scripting, file inclusion, code injection, requests for private files, PHP in the uploads folder, XML-RPC password guessing and known attack tools before WordPress loads. Scanners that hit many missing pages, and addresses that attack again and again, are blocked for a while. Addresses an administrator logged in from are never blocked automatically.
- **Malware scan:** every WordPress file and every plugin from WordPress.org is compared with the official copy, and SEMRS theme and plugin files with semrs.com. Changed SEMRS files are repaired automatically. Everything else is checked for malware code. The scan runs every night in short steps and emails you when it finds something serious.
- **Fixing results:** **Restore original** downloads a clean copy of a changed file. **Quarantine** moves a suspicious file to a protected folder (nothing is deleted, and **Put back** undoes it). **Ignore** hides a result until the file changes.
- **Spam protection:** stops spam in comments, product reviews, sign-ups and Contact Form 7, WPForms, Gravity Forms and Elementor forms, without a CAPTCHA and without any outside service. Spam comments go to the Spam folder, so nothing is lost.

**Using Wordfence or Akismet?** SEMRS works alongside them. While Wordfence is active, the SEMRS firewall and nightly scan pause so nothing is checked twice (you can choose to run both). With Akismet, SEMRS stops the bots first and Akismet checks the rest.

**Firewall blocking something it should not?** Add your address under **Always allow**, or switch the mode to **Log only** while you test. Emergency switch: add `define( 'SEMRS_FIREWALL_OFF', true );` to wp-config.php.

## Backups and migration (Pro)

Included with every SEMRS Pro key. Go to **SEMRS → Backups**.

1. Click **Whole site**, **Database**, **Plugins**, **Themes** or **Media** to back up now.
2. Under **Schedule & contents**, choose how often (twice a day, daily, weekly or monthly) and how many backups to keep.
3. Under **Storage**, add at least one place outside your server: Amazon S3 (or Wasabi, Backblaze B2, DigitalOcean Spaces, Cloudflare R2), Dropbox, Google Drive, FTP or FTPS, SFTP, WebDAV (Nextcloud, ownCloud) or email. Click **Save and test**.
4. To restore, click **Restore** next to a backup, choose the parts and confirm. Click **Check** first to make sure every file is complete.

**Extra features**

- **Rollback copies:** before WordPress updates a plugin or theme, the old version is kept. If an update breaks something, click **Roll back**.
- **Faster backups:** between full backups, save only the files that changed.
- **Encryption:** protect database backups with a password (AES-256). Keep the password safe.
- **Leave out tables or folders:** skip large log tables, video folders or files above a size you choose.
- **Restore on a new server:** on a fresh WordPress with SEMRS Pro, click **Find backups in** your storage, then **Fetch** and **Restore**. Or upload the backup files you downloaded.
- **Backups from other plugins:** upload an UpdraftPlus backup or an All-in-One WP Migration .wpress file, and restore it here.
- **WP-CLI:** `wp semrs backup run`, `wp semrs backup list` and `wp semrs backup restore` for developers.

**Move a site to a new address or host**

1. On the old site, open **Backups → Migrate** and click **Make a migration package**. Copy the key (it works for 24 hours).
2. On the new site, install WordPress, SEMRS Core and SEMRS Pro, open **Backups → Migrate**, paste the key and click **Copy the site here**.
3. Log in with the old site's username and password. Every address, also inside page builder data, now points to the new site.

Sites up to 10 GB move by default; change the limit under **Largest migration**, and leave out large folders if needed.

**Using UpdraftPlus too?** While it is active, the SEMRS schedule pauses so two backups never run at once. Manual backups, restore and migration still work.

## Social images (Pro)

Part of SEO Pro. When a post or page has no featured image, Facebook, LinkedIn, X and WhatsApp show an automatic image with the post title and your colors instead of a blank box. Choose the colors under **SEMRS → Social images**. The image is made once on your own server.

## Keep your 100 PageSpeed score

None of these tools slow your site: they run on your server, and only the cookie banner adds a few kilobytes to the page. After turning features on, test again with [PageSpeed Insights](https://pagespeed.web.dev/) and keep the speed settings from the [speed guide](/support/speed/). Questions? [Contact us](/contact-us/).
