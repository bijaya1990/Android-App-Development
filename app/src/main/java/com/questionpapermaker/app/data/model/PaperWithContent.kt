package com.questionpapermaker.app.data.model

import com.questionpapermaker.app.data.database.entity.PaperEntity
import com.questionpapermaker.app.data.database.entity.QuestionEntity
import com.questionpapermaker.app.data.database.entity.SectionEntity

/** A section together with its questions, already ordered. */
data class SectionWithQuestions(
    val section: SectionEntity,
    val questions: List<QuestionEntity>
)

/** The full aggregate needed to render, preview or export a paper. */
data class PaperWithContent(
    val paper: PaperEntity,
    val sections: List<SectionWithQuestions>
) {
    val totalQuestionCount: Int get() = sections.sumOf { it.questions.size }

    val calculatedTotalMarks: Double
        get() = sections.sumOf { swq ->
            swq.questions.sumOf { it.marks }
        }
}
