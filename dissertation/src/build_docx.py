# -*- coding: utf-8 -*-
"""
Builds the condensed field work report as a .docx that reproduces the layout of the
original submission (A4, Times New Roman 12 pt, 1.5 line spacing, justified body,
1-inch margins, running header, real Word footnotes).

Usage:  python3 build_docx.py <output.docx> [page_map.json]

`page_map.json`, when supplied, maps heading text -> page number and is used to fill
in the Table of Contents and List of Tables.  It is produced by measure.py on a first
(placeholder) rendering pass.
"""
import copy
import json
import os
import re
import shutil
import sys
import zipfile

from docx import Document
from docx.enum.section import WD_SECTION
from docx.enum.style import WD_STYLE_TYPE
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_TAB_ALIGNMENT
from docx.oxml import OxmlElement, parse_xml
from docx.oxml.ns import qn
from docx.shared import Cm, Pt, RGBColor

import content as C

W_NS = 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"'

FONT = "Times New Roman"
BODY_PT = 12
LINE_15 = 1.5
BODY_INDENT_CM = 0.85  # first-line indent measured off the original submission

# collects footnote bodies in document order; index+1 is the w:id
FOOTNOTES = []


# ---------------------------------------------------------------- helpers
def set_run(run, size=BODY_PT, bold=False, italic=False, font=FONT):
    run.font.name = font
    run.font.size = Pt(size)
    run.bold = bold
    run.italic = italic
    rpr = run._element.get_or_add_rPr()
    rfonts = rpr.find(qn("w:rFonts"))
    if rfonts is None:
        rfonts = OxmlElement("w:rFonts")
        rpr.insert(0, rfonts)
    for attr in ("w:ascii", "w:hAnsi", "w:cs", "w:eastAsia"):
        rfonts.set(qn(attr), font)


def add_footnote_ref(par, fid):
    xml = (
        f'<w:r {W_NS}><w:rPr><w:rStyle w:val="FootnoteReference"/>'
        f'<w:vertAlign w:val="superscript"/></w:rPr>'
        f'<w:footnoteReference w:id="{fid}"/></w:r>'
    )
    par._p.append(parse_xml(xml))


def emit_runs(par, runs, size=BODY_PT, bold=False, italic=False):
    """runs: list of str | ("i"|"b"|"fn", str)"""
    for item in runs:
        if isinstance(item, str):
            set_run(par.add_run(item), size=size, bold=bold, italic=italic)
        else:
            kind, text = item
            if kind == "fn":
                FOOTNOTES.append(text)
                add_footnote_ref(par, len(FOOTNOTES))
            elif kind == "i":
                set_run(par.add_run(text), size=size, bold=bold, italic=True)
            elif kind == "b":
                set_run(par.add_run(text), size=size, bold=True, italic=italic)
            else:
                raise ValueError(kind)


def para(doc, align=WD_ALIGN_PARAGRAPH.JUSTIFY, line=LINE_15,
         before=0, after=6, left=0, first=0, hanging=0, keep_next=False):
    p = doc.add_paragraph()
    pf = p.paragraph_format
    pf.alignment = align
    pf.line_spacing = line
    pf.space_before = Pt(before)
    pf.space_after = Pt(after)
    if left:
        pf.left_indent = Cm(left)
    if first:
        pf.first_line_indent = Cm(first)
    if hanging:
        pf.first_line_indent = Cm(-hanging)
    pf.keep_with_next = keep_next
    pf.widow_control = True
    return p


def set_cell_text(cell, text, size=11, bold=False, align=WD_ALIGN_PARAGRAPH.LEFT):
    cell.text = ""
    p = cell.paragraphs[0]
    p.alignment = align
    pf = p.paragraph_format
    pf.line_spacing = 1.0
    pf.space_before = Pt(2.5)
    pf.space_after = Pt(2.5)
    set_run(p.add_run(text), size=size, bold=bold)


