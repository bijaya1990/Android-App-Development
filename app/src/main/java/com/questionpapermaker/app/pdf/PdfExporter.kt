package com.questionpapermaker.app.pdf

import android.content.Context
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.graphics.Canvas
import android.graphics.Color
import android.graphics.Paint
import android.graphics.Typeface
import android.graphics.pdf.PdfDocument
import android.net.Uri
import android.text.Layout
import android.text.StaticLayout
import android.text.TextPaint
import com.questionpapermaker.app.data.database.entity.QuestionEntity
import com.questionpapermaker.app.data.model.NumberingStyle
import com.questionpapermaker.app.data.model.PageNumberPosition
import com.questionpapermaker.app.data.model.PaperWithContent
import com.questionpapermaker.app.data.model.QuestionType
import com.questionpapermaker.app.engine.MarksEngine
import com.questionpapermaker.app.engine.NumberedSection
import com.questionpapermaker.app.engine.NumberingEngine
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import java.io.File
import java.io.FileOutputStream
import kotlin.math.ceil
import kotlin.math.min

/**
 * Renders a [PaperWithContent] into a print-ready PDF using [PdfDocument]. This is the single
 * source of truth for pagination/layout: the live preview rasterizes the exact same PDF, so
 * "what you see" and "what prints" can never drift apart.
 */
class PdfExporter(private val context: Context) {

    suspend fun export(content: PaperWithContent, outputFile: File): File = withContext(Dispatchers.Default) {
        val metrics = PageMetrics.from(content.paper)
        val numberedSections = NumberingEngine.number(content.sections, content.paper.continuousNumbering)
        val logo = loadLogoBitmap(content.paper.institutionLogoUri, content.paper.showInstitutionLogo)

        val document = PdfDocument()
        val renderer = PageRenderer(document, metrics, content, numberedSections, logo)
        renderer.render()
        document.writeTo(FileOutputStream(outputFile))
        document.close()
        outputFile
    }

    private fun loadLogoBitmap(uri: String?, show: Boolean): Bitmap? {
        if (!show || uri.isNullOrBlank()) return null
        return runCatching {
            context.contentResolver.openInputStream(Uri.parse(uri))?.use { BitmapFactory.decodeStream(it) }
        }.getOrNull()
    }
}

