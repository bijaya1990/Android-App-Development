package com.questionpapermaker.app.data.model

/**
 * Built-in question types. [CUSTOM] lets a teacher define their own label
 * (stored on the question itself) without the app needing to know about it.
 */
enum class QuestionType(val displayName: String, val supportsOptions: Boolean = false) {
    MULTIPLE_CHOICE("Multiple Choice Questions", supportsOptions = true),
    FILL_IN_THE_BLANK("Fill in the Blanks"),
    TRUE_OR_FALSE("True or False", supportsOptions = true),
    MATCH_THE_FOLLOWING("Match the Following"),
    ONE_WORD_ANSWER("One Word Answer"),
    VERY_SHORT_ANSWER("Very Short Answer"),
    SHORT_ANSWER("Short Answer"),
    LONG_ANSWER("Long Answer"),
    ESSAY("Essay"),
    CASE_STUDY("Case Study"),
    NUMERICAL("Numerical / Problem Solving"),
    PRACTICAL("Practical"),
    DIAGRAM_BASED("Diagram Based"),
    PROGRAMMING("Programming"),
    CUSTOM("Custom");

    companion object {
        val DEFAULT = SHORT_ANSWER
    }
}
