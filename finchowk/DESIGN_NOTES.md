# DESIGN_NOTES.md

Tracks every place the implementation had to diverge from, or fill a gap
left by, the Figma file and/or the FinChowk Claude Code Prompt (the source
document). Per spec 2.1: where Figma and the document conflict on
**behaviour, data, legal text or compliance**, the document wins and the
conflict is logged here.

## Phase 0 — Setup

- **No Figma file link was provided.** `[PASTE_FIGMA_FILE_LINK]` in the
  source document was never replaced with a real link, and no Figma MCP
  connection is available in this environment. Per spec 2.1 ("If not
  connected, ask the owner to connect it or export frames as PNG + tokens
  JSON into `/design`"), Phase 1 cannot generate `lib/core/theme/tokens.dart`
  from real Figma variables until one of the following happens:
  1. The owner pastes a real Figma file link and connects the Figma MCP
     server, or
  2. The owner exports frames as PNG and a Tokens Studio JSON into
     `app/design/` (directory created, currently empty).
  Until then, Phase 1 will use the token table already given in document
  section 1.3 ("Design system — Trust + Energy palette") as the values of
  record, and flag this as a standing gap rather than inventing anything
  not in that table.
- **No real Firebase project exists yet.** `.firebaserc` contains
  placeholder project IDs (`REPLACE_WITH_DEV_FIREBASE_PROJECT_ID` /
  `REPLACE_WITH_PROD_FIREBASE_PROJECT_ID`) and
  `android/app/src/{dev,prod}/google-services.json` are not present. The
  Android Gradle `com.google.gms.google-services` plugin and
  `firebase_core` initialisation are intentionally **not** wired into the
  build yet, because doing so with no real config file would break
  `flutter analyze`/build for every contributor. This is picked up in
  Phase 2 (Data layer) once the owner creates the two Firebase projects
  (dev/prod) and supplies their config files.
- **No approved partner/affiliate URLs exist yet.** The seed partner list in
  document section 4.3 (ZET, QCred, CreditCares, Upstox, Policybazaar) all
  use `[APPROVED_*_PARTNER_LINK]` placeholders. Per the non-negotiable rule
  in 1.1 ("Never invent... every URL comes from the central offer config")
  and 4.3 ("An offer whose URL is still a placeholder or fails validation is
  treated as inactive and never shown"), `assets/config/offers_fallback.json`
  ships as an empty array (`[]`) rather than fabricated/placeholder offer
  data, so nothing resembling a real offer can ever render before the owner
  supplies real, approved partner URLs.
- **No emulator/Android SDK is available in this build environment.** Phase
  0's acceptance criterion "App runs on emulator" could not be verified
  literally. What was verified instead: `flutter pub get`, `flutter
  analyze` (0 issues), and `flutter test` (widget test boots
  `FinChowkApp` and finds the placeholder screen) all pass, which is the
  full extent of what's checkable without a device/emulator or a real
  Firebase project. Re-verify on a real emulator/device before Phase 1
  sign-off if that matters to you.

## Typography

Poppins/Inter/Noto Sans Devanagari font files are not yet vendored into
`app/assets/fonts/` (directory exists, empty) — they're added in Phase 1
once sourced from the Figma file (or Google Fonts, if the owner confirms
that's an acceptable substitute for the exact files Figma exports).
