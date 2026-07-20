package com.questionpapermaker.app.engine

import com.questionpapermaker.app.data.model.QuestionType
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test

class SmartPasteParserTest {

    @Test
    fun `parses sections, marks, MCQ options and internal choice`() {
        val text = """
            Section A: Multiple Choice Questions (1x2=2 Marks)
            Answer all questions.

            1. What is the capital of France? [1 Mark]
            (a) Berlin
            (b) Madrid
            (c) Paris
            (d) Rome

            Section B: Short Answer Questions
            Answer any three of the following questions. (3x5=15 Marks)

            3. Explain the process of photosynthesis. (5 Marks)

            4. Answer any three of the following: (5 Marks)
            (a) Define Newton's First Law of Motion.
            (b) What is the difference between speed and velocity?
            (c) State the law of conservation of energy.

            5. Write a short note on the water cycle. (5 Marks)
            OR
            Explain the causes of global warming and its effects on the environment. (5 Marks)
        """.trimIndent()

        val result = SmartPasteParser.parse(text)

        assertEquals(2, result.sections.size)
        val sectionA = result.sections[0]
        assertTrue(sectionA.title.startsWith("Section A"))
        assertEquals("Answer all questions.", sectionA.instruction)

        val mcq = sectionA.questions.single()
        assertEquals("What is the capital of France?", mcq.text)
        assertEquals(1.0, mcq.marks)
        assertEquals(QuestionType.MULTIPLE_CHOICE, mcq.questionType)
        assertEquals(listOf("Berlin", "Madrid", "Paris", "Rome"), mcq.options.map { it.text })

        val sectionB = result.sections[1]
        assertEquals(3, sectionB.questions.size)

        val subpartQuestion = sectionB.questions[1]
        assertEquals("Answer any three of the following:", subpartQuestion.text)
        assertTrue(subpartQuestion.options.isEmpty())
        assertEquals(3, subpartQuestion.subQuestions.size)
        assertEquals("Define Newton's First Law of Motion.", subpartQuestion.subQuestions[0].text)

        val choiceQuestion = sectionB.questions[2]
        assertTrue(choiceQuestion.hasInternalChoice)
        assertEquals(
            "Explain the causes of global warming and its effects on the environment.",
            choiceQuestion.alternativeText
        )
        assertEquals(5.0, choiceQuestion.alternativeMarks)
    }

    @Test
    fun `recognises Q-prefixed numbering, true-false statement lists and fill in the blank`() {
        val text = """
            Q1. State whether the following statements are True or False.
            (a) The sun rises in the west.
            (b) Water boils at 100 degree Celsius at sea level.

            Q2) Fill in the blanks with appropriate words.
            The process by which plants make food is called ______.

            Q.3 Define photosynthesis in one word.
        """.trimIndent()

        val result = SmartPasteParser.parse(text)
        val questions = result.sections.single().questions
        assertEquals(3, questions.size)

        val trueFalse = questions[0]
        assertTrue(trueFalse.options.isEmpty()) // true/false cue forces sub-parts, not MCQ options
        assertEquals(2, trueFalse.subQuestions.size)
        assertEquals(QuestionType.TRUE_OR_FALSE, trueFalse.questionType)

        val fillBlank = questions[1]
        assertEquals(QuestionType.FILL_IN_THE_BLANK, fillBlank.questionType)
        assertTrue(fillBlank.text.contains("______"))

        val oneWord = questions[2]
        assertEquals("Define photosynthesis in one word.", oneWord.text)
        assertEquals(QuestionType.ONE_WORD_ANSWER, oneWord.questionType)
    }

    @Test
    fun `falls back gracefully when no markers are present`() {
        val text = "Just a plain paragraph of text with no numbering at all pasted from somewhere."
        val result = SmartPasteParser.parse(text)

        assertEquals(1, result.sections.size)
        assertEquals(1, result.totalQuestionCount)
        assertTrue(result.warnings.any { it.contains("single question") })
    }

    @Test
    fun `bare numbering without section headings produces a single implicit section`() {
        val text = """
            1. What is 2 + 2?
            2. Name the largest planet in the solar system.
        """.trimIndent()

        val result = SmartPasteParser.parse(text)
        assertEquals(1, result.sections.size)
        assertEquals("Imported Questions", result.sections.single().title)
        assertEquals(2, result.sections.single().questions.size)
        assertTrue(result.warnings.any { it.contains("No section headings") })
    }

    @Test
    fun `never alters the wording of a question, only strips structural markers`() {
        val text = "1. Explain, in your own words, why the sky appears blue during the day. (10 Marks)"
        val question = SmartPasteParser.parse(text).sections.single().questions.single()
        assertEquals("Explain, in your own words, why the sky appears blue during the day.", question.text)
        assertEquals(10.0, question.marks)
    }
}
