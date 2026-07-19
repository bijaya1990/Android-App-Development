package com.questionpapermaker.app.data.repository

import android.content.Context
import androidx.core.content.edit
import com.questionpapermaker.app.data.model.TeacherDefaults
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import kotlinx.serialization.encodeToString
import kotlinx.serialization.json.Json

/** Stores the teacher's reusable defaults (institution, logo, footer, signature) locally. */
class DefaultsRepository(context: Context) {

    private val prefs = context.applicationContext.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
    private val json = Json { ignoreUnknownKeys = true }

    suspend fun getDefaults(): TeacherDefaults = withContext(Dispatchers.IO) {
        val raw = prefs.getString(KEY_DEFAULTS, null) ?: return@withContext TeacherDefaults()
        runCatching { json.decodeFromString<TeacherDefaults>(raw) }.getOrDefault(TeacherDefaults())
    }

    suspend fun saveDefaults(defaults: TeacherDefaults) = withContext(Dispatchers.IO) {
        prefs.edit { putString(KEY_DEFAULTS, json.encodeToString(defaults)) }
    }

    private companion object {
        const val PREFS_NAME = "teacher_defaults"
        const val KEY_DEFAULTS = "defaults_json"
    }
}
