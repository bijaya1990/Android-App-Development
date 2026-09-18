# Phase 1 — Foundation

Status: **built, not deployed.** Nothing has been done to naukripatra.in.

## What was built

1. **GeneratePress child theme** (`wp-content/themes/naukripatra-child/`)
   with a proper theme header, `functions.php`, `screenshot.png`, and design
   tokens (`:root` CSS variables) for colour, radius, shadow, spacing and fonts.
   Activation changes no visual output — tokens are declared, not yet applied.
2. **Backup / staging workflow** — `docs/BACKUP-AND-STAGING.md`.
3. **Jobs custom post type** `np_job` + taxonomies `np_job_state` (36 states/UTs
   + All India + USA, grouped into North/South/East/West/Northeast for the
   Phase 2 mega menu) and `np_job_category` (latest-job, admit-card, result,
   syllabus, answer-key, admission). Terms seed themselves once.
4. **Job fields** as an ACF field group registered in code, with a native
   metabox fallback writing the **same meta keys** so data survives ACF being
   deactivated. Automatic status calculation from `np_last_date`.
5. **Health checks** in wp-admin: missing parent theme, ACF recommendation,
   and a URL-collision guard.

## URL safety

New URLs live in namespaces that do not exist on the site today:

| Thing | New URL | Existing URL (untouched) |
|---|---|---|
| Single job | `/job/<slug>/` | posts keep their current permalinks |
| Job archive | `/jobs/` | — |
| State | `/job-state/bihar/` | `/bihar/` (WP category, unchanged) |
| Category | `/job-category/result/` | `/result/` etc. (unchanged) |

No existing post, slug, category or permalink rule is modified, renamed or
deleted. The one-time `flush_rewrite_rules()` only rebuilds WordPress's rule
cache — it never rewrites saved slugs.

Migration of existing Latest Jobs posts is **additive** and comes as a separate
reviewed step: the old post stays published at its old URL; the Jobs entry is a
new record. Nothing is deleted or 301'd without your explicit go-ahead.

## Deploy steps (only after you confirm a backup exists)

1. Take the UpdraftPlus backup (`docs/BACKUP-AND-STAGING.md` §1).
2. Zip `wp-content/themes/naukripatra-child/`.
3. **Appearance → Themes → Add New → Upload Theme** → Activate.
4. **Settings → Permalinks** → Save (no changes, just click Save).
5. Install **Advanced Custom Fields** (free) — optional but recommended.
6. Run the smoke test (`BACKUP-AND-STAGING.md` §4).

## What to test

- [ ] Site looks identical to before activation (homepage, post, category page)
- [ ] 3 old posts open at byte-identical URLs
- [ ] `/bihar/`, `/usa-jobs/`, `/all-india/` still load
- [ ] Menus, widgets, both tickers, WhatsApp/Telegram links work
- [ ] AdSense units render; OneSignal prompt appears
- [ ] **Jobs** menu appears in wp-admin; *Add New* shows the Job Details form
- [ ] **Jobs → States** lists 38 terms; **Job Categories** lists 6
- [ ] Publish one test job, confirm `/job/<slug>/` loads and the Last Date and
      Status columns are correct in Jobs → All Jobs, then trash it
- [ ] No red/orange admin notice about URL collisions

## Rollback

| Level | Action | Effect |
|---|---|---|
| 1 | `define( 'NP_ENABLE_JOBS_CPT', false );` in `wp-config.php` | Jobs UI hidden; all job posts + meta kept in the DB |
| 2 | Appearance → Themes → activate **GeneratePress** | Exact previous site restored; job data kept |
| 3 | UpdraftPlus → Restore | Full revert to the pre-change backup |

Also delete options `np_terms_seeded` and `np_rewrite_flush_version` only if you
want the seeding to run again — they are harmless otherwise.

## Lighthouse

Phase 1 adds one small CSS file (~2 KB) and no JavaScript, so scores should be
unchanged. Record the **before** numbers now so later phases can be compared:
run PageSpeed Insights on the homepage, one state page and one job post,
mobile + desktop, and note LCP / CLS / TBT.

## Flagged for later (free-tier limits)

* ACF **Pro** would give the Phase 4 FAQ repeater and vacancy-breakup repeater
  natively. Free fallback: structured textarea parsing. Works, less pretty.
* GenerateBlocks **Pro** would replace some Phase 2/3 custom PHP with query
  loops editable in the block editor.
* Neither is required; both are convenience upgrades.
