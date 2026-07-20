package com.questionpapermaker.app.engine

import com.questionpapermaker.app.data.model.QuestionType

/** One detected MCQ / True-False choice. */
data class ParsedOption(val text: String)

/** One detected lettered/numbered sub-part of a question. */
data class ParsedSubQuestion(val text: String, val marks: Double? = null)

/** A single question recovered from pasted text, ready for the teacher to confirm or edit. */
data class ParsedQuestion(
    val text: String,
    val questionType: QuestionType,
    val marks: Double? = null,
    val options: List<ParsedOption> = emptyList(),
    val subQuestions: List<ParsedSubQuestion> = emptyList(),
    val hasInternalChoice: Boolean = false,
    val alternativeText: String? = null,
    val alternativeMarks: Double? = null
)

data class ParsedSection(
    val title: String,
    val instruction: String? = null,
    val questions: List<ParsedQuestion> = emptyList()
)

data class SmartPasteResult(
    val sections: List<ParsedSection>,
    val warnings: List<String>
) {
    val totalQuestionCount: Int get() = sections.sumOf { it.questions.size }
}

/**
 * Heuristically converts freeform pasted text -- copied from ChatGPT, Claude, Gemini, Word,
 * Google Docs, a plain .txt file, or typed by hand -- into structured sections and questions.
 *
 * The original wording of every question, option and sub-part is preserved exactly as pasted;
 * the only things this parser recognises and pulls out into their own fields are *structural*
 * markers that were never meant to be printed as part of the question body: leading numbering
 * ("1.", "Q2)"), section labels ("Section A:"), trailing marks annotations ("(5 Marks)"), and
 * "OR" lines that separate an internal-choice alternative.
 *
 * The heuristics are deliberately conservative. Anything ambiguous (e.g. whether a lettered
 * list is a set of MCQ options or a set of sub-questions) falls back to a reasonable default
 * that the teacher can correct in the Import Preview screen or the Question Editor afterwards --
 * this parser never blocks on uncertainty, it just does its best and reports what it wasn't
 * sure about via [SmartPasteResult.warnings].
 */
object SmartPasteParser {

    private val sectionRegex =
        Regex("""(section|part)\s+([ivxlcdm]+|[a-z]|\d+)\b[\s:.\-–—]*(.*)""", RegexOption.IGNORE_CASE)
    private val questionPrefixedRegex =
        Regex("""q(?:uestion)?\.?\s*(\d{1,3})[.):]?\s*(.*)""", RegexOption.IGNORE_CASE)
    private val questionBareRegex = Regex("""(\d{1,3})[.):]\s+(.*)""")
    private val romanRegex = Regex("""\(([ivx]+)\)\s*(.*)""", RegexOption.IGNORE_CASE)
    private val parenLetterRegex = Regex("""\(([a-eA-E])\)\s*(.*)""")
    private val dotLetterRegex = Regex("""([a-eA-E])[.)]\s+(.*)""")
    private val marksBracketRegex =
        Regex("""[\[(]\s*(\d+(?:\.\d+)?)\s*(?:marks?|m)\s*[\])]\s*$""", RegexOption.IGNORE_CASE)
    private val marksSuffixRegex =
        Regex("""[-–:]\s*(\d+(?:\.\d+)?)\s*marks?\s*$""", RegexOption.IGNORE_CASE)
    private val blankRunRegex = Regex("_{3,}")

    private val mcqCues = listOf(
        "choose the correct", "select the correct", "which of the following",
        "tick the correct", "circle the correct", "correct option", "correct answer"
    )
    private val subpartCues = listOf(
        "answer any", "attempt any", "write short notes", "write notes on",
        "explain the following", "answer the following", "solve any",
        "true or false", "true/false", "state whether"
    )

    private sealed interface Line {
        data class Section(val title: String) : Line
        data class Question(val body: String) : Line
        data class Letter(val body: String, val isRoman: Boolean) : Line
        data object Or : Line
        data class Plain(val text: String) : Line
    }

    private class LetterItem(val isRoman: Boolean, val text: String, val marks: Double?)

