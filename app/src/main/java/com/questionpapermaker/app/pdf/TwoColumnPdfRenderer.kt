package com.questionpapermaker.app.pdf

import android.graphics.Bitmap
import android.graphics.Canvas
import android.graphics.Color
import android.graphics.Paint
import android.graphics.RectF
import android.graphics.Typeface
import android.graphics.pdf.PdfDocument
import android.text.Layout
import android.text.StaticLayout
import android.text.TextPaint
import com.questionpapermaker.app.data.database.entity.QuestionEntity
import com.questionpapermaker.app.data.model.NumberingStyle
import com.questionpapermaker.app.data.model.PageNumberPosition
import com.questionpapermaker.app.data.model.PaperWithContent
import com.questionpapermaker.app.data.model.QuestionType
import com.questionpapermaker.app.data.model.SubQuestion
import com.questionpapermaker.app.engine.MarksEngine
import com.questionpapermaker.app.engine.NumberedSection
import kotlin.math.ceil
import kotlin.math.min

/**
 * Renders a [PaperWithContent] as a compact, two-column PDF -- for worksheets/notes where a
 * teacher wants to fit more onto fewer printed pages. This is a completely separate rendering
 * path from [PdfExporter]'s single-column [PdfDocument] page loop, so the well-exercised
 * single-column path can never regress from this addition.
 *
 * Content fills the left column top-to-bottom, then the right column, then a new page -- the
 * same convention word processors use for two-column layout. A question is always measured in
 * full before it is placed, so it never straddles a column or page break.
 */
