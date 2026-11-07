# Upload SEMRS to WordPress.org: step-by-step guide

Last checked: 7 November 2026. All zips below are built and checked. You do every step that needs your WordPress.org account; nobody else can sign in or submit for you.

## What goes to WordPress.org (and what never does)

| Product | Submit? | File to upload | Order |
|---|---|---|---|
| SEMRS theme 1.3.25 | Yes | `dist/wporg/semrs.zip` (299 KB) | 1st (theme queue) |
| SEMRS Core 1.3.55 | Yes | `dist/wporg/semrs-core.zip` (216 KB) | 1st (plugin queue) |
| SEMRS Blocks 1.3.19 | Yes | `dist/wporg/semrs-blocks.zip` (6.9 MB) | 2nd |
| SEMRS Tools 1.4.6 | Yes | `dist/wporg/semrs-tools.zip` (190 KB) | 3rd |
| SEMRS Shops 1.1.3 | Yes, **after Blocks is approved** | `dist/wporg/semrs-shops.zip` (1.9 MB) | 4th (it requires Blocks) |
| SEMRS Child SEO 1.2.9 | Optional, only after the theme is live | `dist/wporg/semrs-child-seo.zip` | Later |
| SEMRS Pro | **Never** (paid, sold on semrs.com) | – | – |
| SEMRS Site, SEMRS Update Server | **Never** (semrs.com only) | – | – |

The `dist/wporg/` zips are special builds: the semrs.com updater and the `Update URI` header are removed, as WordPress.org requires. **Never upload the `dist/releases/` zips to WordPress.org.**

Rebuild them any time with `node build-wporg.mjs` (from the `semrs-site` folder). It prints ✔ for each zip after checking that no updater, no `Update URI` and no semrs.com update calls are left.

---

## Step 1 · Prepare your WordPress.org account (once)

1. Go to **https://login.wordpress.org/register** and create an account, or sign in with the one you already have. Use the **same account for every SEMRS product**.
2. Use a username you are happy to show publicly: it appears as the author ("By SEMRS") and must match `Contributors: semrs` in the readmes. If your username is not `semrs`, tell us and we change the `Contributors:` line in each readme before you submit.
3. Turn on **two-step authentication** (Profile → Account & Security). Plugin owners must have it to commit code.
4. Use an email address you check often: reviewers write to it, and they close submissions that get no answer.

## Step 2 · Test the zips on a staging site (30 minutes, recommended)

WordPress.org reviewers run the same checks. Fixing problems first saves weeks.

