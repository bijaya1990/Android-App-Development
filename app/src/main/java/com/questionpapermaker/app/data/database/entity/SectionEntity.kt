package com.questionpapermaker.app.data.database.entity

import androidx.room.Entity
import androidx.room.ForeignKey
import androidx.room.Index
import androidx.room.PrimaryKey
import com.questionpapermaker.app.data.model.NumberingStyle
import com.questionpapermaker.app.data.model.QuestionType

/** A named block of questions within a paper, e.g. "Section A". */
@Entity(
    tableName = "sections",
    foreignKeys = [
        ForeignKey(
            entity = PaperEntity::class,
            parentColumns = ["id"],
            childColumns = ["paperId"],
            onDelete = ForeignKey.CASCADE
        )
    ],
    indices = [Index("paperId")]
)
data class SectionEntity(
    @PrimaryKey val id: String,
    val paperId: String,
    val orderIndex: Int,
    val title: String = "",
    val description: String? = null,
    val instruction: String? = null,
    val numberingStyle: NumberingStyle = NumberingStyle.ARABIC,
    val subNumberingStyle: NumberingStyle = NumberingStyle.PARENTHESIZED_LOWER_ALPHA,
    val defaultQuestionType: QuestionType = QuestionType.DEFAULT,
    val defaultMarksPerQuestion: Double? = null,
    val isCollapsed: Boolean = false
)