internal class TwoColumnPdfRenderer(
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

    private val columnGutter = 20f
    private val columnWidth = (metrics.contentWidth - columnGutter) / 2f
    private val leftColumnX = metrics.contentLeft
    private val rightColumnX = metrics.contentLeft + columnWidth + columnGutter

    private var currentColumn = 0
    private var cursorY: Float = metrics.contentTop
    private var columnTopY: Float = metrics.contentTop

    private val columnX: Float get() = if (currentColumn == 0) leftColumnX else rightColumnX
    private val columnRight: Float get() = columnX + columnWidth

    private val marksColumnWidth = 46f
    private val bodyTextSize = 9.5f
    private val subTextSize = 9f

    private val titlePaint = textPaint(13f, bold = true)
    private val examTitlePaint = textPaint(16f, bold = true)
    private val subjectPaint = textPaint(11f, color = Color.DKGRAY)
    private val metaLinePaint = textPaint(9.5f, bold = true)
    private val dividerPaint = Paint().apply { color = Color.BLACK; strokeWidth = 0.75f }
    private val boxBorderPaint = Paint().apply {
        color = Color.DKGRAY
        style = Paint.Style.STROKE
        strokeWidth = 1f
    }
    private val instructionLabelPaint = textPaint(9f, bold = true)
    private val instructionBodyPaint = textPaint(9f)
    private val sectionTitlePaint = textPaint(11f, bold = true)
    private val sectionInstructionPaint = textPaint(9f, italic = true, color = Color.DKGRAY)
    private val sectionMarksPaint = textPaint(9f, bold = true)
    private val questionPaint = textPaint(bodyTextSize)
    private val marksPaint = textPaint(bodyTextSize, bold = true)
    private val subQuestionPaint = textPaint(subTextSize)
    private val orLabelPaint = textPaint(9f, bold = true)
    private val footerPaint = textPaint(8f, color = Color.DKGRAY)

    fun render() {
        drawHeader()
        columnTopY = cursorY
        sections.forEach { drawSection(it) }
        finishPage()
    }

    // ---------- Page/column lifecycle ----------

    private fun startPage(): PdfDocument.Page {
        pageIndex++
        val info = PdfDocument.PageInfo.Builder(metrics.pageWidth.toInt(), metrics.pageHeight.toInt(), pageIndex).create()
        return document.startPage(info)
    }

    private fun finishPage() {
        drawFooter()
        document.finishPage(page)
    }

    /** Switches to the right column if the left just filled, or starts a fresh page if both did. */
    private fun ensureColumnSpace(height: Float) {
        if (cursorY + height > metrics.contentBottom) {
            if (currentColumn == 0) {
                currentColumn = 1
                cursorY = columnTopY
            } else {
                finishPage()
                page = startPage()
                canvas = page.canvas
                currentColumn = 0
                cursorY = metrics.contentTop
                columnTopY = metrics.contentTop
            }
        }
    }

    // ---------- Header (full page width, page 1 only) ----------

    private fun drawHeader() {
        logo?.let { bmp ->
            val maxLogoHeight = 34f
            val scale = min(1f, maxLogoHeight / bmp.height)
            val w = bmp.width * scale
            val h = bmp.height * scale
            val left = (metrics.pageWidth - w) / 2f
            canvas.drawBitmap(bmp, null, RectF(left, cursorY, left + w, cursorY + h), null)
            cursorY += h + 4f
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
            paper.className?.takeIf { it.isNotBlank() }
        ).joinToString("   •   ")
        if (subjectLine.isNotBlank()) cursorY += drawCenteredLine(subjectLine, subjectPaint)

        val metaParts = listOfNotNull(
            paper.examDate?.takeIf { it.isNotBlank() }?.let { "Date: $it" },
            paper.duration?.takeIf { it.isNotBlank() }?.let { "Time: $it" },
            paper.maxMarks.takeIf { it.isNotBlank() }?.let { "Marks: $it" }
        )
        if (metaParts.isNotEmpty()) {
            cursorY += 4f
            cursorY += drawCenteredLine(metaParts.joinToString("      "), metaLinePaint)
        }

        cursorY += 4f
        canvas.drawLine(metrics.contentLeft, cursorY, metrics.contentRight, cursorY, dividerPaint)
        cursorY += 10f

        paper.generalInstructions?.takeIf { it.isNotBlank() }?.let { instructions ->
            drawInstructionsBox(instructions)
            cursorY += 8f
        }
    }

    private fun drawInstructionsBox(instructions: String) {
        val innerWidth = (metrics.contentWidth - 20f).toInt().coerceAtLeast(50)
        val lines = instructions.split("\n").map { it.trim() }.filter { it.isNotBlank() }
        val bulletLayouts = lines.map { buildHangingLayout("•  ", it, instructionBodyPaint, innerWidth) }

        val labelHeight = instructionLabelPaint.textSize + 6f
        val bodyHeight = bulletLayouts.sumOf { (it.height + 3f).toDouble() }.toFloat()
        val boxHeight = 10f + labelHeight + bodyHeight + 6f

        val boxTop = cursorY
        canvas.drawRect(metrics.contentLeft, boxTop, metrics.contentRight, boxTop + boxHeight, boxBorderPaint)

        var y = boxTop + 10f
        canvas.drawText("INSTRUCTIONS:", metrics.contentLeft + 10f, y + instructionLabelPaint.textSize, instructionLabelPaint)
        y += labelHeight

        bulletLayouts.forEach { layout ->
            canvas.save()
            canvas.translate(metrics.contentLeft + 10f, y)
            layout.draw(canvas)
            canvas.restore()
            y += layout.height + 3f
        }

        cursorY = boxTop + boxHeight
    }

    // ---------- Sections / questions (flow into the current column) ----------

    private fun drawSection(numbered: NumberedSection) {
        val section = numbered.section
        val titleHeight = sectionTitlePaint.textSize + 3f
        ensureColumnSpace(titleHeight)

        val title = section.title.ifBlank { "Section" }
        canvas.drawText(title, columnX, cursorY + sectionTitlePaint.textSize, sectionTitlePaint)

        val breakdown = MarksEngine.formatBreakdown(content.sections.first { it.section.id == section.id })
        val breakdownWidth = sectionMarksPaint.measureText(breakdown)
        canvas.drawText(breakdown, columnRight - breakdownWidth, cursorY + sectionTitlePaint.textSize, sectionMarksPaint)
        cursorY += titleHeight

        section.instruction?.takeIf { it.isNotBlank() }?.let {
            val layout = buildLayout(it, sectionInstructionPaint, columnWidth.toInt())
            ensureColumnSpace(layout.height.toFloat())
            drawLayout(layout, columnX)
            cursorY += layout.height + 4f
        }
        cursorY += 3f

        numbered.questions.forEach { nq -> drawQuestion(nq.question, nq.displayNumber, nq.subQuestionNumbers) }
        cursorY += 4f
    }

    private fun drawQuestion(
        question: QuestionEntity,
        displayNumber: String,
        subNumbers: List<Pair<SubQuestion, String>>
    ) {
        val prefix = "$displayNumber.  "
        val bodyWidth = (columnWidth - marksColumnWidth - 4f).toInt().coerceAtLeast(40)
        val label = typeLabel(question)
        val fullText = if (label != null) "[$label] ${question.text}" else question.text
        val layout = buildHangingLayout(prefix, fullText, questionPaint, bodyWidth)

        var blockHeight = layout.height.toFloat()
        val subLayouts = subNumbers.map { (sub, num) ->
            val subPrefix = "    $num  "
            val subLayout = buildHangingLayout(subPrefix, sub.text, subQuestionPaint, bodyWidth - 16)
            blockHeight += subLayout.height + 2f
            subLayout
        }

        val optionLayouts = question.options.mapIndexed { index, option ->
            val letter = NumberingStyle.UPPER_ALPHA.render(index + 1)
            val optionLayout = buildHangingLayout("    $letter)  ", option.text, subQuestionPaint, bodyWidth - 16)
            blockHeight += optionLayout.height + 2f
            optionLayout
        }

        val workingAreaHeight = if (question.showWorkingArea) 40f else 0f
        if (question.showWorkingArea) blockHeight += workingAreaHeight + 4f

        var orLayout: StaticLayout? = null
        if (question.hasInternalChoice && !question.alternativeText.isNullOrBlank()) {
            blockHeight += orLabelPaint.textSize + 4f
            orLayout = buildHangingLayout(prefix, question.alternativeText.orEmpty(), questionPaint, bodyWidth)
            blockHeight += orLayout.height
        }

        ensureColumnSpace(blockHeight + 6f)

        drawLayout(layout, columnX)
        val marksText = "[${MarksEngine.formatMarks(question.marks)}]"
        val marksWidth = marksPaint.measureText(marksText)
        canvas.drawText(marksText, columnRight - marksWidth, cursorY + questionPaint.textSize, marksPaint)
        cursorY += layout.height

        subLayouts.forEach { subLayout ->
            cursorY += 2f
            drawLayout(subLayout, columnX)
            cursorY += subLayout.height
        }

        optionLayouts.forEach { optionLayout ->
            cursorY += 2f
            drawLayout(optionLayout, columnX)
            cursorY += optionLayout.height
        }

        if (question.showWorkingArea) {
            cursorY += 4f
            canvas.drawRect(columnX, cursorY, columnRight, cursorY + workingAreaHeight, boxBorderPaint)
            cursorY += workingAreaHeight
        }

        if (orLayout != null) {
            cursorY += 3f
            val orText = "OR"
            val orWidth = orLabelPaint.measureText(orText)
            canvas.drawText(orText, columnX + (columnWidth - orWidth) / 2f, cursorY + orLabelPaint.textSize, orLabelPaint)
            cursorY += orLabelPaint.textSize + 3f

            drawLayout(orLayout, columnX)
            val altMarks = question.alternativeMarks ?: question.marks
            val altMarksText = "[${MarksEngine.formatMarks(altMarks)}]"
            val altMarksWidth = marksPaint.measureText(altMarksText)
            canvas.drawText(altMarksText, columnRight - altMarksWidth, cursorY + questionPaint.textSize, marksPaint)
            cursorY += orLayout.height
        }

        cursorY += 6f
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
    }

    // ---------- Text helpers ----------

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
            .setLineSpacing(0f, 1.12f)
            .setIncludePad(false)
            .build()

    /** A paragraph whose first line starts with [prefix] and whose wrapped lines hang-indent to align under it. */
    private fun buildHangingLayout(prefix: String, body: String, paint: TextPaint, width: Int): StaticLayout {
        val text = prefix + body
        val hangingIndent = paint.measureText(prefix)
        return StaticLayout.Builder.obtain(text, 0, text.length, paint, width.coerceAtLeast(1))
            .setAlignment(Layout.Alignment.ALIGN_NORMAL)
            .setLineSpacing(0f, 1.12f)
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

    /** Draws [text] horizontally centered on the full page and returns the vertical space it consumed. */
    private fun drawCenteredLine(text: String, paint: TextPaint): Float {
        val width = paint.measureText(text)
        val x = (metrics.pageWidth - width) / 2f
        canvas.drawText(text, x, cursorY + paint.textSize, paint)
        return paint.textSize + 3f
    }
}