1. Use a fresh test site (for example a Hostinger staging copy, LocalWP, or **https://playground.wordpress.net**). Not semrs.com.
2. Install the free plugins **Plugin Check (PCP)** and **Theme Check** from Plugins → Add New.
3. Upload the theme `dist/wporg/semrs.zip` (Appearance → Themes → Add New → Upload). Run **Tools → Theme Check**. Fix every **REQUIRED** item; **RECOMMENDED** items are optional.
4. Upload each plugin zip from `dist/wporg/` (Plugins → Add New → Upload). Run **Tools → Plugin Check** for each one. Errors must be fixed; warnings should be read.
   - SEMRS Shops will say it needs SEMRS Blocks: that is expected until Blocks is on WordPress.org.
5. Click through the main screens of each product once. Send us any error you see and we fix it and rebuild the zip.

## Step 3 · Submit the theme

1. Open **https://wordpress.org/themes/upload/** while signed in.
2. Choose `dist/wporg/semrs.zip`, tick the confirmation boxes (GPL, you are the author) and click **Upload**.
3. A ticket is created on **themes.trac.wordpress.org** and you get an email. Every reviewer message arrives there and by email.
4. Wait for a reviewer. The theme queue can take several weeks. Only **one theme per account** can wait in the queue at a time.
5. When the reviewer lists issues, reply in the ticket **within 7 days**, otherwise it is closed. Send us the list; we fix it, bump the version and rebuild, and you upload the new zip on the same upload page (the version must be higher).
6. When the ticket says **live**, the theme appears at **https://wordpress.org/themes/semrs/**.

Theme updates later: upload the new `dist/wporg/semrs.zip` (higher version) at the same upload page. There is no SVN for themes.

## Step 4 · Submit the plugins (one at a time)

Start with SEMRS Core. You can submit Blocks and Tools while Core waits, but **submit Shops only after Blocks is approved**.

1. Open **https://wordpress.org/plugins/developers/add/** while signed in.
2. Upload the zip (for example `dist/wporg/semrs-core.zip`) and tick the boxes.
3. The page shows the **slug** the plugin will get (for example `semrs-core`). Check it.
   - If WordPress.org offers a **different slug** (the name is taken or too close to a trademark), do not accept silently: send us the new slug. The Text Domain inside the plugin must match the slug, so we change it and rebuild before approval.
4. You get an email from **plugins@wordpress.org**. A volunteer reviews the code by hand. Expect anything from a few days to several weeks.
5. If they ask for changes, **reply to that same email** (do not open a new submission). Send us their message; we fix, rebuild with `node build-wporg.mjs` and you reply with the new zip attached or as asked.
6. When approved, the email gives you the SVN address: `https://plugins.svn.wordpress.org/semrs-core/`.

Repeat for `semrs-blocks.zip`, then `semrs-tools.zip`, and finally `semrs-shops.zip` once Blocks is approved.

## Step 5 · Put an approved plugin online (SVN)

Approval only creates an empty folder. The plugin goes live when you upload the code with SVN.

**Tools:** Windows: install **TortoiseSVN** (https://tortoisesvn.net). Your SVN username is your WordPress.org username; the password is the **SVN password** you create at Profile → Account & Security (not your login password).

For each approved plugin (example: semrs-core):

1. Make an empty folder, for example `D:\wporg-svn\semrs-core`. Right-click it → **SVN Checkout** → URL `https://plugins.svn.wordpress.org/semrs-core/` → OK. You get three folders: `trunk`, `tags`, `assets`.
2. **trunk** = the plugin code. Unzip `dist/wporg/semrs-core.zip`. Copy everything **inside** the `semrs-core` folder (not the folder itself) into `trunk`.
3. **tags** = one copy per version. Make the folder `tags\1.3.55` and copy the same files into it. (The version must match `Stable tag:` in `readme.txt`.)
4. **assets** = the plugin page artwork (not part of the plugin). Copy the files from `dist/wporg-assets/semrs-core/` into `assets`:
   `banner-772x250.png`, `banner-1544x500.png`, `icon-128x128.png`, `icon-256x256.png`, `icon.svg`.
   Optional screenshots: add `screenshot-1.png`, `screenshot-2.png`… and a matching `== Screenshots ==` section in readme.txt (ask us to add it).
5. Right-click `D:\wporg-svn\semrs-core` → **SVN Add** (select all new files) → then **SVN Commit**, message: `Release 1.3.55`.
6. Within about 15 minutes the plugin is live at **https://wordpress.org/plugins/semrs-core/**. Check the page, the banner and the "Download" button.

Repeat for semrs-blocks (`tags\1.3.19`), semrs-tools (`tags\1.4.6`) and semrs-shops (`tags\1.1.3`), each with its own `dist/wporg-assets/<slug>/` artwork.

## Step 6 · Releasing updates later

1. We make the changes and run `node release.mjs` (or `node build-wporg.mjs` for the current version). This builds both the semrs.com zips (`dist/releases/`) and the WordPress.org zips (`dist/wporg/`).
2. semrs.com: publish the `dist/releases/` zip in **SEMRS Updates → Releases** (as now).
3. WordPress.org plugins: in your SVN folder, **SVN Update**, replace the files in `trunk` with the new `dist/wporg/` build, add `tags\<new version>`, then **SVN Commit**. Sites using the WordPress.org copy get the update automatically.
4. WordPress.org theme: upload the new `dist/wporg/semrs.zip` at https://wordpress.org/themes/upload/.

Keep the version numbers the same on both channels, so a site never "downgrades" when it moves between them.

## Rules to keep (so a listing is never closed)

- No paid or locked code in the free plugins. Pro features stay in SEMRS Pro; the free plugins may show what Pro adds with a clear "PRO" label and a link (allowed).
- No calls to semrs.com from the WordPress.org copies (the build removes the updater; never re-add it to `trunk`).
- No tracking, no ads in the admin, no "review us" nags that cannot be closed.
- Everything stays GPL; only use images and code you own or that are GPL-compatible (already true).
- Answer reviewer and user-support emails quickly; the Support tab on each plugin page is public.

## Quick checklist

- [ ] WordPress.org account with two-step authentication; username matches `Contributors:` (or tell us)
- [ ] Plugin Check and Theme Check clean on a staging site
- [ ] Theme submitted: `dist/wporg/semrs.zip`
- [ ] Core submitted: `dist/wporg/semrs-core.zip`
- [ ] Blocks submitted: `dist/wporg/semrs-blocks.zip`
- [ ] Tools submitted: `dist/wporg/semrs-tools.zip`
- [ ] Shops submitted after Blocks is approved: `dist/wporg/semrs-shops.zip`
- [ ] Each approved plugin: SVN `trunk`, `tags/<version>`, `assets` (from `dist/wporg-assets/<slug>/`) committed
- [ ] Each plugin page checked on wordpress.org (banner, icon, download)
