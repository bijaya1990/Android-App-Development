# naukripatra.in — premium redesign

WordPress build files for naukripatra.in: a GeneratePress **child theme** plus
the Jobs data model that powers the state-wise job tables and job detail pages.

* Theme: [`wp-content/themes/naukripatra-child/`](wp-content/themes/naukripatra-child/) — see its README for the admin guide
* Backup & staging workflow: [`docs/BACKUP-AND-STAGING.md`](docs/BACKUP-AND-STAGING.md)
* Phase 1 notes, test checklist and rollback: [`docs/PHASE-1.md`](docs/PHASE-1.md)

Rules this repo follows: never edit the GeneratePress parent theme, never change
an existing URL or slug, never delete data on deactivation, and ship every phase
behind a feature flag with a documented rollback.
