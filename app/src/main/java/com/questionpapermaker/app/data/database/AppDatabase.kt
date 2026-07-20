package com.questionpapermaker.app.data.database

import android.content.Context
import androidx.room.Database
import androidx.room.Room
import androidx.room.RoomDatabase
import androidx.room.TypeConverters
import com.questionpapermaker.app.data.database.converter.Converters
import com.questionpapermaker.app.data.database.dao.PaperDao
import com.questionpapermaker.app.data.database.dao.QuestionDao
import com.questionpapermaker.app.data.database.dao.SectionDao
import com.questionpapermaker.app.data.database.entity.PaperEntity
import com.questionpapermaker.app.data.database.entity.QuestionEntity
import com.questionpapermaker.app.data.database.entity.SectionEntity

@Database(
    entities = [PaperEntity::class, SectionEntity::class, QuestionEntity::class],
    version = 3,
    exportSchema = true
)
@TypeConverters(Converters::class)
abstract class AppDatabase : RoomDatabase() {

    abstract fun paperDao(): PaperDao
    abstract fun sectionDao(): SectionDao
    abstract fun questionDao(): QuestionDao

    companion object {
        private const val DATABASE_NAME = "question_paper_maker.db"

        @Volatile
        private var instance: AppDatabase? = null

        fun getInstance(context: Context): AppDatabase =
            instance ?: synchronized(this) {
                instance ?: Room.databaseBuilder(
                    context.applicationContext,
                    AppDatabase::class.java,
                    DATABASE_NAME
                )
                    // Pre-release app, no migration history to preserve yet -- recreate on schema bumps.
                    .fallbackToDestructiveMigration()
                    .build().also { instance = it }
            }
    }
}
