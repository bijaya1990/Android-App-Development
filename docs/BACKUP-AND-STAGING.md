# Backup & staging workflow (do this before anything goes live)

naukripatra.in is a live, ad-monetised, SEO-ranking site. Every change below
assumes a restorable backup exists **first**.

## 1. Full backup (UpdraftPlus, free)

1. **Plugins → Add New** → install & activate **UpdraftPlus**.
2. **Settings → UpdraftPlus Backups → Settings**
   * Files backup: *Manual* (or Weekly)
   * Database backup: *Daily*
   * Remote storage: **Google Drive** (free, survives a host failure —
     backups stored only on the server are worthless if the server dies)
   * Include: plugins, themes, uploads, others → **all ticked**
3. **Backup / Restore → Backup Now** → tick *database* and *files* →
   wait for "The backup apparently succeeded and is now complete".
4. Download the 5 zip files to your own computer too.

**Do not proceed to any live change until step 4 is done.**

Restore = UpdraftPlus → Existing Backups → **Restore** → tick all components.

## 2. Staging (strongly preferred)

Check your host's cPanel / dashboard for a one-click staging option:

* Hostinger: hPanel → Websites → **Staging**
* SiteGround: Site Tools → Dev → **Staging**
* Cloudways / Kinsta / WP Engine: built-in staging

If your host has none, the free path is **WP Staging** (Plugins → Add New →
"WP Staging") → *Create new staging site*. It clones the DB and files to
`naukripatra.in/staging` behind a login.

Build and test every phase on staging, then repeat the same steps on live.

## 3. If there is no staging at all

Phase 1 is designed to be safe without staging, because:

* it is a **child theme** — the parent theme and all plugins are untouched,
* it **adds** a post type and taxonomies; it changes no existing post,
  category, slug or permalink rule,
* every feature sits behind a flag that can be switched off in `wp-config.php`,
* deactivating the child theme (switch back to GeneratePress) restores the
  previous state instantly, with all data intact.

Still take the backup in step 1. Activate at a low-traffic hour.

## 4. Post-change smoke test (5 minutes, every time)

Run through this list right after any live change:

- [ ] Homepage loads, layout unchanged
- [ ] A Latest Jobs post, an Admit Card post and a Result post open at their
      **original URLs** (spot-check 3 old posts — URL must be byte-identical)
- [ ] A state page (`/bihar/`) and `/usa-jobs/`, `/all-india/` load
- [ ] Main menu, footer menu, sidebar widgets all render
- [ ] Live ticker / results ticker still animates
- [ ] WhatsApp and Telegram links open
- [ ] AdSense units render (check Site Kit → no drop in impressions next day)
- [ ] OneSignal bell / prompt appears; send one test push
- [ ] Search box returns results
- [ ] Google Search Console → no new coverage errors after 48h

## 5. Rollback (universal)

1. **Appearance → Themes → GeneratePress → Activate** (instant, no data loss), or
2. Feature flag off in `wp-config.php` (see the theme README), or
3. UpdraftPlus → Existing Backups → Restore (last resort, full revert).
