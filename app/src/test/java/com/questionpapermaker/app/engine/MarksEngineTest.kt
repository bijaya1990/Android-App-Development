package com.questionpapermaker.app.engine

import com.questionpapermaker.app.data.database.entity.QuestionEntity
import com.questionpapermaker.app.data.database.entity.SectionEntity
import com.questionpapermaker.app.data.model.SectionWithQuestions
import org.junit.Assert.assertEquals
import org.junit.Test

class MarksEngineTest {

    private fun section(id: String) = SectionEntity(id = id, paperId = "p1", orderIndex = 0, title = "Section A")

    private fun question(id: String, sectionId: String, marks: Double) =
        QuestionEntity(id = id, sectionId = sectionId, orderIndex = 0, marks = marks)

    @Test
    fun `uniform marks produce a multiplication breakdown`() {
        val swq = SectionWithQuestions(
            section("A"),
            listOf(question("q1", "A", 2.0), question("q2", "A", 2.0), question("q3", "A", 2.0))
        )

        assertEquals(6.0, MarksEngine.sectionTotal(swq), 0.0001)
        assertEquals("3 × 2 = 6", MarksEngine.formatBreakdown(swq))
    }

    @Test
    fun `mixed marks fall back to a plain total`() {
        val swq = SectionWithQuestions(
            section("A"),
            listOf(question("q1", "A", 2.0), question("q2", "A", 5.0))
        )

        assertEquals(7.0, MarksEngine.sectionTotal(swq), 0.0001)
        assertEquals("Total: 7", MarksEngine.formatBreakdown(swq))
    }

    @Test
    fun `total recalculates instantly when a question is added`() {
        val swq = SectionWithQuestions(section("A"), listOf(question("q1", "A", 4.0)))
        assertEquals(4.0, MarksEngine.sectionTotal(swq), 0.0001)

        val updated = swq.copy(questions = swq.questions + question("q2", "A", 4.0))
        assertEquals(8.0, MarksEngine.sectionTotal(updated), 0.0001)
    }

    @Test
    fun `paper total sums every section`() {
        val sections = listOf(
            SectionWithQuestions(section("A"), listOf(question("q1", "A", 2.0), question("q2", "A", 2.0))),
            SectionWithQuestions(section("B"), listOf(question("q3", "B", 5.0)))
        )

        assertEquals(9.0, MarksEngine.paperTotal(sections), 0.0001)
    }

    @Test
    fun `formatMarks drops trailing zero for whole numbers`() {
        assertEquals("6", MarksEngine.formatMarks(6.0))
        assertEquals("2.50", MarksEngine.formatMarks(2.5))
    }
}
