# NaukriPatra Pro (GeneratePress child theme) v1.0.0

All-India job aggregator theme. Needs **GeneratePress (free)** as parent. No paid plugins, no jQuery, no tracking.

## Install
1. Install and keep **GeneratePress** (Appearance > Themes > Add New).
2. Upload `naukripatra-pro.zip` (Appearance > Themes > Upload) and **Activate**.
3. On activation the theme auto-creates (never overwriting existing items):
   - Categories: Latest Jobs, Admit Card, Result, Answer Key, Syllabus, Admission, Scholarship & Schemes (extra, for the homepage box), All India, 28 states, 8 UTs. Old slugs kept (`latest-jobs`, `jammu-kashmir`, `andaman-nicobar` ...). UT "Dadra & Nagar Haveli and Daman & Diu" uses slug `dadra-nagar-haveli-daman-diu`; if your old slug differs, edit it in Posts > Categories.
   - Pages: Post a Job, Resume Maker, Photo Resizer, Signature Scanner, About, Contact, Privacy Policy, Disclaimer, DMCA (starter text, edit freely).
   - Menus: Primary and Footer (assigned only if the location was empty).
4. Settings > Permalinks > Save (flushes URLs).
5. Optional: put `inter-var.woff2` (Latin subset) in `assets/fonts/`; it is then preloaded automatically. Without it the system font is used.

## One-page admin guide: NaukriPatra > "NaukriPatra Control"
| Tab | What you do |
|---|---|
| General | Brand text/logo (attachment ID), tagline, Post Job button, who may post (default: any logged-in user; always saved as **draft**), notification email, dark-mode default, GeneratePress CSS on/off, inline CSS, meta key prefix |
| Homepage | Show/hide + order each block, ticker labels/counts/speed, boxes, SEO text block, six button labels/colours |
| Tools | Add/edit tools (name, link, icon, colour, show). Leave name empty to delete |
| Locations | Hide or rename states/UTs, extra location buttons (`USA Jobs|url`) |
| Lists | Rows per page, NEW days, expiry colours, Adv No / Posts columns, live filter |
| Ads | Adsterra code, on/off and device per slot, lazy delay, max ads per page, Social Bar, Popunder |
| SEO | Home title/description, default OG image, noindex search/tags, robots.txt lines |
| Footer / Social | Telegram, WhatsApp, YouTube, Play Store (blank = hidden), disclaimer, copyright |
| Data | **Meta Key Scanner**, **Approval queue** (Preview / Edit / Approve and Publish / Reject), settings export/import (JSON) |

Per-category SEO (title, description, H1, intro, SEO text) is on each category's edit screen.

## How data is read
Each field is read from the plugin's key, trying `key`, `_key`, `np_key`, `_np_key` (+ your prefix), then the article's overview table, then (for Adv No) the title. Use Data > Meta Key Scanner to confirm real names.

## REST / metabox safety
The theme never edits the plugin. Old REST fields (`qualification, last_date, posts_count, organization, salary, employment_type, locality, street, postal_code, job_details`) are re-declared **only if no plugin already registered them**. `advt_no` is a new additive field. Uninstalling/switching theme deletes nothing.

## Ads and performance honesty
Slots reserve fixed sizes (no layout shift). Ad code stays inert until first interaction or the delay, then loads one ad at a time when near the viewport. Adsterra scripts themselves can still lower Lighthouse (TBT, Best Practices, third-party requests) once they load; Lighthouse does not interact, so with the delay it mostly measures the page without ads. Logged-in admins see no ads.
