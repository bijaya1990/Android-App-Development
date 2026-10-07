# Self-test and assumptions

## Acceptance table (verified in this build on WordPress + SQLite, PHP 8.3, Chromium)
| Check | Result | Evidence |
|---|---|---|
| Old REST keys intact | Pass | A plugin-registered `qualification` field kept its value (`PLUGIN-VALUE`); theme only adds missing keys |
| Order and responsive | Pass | Blocks in spec order; no horizontal scroll at 320/768/1280 on home, list, single, tools; menu toggles, closes on Esc |
| State to list | Pass | Table with Sl, Name, Adv No, Last Date, Posts, Get Details; Sl continues across pages; rel next/prev; filter chips/dropdown work, filtered pages noindex + canonical to base |
| Reader | Pass (light/dark checked by script, not by eye) | Inline styles stripped, tables wrapped, sticky sidebar, overview from meta |
| Schema | Partly verified | JobPosting, FAQPage, BreadcrumbList, Organization, WebSite generated automatically. **Google Rich Results Test not run (no external access)** |
| Post workflow | Pass | Subscriber submission saved as draft, script/onclick stripped, Yoast fields + advt_no saved; approval published it; featured image filled Yoast FB/X image + id, manual value not overwritten |
| Ads | Pass | Code loaded only after interaction/delay; CLS measured 0 |
| Scores | **Not measured** | Lighthouse/PageSpeed, W3C validator and the rich-results test were not run. Run them on your live site |

PHP lint: every file clean. JS syntax: every file clean.

## Assumptions
1. Plugin fields saved under the plain key (or your prefix). Schema-override fields use `_np_job_sector`, `_np_qualification`, `_np_last_date`, `_np_posts_count`, `_np_apply_link`, `_np_notification_link`, `_np_application_fee`; your real names for those were not provided.
2. With Yoast/Rank Math active the theme outputs only JobPosting, FAQPage and ItemList as separate JSON-LD blocks (not merged into Yoast's graph) to avoid duplicates.
3. Yoast has no schema image meta field; FB/X image and image-id fields are synced.
4. Job posts for JobPosting = Latest Jobs category (or posts with vacancy data and no other section).
5. baseSalary only when a rupee figure and a clear unit (per month/year...) exist.
6. Inter font not bundled (file could not be fetched); system-ui fallback used until you add the WOFF2.
7. Post Job form uses a paste-HTML textarea (no rich editor).
8. Dadra/Daman slug as noted in README.

## Manual test checklist
Activate theme; check categories/pages/menus; open each homepage block at 320/768/1280; tap a state, a section, filters, page 2; open a job post (light/dark, A+/A-, share, print); submit the Post Job form as a normal user, approve in Control > Data; paste an Adsterra test code in a slot and confirm it appears after scrolling; run Meta Key Scanner against your latest post.
