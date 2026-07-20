package com.questionpapermaker.app.engine

import com.questionpapermaker.app.data.model.PaperWithContent
import com.questionpapermaker.app.pdf.PageMetrics

enum class ValidationSeverity { ERROR, WARNING }

data class ValidationIssue(
    val severity: ValidationSeverity,
    val message: String
)

/**
 * Pre-export sanity checks. Errors block export (e.g. no questions at all); warnings are
 * surfaced to the teacher but never force a change, per the "warn, don't block" design rule.
 */
object ValidationEngine {

    // Matches PdfExporter's body text size -- used only to estimate whether a single question's
    // text is long enough to risk spilling awkwardly across pages. Approximate by design: this
    // is a heads-up, not a pixel-accurate layout measurement.
    private const val BODY_TEXT_SIZE_PT = 11f
    private const val AVG_CHAR_WIDTH_FACTOR = 0.5f
    private const val LINE_HEIGHT_FACTOR = 1.2f
    private const val OVERFLOW_WARNING_FRACTION = 0.6

    fun validate(
        content: PaperWithContent,
        imageExists: (String) -> Boolean = { true }
    ): List<ValidationIssue> {
        val issues = mutableListOf<ValidationIssue>()

        if (content.sections.isEmpty()) {
            issues += ValidationIssue(ValidationSeverity.ERROR, "Add at least one section before exporting.")
        }

        if (content.totalQuestionCount == 0) {
            issues += ValidationIssue(ValidationSeverity.ERROR, "Add at least one question before exporting.")
        }

        val approxCharsPerPage = estimateCharsPerPage(content)

        content.sections.forEach { swq ->
            if (swq.section.title.isBlank()) {
                issues += ValidationIssue(ValidationSeverity.WARNING, "A section is missing a title.")
            }
            if (swq.questions.isEmpty()) {
                issues += ValidationIssue(
                    ValidationSeverity.WARNING,
                    "\"${swq.section.title}\" has no questions yet."
                )
            }

            val seenQuestionTexts = mutableSetOf<String>()
            swq.questions.forEachIndexed { index, question ->
                val label = "Question ${index + 1} in \"${swq.section.title}\""

                if (question.text.isBlank()) {
                    issues += ValidationIssue(ValidationSeverity.WARNING, "$label is empty.")
                }

                val imageUri = question.imageUri
                if (imageUri != null && !imageExists(imageUri)) {
                    issues += ValidationIssue(ValidationSeverity.WARNING, "$label references a missing image.")
                }

                if (question.hasInternalChoice && question.alternativeText.isNullOrBlank()) {
                    issues += ValidationIssue(
                        ValidationSeverity.WARNING,
                        "$label has internal choice enabled but no alternative question."
                    )
                }

                if (question.questionType.supportsOptions) {
                    if (question.options.size < 2) {
                        issues += ValidationIssue(ValidationSeverity.WARNING, "$label needs at least two options.")
                    } else if (question.options.none { it.isCorrect }) {
                        issues += ValidationIssue(ValidationSeverity.WARNING, "$label has no option marked as correct.")
                    }
                }

                if (question.text.isNotBlank() && question.marks <= 0.0) {
                    issues += ValidationIssue(ValidationSeverity.WARNING, "$label has 0 marks assigned.")
                }

                val normalizedText = question.text.trim().lowercase()
                if (normalizedText.isNotEmpty() && !seenQuestionTexts.add(normalizedText)) {
                    issues += ValidationIssue(
                        ValidationSeverity.WARNING,
                        "$label looks like a duplicate of another question in the same section."
                    )
                }

                val totalLength = question.text.length +
                    question.subQuestions.sumOf { it.text.length } +
                    question.options.sumOf { it.text.length }
                if (approxCharsPerPage > 0 && totalLength > approxCharsPerPage * OVERFLOW_WARNING_FRACTION) {
                    issues += ValidationIssue(
                        ValidationSeverity.WARNING,
                        "$label is very long and may span multiple pages awkwardly -- consider shortening it or splitting it up."
                    )
                }
            }
        }

        if (content.paper.examName.isBlank()) {
            issues += ValidationIssue(ValidationSeverity.WARNING, "Exam name is empty -- it will be blank on the printed paper.")
        }
        if (content.paper.subject.isBlank()) {
            issues += ValidationIssue(ValidationSeverity.WARNING, "Subject is empty -- it will be blank on the printed paper.")
        }

        val declaredMax = content.paper.maxMarks.trim().toDoubleOrNull()
        val calculatedTotal = content.calculatedTotalMarks
        if (declaredMax != null && kotlin.math.abs(declaredMax - calculatedTotal) > 0.001) {
            issues += ValidationIssue(
                ValidationSeverity.WARNING,
                "Section marks add up to ${MarksEngine.formatMarks(calculatedTotal)}, " +
                    "but Maximum Marks is set to ${MarksEngine.formatMarks(declaredMax)}."
            )
        }

        return issues
    }

    /** Very rough "characters that fit on one printed page" estimate, from the paper's real page geometry. */
    private fun estimateCharsPerPage(content: PaperWithContent): Int {
        val metrics = PageMetrics.from(content.paper)
        val avgCharWidthPt = BODY_TEXT_SIZE_PT * AVG_CHAR_WIDTH_FACTOR
        val lineHeightPt = BODY_TEXT_SIZE_PT * LINE_HEIGHT_FACTOR
        val contentHeight = metrics.contentBottom - metrics.contentTop
        if (avgCharWidthPt <= 0f || lineHeightPt <= 0f || metrics.contentWidth <= 0f || contentHeight <= 0f) return 0
        val charsPerLine = (metrics.contentWidth / avgCharWidthPt).toInt().coerceAtLeast(1)
        val linesPerPage = (contentHeight / lineHeightPt).toInt().coerceAtLeast(1)
        return charsPerLine * linesPerPage
    }
}
