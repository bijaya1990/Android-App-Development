package com.questionpapermaker.app.docx

import com.questionpapermaker.app.data.database.entity.QuestionEntity
import com.questionpapermaker.app.data.model.NumberingStyle
import com.questionpapermaker.app.data.model.PaperWithContent
import com.questionpapermaker.app.data.model.QuestionType
import com.questionpapermaker.app.data.model.SubQuestion
import com.questionpapermaker.app.engine.MarksEngine
import com.questionpapermaker.app.engine.NumberedSection
import com.questionpapermaker.app.engine.NumberingEngine
import com.questionpapermaker.app.pdf.PageMetrics
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import java.io.File
import java.io.FileOutputStream
import java.util.zip.ZipEntry
import java.util.zip.ZipOutputStream

/**
 * Renders a [PaperWithContent] into a real, valid .docx (a ZIP package of WordprocessingML
 * parts) using nothing but [java.util.zip] -- no external library, no network access needed.
 *
 * Unlike [com.questionpapermaker.app.pdf.PdfExporter] this writer never has to decide where a
 * page breaks: Word/Google Docs/LibreOffice paginate the flowing paragraphs themselves once the
 * file is opened, so the whole page-break/`ensureSpace` machinery the PDF exporter needs simply
 * doesn't apply here.
 */
class DocxExporter {

    suspend fun export(content: PaperWithContent, outputFile: File): File = withContext(Dispatchers.Default) {
        val numberedSections = NumberingEngine.number(content.sections, content.paper.continuousNumbering)
        val documentXml = DocumentXmlBuilder(content, numberedSections).build()

        ZipOutputStream(FileOutputStream(outputFile)).use { zip ->
            writeEntry(zip, "[Content_Types].xml", CONTENT_TYPES_XML)
            writeEntry(zip, "_rels/.rels", ROOT_RELS_XML)
            writeEntry(zip, "word/_rels/document.xml.rels", DOCUMENT_RELS_XML)
            writeEntry(zip, "word/styles.xml", STYLES_XML)
            writeEntry(zip, "word/document.xml", documentXml)
        }
        outputFile
    }

    private fun writeEntry(zip: ZipOutputStream, name: String, text: String) {
        zip.putNextEntry(ZipEntry(name))
        zip.write(text.toByteArray(Charsets.UTF_8))
        zip.closeEntry()
    }

    private companion object {
        val CONTENT_TYPES_XML = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/></Types>"""

        val ROOT_RELS_XML = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>"""

        val DOCUMENT_RELS_XML = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>"""

        val STYLES_XML = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:sz w:val="22"/></w:rPr></w:rPrDefault></w:docDefaults><w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/></w:style></w:styles>"""
    }
}

private const val TWIPS_PER_POINT = 20

