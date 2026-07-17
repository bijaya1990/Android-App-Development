package com.pdfimagetools.app.ui.imagetopdf

import android.app.Activity
import android.net.Uri
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.pdfimagetools.app.core.pdf.PdfManager
import com.pdfimagetools.app.core.storage.SaveTarget
import com.pdfimagetools.app.core.storage.StorageManager
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

data class ImageToPdfUiState(
    val phase: ToolPhase = ToolPhase.PICK,
    val images: List<Uri> = emptyList(),
    val progress: Pair<Int, Int>? = null,
    val result: ToolResult? = null,
    val errorMessage: String? = null
)

class ImageToPdfViewModel(
    private val pdfManager: PdfManager,
    private val storageManager: StorageManager,
    private val saveOrchestrator: SaveOrchestrator
) : ViewModel() {

    private val _uiState = MutableStateFlow(ImageToPdfUiState())
    val uiState: StateFlow<ImageToPdfUiState> = _uiState.asStateFlow()

    fun onImagesPicked(uris: List<Uri>) {
        if (uris.isEmpty()) return
        _uiState.update { it.copy(images = it.images + uris, phase = ToolPhase.CONFIGURE) }
    }

    fun moveImage(fromIndex: Int, toIndex: Int) {
        _uiState.update { state ->
            val mutable = state.images.toMutableList()
            if (toIndex !in mutable.indices || fromIndex !in mutable.indices) return@update state
            val item = mutable.removeAt(fromIndex)
            mutable.add(toIndex, item)
            state.copy(images = mutable)
        }
    }

    fun removeImage(index: Int) {
        _uiState.update { state ->
            val mutable = state.images.toMutableList()
            if (index in mutable.indices) mutable.removeAt(index)
            state.copy(images = mutable, phase = if (mutable.isEmpty()) ToolPhase.PICK else state.phase)
        }
    }

    fun process() {
        val images = _uiState.value.images
        if (images.isEmpty()) return
        _uiState.update { it.copy(phase = ToolPhase.PROCESSING, progress = 0 to images.size, errorMessage = null) }
        viewModelScope.launch {
            runCatching {
                val outputFile = storageManager.newWorkFile("image_to_pdf", "pdf")
                pdfManager.imagesToPdf(images, outputFile) { done, total ->
                    _uiState.update { it.copy(progress = done to total) }
                }
                outputFile
            }.onSuccess { outputFile ->
                val displayName = "ImageToPDF_${FileUtils.timestamp()}.pdf"
                val output = ProcessedOutput(outputFile, displayName, MimeUtils.PDF)
                _uiState.update {
                    it.copy(phase = ToolPhase.RESULT, result = ToolResult(outputs = listOf(output)))
                }
            }.onFailure { throwable ->
                _uiState.update {
                    it.copy(phase = ToolPhase.CONFIGURE, errorMessage = throwable.message ?: "Failed to create PDF")
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
                saveOrchestrator.saveOutputs(activity, currentResult.outputs, SaveTarget.DOWNLOADS, "Image to PDF")
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

    fun reset() {
        _uiState.value = ImageToPdfUiState()
    }
}
