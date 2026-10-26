# Saapwaa’s Narak Vlogs — ebook production

A planned 300-page handbook: source-checked Garuda Purana research, an original comedy vlog series bible, and AI image/video prompts for every 10-second scene. It is produced in numbered batches.

**Current output:** `dist/saapwaa-narak-vlogs-batch-01.pdf` (Batch 1: 43 pages, measured by the build) and `dist/saapwaa-narak-vlogs-batch-01-prompts.txt` (plain-text prompts for copy and paste).

## Layout

- `manuscript/*.md` — the book source, in reading order. Scenes use `~~~image`, `~~~video` and `~~~dialogue` blocks.
- `tools/scenes.py` — checks every scene: field order, the 900-character video limit, the character-lock phrases, and that each dialogue line is copied verbatim into its video prompt.
- `tools/build.py` — runs the checks, renders the PDF with Chromium (Playwright), fills in measured page numbers, adds the cover and prints the verified page count.
- `tools/book.css`, `tools/cover.html` — page design and cover art.
- `fonts/` — Noto Serif/Sans Devanagari (SIL Open Font License, via @fontsource).

## Build

```
pip install markdown pypdf   # Node with the playwright package must also be available
python3 tools/scenes.py      # scene report
python3 tools/build.py       # writes dist/
```
