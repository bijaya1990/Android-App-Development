# -*- coding: utf-8 -*-
"""
Renders the .docx to PDF with LibreOffice, then reports where every heading and table
caption landed.  Writes page_map.json for the second build pass.

Usage: python3 measure.py <report.docx>
"""
import json
import os
import re
import subprocess
import sys

import pypdfium2 as pdfium

sys.path.insert(0, "/root/.claude/skills/docx/scripts")
from office.soffice import run_soffice  # noqa: E402

import build_docx as B  # noqa: E402


def norm(s):
    s = s.replace("—", "-").replace("–", "-").replace("’", "'")
    s = s.replace("“", '"').replace("”", '"').replace("‘", "'")
    s = s.replace("ﬁ", "fi").replace("ﬂ", "fl")
    return re.sub(r"\s+", " ", s).strip().lower()


def to_pdf(docx_path, outdir):
    run_soffice(
        ["--headless", "--convert-to", "pdf", "--outdir", outdir, docx_path],
        check=True, capture_output=True, timeout=300,
    )
    return os.path.join(outdir, os.path.splitext(os.path.basename(docx_path))[0] + ".pdf")


def page_texts(pdf_path):
    doc = pdfium.PdfDocument(pdf_path)
    return [norm(doc[i].get_textpage().get_text_range()) for i in range(len(doc))]


def find(pages, needle, start=0):
    n = norm(needle)
    for i in range(start, len(pages)):
        if n in pages[i]:
            return i + 1
    return None


def main(docx_path):
    outdir = os.path.dirname(os.path.abspath(docx_path))
    pdf = to_pdf(docx_path, outdir)
    pages = page_texts(pdf)

    # skip the front matter: the TOC and List of Tables repeat every heading, so
    # matching must start on the page after the List of Abbreviations.
    body_start = 0
    for i, t in enumerate(pages):
        if "list of abbreviations" in t:
            body_start = i + 1

    pmap, cursor, missing = {}, body_start, []
    for text, _lvl in B.TOC_ENTRIES:
        p = find(pages, text, cursor)
        if p is None:
            missing.append(text)
            p = 0
        else:
            cursor = p - 1
        pmap[text] = p

    cursor = body_start
    for cap in B.TABLE_CAPTIONS:
        p = find(pages, cap, cursor)
        if p is None:
            missing.append(cap)
            p = 0
        else:
            cursor = p - 1
        pmap[cap] = p

    json.dump(pmap, open(os.path.join(outdir, "page_map.json"), "w"), indent=1)

    ch1 = pmap.get("CHAPTER 1: INTRODUCTION", 0)
    bib = pmap.get("BIBLIOGRAPHY (WORKS CITED — MLA STYLE)", 0)
    print(f"total pages          : {len(pages)}")
    print(f"chapter 1 starts     : p.{ch1}")
    print(f"bibliography starts  : p.{bib}")
    print(f"MAIN CONTENT (Ch1-8) : {bib - ch1} pages")
    print(f"front matter         : {ch1 - 1} pages")
    print(f"back matter          : {len(pages) - bib + 1} pages")
    if missing:
        print("NOT FOUND:", *missing, sep="\n  ")

    # per-chapter span
    chs = [t for t, l in B.TOC_ENTRIES if l == 0 and t.startswith("CHAPTER")]
    starts = [pmap[c] for c in chs] + [bib]
    print("\nper-chapter pages:")
    for i, c in enumerate(chs):
        print(f"  {c.split(':')[0]:<10} p.{starts[i]:>3} - {starts[i+1]-1:>3}"
              f"  ({starts[i+1]-starts[i]} pp)")
    return bib - ch1


if __name__ == "__main__":
    main(sys.argv[1] if len(sys.argv) > 1 else "report.docx")