# CT_TblPrBase child order — w:tblBorders must be inserted at the right place or
# the file fails OOXML schema validation even though Word tolerates it.
TBLPR_ORDER = [
    "tblStyle", "tblpPr", "tblOverlap", "bidiVisual", "tblStyleRowBandSize",
    "tblStyleColBandSize", "tblW", "jc", "tblCellSpacing", "tblInd", "tblBorders",
    "shd", "tblLayout", "tblCellMar", "tblLook", "tblCaption", "tblDescription",
]


def style_table(tbl):
    tblPr = tbl._tbl.tblPr
    borders = parse_xml(
        f"<w:tblBorders {W_NS}>"
        + "".join(
            f'<w:{e} w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
            for e in ("top", "left", "bottom", "right", "insideH", "insideV")
        )
        + "</w:tblBorders>"
    )
    cut = TBLPR_ORDER.index("tblBorders")
    later = {qn("w:" + n) for n in TBLPR_ORDER[cut + 1:]}
    for child in tblPr:
        if child.tag in later:
            child.addprevious(borders)
            return
    tblPr.append(borders)


def add_table(doc, caption, rows, note):
    cap = para(doc, align=WD_ALIGN_PARAGRAPH.LEFT, line=1.0, before=8, after=4,
               keep_next=True)
    set_run(cap.add_run(caption), size=11, bold=True)

    tbl = doc.add_table(rows=len(rows), cols=len(rows[0]))
    tbl.alignment = WD_TABLE_ALIGNMENT.CENTER
    tbl.autofit = True
    style_table(tbl)
    # a table short enough to fit a page is held together with its caption; a longer
    # one is allowed to break, with its header row repeating on the next page
    keep_together = len(rows) <= 14
    for r, row in enumerate(rows):
        trPr = tbl.rows[r]._tr.get_or_add_trPr()
        trPr.append(parse_xml(f'<w:cantSplit {W_NS}/>'))
        if r == 0:
            trPr.append(parse_xml(f'<w:tblHeader {W_NS}/>'))
        for c, val in enumerate(row):
            set_cell_text(tbl.cell(r, c), val, size=11, bold=(r == 0))
            if keep_together:
                for cp in tbl.cell(r, c).paragraphs:
                    cp.paragraph_format.keep_with_next = True

    n = para(doc, align=WD_ALIGN_PARAGRAPH.LEFT, line=1.0, before=3, after=10)
    set_run(n.add_run(note), size=9, italic=True)


def add_field(par, instr):
    """Insert a simple Word field (used for PAGE in the header)."""
    r1 = par.add_run()
    fc = OxmlElement("w:fldChar")
    fc.set(qn("w:fldCharType"), "begin")
    r1._r.append(fc)
    r2 = par.add_run()
    it = OxmlElement("w:instrText")
    it.set(qn("xml:space"), "preserve")
    it.text = instr
    r2._r.append(it)
    r3 = par.add_run()
    fc = OxmlElement("w:fldChar")
    fc.set(qn("w:fldCharType"), "end")
    r3._r.append(fc)
    for r in (r1, r2, r3):
        set_run(r, size=10)


