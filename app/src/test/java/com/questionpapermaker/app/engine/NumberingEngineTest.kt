package com.questionpapermaker.app.engine

import com.questionpapermaker.app.data.database.entity.QuestionEntity
import com.questionpapermaker.app.data.database.entity.SectionEntity
import com.questionpapermaker.app.data.model.NumberingStyle
import com.questionpapermaker.app.data.model.SectionWithQuestions
import org.junit.Assert.assertEquals
import org.junit.Test

class NumberingEngineTest {

    private fun section(id: String, order: Int, style: NumberingStyle = NumberingStyle.ARABIC) = SectionEntity(
        id = id,
        paperId = "paper1",
        orderIndex = order,
        title = "Section",
        numberingStyle = style
    )

    private fun question(id: String, sectionId: String, order: Int) = QuestionEntity(
        id = id,
        sectionId = sectionId,
        orderIndex = order
    )

    @Test
    fun `continuous numbering runs across sections without resetting`() {
        val sectionA = section("A", 0)
        val sectionB = section("B", 1)
        val content = listOf(
            SectionWithQuestions(sectionA, listOf(question("q1", "A", 0), question("q2", "A", 1))),
            SectionWithQuestions(sectionB, listOf(question("q3", "B", 0)))
        )

        val result = NumberingEngine.number(content, continuousNumbering = true)

        assertEquals(listOf("1", "2"), result[0].questions.map { it.displayNumber })
        assertEquals(listOf("3"), result[1].questions.map { it.displayNumber })
    }

    @Test
    fun `non-continuous numbering restarts every section`() {
        val sectionA = section("A", 0)
        val sectionB = section("B", 1)
        val content = listOf(
            SectionWithQuestions(sectionA, listOf(question("q1", "A", 0), question("q2", "A", 1))),
            SectionWithQuestions(sectionB, listOf(question("q3", "B", 0)))
        )

        val result = NumberingEngine.number(content, continuousNumbering = false)

        assertEquals(listOf("1", "2"), result[0].questions.map { it.displayNumber })
        assertEquals(listOf("1"), result[1].questions.map { it.displayNumber })
    }

    @Test
    fun `deleting a question closes the numbering gap`() {
        val sectionA = section("A", 0)
        // Simulates question 2 having been deleted and the remaining question re-indexed to 1.
        val content = listOf(
            SectionWithQuestions(sectionA, listOf(question("q1", "A", 0), question("q3", "A", 1)))
        )

        val result = NumberingEngine.number(content, continuousNumbering = true)

        assertEquals(listOf("1", "2"), result[0].questions.map { it.displayNumber })
    }

    @Test
    fun `section numbering style is respected`() {
        val sectionA = section("A", 0, style = NumberingStyle.UPPER_ROMAN)
        val content = listOf(
            SectionWithQuestions(sectionA, listOf(question("q1", "A", 0), question("q2", "A", 1), question("q3", "A", 2)))
        )

        val result = NumberingEngine.number(content, continuousNumbering = true)

        assertEquals(listOf("I", "II", "III"), result[0].questions.map { it.displayNumber })
    }
}
