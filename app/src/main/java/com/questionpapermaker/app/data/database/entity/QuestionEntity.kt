package com.questionpapermaker.app.data.database.entity

import androidx.room.Entity
import androidx.room.ForeignKey
import androidx.room.Index
import androidx.room.PrimaryKey
import com.questionpapermaker.app.data.model.QuestionOption
import com.questionpapermaker.app.data.model.QuestionType
import com.questionpapermaker.app.data.model.SubQuestion

/**
 * A single question (or an OR-block's primary question). Text is stored exactly as
 * entered by the teacher -- no normalization, no autocorrect, no character substitution.
 */
@Entity(
    tableName = "questions",
    foreignKeys = [
        ForeignKey(
            entity = SectionEntity::class,
            parentColumns = ["id"],
            childColumns = ["sectionId"],
            onDelete = ForeignKey.CASCADE
        )
    ],
    indices = [Index("sectionId")]
)
data class QuestionEntity(
    @PrimaryKey val id: String,
    val sectionId: String,
    val orderIndex: Int,
    val questionType: QuestionType = QuestionType.DEFAULT,
    val customTypeLabel: String? = null,
    val text: String = "",
    val marks: Double = 0.0,
    val options: List<QuestionOption> = emptyList(),
    val subQuestions: List<SubQuestion> = emptyList(),
    val imageUri: String? = null,
    val hasInternalChoice: Boolean = false,
    val alternativeText: String? = null,
    val alternativeMarks: Double? = null
)
