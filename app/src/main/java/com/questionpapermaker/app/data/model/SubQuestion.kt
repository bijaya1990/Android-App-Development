package com.questionpapermaker.app.data.model

import kotlinx.serialization.Serializable

/** A lettered/numbered sub-part of a question, e.g. Q4 (a), (b), (c). */
@Serializable
data class SubQuestion(
    val id: String,
    val text: String = "",
    val marks: Double? = null
)

/** A single option in a Multiple Choice / True-False question. */
@Serializable
data class QuestionOption(
    val id: String,
    val text: String = "",
    val isCorrect: Boolean = false
)
