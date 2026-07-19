package com.questionpapermaker.app.data.model

/** Numbering style used for questions or sub-questions. */
enum class NumberingStyle(val displayName: String) {
    ARABIC("1, 2, 3"),
    UPPER_ALPHA("A, B, C"),
    LOWER_ALPHA("a, b, c"),
    UPPER_ROMAN("I, II, III"),
    LOWER_ROMAN("i, ii, iii"),
    PARENTHESIZED_LOWER_ALPHA("(a), (b), (c)"),
    PARENTHESIZED_ARABIC("(1), (2), (3)");

    /** Renders [index] (1-based) using this numbering style. */
    fun render(index: Int): String = when (this) {
        ARABIC -> index.toString()
        UPPER_ALPHA -> alpha(index).uppercase()
        LOWER_ALPHA -> alpha(index)
        UPPER_ROMAN -> roman(index)
        LOWER_ROMAN -> roman(index).lowercase()
        PARENTHESIZED_LOWER_ALPHA -> "(${alpha(index)})"
        PARENTHESIZED_ARABIC -> "($index)"
    }

    private fun alpha(index: Int): String {
        if (index <= 0) return index.toString()
        var n = index
        val sb = StringBuilder()
        while (n > 0) {
            val rem = (n - 1) % 26
            sb.insert(0, ('a' + rem))
            n = (n - 1) / 26
        }
        return sb.toString()
    }

    private fun roman(index: Int): String {
        if (index <= 0) return index.toString()
        val values = intArrayOf(1000, 900, 500, 400, 100, 90, 50, 40, 10, 9, 5, 4, 1)
        val symbols = arrayOf("M", "CM", "D", "CD", "C", "XC", "L", "XL", "X", "IX", "V", "IV", "I")
        var n = index
        val sb = StringBuilder()
        for (i in values.indices) {
            while (n >= values[i]) {
                sb.append(symbols[i])
                n -= values[i]
            }
        }
        return sb.toString()
    }
}
