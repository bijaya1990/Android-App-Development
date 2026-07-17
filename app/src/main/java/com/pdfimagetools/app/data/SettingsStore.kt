package com.pdfimagetools.app.data

import android.content.Context
import com.pdfimagetools.app.core.storage.SaveTarget
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow

data class AppSettings(
    val defaultImageSaveTarget: SaveTarget = SaveTarget.PICTURES,
    val defaultQualityPercent: Int = 85
)

class SettingsStore(context: Context) {

    private val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
    private val _settings = MutableStateFlow(load())
    val settings: StateFlow<AppSettings> = _settings.asStateFlow()

    fun setDefaultImageSaveTarget(target: SaveTarget) {
        update { it.copy(defaultImageSaveTarget = target) }
        prefs.edit().putString(KEY_SAVE_TARGET, target.name).apply()
    }

    fun setDefaultQualityPercent(quality: Int) {
        val clamped = quality.coerceIn(10, 100)
        update { it.copy(defaultQualityPercent = clamped) }
        prefs.edit().putInt(KEY_QUALITY, clamped).apply()
    }

    private fun update(transform: (AppSettings) -> AppSettings) {
        _settings.value = transform(_settings.value)
    }

    private fun load(): AppSettings {
        val target = runCatching {
            SaveTarget.valueOf(prefs.getString(KEY_SAVE_TARGET, SaveTarget.PICTURES.name)!!)
        }.getOrDefault(SaveTarget.PICTURES)
        val quality = prefs.getInt(KEY_QUALITY, 85)
        return AppSettings(defaultImageSaveTarget = target, defaultQualityPercent = quality)
    }

    companion object {
        private const val PREFS_NAME = "app_settings"
        private const val KEY_SAVE_TARGET = "default_image_save_target"
        private const val KEY_QUALITY = "default_quality_percent"
    }
}
