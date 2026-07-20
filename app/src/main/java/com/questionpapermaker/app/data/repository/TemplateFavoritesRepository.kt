package com.questionpapermaker.app.data.repository

import android.content.Context
import androidx.core.content.edit
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.withContext

/** Stores which built-in templates the teacher has starred as favourites, locally. */
class TemplateFavoritesRepository(context: Context) {

    private val prefs = context.applicationContext.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)

    private val _favoriteIds = MutableStateFlow(loadFavoriteIds())
    val favoriteIds: StateFlow<Set<String>> = _favoriteIds.asStateFlow()

    private fun loadFavoriteIds(): Set<String> = prefs.getStringSet(KEY_FAVORITES, emptySet())?.toSet().orEmpty()

    suspend fun toggleFavorite(templateId: String) = withContext(Dispatchers.IO) {
        val current = _favoriteIds.value
        val updated = if (templateId in current) current - templateId else current + templateId
        prefs.edit { putStringSet(KEY_FAVORITES, updated) }
        _favoriteIds.value = updated
    }

    private companion object {
        const val PREFS_NAME = "template_favorites"
        const val KEY_FAVORITES = "favorite_ids"
    }
}
