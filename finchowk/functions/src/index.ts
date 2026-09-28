/**
 * FinChowk Cloud Functions.
 *
 * Phase 0 (Setup): empty entry point, TS build only.
 * Phase 4 (Analytics) adds `aggregateClick` (clicks/{autoId} onCreate ->
 * stats/daily_yyyymmdd) and the scheduled 90-day raw-click purge.
 * Phase 5 (Admin panel) adds the `setAdmin` callable used by
 * functions/scripts/setAdmin.ts to grant the admin custom claim.
 */
export {};
