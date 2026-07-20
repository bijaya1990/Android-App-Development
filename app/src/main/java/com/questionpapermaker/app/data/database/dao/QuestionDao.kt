package com.questionpapermaker.app.data.database.dao

import androidx.room.Dao
import androidx.room.Delete
import androidx.room.Query
import androidx.room.Update
import androidx.room.Upsert
import com.questionpapermaker.app.data.database.entity.QuestionEntity
import kotlinx.coroutines.flow.Flow

@Dao
interface QuestionDao {

    @Query("SELECT * FROM questions WHERE sectionId = :sectionId ORDER BY orderIndex ASC")
    fun observeQuestions(sectionId: String): Flow<List<QuestionEntity>>

    @Query(
        """SELECT questions.* FROM questions
           INNER JOIN sections ON questions.sectionId = sections.id
           WHERE sections.paperId = :paperId
           ORDER BY sections.orderIndex ASC, questions.orderIndex ASC"""
    )
    fun observeQuestionsForPaper(paperId: String): Flow<List<QuestionEntity>>

    @Query(
        """SELECT questions.* FROM questions
           INNER JOIN sections ON questions.sectionId = sections.id
           WHERE sections.paperId = :paperId
           ORDER BY sections.orderIndex ASC, questions.orderIndex ASC"""
    )
    suspend fun getQuestionsForPaper(paperId: String): List<QuestionEntity>

    @Query("SELECT * FROM questions WHERE id = :questionId")
    suspend fun getQuestion(questionId: String): QuestionEntity?

    @Upsert
    suspend fun upsert(question: QuestionEntity)

    @Upsert
    suspend fun upsertAll(questions: List<QuestionEntity>)

    @Update
    suspend fun update(question: QuestionEntity)

    @Delete
    suspend fun delete(question: QuestionEntity)

    @Query("DELETE FROM questions WHERE id = :questionId")
    suspend fun deleteById(questionId: String)

    @Query("SELECT COUNT(*) FROM questions WHERE sectionId = :sectionId")
    suspend fun countForSection(sectionId: String): Int
}
