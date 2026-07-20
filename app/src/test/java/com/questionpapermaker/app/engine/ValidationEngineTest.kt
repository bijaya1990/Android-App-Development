package com.questionpapermaker.app.engine

import com.questionpapermaker.app.data.database.entity.PaperEntity
import com.questionpapermaker.app.data.database.entity.QuestionEntity
import com.questionpapermaker.app.data.database.entity.SectionEntity
import com.questionpapermaker.app.data.model.PaperWithContent
import com.questionpapermaker.app.data.model.QuestionOption
import com.questionpapermaker.app.data.model.QuestionType
import com.questionpapermaker.app.data.model.SectionWithQuestions
import org.junit.Assert.assertFalse
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

    @Test
    fun `MCQ with fewer than two options is flagged`() {
        val q = question("q1", "A", 5.0).copy(
            questionType = QuestionType.MULTIPLE_CHOICE,
            options = listOf(QuestionOption(id = "o1", text = "Only one"))
        )
        val content = PaperWithContent(paper("5"), listOf(SectionWithQuestions(section("A"), listOf(q))))
        val issues = ValidationEngine.validate(content)
        assertTrue(issues.any { it.message.contains("at least two options") })
    }

    @Test
    fun `MCQ with no correct option is flagged`() {
        val q = question("q1", "A", 5.0).copy(
            questionType = QuestionType.MULTIPLE_CHOICE,
            options = listOf(
                QuestionOption(id = "o1", text = "A", isCorrect = false),
                QuestionOption(id = "o2", text = "B", isCorrect = false)
            )
        )
        val content = PaperWithContent(paper("5"), listOf(SectionWithQuestions(section("A"), listOf(q))))
        val issues = ValidationEngine.validate(content)
        assertTrue(issues.any { it.message.contains("no option marked as correct") })
    }

    @Test
    fun `MCQ with a correct option marked produces no MCQ warning`() {
        val q = question("q1", "A", 5.0).copy(
            questionType = QuestionType.MULTIPLE_CHOICE,
            options = listOf(
                QuestionOption(id = "o1", text = "A", isCorrect = true),
                QuestionOption(id = "o2", text = "B", isCorrect = false)
            )
        )
        val content = PaperWithContent(paper("5"), listOf(SectionWithQuestions(section("A"), listOf(q))))
        val issues = ValidationEngine.validate(content)
        assertFalse(issues.any { it.message.contains("no option marked as correct") })
        assertFalse(issues.any { it.message.contains("at least two options") })
    }

    @Test
    fun `duplicate question text within a section is flagged`() {
        val q1 = question("q1", "A", 5.0, text = "Explain photosynthesis.")
        val q2 = question("q2", "A", 5.0, text = "Explain photosynthesis.").copy(orderIndex = 1)
        val content = PaperWithContent(paper("10"), listOf(SectionWithQuestions(section("A"), listOf(q1, q2))))
        val issues = ValidationEngine.validate(content)
        assertTrue(issues.any { it.message.contains("duplicate") })
    }

    @Test
    fun `blank section title is flagged`() {
        val blankSection = section("A").copy(title = "")
        val content = PaperWithContent(paper("5"), listOf(SectionWithQuestions(blankSection, listOf(question("q1", "A", 5.0)))))
        val issues = ValidationEngine.validate(content)
        assertTrue(issues.any { it.message.contains("missing a title") })
    }

    @Test
    fun `blank exam name and subject are flagged`() {
        val blankPaper = PaperEntity(id = "p1", examName = "", subject = "", maxMarks = "5")
        val content = PaperWithContent(blankPaper, listOf(SectionWithQuestions(section("A"), listOf(question("q1", "A", 5.0)))))
        val issues = ValidationEngine.validate(content)
        assertTrue(issues.any { it.message.contains("Exam name is empty") })
        assertTrue(issues.any { it.message.contains("Subject is empty") })
    }

    @Test
    fun `zero marks question is flagged`() {
        val q = question("q1", "A", 0.0)
        val content = PaperWithContent(paper("0"), listOf(SectionWithQuestions(section("A"), listOf(q))))
        val issues = ValidationEngine.validate(content)
        assertTrue(issues.any { it.message.contains("0 marks") })
    }

    @Test
    fun `a very long question is flagged as a possible layout overflow`() {
        val longText = "Explain in detail. ".repeat(400)
        val q = question("q1", "A", 10.0, text = longText)
        val content = PaperWithContent(paper("10"), listOf(SectionWithQuestions(section("A"), listOf(q))))
        val issues = ValidationEngine.validate(content)
        assertTrue(issues.any { it.message.contains("span multiple pages") })
    }
}
