# Naukripatra Skin — what it is and how to roll back

`naukripatra-child.zip` = **look-only** GeneratePress child theme for naukripatra.in.

## How the skin wins without breaking anything

`functions.php` adds one class to `<body>`: `np-skin`. Every rule in
`style.css` is written as `.np-skin .np-hero { … }`. That gives it higher
specificity than the CSS already saved in **Appearance → Customize → Additional
CSS**, so the new look applies without deleting, editing or reordering a single
line of your existing CSS. Remove the theme and the class disappears with it.

## The only JavaScript

`assets/js/skin.js` loads on single posts only, in the footer. It:

1. adds the class `np-post-title` to the post `h1`;
2. wraps wide tables in `<div class="np-tablescroll">` so phones scroll the
   table, not the whole page;
3. adds the class `np-links` to the Important Links list;
4. turns each FAQ `h3` + answer into a `<details>` accordion — the answer text
   stays in the page, so FAQ rich results are unaffected;
5. builds the mobile sticky Apply bar **from links already in the post** (it
   never invents a URL).

No text is added, removed or reworded anywhere.

## Verified untouched

| Area | Status |
|---|---|
| Permalinks, slugs, redirects | not touched — no rewrite code in the theme |
| REST API `/wp-json` | not touched — no post type, field or endpoint registered |
| Sitemaps, canonical, schema, SEO plugin | not touched |
| Post content in the database | never written to |
| GeneratePress parent theme | never edited |
| Plugins (Site Kit, OneSignal) | never touched |

## Rollback, in order of speed

1. `define( 'NP_SKIN', false );` in `wp-config.php` — skin off, theme stays active.
2. **Appearance → Themes → GeneratePress → Activate** — exact previous look, instantly.
3. UpdraftPlus → Existing Backups → **Restore** — full revert.

Granular switches (all in `wp-config.php`): `NP_SKIN_FONTS`, `NP_SKIN_POST_JS`,
`NP_SKIN_FAQ`, `NP_SKIN_APPLY_BAR`.

## Test checklist after activating

- [ ] Homepage: hero, LIVE ticker, LIVE RESULTS ticker, Trending Jobs, Quick
      Menu, App & Channels, State-Wise Jobs, all six category cards — all present
- [ ] 3 purane posts apne **same URL** par khulte hain
- [ ] `/bihar/`, `/usa-jobs/`, `/all-india/` load ho rahe hain
- [ ] Menu, footer links, WhatsApp float, back-to-top, mobile bottom menu — chal rahe hain
- [ ] AdSense units dikh rahe hain (Site Kit me agle din impressions check karein)
- [ ] OneSignal prompt aa raha hai
- [ ] Ek job post: Quick Details strip, tables, Important Links buttons, FAQ
      accordion, mobile Apply bar
- [ ] Search Console → 48 ghante baad koi nayi coverage error nahi
