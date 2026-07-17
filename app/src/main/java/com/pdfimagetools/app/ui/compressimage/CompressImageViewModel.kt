package com.pdfimagetools.app.ui.compressimage

import android.app.Activity
import android.net.Uri
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.pdfimagetools.app.core.image.ImageManager
import com.pdfimagetools.app.core.storage.SaveTarget
import com.pdfimagetools.app.core.storage.StorageManager
import com.pdfimagetools.app.data.SettingsStore
import com.pdfimagetools.app.domain.SaveOrchestrator
import com.pdfimagetools.app.ui.common.ProcessedOutput
import com.pdfimagetools.app.ui.common.ToolPhase
import com.pdfimagetools.app.ui.common.ToolResult
import com.pdfimagetools.app.util.FileUtils
import com.pdfimagetools.app.util.MimeUtils
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class CompressImageUiState(
    val phase: ToolPhase = ToolPhase.PICK,
    val images: List<Uri> = emptyList(),
    val quality: Int = 85,
    val progress: Pair<Int, Int>? = null,
    val result: ToolResult? = null,
    val errorMessage: String? = null
)

class CompressImageViewModel(
    private val imageManager: ImageManager,
    private val storageManager: StorageManager,
    private val saveOrchestrator: SaveOrchestrator,
    settingsStore: SettingsStore
) : ViewModel() {

    private val _uiState = MutableStateFlow(CompressImageUiState(quality = settingsStore.settings.value.defaultQualityPercent))
    val uiState: StateFlow<CompressImageUiState> = _uiState.asStateFlow()

    fun onImagesPicked(uris: List<Uri>) {
        if (uris.isEmpty()) return
        _uiState.update { it.copy(images = it.images + uris, phase = ToolPhase.CONFIGURE) }
    }

    fun setQuality(value: Int) = _uiState.update { it.copy(quality = value.coerceIn(10, 100)) }

    fun removeImage(index: Int) = _uiState.update { state ->
        val mutable = state.images.toMutableList()
        if (index in mutable.indices) mutable.removeAt(index)
        state.copy(images = mutable, phase = if (mutable.isEmpty()) ToolPhase.PICK else state.phase)
    }

    fun process() {
        val state = _uiState.value
        if (state.images.isEmpty()) return
        _uiState.update { it.copy(phase = ToolPhase.PROCESSING, progress = 0 to state.images.size, errorMessage = null) }
        viewModelScope.launch {
            runCatching {
                var originalTotal = 0L
                val outputs = mutableListOf<ProcessedOutput>()
                state.images.forEachIndexed { index, uri ->
                    originalTotal += imageManager.originalSizeBytes(uri)
                    val outputFile = storageManager.newWorkFile("compressed_image", "jpg")
                    imageManager.compressImage(uri, state.quality, outputFile)
                    val displayName = "Compressed_${FileUtils.timestamp()}_${index + 1}.jpg"
                    outputs.add(ProcessedOutput(outputFile, displayName, MimeUtils.JPEG))
                    _uiState.update { it.copy(progress = (index + 1) to state.images.size) }
                }
                originalTotal to outputs
            }.onSuccess { (originalTotal, outputs) ->
                _uiState.update {
                    it.copy(
                        phase = ToolPhase.RESULT,
                        result = ToolResult(outputs = outputs, originalTotalSizeBytes = originalTotal)
                    )
                }
            }.onFailure { throwable ->
                _uiState.update {
                    it.copy(phase = ToolPhase.CONFIGURE, errorMessage = throwable.message ?: "Failed to compress images")
                }
            }
        }
    }

    fun save(activity: Activity) {
        val currentResult = _uiState.value.result ?: return
        if (currentResult.isSaving) return
        _uiState.update { it.copy(result = currentResult.copy(isSaving = true, saveError = null)) }
        viewModelScope.launch {
            runCatching {
                saveOrchestrator.saveOutputs(activity, currentResult.outputs, SaveTarget.PICTURES, "Compress image")
            }.onSuccess { saved ->
                _uiState.update {
                    val latest = it.result ?: currentResult
                    it.copy(result = latest.copy(isSaving = false, savedFiles = latest.savedFiles + saved))
                }
            }.onFailure { throwable ->
                _uiState.update {
                    val latest = it.result ?: currentResult
                    it.copy(result = latest.copy(isSaving = false, saveError = throwable.message ?: "Could not save file"))
                }
            }
        }
    }
}