/** Owns one export pass: current page/canvas, cursor position, and page-break decisions. */
private class PageRenderer(
    private val document: PdfDocument,
    private val metrics: PageMetrics,
    private val content: PaperWithContent,
    private val sections: List<NumberedSection>,
    private val logo: Bitmap?
) {
    private val paper = content.paper

    private var pageIndex = 0
    private var page: PdfDocument.Page = startPage()
    private var canvas: Canvas = page.canvas
    private var cursorY: Float = metrics.contentTop

    private val marksColumnWidth = 62f
    private val bodyTextSize = 11f
    private val subTextSize = 10.5f

    private val titlePaint = textPaint(15f, bold = true)
    private val examTitlePaint = textPaint(19f, bold = true)
    private val subjectPaint = textPaint(13f, color = Color.DKGRAY)
    private val metaLinePaint = textPaint(11f, bold = true)
    private val dividerPaint = Paint().apply { color = Color.BLACK; strokeWidth = 0.75f }
    private val boxBorderPaint = Paint().apply {
        color = Color.DKGRAY
        style = Paint.Style.STROKE
        strokeWidth = 1f
    }
    private val instructionLabelPaint = textPaint(10.5f, bold = true)
    private val instructionBodyPaint = textPaint(10.5f)
    private val sectionTitlePaint = textPaint(13f, bold = true)
    private val sectionInstructionPaint = textPaint(10.5f, italic = true, color = Color.DKGRAY)
    private val sectionMarksPaint = textPaint(10.5f, bold = true)
    private val questionPaint = textPaint(bodyTextSize)
    private val marksPaint = textPaint(bodyTextSize, bold = true)
    private val subQuestionPaint = textPaint(subTextSize)
    private val orLabelPaint = textPaint(10.5f, bold = true)
    private val footerPaint = textPaint(9f, color = Color.DKGRAY)
    private val watermarkPaint = textPaint(64f, bold = true, color = Color.LTGRAY).apply { alpha = 60 }

    fun render() {
        drawHeader()
        sections.forEach { drawSection(it) }
        if (paper.showSignatureArea) drawSignatureArea()
        finishPage()
    }

    // ---------- Page lifecycle ----------

    private fun startPage(): PdfDocument.Page {
        pageIndex++
        val info = PdfDocument.PageInfo.Builder(
            metrics.pageWidth.toInt(),
            metrics.pageHeight.toInt(),
            pageIndex
        ).create()
        return document.startPage(info)
    }

    private fun finishPage() {
        drawFooter()
        document.finishPage(page)
    }

    private fun newPage() {
        finishPage()
        page = startPage()
        canvas = page.canvas
        cursorY = metrics.contentTop
    }

    /** Starts a new page if [height] of content would overflow the current one. */
    private fun ensureSpace(height: Float) {
        if (cursorY + height > metrics.contentBottom) {
            newPage()
        }
    }

    // ---------- Header ----------

    private fun drawHeader() {
        logo?.let { bmp ->
            val maxLogoHeight = 46f
            val scale = min(1f, maxLogoHeight / bmp.height)
            val w = bmp.width * scale
            val h = bmp.height * scale
            val left = (metrics.pageWidth - w) / 2f
            canvas.drawBitmap(bmp, null, android.graphics.RectF(left, cursorY, left + w, cursorY + h), null)
            cursorY += h + 6f
        }

        paper.institutionName?.takeIf { it.isNotBlank() }?.let {
            cursorY += drawCenteredLine(it, titlePaint)
        }

        val examLine = paper.examName.takeIf { it.isNotBlank() }?.uppercase()
        if (examLine != null) cursorY += drawCenteredLine(examLine, examTitlePaint)

        val subjectLine = listOfNotNull(
            paper.examType?.takeIf { it.isNotBlank() },
            paper.subject.takeIf { it.isNotBlank() },
            paper.courseCode?.takeIf { it.isNotBlank() },
            paper.programme?.takeIf { it.isNotBlank() },
            paper.department?.takeIf { it.isNotBlank() },
            paper.className?.takeIf { it.isNotBlank() },
            paper.semester?.takeIf { it.isNotBlank() }
        ).joinToString("   •   ")
        if (subjectLine.isNotBlank()) cursorY += drawCenteredLine(subjectLine, subjectPaint)

        val metaParts = listOfNotNull(
            paper.examDate?.takeIf { it.isNotBlank() }?.let { "Date: $it" },
            paper.duration?.takeIf { it.isNotBlank() }?.let { "Time Allowed: $it" },
            paper.maxMarks.takeIf { it.isNotBlank() }?.let { "Total Marks: $it" }
        )
        if (metaParts.isNotEmpty()) {
            cursorY += 6f
            cursorY += drawCenteredLine(metaParts.joinToString("      "), metaLinePaint)
        }

        cursorY += 6f
        canvas.drawLine(metrics.contentLeft, cursorY, metrics.contentRight, cursorY, dividerPaint)
        cursorY += 14f

        paper.generalInstructions?.takeIf { it.isNotBlank() }?.let { instructions ->
            drawInstructionsBox(instructions)
            cursorY += 10f
        }
    }

    private fun drawInstructionsBox(instructions: String) {
        val innerWidth = (metrics.contentWidth - 24f).toInt().coerceAtLeast(50)
        val lines = instructions.split("\n").map { it.trim() }.filter { it.isNotBlank() }
        val bulletLayouts = lines.map { buildHangingLayout("•  ", it, instructionBodyPaint, innerWidth) }

        val labelHeight = instructionLabelPaint.textSize + 8f
        val bodyHeight = bulletLayouts.sumOf { (it.height + 4f).toDouble() }.toFloat()
        val boxHeight = 12f + labelHeight + bodyHeight + 8f

        ensureSpace(boxHeight)
        val boxTop = cursorY
        canvas.drawRect(metrics.contentLeft, boxTop, metrics.contentRight, boxTop + boxHeight, boxBorderPaint)

        var y = boxTop + 12f
        canvas.drawText("INSTRUCTIONS:", metrics.contentLeft + 12f, y + instructionLabelPaint.textSize, instructionLabelPaint)
        y += labelHeight

        bulletLayouts.forEach { layout ->
            canvas.save()
            canvas.translate(metrics.contentLeft + 12f, y)
            layout.draw(canvas)
            canvas.restore()
            y += layout.height + 4f
        }

        cursorY = boxTop + boxHeight
    }

    // ---------- Sections ----------

    private fun drawSection(numbered: NumberedSection) {
        val section = numbered.section
        ensureSpace(sectionTitlePaint.textSize + 4f)

        val title = section.title.ifBlank { "Section" }
        canvas.drawText(title, metrics.contentLeft, cursorY + sectionTitlePaint.textSize, sectionTitlePaint)

        val breakdown = MarksEngine.formatBreakdown(content.sections.first { it.section.id == section.id })
        val breakdownWidth = sectionMarksPaint.measureText(breakdown)
        canvas.drawText(breakdown, metrics.contentRight - breakdownWidth, cursorY + sectionTitlePaint.textSize, sectionMarksPaint)
        cursorY += sectionTitlePaint.textSize + 4f

        section.instruction?.takeIf { it.isNotBlank() }?.let {
            val layout = buildLayout(it, sectionInstructionPaint, metrics.contentWidth.toInt())
            ensureSpace(layout.height.toFloat())
            drawLayout(layout, metrics.contentLeft)
            cursorY += layout.height + 6f
        }
        cursorY += 4f

        numbered.questions.forEach { nq -> drawQuestion(nq.question, nq.displayNumber, nq.subQuestionNumbers) }
        cursorY += 6f
    }

    private fun drawQuestion(
        question: QuestionEntity,
        displayNumber: String,
        subNumbers: List<Pair<com.questionpapermaker.app.data.model.SubQuestion, String>>
    ) {
        val prefix = "$displayNumber.  "
        val bodyWidth = (metrics.contentWidth - marksColumnWidth - 6f).toInt().coerceAtLeast(50)
        val label = typeLabel(question)
        val fullText = if (label != null) "[$label] ${question.text}" else question.text
        val layout = buildHangingLayout(prefix, fullText, questionPaint, bodyWidth)

        var blockHeight = layout.height.toFloat()
        val subLayouts = subNumbers.map { (sub, num) ->
            val subPrefix = "      $num  "
            val subLayout = buildHangingLayout(subPrefix, sub.text, subQuestionPaint, bodyWidth - 20)
            blockHeight += subLayout.height + 3f
            subLayout
        }

        val optionLayouts = question.options.mapIndexed { index, option ->
            val letter = NumberingStyle.UPPER_ALPHA.render(index + 1)
            val optionLayout = buildHangingLayout("      $letter)  ", option.text, subQuestionPaint, bodyWidth - 20)
            blockHeight += optionLayout.height + 2f
            optionLayout
        }

        val workingAreaHeight = if (question.showWorkingArea) 70f else 0f
        if (question.showWorkingArea) blockHeight += workingAreaHeight + 6f

        var orLayout: StaticLayout? = null
        if (question.hasInternalChoice && !question.alternativeText.isNullOrBlank()) {
            blockHeight += orLabelPaint.textSize + 6f
            orLayout = buildHangingLayout(prefix, question.alternativeText.orEmpty(), questionPaint, bodyWidth)
            blockHeight += orLayout.height
        }

        ensureSpace(blockHeight + 8f)

        drawLayout(layout, metrics.contentLeft)
        val marksText = "[${MarksEngine.formatMarks(question.marks)}]"
        val marksWidth = marksPaint.measureText(marksText)
        canvas.drawText(marksText, metrics.contentRight - marksWidth, cursorY + questionPaint.textSize, marksPaint)
        cursorY += layout.height

        subLayouts.forEach { subLayout ->
            cursorY += 3f
            drawLayout(subLayout, metrics.contentLeft)
            cursorY += subLayout.height
        }

        optionLayouts.forEach { optionLayout ->
            cursorY += 2f
            drawLayout(optionLayout, metrics.contentLeft)
            cursorY += optionLayout.height
        }

        if (question.showWorkingArea) {
            cursorY += 6f
            canvas.drawRect(metrics.contentLeft, cursorY, metrics.contentRight, cursorY + workingAreaHeight, boxBorderPaint)
            cursorY += workingAreaHeight
        }

        if (orLayout != null) {
            cursorY += 4f
            val orText = "OR"
            val orWidth = orLabelPaint.measureText(orText)
            canvas.drawText(orText, metrics.contentLeft + (metrics.contentWidth - orWidth) / 2f, cursorY + orLabelPaint.textSize, orLabelPaint)
            cursorY += orLabelPaint.textSize + 4f

            drawLayout(orLayout, metrics.contentLeft)
            val altMarks = question.alternativeMarks ?: question.marks
            val altMarksText = "[${MarksEngine.formatMarks(altMarks)}]"
            val altMarksWidth = marksPaint.measureText(altMarksText)
            canvas.drawText(altMarksText, metrics.contentRight - altMarksWidth, cursorY + questionPaint.textSize, marksPaint)
            cursorY += orLayout.height
        }

        cursorY += 8f
    }

    private fun typeLabel(question: QuestionEntity): String? =
        if (question.questionType == QuestionType.CUSTOM) {
            question.customTypeLabel?.takeIf { it.isNotBlank() }
        } else {
            null
        }

    // ---------- Footer ----------

    private fun drawFooter() {
        if (paper.pageNumberPosition != PageNumberPosition.HIDDEN) {
            val text = pageIndex.toString()
            val width = footerPaint.measureText(text)
            val (x, y) = when (paper.pageNumberPosition) {
                PageNumberPosition.TOP_LEFT -> metrics.contentLeft to metrics.marginTop - 14f
                PageNumberPosition.TOP_CENTER -> (metrics.pageWidth - width) / 2f to metrics.marginTop - 14f
                PageNumberPosition.TOP_RIGHT -> metrics.contentRight - width to metrics.marginTop - 14f
                PageNumberPosition.BOTTOM_LEFT -> metrics.contentLeft to metrics.pageHeight - metrics.marginBottom + 20f
                PageNumberPosition.BOTTOM_CENTER -> (metrics.pageWidth - width) / 2f to metrics.pageHeight - metrics.marginBottom + 20f
                PageNumberPosition.BOTTOM_RIGHT -> metrics.contentRight - width to metrics.pageHeight - metrics.marginBottom + 20f
                PageNumberPosition.HIDDEN -> 0f to 0f
            }
            canvas.drawText(text, x, y, footerPaint)
        }

        paper.watermarkText?.takeIf { it.isNotBlank() }?.let { text ->
            canvas.save()
            val cx = metrics.pageWidth / 2f
            val cy = metrics.pageHeight / 2f
            canvas.rotate(-40f, cx, cy)
            val width = watermarkPaint.measureText(text)
            canvas.drawText(text, cx - width / 2f, cy, watermarkPaint)
            canvas.restore()
        }
    }

    private fun drawSignatureArea() {
        val height = 40f
        ensureSpace(height)
        cursorY += 20f
        val lineWidth = 160f
        canvas.drawLine(metrics.contentRight - lineWidth, cursorY, metrics.contentRight, cursorY, dividerPaint)
        cursorY += 4f
        val label = paper.signatureLabel?.takeIf { it.isNotBlank() } ?: "Signature"
        val labelWidth = footerPaint.measureText(label)
        canvas.drawText(label, metrics.contentRight - labelWidth, cursorY + footerPaint.textSize, footerPaint)
        cursorY += footerPaint.textSize + 4f
    }

    // ---------- Text helpers ----------

    /**
     * The printed page uses a serif face throughout (per the design system: the UI is
     * sans-serif, but the actual exam paper content should read like a typeset document).
     */
    private fun textPaint(size: Float, bold: Boolean = false, italic: Boolean = false, color: Int = Color.BLACK) =
        TextPaint(Paint.ANTI_ALIAS_FLAG).apply {
            textSize = size
            this.color = color
            typeface = when {
                bold && italic -> Typeface.create(Typeface.SERIF, Typeface.BOLD_ITALIC)
                bold -> Typeface.create(Typeface.SERIF, Typeface.BOLD)
                italic -> Typeface.create(Typeface.SERIF, Typeface.ITALIC)
                else -> Typeface.SERIF
            }
        }

    private fun buildLayout(text: String, paint: TextPaint, width: Int): StaticLayout =
        StaticLayout.Builder.obtain(text, 0, text.length, paint, width.coerceAtLeast(1))
            .setAlignment(Layout.Alignment.ALIGN_NORMAL)
            .setLineSpacing(0f, 1.15f)
            .setIncludePad(false)
            .build()

    /** A paragraph whose first line starts with [prefix] and whose wrapped lines hang-indent to align under it. */
    private fun buildHangingLayout(prefix: String, body: String, paint: TextPaint, width: Int): StaticLayout {
        val text = prefix + body
        val hangingIndent = paint.measureText(prefix)
        return StaticLayout.Builder.obtain(text, 0, text.length, paint, width.coerceAtLeast(1))
            .setAlignment(Layout.Alignment.ALIGN_NORMAL)
            .setLineSpacing(0f, 1.15f)
            .setIncludePad(false)
            .setIndents(intArrayOf(0, ceil(hangingIndent).toInt()), intArrayOf(0))
            .build()
    }

    private fun drawLayout(layout: StaticLayout, x: Float) {
        canvas.save()
        canvas.translate(x, cursorY)
        layout.draw(canvas)
        canvas.restore()
    }

    /** Draws [text] horizontally centered on the page and returns the vertical space it consumed. */
    private fun drawCenteredLine(text: String, paint: TextPaint): Float {
        val width = paint.measureText(text)
        val x = (metrics.pageWidth - width) / 2f
        canvas.drawText(text, x, cursorY + paint.textSize, paint)
        return paint.textSize + 4f
    }
}