/** Builds the WordprocessingML for word/document.xml, paragraph by paragraph. */
private class DocumentXmlBuilder(
    private val content: PaperWithContent,
    private val sections: List<NumberedSection>
) {
    private val paper = content.paper
    private val sb = StringBuilder()

    fun build(): String {
        val metrics = PageMetrics.from(paper)
        val contentWidthTwips = (metrics.contentWidth * TWIPS_PER_POINT).toInt()

        sb.append("""<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>""")

        writeHeader()
        sections.forEach { writeSection(it, contentWidthTwips) }
        if (paper.showSignatureArea) writeSignatureArea()

        sb.append(sectPr(metrics))
        sb.append("</w:body></w:document>")
        return sb.toString()
    }

    private fun writeHeader() {
        paper.institutionName?.takeIf { it.isNotBlank() }?.let {
            sb.append(paragraph(run(it, bold = true, sizeHalfPoints = 30), alignment = "center", spacingAfterTwips = 40))
        }
        paper.examName.takeIf { it.isNotBlank() }?.let {
            sb.append(paragraph(run(it.uppercase(), bold = true, sizeHalfPoints = 38), alignment = "center", spacingAfterTwips = 40))
        }

        val subjectLine = listOfNotNull(
            paper.examType?.takeIf { it.isNotBlank() },
            paper.subject.takeIf { it.isNotBlank() },
            paper.courseCode?.takeIf { it.isNotBlank() },
            paper.programme?.takeIf { it.isNotBlank() },
            paper.department?.takeIf { it.isNotBlank() },
            paper.className?.takeIf { it.isNotBlank() },
            paper.semester?.takeIf { it.isNotBlank() }
        ).joinToString("   •   ")
        if (subjectLine.isNotBlank()) {
            sb.append(paragraph(run(subjectLine, sizeHalfPoints = 26), alignment = "center", spacingAfterTwips = 40))
        }

        val metaParts = listOfNotNull(
            paper.examDate?.takeIf { it.isNotBlank() }?.let { "Date: $it" },
            paper.duration?.takeIf { it.isNotBlank() }?.let { "Time Allowed: $it" },
            paper.maxMarks.takeIf { it.isNotBlank() }?.let { "Total Marks: $it" }
        )
        if (metaParts.isNotEmpty()) {
            sb.append(
                paragraph(
                    run(metaParts.joinToString("      "), bold = true, sizeHalfPoints = 22),
                    alignment = "center",
                    spacingAfterTwips = 200,
                    bottomBorder = true
                )
            )
        }

        paper.generalInstructions?.takeIf { it.isNotBlank() }?.let { instructions ->
            sb.append(paragraph(run("INSTRUCTIONS:", bold = true), spacingAfterTwips = 60))
            instructions.split("\n").map { it.trim() }.filter { it.isNotBlank() }.forEach { line ->
                sb.append(paragraph(run("•  $line"), spacingAfterTwips = 40, indentLeftTwips = 240))
            }
            sb.append(paragraph(run(""), spacingAfterTwips = 120))
        }
    }

    private fun writeSection(numbered: NumberedSection, contentWidthTwips: Int) {
        val section = numbered.section
        val breakdown = MarksEngine.formatBreakdown(content.sections.first { it.section.id == section.id })
        val titleRuns = run(section.title.ifBlank { "Section" }, bold = true, sizeHalfPoints = 26) +
            tabRun() + run(breakdown, bold = true, sizeHalfPoints = 21)
        sb.append(paragraph(titleRuns, spacingAfterTwips = 60, rightTabPosTwips = contentWidthTwips))

        section.instruction?.takeIf { it.isNotBlank() }?.let {
            sb.append(paragraph(run(it, italic = true, sizeHalfPoints = 21), spacingAfterTwips = 120))
        }

        numbered.questions.forEach { nq ->
            writeQuestion(nq.question, nq.displayNumber, nq.subQuestionNumbers, contentWidthTwips)
        }
    }

    private fun writeQuestion(
        question: QuestionEntity,
        displayNumber: String,
        subNumbers: List<Pair<SubQuestion, String>>,
        contentWidthTwips: Int
    ) {
        val label = if (question.questionType == QuestionType.CUSTOM) {
            question.customTypeLabel?.takeIf { it.isNotBlank() }
        } else {
            null
        }
        val bodyText = if (label != null) "[$label] ${question.text}" else question.text
        val marksText = "[${MarksEngine.formatMarks(question.marks)}]"

        val questionRuns = run("$displayNumber.  ") + run(bodyText) + tabRun() + run(marksText, bold = true)
        sb.append(
            paragraph(
                questionRuns,
                spacingAfterTwips = 60,
                rightTabPosTwips = contentWidthTwips,
                indentLeftTwips = 360,
                hangingIndentTwips = 360
            )
        )

        subNumbers.forEach { (sub, num) ->
            sb.append(
                paragraph(
                    run("$num  ", sizeHalfPoints = 21) + run(sub.text, sizeHalfPoints = 21),
                    spacingAfterTwips = 40,
                    indentLeftTwips = 720,
                    hangingIndentTwips = 300
                )
            )
        }

        question.options.forEachIndexed { index, option ->
            val letter = NumberingStyle.UPPER_ALPHA.render(index + 1)
            sb.append(
                paragraph(
                    run("$letter)  ", sizeHalfPoints = 21) + run(option.text, sizeHalfPoints = 21),
                    spacingAfterTwips = 30,
                    indentLeftTwips = 720,
                    hangingIndentTwips = 300
                )
            )
        }

        if (question.showWorkingArea) {
            sb.append(
                paragraph(
                    run("[Space for working / answer]", italic = true, sizeHalfPoints = 20),
                    spacingAfterTwips = 200,
                    indentLeftTwips = 360
                )
            )
        }

        if (question.hasInternalChoice && !question.alternativeText.isNullOrBlank()) {
            sb.append(paragraph(run("OR", bold = true), alignment = "center", spacingAfterTwips = 60))
            val altMarks = question.alternativeMarks ?: question.marks
            val altMarksText = "[${MarksEngine.formatMarks(altMarks)}]"
            val altRuns = run("$displayNumber.  ") + run(question.alternativeText.orEmpty()) + tabRun() +
                run(altMarksText, bold = true)
            sb.append(
                paragraph(
                    altRuns,
                    spacingAfterTwips = 120,
                    rightTabPosTwips = contentWidthTwips,
                    indentLeftTwips = 360,
                    hangingIndentTwips = 360
                )
            )
        }
    }

    private fun writeSignatureArea() {
        val label = paper.signatureLabel?.takeIf { it.isNotBlank() } ?: "Signature"
        sb.append(paragraph(run("_______________________", sizeHalfPoints = 22), alignment = "right", spacingAfterTwips = 20))
        sb.append(paragraph(run(label, sizeHalfPoints = 20), alignment = "right", spacingAfterTwips = 120))
    }

    private fun sectPr(metrics: PageMetrics): String {
        val w = (metrics.pageWidth * TWIPS_PER_POINT).toInt()
        val h = (metrics.pageHeight * TWIPS_PER_POINT).toInt()
        val top = (metrics.marginTop * TWIPS_PER_POINT).toInt()
        val bottom = (metrics.marginBottom * TWIPS_PER_POINT).toInt()
        val left = (metrics.marginLeft * TWIPS_PER_POINT).toInt()
        val right = (metrics.marginRight * TWIPS_PER_POINT).toInt()
        return """<w:sectPr><w:pgSz w:w="$w" w:h="$h"/><w:pgMar w:top="$top" w:right="$right" w:bottom="$bottom" w:left="$left" w:header="0" w:footer="0" w:gutter="0"/></w:sectPr>"""
    }

    // ---------- WordprocessingML fragment helpers ----------

    private fun run(text: String, bold: Boolean = false, italic: Boolean = false, sizeHalfPoints: Int = 22): String {
        val rPr = buildString {
            append("<w:rPr>")
            if (bold) append("<w:b/>")
            if (italic) append("<w:i/>")
            append("""<w:sz w:val="$sizeHalfPoints"/>""")
            append("</w:rPr>")
        }
        return """<w:r>$rPr<w:t xml:space="preserve">${escape(text)}</w:t></w:r>"""
    }

    private fun tabRun(): String = "<w:r><w:tab/></w:r>"

    private fun paragraph(
        runs: String,
        alignment: String? = null,
        spacingAfterTwips: Int = 120,
        indentLeftTwips: Int = 0,
        hangingIndentTwips: Int = 0,
        rightTabPosTwips: Int? = null,
        bottomBorder: Boolean = false
    ): String {
        val pPr = buildString {
            append("<w:pPr>")
            if (alignment != null) append("""<w:jc w:val="$alignment"/>""")
            append("""<w:spacing w:after="$spacingAfterTwips"/>""")
            if (indentLeftTwips > 0 || hangingIndentTwips > 0) {
                append("<w:ind w:left=\"$indentLeftTwips\"")
                if (hangingIndentTwips > 0) append(" w:hanging=\"$hangingIndentTwips\"")
                append("/>")
            }
            if (rightTabPosTwips != null) {
                append("""<w:tabs><w:tab w:val="right" w:pos="$rightTabPosTwips"/></w:tabs>""")
            }
            if (bottomBorder) {
                append("""<w:pBdr><w:bottom w:val="single" w:sz="6" w:space="4" w:color="000000"/></w:pBdr>""")
            }
            append("</w:pPr>")
        }
        return "<w:p>$pPr$runs</w:p>"
    }

    private fun escape(text: String): String = text
        .replace("&", "&amp;")
        .replace("<", "&lt;")
        .replace(">", "&gt;")
}
