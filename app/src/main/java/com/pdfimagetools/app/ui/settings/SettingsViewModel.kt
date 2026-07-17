package com.pdfimagetools.app.ui.settings

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.pdfimagetools.app.core.storage.SaveTarget
import com.pdfimagetools.app.core.storage.StorageManager
import com.pdfimagetools.app.data.AppSettings
import com.pdfimagetools.app.data.SettingsStore
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

class SettingsViewModel(
    private val settingsStore: SettingsStore,
    private val storageManager: StorageManager
) : ViewModel() {

    val settings: StateFlow<AppSettings> = settingsStore.settings

    private val _cacheSizeBytes = MutableStateFlow(storageManager.cacheSizeBytes())
    val cacheSizeBytes: StateFlow<Long> = _cacheSizeBytes.asStateFlow()

    fun setDefaultImageSaveTarget(target: SaveTarget) = settingsStore.setDefaultImageSaveTarget(target)

    fun setDefaultQuality(quality: Int) = settingsStore.setDefaultQualityPercent(quality)

    fun clearCache() {
        viewModelScope.launch {
            storageManager.clearWorkCache()
            _cacheSizeBytes.value = storageManager.cacheSizeBytes()
        }
    }

    fun refreshCacheSize() {
        _cacheSizeBytes.value = storageManager.cacheSizeBytes()
    }
}
