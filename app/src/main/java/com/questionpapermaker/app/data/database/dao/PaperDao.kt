package com.questionpapermaker.app.data.database.dao

import androidx.room.Dao
import androidx.room.Delete
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query
import androidx.room.Update
import com.questionpapermaker.app.data.database.entity.PaperEntity
import kotlinx.coroutines.flow.Flow

@Dao
interface PaperDao {

    @Query("SELECT * FROM papers WHERE isTrashed = 0 ORDER BY updatedAt DESC")
    fun observeActivePapers(): Flow<List<PaperEntity>>

    @Query("SELECT * FROM papers WHERE isTrashed = 1 ORDER BY trashedAt DESC")
    fun observeTrashedPapers(): Flow<List<PaperEntity>>

    @Query("SELECT * FROM papers WHERE id = :paperId")
    fun observePaper(paperId: String): Flow<PaperEntity?>

    @Query("SELECT * FROM papers WHERE id = :paperId")
    suspend fun getPaper(paperId: String): PaperEntity?

    @Query(
        """SELECT * FROM papers WHERE isTrashed = 0 AND (
            examName LIKE '%' || :query || '%' OR
            subject LIKE '%' || :query || '%' OR
            institutionName LIKE '%' || :query || '%' OR
            courseName LIKE '%' || :query || '%'
        ) ORDER BY updatedAt DESC"""
    )
    fun searchPapers(query: String): Flow<List<PaperEntity>>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun upsert(paper: PaperEntity)

    @Update
    suspend fun update(paper: PaperEntity)

    @Query("UPDATE papers SET isTrashed = 1, trashedAt = :trashedAt WHERE id = :paperId")
    suspend fun moveToTrash(paperId: String, trashedAt: Long)

    @Query("UPDATE papers SET isTrashed = 0, trashedAt = NULL WHERE id = :paperId")
    suspend fun restoreFromTrash(paperId: String)

    @Delete
    suspend fun delete(paper: PaperEntity)

    @Query("DELETE FROM papers WHERE id = :paperId")
    suspend fun deleteById(paperId: String)
}
