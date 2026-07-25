# EMI Calculator — Car, Home, Personal & Bike Loan

A premium, offline-first Android EMI calculator built with Java, XML layouts, and Material Design 3. Supports Car, Home, Personal, and Bike loan EMI planning with amortization schedules, charts, and PDF reports.

## Tech stack

- Java, AndroidX, Material Design 3 (dynamic color, edge-to-edge, dark mode)
- MVVM architecture (`ViewModel` + `LiveData`)
- View Binding, no deprecated APIs
- [MPAndroidChart](https://github.com/PhilJay/MPAndroidChart) for the principal/interest pie chart
- Built-in `android.graphics.pdf.PdfDocument` for PDF report generation (no third-party PDF dependency)
- minSdk 26 (Android 8.0), compileSdk / targetSdk 35

## Project structure

```
app/src/main/java/com/naukripatra/emicalculator/
├── EmiApplication.java          # Dynamic color + theme bootstrap
├── model/                       # LoanType, EmiResult, ScheduleEntry
├── util/                        # EMI math, currency formatting, validation, PDF, theme prefs
└── ui/
    ├── home/                    # Home screen — 4 loan-type cards
    ├── calculator/               # Dynamic input form per loan type
    ├── result/                  # Summary / Chart / Schedule tabs
    ├── schedule/                # Amortization table adapter
    ├── settings/                 # Dark mode, rate/share app, about
    └── legal/                   # Privacy Policy, Terms of Use, Contact Us, Open Source
```

## Building

Open the project root in Android Studio (Ladybug or newer) and let Gradle sync — no additional configuration is required. The project uses the Gradle Kotlin DSL (`build.gradle.kts`) build scripts and the included Gradle wrapper.

> Note: this repository was assembled in a sandboxed environment without access to the Android SDK or Google's Maven repository, so the build could not be executed end-to-end here. All Java sources, resources, and layouts were validated for well-formed XML, balanced braces, and consistent View Binding field references; a full `./gradlew assembleDebug` should be run locally/in CI before release.

## Privacy & Terms

Bundled locally at `app/src/main/assets/privacy_policy.html` and `terms_of_use.html`, shown in-app via Settings → Privacy Policy / Terms of Use.
