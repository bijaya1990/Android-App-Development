# FinChowk

Financial offer discovery & referral platform for Indian users — **not a
lender**. It only displays partner-supplied offer information and opens the
partner's official website/app when the user taps "Apply Now". Full product
brief: `finchowk/FinChowk_Claude_Code_Prompt.pdf`; running divergence/gap log:
[`finchowk/DESIGN_NOTES.md`](finchowk/DESIGN_NOTES.md).

## Layout

```
finchowk/
  app/         Flutter app (lib/, android/, test/, integration_test/)
  admin/       Admin panel (React + Vite + TS), deployed to Firebase Hosting
  functions/   Cloud Functions (TypeScript) — click aggregation, admin claims
  firebase.json, firestore.rules, firestore.indexes.json, .firebaserc
```

## Status

Phase 0 (Setup) only. See `DESIGN_NOTES.md` for what's still blocked on the
owner (a real Figma link, real Firebase projects, and approved partner
URLs). Nothing beyond this has been built yet — remaining phases (design
system, data layer, screens, analytics, admin panel, hardening, store
readiness) are built one at a time with a stop for review after each, per
the source document's instructions.

## Local development

```bash
# Flutter app
cd finchowk/app && flutter pub get && flutter analyze && flutter test

# Admin panel
cd finchowk/admin && npm install && npm run lint && npm run build

# Cloud Functions
cd finchowk/functions && npm install && npm run build
```

CI runs the same three checks on every push/PR touching each directory
(`.github/workflows/{app,admin,functions}-ci.yml`).