# ---------------------------------------------------------------- block renderer
def render(doc, blocks, page_map=None):
    num_counter = 0
    for block in blocks:
        kind = block[0]

        if kind != "num":
            num_counter = 0

        if kind == "break":
            doc.add_page_break()

        elif kind == "blank":
            para(doc, align=WD_ALIGN_PARAGRAPH.LEFT, after=0)

        elif kind == "h1":
            p = para(doc, align=WD_ALIGN_PARAGRAPH.LEFT, line=1.15, before=0, after=12,
                     keep_next=True)
            set_run(p.add_run(block[1]), size=14, bold=True)

        elif kind == "h2":
            p = para(doc, align=WD_ALIGN_PARAGRAPH.LEFT, line=1.15, before=10, after=4,
                     keep_next=True)
            set_run(p.add_run(block[1]), size=12.5, bold=True)

        elif kind == "h3":
            p = para(doc, align=WD_ALIGN_PARAGRAPH.LEFT, line=1.15, before=8, after=3,
                     keep_next=True)
            set_run(p.add_run(block[1]), size=12, bold=True, italic=True)

        elif kind == "p":
            p = para(doc, first=BODY_INDENT_CM)
            emit_runs(p, block[1])

        elif kind == "pl":  # left-aligned, no first-line indent
            p = para(doc, align=WD_ALIGN_PARAGRAPH.LEFT, after=4)
            emit_runs(p, block[1])

        elif kind == "pc":
            p = para(doc, align=WD_ALIGN_PARAGRAPH.CENTER)
            emit_runs(p, block[1])

        elif kind == "bullet":
            p = para(doc, left=0.9, hanging=0.5, after=4)
            set_run(p.add_run("•\t"))
            emit_runs(p, block[1])

        elif kind == "num":
            num_counter += 1
            p = para(doc, left=0.9, hanging=0.7, after=4)
            set_run(p.add_run(f"{num_counter}.\t"))
            emit_runs(p, block[1])

        elif kind == "table":
            add_table(doc, block[1], block[2], block[3])

        else:
            raise ValueError(kind)


# ---------------------------------------------------------------- dotted TOC line
def toc_line(doc, text, page, level=0, bold=False):
    indent = [0, 0.8, 1.5][level]
    p = para(doc, align=WD_ALIGN_PARAGRAPH.LEFT, line=1.15, before=0, after=2,
             left=indent)
    pf = p.paragraph_format
    pf.tab_stops.add_tab_stop(Cm(16.0 - indent), WD_TAB_ALIGNMENT.RIGHT, 1)  # dotted
    set_run(p.add_run(text), size=11.5, bold=bold)
    set_run(p.add_run("\t" + str(page)), size=11.5, bold=bold)


# ---------------------------------------------------------------- footnote part
FOOTNOTES_HEAD = (
    '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n'
    '<w:footnotes xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
    'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
    '<w:footnote w:type="separator" w:id="-1"><w:p><w:pPr><w:spacing w:after="0" '
    'w:line="240" w:lineRule="auto"/></w:pPr><w:r><w:separator/></w:r></w:p></w:footnote>'
    '<w:footnote w:type="continuationSeparator" w:id="0"><w:p><w:pPr><w:spacing '
    'w:after="0" w:line="240" w:lineRule="auto"/></w:pPr><w:r><w:continuationSeparator/>'
    '</w:r></w:p></w:footnote>'
)


