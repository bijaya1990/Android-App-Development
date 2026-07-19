package com.questionpapermaker.app.engine

import com.questionpapermaker.app.data.database.entity.QuestionEntity
import com.questionpapermaker.app.data.database.entity.SectionEntity
import com.questionpapermaker.app.data.model.SectionWithQuestions
import com.questionpapermaker.app.data.model.SubQuestion

/** A question paired with the number the paper should display for it. */
data class NumberedQuestion(
    val question: QuestionEntity,
    val displayNumber: String,
    val subQuestionNumbers: List<Pair<SubQuestion, String>>
)

data class NumberedSection(
    val section: SectionEntity,
    val questions: List<NumberedQuestion>
)

/**
 * Computes the number shown next to every question and sub-question. This is the single
 * source of truth for numbering so deleting/reordering a question can never leave a gap
 * or a duplicate number -- every caller (preview, PDF export) re-derives numbers from here.
 */
object NumberingEngine {

    fun number(sections: List<SectionWithQuestions>, continuousNumbering: Boolean): List<NumberedSection> {
        var runningCounter = 0
        return sections.map { swq ->
            if (!continuousNumbering) runningCounter = 0
            val numberedQuestions = swq.questions.map { question ->
                runningCounter++
                val displayNumber = swq.section.numberingStyle.render(runningCounter)
                val subNumbers = question.subQuestions.mapIndexed { index, sub ->
                    sub to swq.section.subNumberingStyle.render(index + 1)
                }
                NumberedQuestion(question, displayNumber, subNumbers)
            }
            NumberedSection(swq.section, numberedQuestions)
        }
    }
}
