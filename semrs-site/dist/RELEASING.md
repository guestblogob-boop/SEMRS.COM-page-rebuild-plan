# How to release an update and notify every live site

## How sites get notified

Every WordPress site checks for updates about twice a day. SEMRS themes and plugins (from version 1.1.0 / Tools 1.2.0) carry an `Update URI` header and the SEMRS updater, so each site asks **semrs.com** whether a newer version exists. When you publish one, WordPress on every site shows its normal notices:

- **Dashboard → Updates**: "SEMRS Core … Update to 1.2.0"
- **Plugins** / **Themes** screens: "There is a new version of SEMRS Core available. View version 1.2.0 details or update now."
- The update count in the admin bar and admin menu
- If the site owner turned on **auto-updates** for that plugin or theme, WordPress installs it automatically and emails them.

You do not email or contact anyone yourself. The notice is the notification, and it reaches every site where the product is installed and active.

**Pro:** the update is offered to everyone, but only sites with a **valid license key** get the download. Without a key, an expired key or one over its site limit, the notice still shows but WordPress says the package is unavailable.

## One-time setup on semrs.com

1. Plugins → Add New → Upload `dist/server/semrs-update-server-1.0.0.zip` → Activate. This creates the **SEMRS Updates** menu (Releases, License keys, Live sites).
2. **SEMRS Updates → Releases**: upload each zip from `dist/releases/` (the product and version are read from the zip). Add the changelog.
3. **SEMRS Updates → License keys**: create a key for each Pro customer (email, number of sites, expiry date). Local and staging sites never use up a slot.

## Releasing a new version (every time)

1. Make and test your changes on test.semrs.com.
2. Build the release on your PC, from the `semrs-site` folder:
   ```
   node release.mjs --bump=patch --notes="Fix X|Improve Y"
   ```
   - `--bump=patch` (1.1.0 → 1.1.1) for fixes, `minor` (→ 1.2.0) for features, `major` (→ 2.0.0) for breaking changes.
   - `--only=semrs-core` to release one product; `--set=semrs-pro:2.0.0` to choose an exact version.
   - The script updates the versions, readmes and changelogs, then builds `dist/releases/<slug>-<version>.zip` (self-hosted) and `dist/wporg/<slug>.zip` (WordPress.org build, no custom updater).
3. On semrs.com: **SEMRS Updates → Releases → Publish release**, upload the new zip(s), paste the changelog.
4. Done. Every live site sees the update within about 12 hours, or immediately when someone clicks **Dashboard → Updates → Check again**.
5. Watch **SEMRS Updates → Live sites**: each site's installed version turns from "update pending" to the new version as owners update.

## Important: sites with older copies

Sites running versions **before** 1.1.0 (theme/Core/Pro) or 1.2.0 (Tools) do not have the updater yet. They need **one manual update**: upload the new zip in Plugins/Themes → Add New → Upload and choose "Replace current with uploaded". After that, every future update arrives automatically.

## WordPress.org (if you list the free versions there)

WordPress.org does not allow custom updaters. Upload the `dist/wporg/` zips; they have the updater and the `Update URI` line removed. For plugins, commit the new version to the plugin's SVN `trunk` and a `tags/<version>` folder; for themes, upload the new zip in the theme's WordPress.org dashboard. WordPress.org then notifies every site itself. Do not install both a WordPress.org copy and a semrs.com copy of the same product on one site.

## Privacy (put this in your Privacy Policy and readme)

Each update check sends: the product and its version, the site address, the WordPress and PHP versions, and (SEMRS Pro only) the license key. Nothing else. It is used only to offer updates, check licenses and count active sites. Site owners can switch checks off with:
```php
add_filter( 'semrs_updater_enabled', '__return_false' );
```

## Tested (local preview, 2026-10-11)

- WordPress core listed SEMRS theme, SEMRS Core and SEMRS Pro updates (1.1.0 → test release 1.9.0) on Dashboard → Updates and the Plugins screen.
- Pro license reported back as "valid"; the signed download returned the 1.9.0 zip; a tampered link was refused (403).
- A 1-site key was refused on a second real site ("limit"), kept working on its first site, and was never blocked on staging sites.
- Live sites listed each site with product, installed and latest version, and WordPress/PHP versions.
- Not testable in the sandbox: the one-click install itself, because the preview cannot download from its own address. On a real site it uses WordPress's standard updater with the signed link above.
