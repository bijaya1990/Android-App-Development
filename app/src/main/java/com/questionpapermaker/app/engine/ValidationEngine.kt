package com.questionpapermaker.app.engine

import com.questionpapermaker.app.data.model.PaperWithContent

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

        content.sections.forEach { swq ->
            if (swq.questions.isEmpty()) {
                issues += ValidationIssue(
                    ValidationSeverity.WARNING,
                    "\"${swq.section.title}\" has no questions yet."
                )
            }
            swq.questions.forEachIndexed { index, question ->
                if (question.text.isBlank()) {
                    issues += ValidationIssue(
                        ValidationSeverity.WARNING,
                        "Question ${index + 1} in \"${swq.section.title}\" is empty."
                    )
                }
                val imageUri = question.imageUri
                if (imageUri != null && !imageExists(imageUri)) {
                    issues += ValidationIssue(
                        ValidationSeverity.WARNING,
                        "Question ${index + 1} in \"${swq.section.title}\" references a missing image."
                    )
                }
                if (question.hasInternalChoice && question.alternativeText.isNullOrBlank()) {
                    issues += ValidationIssue(
                        ValidationSeverity.WARNING,
                        "Question ${index + 1} in \"${swq.section.title}\" has internal choice enabled but no alternative question."
                    )
                }
            }
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
}
