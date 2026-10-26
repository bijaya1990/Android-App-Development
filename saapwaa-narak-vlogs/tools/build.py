"""Build the Saapwaa ebook PDF from manuscript/*.md.

    python3 tools/build.py

Steps: check every scene (stop on errors) -> Markdown to HTML -> render with
Chromium -> find where each chapter landed -> render again with real page
numbers in the contents -> prepend the cover -> report the verified page count.
"""
import html
import pathlib
import re
import subprocess
import sys

import markdown
from pypdf import PdfReader, PdfWriter

sys.path.insert(0, str(pathlib.Path(__file__).parent))
import scenes as scene_tools  # noqa: E402

ROOT = pathlib.Path(__file__).resolve().parent.parent
DIST = ROOT / "dist"
BUILD = ROOT / "build"
BATCH_LABEL = "Saapwaa’s Narak Vlogs · Production Batch 1"
OUT_NAME = "saapwaa-narak-vlogs-batch-01.pdf"

TAGS = {
    "{SRC}": '<span class="tag src">SOURCE-BASED</span>',
    "{INT}": '<span class="tag int">INTERPRETATION</span>',
    "{FIC}": '<span class="tag fic">FICTIONAL COMEDY</span>',
    "{UNC}": '<span class="tag unc">UNVERIFIED</span>',
}


def dialogue_html(block):
    rows = []
    for raw in block.strip().splitlines():
        raw = raw.strip()
        if not raw:
            continue
        if raw.startswith("("):
            rows.append(f'<p class="dl-note">{html.escape(raw)}</p>')
            continue
        speaker, _, line = raw.partition(":")
        rows.append(f'<p><span class="dl-sp">{html.escape(speaker)}:</span> '
                    f'{html.escape(line.strip())}</p>')
    return '<div class="dialogue" lang="hi">' + "".join(rows) + "</div>"


def prompt_html(kind, block):
    block = block.strip()
    label = "IMAGE PROMPT · 9:16" if kind == "image" else f"VIDEO PROMPT · {len(block)} / 900 characters"
    return (f'<div class="prompt {kind}"><div class="plabel">{label}</div>'
            f'<pre>{html.escape(block)}</pre></div>')


def preprocess(md):
    def fence(m):
        kind, body = m.group(1), m.group(2)
        out = dialogue_html(body) if kind == "dialogue" else prompt_html(kind, body)
        return "\n" + out + "\n"
    # Keep each field label on the same page as its prompt or dialogue box.
    def field(m):
        label, kind, body = m.group(1), m.group(2), m.group(3)
        out = dialogue_html(body) if kind == "dialogue" else prompt_html(kind, body)
        return f'\n<div class="field-block"><p><strong>{label}</strong></p>{out}</div>\n'
    md = re.sub(r"^\*\*(IMAGE PROMPT:|VIDEO PROMPT — 10 SECONDS:|DIALOGUE:)\*\*\s*\n\s*\n"
                r"~~~(image|video|dialogue)\n(.*?)\n~~~\s*$", field, md, flags=re.M | re.S)
    md = re.sub(r"^~~~(image|video|dialogue)\n(.*?)\n~~~\s*$", fence, md, flags=re.M | re.S)
    for k, v in TAGS.items():
        md = md.replace(k, v)
    return md


def register_md(scenes):
    rows = ["# Appendix — Scene Register (Batch 1)", "",
            "Generated automatically by `tools/scenes.py` from the scenes in this batch. "
            "“Video” is the exact character count of the video prompt, including spaces "
            "(hard limit 900). “Words” counts Hindi words in the dialogue.", "",
            "| ID | Scene title | Source status | Video | Words |",
            "|---|---|---|---:|---:|"]
    for s in scenes:
        status = s["status"].split("—")[0].split(".")[0].strip()
        rows.append(f"| E{s['episode']:02d}-S{s['scene']:02d} | {s['title']} | {status} | "
                    f"{s['chars']} | {s['words']} |")
    longest = max(s["chars"] for s in scenes)
    rows += ["", f"**Totals:** {len(scenes)} scenes · longest video prompt {longest} characters · "
             f"0 prompts at or over 900 characters.", ""]
    return "\n".join(rows)


def to_html(md_text, contents_rows):
    body = markdown.markdown(md_text, extensions=["tables", "attr_list", "md_in_html", "sane_lists"])
    # Mark each chapter (h1) so we can find its page in the rendered PDF.
    count = [0]

    def h1(m):
        count[0] += 1
        return f'<h1 id="ch{count[0]}"{m.group(1)}><span class="mk">@@CH{count[0]:02d}@@</span>'
    body = re.sub(r"<h1([^>]*)>", h1, body)
    # Wrap each scene so it is kept together on the page where possible.
    parts = re.split(r'(?=<h3>EPISODE \d+ — SCENE \d+</h3>)', body)
    body = parts[0] + "".join(
        f'<section class="scene">{p}</section>' if p.startswith("<h3>EPISODE") else p for p in parts[1:])
    body = body.replace("<!--CONTENTS-->", contents_rows)
    css = (ROOT / "tools" / "book.css").read_text(encoding="utf-8")
    return (f'<!doctype html><html lang="en"><head><meta charset="utf-8">'
            f'<title>Saapwaa’s Narak Vlogs — Batch 1</title><style>{css}</style></head>'
            f'<body>{body}</body></html>'), count[0]


