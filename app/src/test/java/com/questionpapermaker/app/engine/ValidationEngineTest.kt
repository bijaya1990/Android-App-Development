package com.questionpapermaker.app.engine

import com.questionpapermaker.app.data.database.entity.PaperEntity
import com.questionpapermaker.app.data.database.entity.QuestionEntity
import com.questionpapermaker.app.data.database.entity.SectionEntity
import com.questionpapermaker.app.data.model.PaperWithContent
import com.questionpapermaker.app.data.model.SectionWithQuestions
import org.junit.Assert.assertTrue
import org.junit.Test

class ValidationEngineTest {

    private fun paper(maxMarks: String) = PaperEntity(id = "p1", examName = "Mid Term", subject = "Physics", maxMarks = maxMarks)
    private fun section(id: String) = SectionEntity(id = id, paperId = "p1", orderIndex = 0, title = "Section A")
    private fun question(id: String, sectionId: String, marks: Double, text: String = "What is gravity?") =
        QuestionEntity(id = id, sectionId = sectionId, orderIndex = 0, marks = marks, text = text)

    @Test
    fun `paper with no sections reports an error`() {
        val content = PaperWithContent(paper("10"), emptyList())
        val issues = ValidationEngine.validate(content)
        assertTrue(issues.any { it.severity == ValidationSeverity.ERROR && it.message.contains("section") })
    }

    @Test
    fun `paper with a section but no questions reports an error`() {
        val content = PaperWithContent(paper("10"), listOf(SectionWithQuestions(section("A"), emptyList())))
        val issues = ValidationEngine.validate(content)
        assertTrue(issues.any { it.severity == ValidationSeverity.ERROR && it.message.contains("question") })
    }

    @Test
    fun `marks mismatch produces a warning not an error`() {
        val content = PaperWithContent(
            paper("100"),
            listOf(SectionWithQuestions(section("A"), listOf(question("q1", "A", 5.0))))
        )
        val issues = ValidationEngine.validate(content)
        val marksIssue = issues.first { it.message.contains("Maximum Marks") }
        assertTrue(marksIssue.severity == ValidationSeverity.WARNING)
    }

    @Test
    fun `matching marks produces no warning`() {
        val content = PaperWithContent(
            paper("5"),
            listOf(SectionWithQuestions(section("A"), listOf(question("q1", "A", 5.0))))
        )
        val issues = ValidationEngine.validate(content)
        assertTrue(issues.none { it.message.contains("Maximum Marks") })
    }

    @Test
    fun `missing image is reported via injected checker`() {
        val q = question("q1", "A", 5.0).copy(imageUri = "content://missing/1")
        val content = PaperWithContent(paper("5"), listOf(SectionWithQuestions(section("A"), listOf(q))))

        val issues = ValidationEngine.validate(content, imageExists = { false })
        assertTrue(issues.any { it.message.contains("missing image") })
    }

    @Test
    fun `valid paper produces no errors`() {
        val content = PaperWithContent(
            paper("5"),
            listOf(SectionWithQuestions(section("A"), listOf(question("q1", "A", 5.0))))
        )
        val issues = ValidationEngine.validate(content)
        assertTrue(issues.none { it.severity == ValidationSeverity.ERROR })
    }
}
