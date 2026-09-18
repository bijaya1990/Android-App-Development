# Naukripatra Child Theme (GeneratePress)

All custom code for the naukripatra.in redesign. The GeneratePress parent theme
is **never** edited — updating GeneratePress will never overwrite anything here.

## Install

1. Zip the `naukripatra-child` folder.
2. **Appearance → Themes → Add New → Upload Theme** → upload → **Activate**.
   (GeneratePress must stay installed — it is the parent.)
3. Visit **Settings → Permalinks** once and click *Save* (rebuilds the URL cache).

Nothing about the site's appearance changes after activation. Phase 1 is
foundation only.

## Files

| File | What it does |
|---|---|
| `style.css` | Theme header + design tokens (`:root` CSS variables): colours, radius, shadow, spacing scale, fonts. Change brand colours here in one place. |
| `functions.php` | Loads everything, enqueues styles, one-time rewrite flush. |
| `inc/flags.php` | Feature flags — the rollback switch for every phase. |
| `inc/helpers.php` | Field reading, date parsing, **automatic status calculation**. |
| `inc/cpt-jobs.php` | Jobs post type, State + Job Category taxonomies, state/region list. |
| `inc/fields-jobs.php` | ACF field group (with a metabox fallback so data survives without ACF) + Jobs admin columns. |
| `inc/admin-notices.php` | Health checks: missing parent theme, missing ACF, URL collisions. |

## Which admin screen controls which element

| You want to change… | Go to |
|---|---|
| Add / edit a job posting | **Jobs → Add New** (fill the *Job Details* form — no code) |
| The state list | **Jobs → States** |
| Latest Job / Admit Card / Result / … list | **Jobs → Job Categories** |
| Logo, site title, favicon | **Appearance → Customize → Site Identity** |
| Global colours | **Appearance → Customize → Colors** (brand tokens: `style.css` `:root`) |
| Fonts | **Appearance → Customize → Typography** |
| Header / footer / sidebar layout | **Appearance → Customize → Layout** |
| Menus (incl. the future States mega menu) | **Appearance → Menus** |
| Sidebar & footer widgets, ad slots | **Appearance → Widgets** |
| Push notifications | **OneSignal** menu (unchanged) |
| AdSense | **Site Kit** menu (unchanged) |

## Status badges are automatic

Never tag a job as open/closed by hand. The badge comes from **Last Date to
Apply**:

* more than 7 days left → 🟢 Apply Now
* 7 days or fewer → 🟡 Closing Soon
* past the last date → 🔴 Closed
* no last date entered → treated as Apply Now

## Turning things off (rollback)

Add to `wp-config.php`, above `/* That's all, stop editing! */`:

```php
define( 'NP_ENABLE_JOBS_CPT', false );
```

The Jobs menu disappears; **no job posts and no field data are deleted** — they
stay in the database and reappear when the flag is removed. Switching back to
plain GeneratePress (Appearance → Themes) restores the previous site exactly.

## Free-tier notes

* **ACF free** covers every field above. An FAQ *repeater* (Phase 4) and
  flexible content need **ACF Pro** — the free fallback will be a structured
  textarea (`Question | Answer` per line). Works, just less pretty to edit.
* **GenerateBlocks free** is enough for Phases 2–3 layouts. GenerateBlocks Pro
  would add loop/query blocks and global styles, saving some custom PHP.
* No Elementor Pro needed anywhere in this plan.
