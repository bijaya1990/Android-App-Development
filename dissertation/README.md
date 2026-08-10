# Condensed Field Work Report — Microfinance Institutions and SHGs in Pallahara

The original submission ran to **54 pages**, of which Chapters 1–8 occupied 38.
This version reduces the **main content to exactly 30 pages** (pp. 10–39) while
keeping every chapter, section, table and citation of the original.

## Deliverables

| File | What it is |
| --- | --- |
| `Microfinance-Institutions-and-SHGs-in-Pallahara-Condensed.docx` | Editable MS Word file — the deliverable |
| `Microfinance-Institutions-and-SHGs-in-Pallahara-Condensed.pdf` | Rendered PDF, used to verify pagination |

## Page budget

| Part | Pages |
| --- | --- |
| Front matter (title, certificate, declaration, acknowledgement, contents, tables, abbreviations) | 9 |
| **Main content — Chapters 1 to 8** | **30** |
| Bibliography and Appendix I | 4 |
| **Total** | **43** |

Per chapter: Ch 1 — 5 pp · Ch 2 — 3 · Ch 3 — 4 · Ch 4 — 4 ·
Ch 5 — 3 · Ch 6 — 4 · Ch 7 — 3 · Ch 8 — 4.

## Formatting

Measured off the original PDF and reproduced: A4, 1-inch margins, Times New Roman
12 pt, 1.5 line spacing, justified body with a 0.85 cm first-line indent, running
title centred in the header, page number centred in the footer, and real Word
footnotes (46 of them) rather than endnotes or inline citations.

## How the page count was hit

`src/` holds the generator. `content.py` carries the text as structured blocks;
`build_docx.py` renders it to `.docx` (including the OOXML footnote part, which
python-docx does not provide); `measure.py` converts to PDF with LibreOffice,
reports where each heading landed, and writes `page_map.json`, which the next
build pass uses to fill in real page numbers in the Table of Contents and List of
Tables. Building and measuring alternately until the page map stops changing gives
a document whose contents pages match its own pagination.

```
python3 build_docx.py report.docx page_map.json
python3 measure.py report.docx
```

## What changed from the original

**Cut.** Cross-chapter repetition, which was the bulk of the saving — the Maa
Gojabayani SHG, the Hot Cooked Food programme and the mushroom-cultivation case
were each described nearly in full in Chapters 5, 7 and 8; they are now stated
once and cross-referenced. Literature summaries in Chapter 2, the methodology
subsections in 1.6, and the institutional description in Chapter 4 were tightened.
Consecutive footnotes citing the same source were merged.

**Kept.** All eight chapters, every numbered section, every statistic, every case
study, all 33 bibliography entries and the full Appendix I questionnaire.

**Added**, to use the 30-page budget on substance rather than whitespace:

- Table 1.1, *Objectives and Corresponding Research Methods* — listed in the
  original's List of Tables but absent from its body.
- Table 6.4, *Dimensions of Perceived Empowerment Reported by SHG Members*, on the
  same qualitative footing as the field-observation tables it sits beside.
- Analytical paragraphs closing §2.4, §3.6, §6.6 and Case Study 7.1, tying the
  evidence back to the objectives stated in §1.5.

**Corrected.** The findings in §8.1 were numbered 6–13 in the original and now run
1–8. The Table of Contents and List of Tables previously cited page numbers that
did not match the document and listed tables that did not exist; both are now
generated from the rendered file and are accurate.