    private class MutableQuestion(var text: String, var marks: Double? = null) {
        var hasInternalChoice: Boolean = false
        var inAlternative: Boolean = false
        var alternativeText: String? = null
        var alternativeMarks: Double? = null
        val letterRun: MutableList<LetterItem> = mutableListOf()

        fun toParsedQuestion(): ParsedQuestion {
            val (options, subQuestions) = resolveLetterRun(text, letterRun)
            return ParsedQuestion(
                text = text,
                questionType = inferQuestionType(text, options.isNotEmpty()),
                marks = marks,
                options = options,
                subQuestions = subQuestions,
                hasInternalChoice = hasInternalChoice,
                alternativeText = alternativeText?.takeIf { it.isNotBlank() },
                alternativeMarks = alternativeMarks
            )
        }
    }

    private class MutableSection(val title: String) {
        var instruction: String? = null
        val questions: MutableList<MutableQuestion> = mutableListOf()

        fun toParsedSection() = ParsedSection(
            title = title,
            instruction = instruction?.takeIf { it.isNotBlank() },
            questions = questions.map { it.toParsedQuestion() }
        )
    }

    fun parse(rawText: String): SmartPasteResult {
        val lines = rawText.replace("\r\n", "\n").replace('\r', '\n').split("\n")
        val sections = mutableListOf<MutableSection>()
        var currentSection: MutableSection? = null
        var currentQuestion: MutableQuestion? = null
        var pendingInstructionSlot = false
        var sawAnyMarker = false

        fun openSection(title: String): MutableSection {
            val section = MutableSection(title)
            sections += section
            currentSection = section
            return section
        }

        fun pushQuestion() {
            val question = currentQuestion ?: return
            currentSection?.questions?.add(question)
            currentQuestion = null
        }

        for (rawLine in lines) {
            val trimmed = rawLine.trim()
            if (trimmed.isEmpty()) continue

            when (val classified = classify(trimmed)) {
                is Line.Section -> {
                    pushQuestion()
                    openSection(classified.title)
                    pendingInstructionSlot = true
                    sawAnyMarker = true
                }

                is Line.Question -> {
                    pushQuestion()
                    if (currentSection == null) openSection("Imported Questions")
                    val (text, marks) = stripMarks(classified.body)
                    currentQuestion = MutableQuestion(text = text, marks = marks)
                    pendingInstructionSlot = false
                    sawAnyMarker = true
                }

                Line.Or -> {
                    currentQuestion?.let {
                        it.hasInternalChoice = true
                        it.inAlternative = true
                    }
                }

                is Line.Letter -> {
                    sawAnyMarker = true
                    var question = currentQuestion
                    if (question == null) {
                        if (currentSection == null) openSection("Imported Questions")
                        question = MutableQuestion(text = "")
                        currentQuestion = question
                    }
                    val (text, marks) = stripMarks(classified.body)
                    question.letterRun.add(LetterItem(classified.isRoman, text, marks))
                }

                is Line.Plain -> {
                    val (text, marks) = stripMarks(classified.text)
                    val section = currentSection
                    val question = currentQuestion
                    if (pendingInstructionSlot && section != null && section.questions.isEmpty() && question == null) {
                        section.instruction = text
                        pendingInstructionSlot = false
                        continue
                    }
                    when {
                        question == null -> {
                            if (currentSection == null) openSection("Imported Questions")
                            currentQuestion = MutableQuestion(text = text, marks = marks)
                        }
                        question.inAlternative -> {
                            question.alternativeText =
                                if (question.alternativeText.isNullOrEmpty()) text else "${question.alternativeText}\n$text"
                            if (marks != null) question.alternativeMarks = marks
                        }
                        question.letterRun.isNotEmpty() -> {
                            val last = question.letterRun.removeAt(question.letterRun.size - 1)
                            val mergedText = if (last.text.isEmpty()) text else "${last.text}\n$text"
                            question.letterRun.add(LetterItem(last.isRoman, mergedText, last.marks ?: marks))
                        }
                        else -> {
                            question.text = if (question.text.isEmpty()) text else "${question.text}\n$text"
                            if (marks != null) question.marks = marks
                        }
                    }
                }
            }
        }
        pushQuestion()

        val warnings = mutableListOf<String>()
        if (!sawAnyMarker) {
            warnings += "No question numbers or section headings were detected -- the pasted " +
                "text was imported as a single question. Split it up in the Question Editor."
        } else if (sections.size == 1 && sections[0].title == "Imported Questions") {
            warnings += "No section headings were detected -- all questions were placed into one section."
        }

        return SmartPasteResult(sections.map { it.toParsedSection() }, warnings)
    }

