# NaukriPatra 2.0 (Android)

Job-focused redesign of the NaukriPatra app — **Find Your Dream Job**.
Package name `in.naukripatra.in` is unchanged, so this ships as an **update** to the app already on Google Play.

| | |
|---|---|
| Version | `versionName 2.0`, `versionCode 10` |
| Target / compile SDK | 36 (Android 16) |
| Min SDK | 24 (Android 7.0) |
| Language | Java, Android Views + Material 3 |
| Data | `https://naukripatra.in/wp-json/naukripatra/v2/` (same REST API as the old app) |
| Ads | Google AdMob — same production ad units as the old app, with Google consent (UMP) |
| Push | OneSignal — same App ID as the old app |

## Screens

`design/app-screenshots/` holds screenshots rendered from this code with real API data:
splash, onboarding (qualification + state), Home, Jobs (with the state dropdown), Job Details,
Saved, More, About / Contact / Privacy / Disclaimer, dark mode, small phone and tablet.

## Open in Android Studio

1. **File → Open** and choose this folder (the one with `settings.gradle`).
2. Let Gradle sync finish. Android Studio uses its bundled JDK 21.
3. Run on a phone or emulator with the ▶ button (debug build).

## Build the release bundle for Play Console

Use the **same upload key** you used for the current Play Store version (`naukripatra_keystore`).

**Option A – Android Studio:** Build → Generate Signed App Bundle / APK → Android App Bundle →
choose your existing keystore → `release` → Create. The file is `app/release/app-release.aab`.

**Option B – command line:** create `keystore.properties` in this folder (it is git-ignored, never commit it):

```properties
storeFile=naukripatra_keystore
storePassword=YOUR_STORE_PASSWORD
keyAlias=YOUR_KEY_ALIAS
keyPassword=YOUR_KEY_PASSWORD
```

then run `./gradlew bundleRelease`. Output: `app/build/outputs/bundle/release/app-release.aab`.

## Upload to Play Console

1. Play Console → your app → **Test and release → Production → Create new release**.
2. Upload `app-release.aab`. Release name: `2.0 (10)`.
3. Release notes (example):
   ```
   <en-IN>
   All-new NaukriPatra – find your dream job faster!
   • Fresh, colourful design that is easy to read
   • Jobs for your qualification and state
   • State dropdown on every job list
   • Save jobs and see which ones close soon
   • Faster loading, dark mode and tablet support
   </en-IN>
   ```
4. **App content → Data safety**: the answers stay the same as before — the app has **no login**
   and collects no personal info itself. Declare what the SDKs collect:
   *Device or other IDs* (AdMob advertising ID, OneSignal push token) and
   *App info and performance* (diagnostics) — used for Advertising and App functionality,
   encrypted in transit, not sold.
5. **Store listing**: do not use the words "Sarkari" or "Govt/Government" in the app name,
   short description or screenshots. Add this line to the full description:
   > NaukriPatra is an independent job information app and is not affiliated with any government
   > body. Job details are collected from official notifications and websites – please verify
   > on the official website before applying.
6. **Privacy policy URL**: `https://naukripatra.in/privacy-policy/` (the in-app policy matches it).

## Play policy checklist (2026)

- Target SDK 36, edge-to-edge UI, predictive back gesture.
- No native libraries, so the 16 KB page-size requirement is met.
- No screen-orientation lock; layouts adapt to phones, foldables and tablets.
- Notification permission asked once (Android 13+); Job alerts can be switched off in More.
- AdMob consent form (UMP) before ads; "Ad privacy choices" shows in More where required.
- Ads are labelled "Ad" and never placed next to the Apply button.
- Every job shows its official source and a disclaimer; all links in Disclaimer/Contact were checked.

## Project layout

```
app/src/main/java/in/naukripatra/app/
  NaukriPatraApp.java      app start, OneSignal, notification → job page
  data/                    API client (cache + retry), models, saved jobs, preferences
  ads/Ads.java             consent, banner, native, interstitial (every 3rd job opened)
  ui/main/                 4 tabs: Home, Jobs, Saved, More
  ui/home, ui/jobs, ui/saved, ui/more
  ui/detail/               Job Details + full article
  ui/onboarding/           qualification + state selection
  ui/info/                 About, Contact, Privacy Policy, Disclaimer
  util/                    dates/deadlines, links, text, insets helpers
```
