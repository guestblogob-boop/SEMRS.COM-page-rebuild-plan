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

Go to **SEMRS → Security**. These settings are safe for almost every site:

| Setting | What it does | Turn on? |
|---|---|---|
| Limit log-in attempts | Locks out an IP after 5 wrong passwords for 15 minutes | Yes |
| Same error for wrong user or password | Bots cannot learn which usernames exist | Yes |
| Hide the WordPress version | Removes the version number from the page code | Yes |
| Security headers | Adds browser protections against clickjacking and file sniffing | Yes |
| Turn off the file editor | Removes the theme and plugin code editors | Yes |
| Hide usernames | Blocks author scans and the public users list | Only if you have no author pages to rank |
| HSTS | Forces HTTPS for a year | Only when every page and subdomain uses HTTPS |
| Safe SVG uploads | Lets administrators upload cleaned SVG files | When you need SVG logos or icons |

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

## Social images (Pro)

Part of SEO Pro. When a post or page has no featured image, Facebook, LinkedIn, X and WhatsApp show an automatic image with the post title and your colors instead of a blank box. Choose the colors under **SEMRS → Social images**. The image is made once on your own server.

## Keep your 100 PageSpeed score

None of these tools slow your site: they run on your server, and only the cookie banner adds a few kilobytes to the page. After turning features on, test again with [PageSpeed Insights](https://pagespeed.web.dev/) and keep the speed settings from the [speed guide](/support/speed/). Questions? [Contact us](/contact-us/).
