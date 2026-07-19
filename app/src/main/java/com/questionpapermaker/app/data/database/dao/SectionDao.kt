package com.questionpapermaker.app.data.database.dao

import androidx.room.Dao
import androidx.room.Delete
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query
import androidx.room.Update
import com.questionpapermaker.app.data.database.entity.SectionEntity
import kotlinx.coroutines.flow.Flow

@Dao
interface SectionDao {

    @Query("SELECT * FROM sections WHERE paperId = :paperId ORDER BY orderIndex ASC")
    fun observeSections(paperId: String): Flow<List<SectionEntity>>

    @Query("SELECT * FROM sections WHERE paperId = :paperId ORDER BY orderIndex ASC")
    suspend fun getSections(paperId: String): List<SectionEntity>

    @Query("SELECT * FROM sections WHERE id = :sectionId")
    suspend fun getSection(sectionId: String): SectionEntity?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun upsert(section: SectionEntity)

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun upsertAll(sections: List<SectionEntity>)

    @Update
    suspend fun update(section: SectionEntity)

    @Delete
    suspend fun delete(section: SectionEntity)

    @Query("DELETE FROM sections WHERE id = :sectionId")
    suspend fun deleteById(sectionId: String)

    @Query("SELECT COUNT(*) FROM sections WHERE paperId = :paperId")
    suspend fun countForPaper(paperId: String): Int
}
