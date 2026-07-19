package com.questionpapermaker.app.engine

import com.questionpapermaker.app.data.model.SectionWithQuestions
import java.util.Locale
import kotlin.math.abs

/** Marks a teacher would otherwise have to add up and align by hand. */
object MarksEngine {

    fun sectionTotal(section: SectionWithQuestions): Double =
        section.questions.sumOf { it.marks }

    fun paperTotal(sections: List<SectionWithQuestions>): Double =
        sections.sumOf { sectionTotal(it) }

    /**
     * Human readable breakdown, e.g. "10 × 2 = 20" when every question in the section carries
     * the same marks, otherwise a plain total like "Total: 35".
     */
    fun formatBreakdown(section: SectionWithQuestions): String {
        val questions = section.questions
        if (questions.isEmpty()) return "Total: 0"
        val first = questions.first().marks
        val uniform = questions.all { it.marks == first }
        val total = sectionTotal(section)
        return if (uniform && first > 0) {
            "${questions.size} × ${formatMarks(first)} = ${formatMarks(total)}"
        } else {
            "Total: ${formatMarks(total)}"
        }
    }

    fun formatMarks(marks: Double): String =
        if (abs(marks - marks.toLong()) < 0.0001) {
            marks.toLong().toString()
        } else {
            String.format(Locale.US, "%.2f", marks)
        }
}