def xml_escape(s):
    return (s.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;"))


def build_footnotes_xml(notes):
    out = [FOOTNOTES_HEAD]
    for i, text in enumerate(notes, start=1):
        out.append(
            # CT_PPrBase order: pStyle ... spacing, ind, jc
            f'<w:footnote w:id="{i}"><w:p><w:pPr><w:pStyle w:val="FootnoteText"/>'
            f'<w:spacing w:after="0" w:line="240" w:lineRule="auto"/>'
            f'<w:jc w:val="both"/></w:pPr>'
            f'<w:r><w:rPr><w:rStyle w:val="FootnoteReference"/>'
            f'<w:vertAlign w:val="superscript"/></w:rPr><w:footnoteRef/></w:r>'
            f'<w:r><w:rPr><w:rFonts w:ascii="{FONT}" w:hAnsi="{FONT}" w:cs="{FONT}"/>'
            f'<w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr>'
            f'<w:t xml:space="preserve"> {xml_escape(text)}</w:t></w:r>'
            f'</w:p></w:footnote>'
        )
    out.append("</w:footnotes>")
    return "".join(out)


def inject_footnotes(docx_path, notes):
    tmp = docx_path + ".tmp"
    with zipfile.ZipFile(docx_path) as zin, zipfile.ZipFile(
        tmp, "w", zipfile.ZIP_DEFLATED
    ) as zout:
        for item in zin.infolist():
            data = zin.read(item.filename)

            if item.filename == "[Content_Types].xml":
                s = data.decode("utf-8")
                if "footnotes+xml" not in s:
                    s = s.replace(
                        "</Types>",
                        '<Override PartName="/word/footnotes.xml" ContentType='
                        '"application/vnd.openxmlformats-officedocument.'
                        'wordprocessingml.footnotes+xml"/></Types>',
                    )
                data = s.encode("utf-8")

            elif item.filename == "word/settings.xml":
                # the stock python-docx template ships <w:zoom/> without the
                # required w:percent attribute
                s = data.decode("utf-8")
                s = re.sub(
                    r"<w:zoom(?![^>]*w:percent)([^>]*)/>",
                    r'<w:zoom\1 w:percent="100"/>',
                    s,
                )
                data = s.encode("utf-8")

            elif item.filename == "word/_rels/document.xml.rels":
                s = data.decode("utf-8")
                if "footnotes.xml" not in s:
                    ids = [int(m) for m in re.findall(r'Id="rId(\d+)"', s)]
                    new_id = max(ids) + 1 if ids else 1
                    s = s.replace(
                        "</Relationships>",
                        f'<Relationship Id="rId{new_id}" Type="http://schemas.'
                        f'openxmlformats.org/officeDocument/2006/relationships/footnotes"'
                        f' Target="footnotes.xml"/></Relationships>',
                    )
                data = s.encode("utf-8")

            zout.writestr(item, data)

        zout.writestr("word/footnotes.xml", build_footnotes_xml(notes))

    shutil.move(tmp, docx_path)


# ---------------------------------------------------------------- document assembly
def build(out_path, page_map=None):
    FOOTNOTES.clear()
    pm = page_map or {}

    def pg(key):
        return pm.get(key, "0")

    doc = Document()

    # ---- base styles -----------------------------------------------------
    normal = doc.styles["Normal"]
    normal.font.name = FONT
    normal.font.size = Pt(BODY_PT)
    normal.element.rPr.rFonts.set(qn("w:eastAsia"), FONT)
    normal.element.rPr.rFonts.set(qn("w:cs"), FONT)

    ft = doc.styles.add_style("Footnote Text", WD_STYLE_TYPE.PARAGRAPH)
    ft.font.name = FONT
    ft.font.size = Pt(10)
    ft.paragraph_format.line_spacing = 1.0
    ft.paragraph_format.space_after = Pt(0)
    fr = doc.styles.add_style("Footnote Reference", WD_STYLE_TYPE.CHARACTER)
    fr.font.superscript = True

    # ---- page setup ------------------------------------------------------
    sec = doc.sections[0]
    sec.page_width = Cm(21.0)
    sec.page_height = Cm(29.7)
    for attr in ("left_margin", "right_margin", "top_margin", "bottom_margin"):
        setattr(sec, attr, Cm(2.54))
    sec.header_distance = Cm(1.25)
    sec.footer_distance = Cm(1.25)

    # =====================================================================
    # SECTION 1 — title page + certificate (no running header)
    # =====================================================================
    render(doc, C.TITLE_PAGE)
    doc.add_page_break()
    render(doc, C.CERTIFICATE)

    # =====================================================================
    # SECTION 2 — everything else, with running header
    # =====================================================================
    sec2 = doc.add_section(WD_SECTION.NEW_PAGE)
    sec2.page_width = Cm(21.0)
    sec2.page_height = Cm(29.7)
    for attr in ("left_margin", "right_margin", "top_margin", "bottom_margin"):
        setattr(sec2, attr, Cm(2.54))
    sec2.header_distance = Cm(1.25)
    sec2.footer_distance = Cm(1.25)

    # running title centred in the header, page number centred in the footer,
    # reproducing the layout of the original submission
    hdr = sec2.header
    hdr.is_linked_to_previous = False
    hp = hdr.paragraphs[0]
    hp.alignment = WD_ALIGN_PARAGRAPH.CENTER
    hp.paragraph_format.line_spacing = 1.0
    hp.paragraph_format.space_after = Pt(0)
    set_run(hp.add_run(C.TITLE), size=10)

    ftr = sec2.footer
    ftr.is_linked_to_previous = False
    fp = ftr.paragraphs[0]
    fp.alignment = WD_ALIGN_PARAGRAPH.CENTER
    fp.paragraph_format.line_spacing = 1.0
    fp.paragraph_format.space_after = Pt(0)
    add_field(fp, " PAGE ")

    render(doc, C.DECLARATION)
    doc.add_page_break()
    render(doc, C.ACKNOWLEDGEMENT)
    doc.add_page_break()

    # ---- Table of Contents ----------------------------------------------
    p = para(doc, align=WD_ALIGN_PARAGRAPH.CENTER, after=10)
    set_run(p.add_run("TABLE OF CONTENTS"), size=13, bold=True)
    for entry in TOC_ENTRIES:
        text, level = entry
        toc_line(doc, text, pg(text), level=level, bold=(level == 0))
    doc.add_page_break()

    # ---- List of Tables --------------------------------------------------
    p = para(doc, align=WD_ALIGN_PARAGRAPH.CENTER, after=10)
    set_run(p.add_run("LIST OF TABLES"), size=13, bold=True)
    for cap in TABLE_CAPTIONS:
        toc_line(doc, cap, pg(cap), level=0)
    doc.add_page_break()

    # ---- List of Abbreviations ------------------------------------------
    p = para(doc, align=WD_ALIGN_PARAGRAPH.CENTER, after=10)
    set_run(p.add_run("LIST OF ABBREVIATIONS"), size=13, bold=True)
    tbl = doc.add_table(rows=len(C.ABBREVIATIONS), cols=2)
    tbl.autofit = True
    for i, (abbr, full) in enumerate(C.ABBREVIATIONS):
        set_cell_text(tbl.cell(i, 0), abbr, size=11, bold=True)
        set_cell_text(tbl.cell(i, 1), full, size=11)
    doc.add_page_break()

    # ---- main content ----------------------------------------------------
    render(doc, C.CHAPTERS)

    # ---- bibliography ----------------------------------------------------
    doc.add_page_break()
    p = para(doc, align=WD_ALIGN_PARAGRAPH.LEFT, after=8)
    set_run(p.add_run("BIBLIOGRAPHY (WORKS CITED — MLA STYLE)"), size=14, bold=True)
    p = para(doc, line=1.15, after=8)
    set_run(p.add_run(C.BIBLIOGRAPHY_INTRO), size=11, italic=True)
    for item in C.BIBLIOGRAPHY:
        p = para(doc, line=1.15, after=6, left=1.0, hanging=1.0)
        set_run(p.add_run(item), size=11.5)

    # ---- appendix --------------------------------------------------------
    doc.add_page_break()
    render(doc, C.APPENDIX)

    doc.save(out_path)
    inject_footnotes(out_path, FOOTNOTES)
    return len(FOOTNOTES)


# ---------------------------------------------------------------- TOC model
def _headings():
    out = []
    for b in C.CHAPTERS:
        if b[0] == "h1":
            out.append((b[1], 0))
        elif b[0] == "h2":
            out.append((b[1], 1))
        elif b[0] == "h3":
            out.append((b[1], 2))
    return out


TOC_ENTRIES = _headings() + [
    ("BIBLIOGRAPHY (WORKS CITED — MLA STYLE)", 0),
    ("APPENDIX I: INTERVIEW SCHEDULE / QUESTIONNAIRE", 0),
]

TABLE_CAPTIONS = [b[1] for b in C.CHAPTERS if b[0] == "table"]


if __name__ == "__main__":
    out = sys.argv[1] if len(sys.argv) > 1 else "report.docx"
    pmap = None
    if len(sys.argv) > 2 and os.path.exists(sys.argv[2]):
        pmap = json.load(open(sys.argv[2]))
    n = build(out, pmap)
    print(f"wrote {out} with {n} footnotes")