    private fun classify(line: String): Line {
        if (line.equals("or", ignoreCase = true)) return Line.Or
        sectionRegex.matchEntire(line)?.let { return Line.Section(line) }
        questionPrefixedRegex.matchEntire(line)?.let { return Line.Question(it.groupValues[2]) }
        questionBareRegex.matchEntire(line)?.let { return Line.Question(it.groupValues[2]) }
        romanRegex.matchEntire(line)?.let { return Line.Letter(it.groupValues[2], isRoman = true) }
        parenLetterRegex.matchEntire(line)?.let { return Line.Letter(it.groupValues[2], isRoman = false) }
        dotLetterRegex.matchEntire(line)?.let { return Line.Letter(it.groupValues[2], isRoman = false) }
        return Line.Plain(line)
    }

    private fun stripMarks(raw: String): Pair<String, Double?> {
        marksBracketRegex.find(raw)?.let { match ->
            val marks = match.groupValues[1].toDoubleOrNull()
            return raw.substring(0, match.range.first).trimEnd() to marks
        }
        marksSuffixRegex.find(raw)?.let { match ->
            val marks = match.groupValues[1].toDoubleOrNull()
            return raw.substring(0, match.range.first).trimEnd() to marks
        }
        return raw to null
    }

    private fun resolveLetterRun(
        stemText: String,
        run: List<LetterItem>
    ): Pair<List<ParsedOption>, List<ParsedSubQuestion>> {
        if (run.isEmpty()) return emptyList<ParsedOption>() to emptyList()

        val stemLower = stemText.lowercase()
        val hasMcqCue = mcqCues.any { stemLower.contains(it) }
        val hasSubpartCue = subpartCues.any { stemLower.contains(it) }
        val hasRoman = run.any { it.isRoman }
        val allShort = run.all { it.text.length <= 60 }

        val treatAsOptions = when {
            hasRoman || hasSubpartCue -> false
            hasMcqCue -> true
            else -> run.size in 2..5 && allShort
        }

        return if (treatAsOptions) {
            run.map { ParsedOption(it.text) } to emptyList()
        } else {
            emptyList<ParsedOption>() to run.map { ParsedSubQuestion(it.text, it.marks) }
        }
    }

    private fun inferQuestionType(text: String, hasOptions: Boolean): QuestionType {
        val lower = text.lowercase()
        return when {
            hasOptions -> QuestionType.MULTIPLE_CHOICE
            lower.contains("true or false") || lower.contains("true/false") -> QuestionType.TRUE_OR_FALSE
            lower.contains("fill in the blank") || lower.contains("fill in blank") ||
                blankRunRegex.containsMatchIn(text) -> QuestionType.FILL_IN_THE_BLANK
            lower.contains("match the following") || lower.contains("match column") -> QuestionType.MATCH_THE_FOLLOWING
            lower.contains("case study") || lower.contains("read the following passage") ||
                lower.contains("read the passage") -> QuestionType.CASE_STUDY
            lower.contains("write a program") || lower.contains("write code") ||
                lower.contains("programming question") -> QuestionType.PROGRAMMING
            lower.contains("draw") || lower.contains("diagram") || lower.contains("sketch") -> QuestionType.DIAGRAM_BASED
            (lower.contains("solve") || lower.contains("calculate") || lower.contains("evaluate") ||
                lower.contains("find the value")) && text.any { it.isDigit() } -> QuestionType.NUMERICAL
            lower.contains("essay") -> QuestionType.ESSAY
            lower.startsWith("define ") && text.length <= 60 -> QuestionType.ONE_WORD_ANSWER
            text.length > 350 -> QuestionType.LONG_ANSWER
            text.length <= 40 -> QuestionType.VERY_SHORT_ANSWER
            else -> QuestionType.SHORT_ANSWER
        }
    }
}
