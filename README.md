# Question Paper Maker

*Create Professional Exam Papers in Minutes*

An offline-first Android app that lets teachers write questions and automatically get a
print-ready, professionally formatted exam paper — no manual formatting, no Microsoft Word.
Built with Kotlin and Jetpack Compose.

## Status

This is a working foundation and core end-to-end flow, not the full product vision. It covers:

- **Home** — recent papers, search, create/duplicate/delete
- **Paper Details** — institution, exam metadata, academic info, instructions (only Exam Name,
  Subject and Maximum Marks are required; everything else is optional, per-country flexible)
- **Paper Layout** — page size, orientation, margins, header/footer style, page numbering,
  signature area, watermark
- **Section Builder** — unlimited sections, per-section numbering style and instructions,
  add/reorder/delete questions and sections
- **Question Entry** — Multiple Choice, Fill in the Blank, Short/Long Answer, Essay, Case Study,
  Numerical, Practical, and more, plus fully custom types; sub-questions, internal choice
  ("OR" questions), and options editor for MCQ/True-False
- **Auto-numbering & marks engine** — numbers and totals recompute instantly on every edit,
  never leaving gaps or manual arithmetic
- **Live Preview** — renders the *actual* generated PDF (via `PdfRenderer`) so preview and
  export can never visually drift apart
- **PDF export, print & share** — automatic page breaks, header/footer, watermark, page
  numbering; print via the system print dialog, share via any installed app
- **Autosave** — every edit is persisted to a local Room database with no explicit save step

Deferred to future work (see the original product spec for full scope): DOCX export, reusable
templates, Question Bank, backup/restore, OCR/voice input, password-protected export, and a
dedicated Settings screen for global defaults beyond the "save institution as default" action
already on the Paper Details screen.

## Architecture

- **UI**: Jetpack Compose, Material 3, Navigation-Compose, one `ViewModel` per screen
  (hand-rolled `ViewModelProvider.Factory`, no DI framework — the app is small enough that one
  would be pure ceremony)
- **Data**: Room database (`PaperEntity` / `SectionEntity` / `QuestionEntity`), a single
  `PaperRepository` that every screen goes through, `kotlinx.serialization` for the JSON columns
  (sub-questions, MCQ options)
- **Engines** (`engine/`): pure-Kotlin, unit-tested logic for numbering, marks calculation, and
  pre-export validation — deliberately Android-independent so they're fast to test
- **PDF** (`pdf/`): `PdfExporter` draws directly onto `android.graphics.pdf.PdfDocument` using
  `StaticLayout`/`TextPaint` for correct, unmodified Unicode text shaping and wrapping (no
  character substitution, no autocorrect); the same generated PDF is rasterized for the preview
  screen and streamed to the system print pipeline

```
app/src/main/java/com/questionpapermaker/app/
├── data/            # Room entities, DAOs, repositories, domain models
├── engine/           # Numbering / marks / validation (pure Kotlin, unit tested)
├── pdf/               # PdfDocument-based export, print adapter, page metrics
├── navigation/     # NavHost + route definitions
└── ui/                  # One package per screen (home, paperdetails, layout, sections, preview)
```

## Building

Requires Android Studio (or the Android SDK + platform 35 installed) since AGP and the
`androidx.*` libraries are hosted on Google's Maven repository. Kotlin unit tests under
`engine/` build and run standalone via `./gradlew test`.

```
./gradlew assembleDebug
./gradlew test
```