def chapter_titles(md_text):
    return [re.sub(r"\{.*?\}", "", t).strip() for t in re.findall(r"^# (.+)$", md_text, re.M)]


def contents_html(titles, pages):
    rows = []
    for i, t in enumerate(titles, 1):
        pg = pages.get(i, "…")
        rows.append(f'<div class="toc-row"><span>{html.escape(t)}</span>'
                    f'<span class="dots"></span><span>{pg}</span></div>')
    return '<div class="toc">' + "".join(rows) + "</div>"


def render(html_path, pdf_path, *flags):
    subprocess.run(["node", str(ROOT / "tools" / "render.js"), str(html_path), str(pdf_path), *flags],
                   check=True)


def find_pages(pdf_path, n):
    found = {}
    reader = PdfReader(str(pdf_path))
    for pno, page in enumerate(reader.pages, 1):
        text = page.extract_text() or ""
        for m in re.finditer(r"@@CH(\d\d)@@", text):
            found.setdefault(int(m.group(1)), pno)
    return found, len(reader.pages)


def main():
    scenes, bad = scene_tools.run()
    if bad:
        print("Scene check failed; run tools/scenes.py for details.")
        sys.exit(1)
    BUILD.mkdir(exist_ok=True)
    DIST.mkdir(exist_ok=True)

    md_text = "\n\n".join(p.read_text(encoding="utf-8")
                          for p in sorted((ROOT / "manuscript").glob("*.md")))
    md_text += "\n\n" + register_md(scenes)
    titles = chapter_titles(md_text)
    pre = preprocess(md_text)

    body_html = BUILD / "body.html"
    body_pdf = BUILD / "body.pdf"
    # Pass 1: placeholder page numbers. Pass 2: real ones.
    # Page numbers are the printed footer numbers; the cover is unnumbered.
    pages = {}
    for _ in range(2):
        doc, n = to_html(pre, contents_html(titles, pages))
        body_html.write_text(doc, encoding="utf-8")
        render(body_html, body_pdf, "--label", BATCH_LABEL)
        pages, _ = find_pages(body_pdf, n)
    missing = [i for i in range(1, n + 1) if i not in pages]
    if missing:
        print("Could not locate chapters:", missing)
        sys.exit(1)

    # Pass 3: same layout, but strip the 1px chapter markers so they never end
    # up in copied text. Re-check that no chapter moved.
    doc, _ = to_html(pre, contents_html(titles, pages))
    doc = re.sub(r'<span class="mk">@@CH\d\d@@</span>', '<span class="mk"></span>', doc)
    body_html.write_text(doc, encoding="utf-8")
    pass2_count = len(PdfReader(str(body_pdf)).pages)
    render(body_html, body_pdf, "--label", BATCH_LABEL)
    if len(PdfReader(str(body_pdf)).pages) != pass2_count:
        print("Page count changed after removing markers; aborting.")
        sys.exit(1)
    cover_pdf = BUILD / "cover.pdf"
    render(ROOT / "tools" / "cover.html", cover_pdf, "--no-footer")

    writer = PdfWriter()
    for src in (cover_pdf, body_pdf):
        for page in PdfReader(str(src)).pages:
            writer.add_page(page)
    writer.add_metadata({"/Title": "Saapwaa’s Narak Vlogs — Production Batch 1",
                         "/Author": "Saapwaa Narak Vlogs production team"})
    out = DIST / OUT_NAME
    with open(out, "wb") as fh:
        writer.write(fh)

    # Plain-text prompt sheet: the cleanest source for copy and paste.
    lines = []
    for sc in scenes:
        lines += [f"=== EPISODE {sc['episode']} — SCENE {sc['scene']} · {sc['title']} ===", "",
                  "IMAGE PROMPT:", sc["image"], "",
                  f"VIDEO PROMPT — 10 SECONDS ({sc['chars']} characters):", sc["video"], "",
                  "DIALOGUE:", sc["dialogue"], "", ""]
    (DIST / OUT_NAME.replace(".pdf", "-prompts.txt")).write_text("\n".join(lines), encoding="utf-8")

    total = len(PdfReader(str(out)).pages)
    print(f"Wrote {out.relative_to(ROOT)} — verified page count: {total} (cover + {total - 1} numbered pages)")
    for i, t in enumerate(titles, 1):
        print(f"  p.{pages[i]:>3}  {t}")


if __name__ == "__main__":
    main()
