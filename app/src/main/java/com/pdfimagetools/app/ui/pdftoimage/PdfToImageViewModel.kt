package com.pdfimagetools.app.ui.pdftoimage

import android.app.Activity
import android.content.Context
import android.net.Uri
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.pdfimagetools.app.core.image.ImageFormat
import com.pdfimagetools.app.core.pdf.PdfManager
import com.pdfimagetools.app.core.storage.SaveTarget
import com.pdfimagetools.app.core.storage.StorageManager
import com.pdfimagetools.app.domain.SaveOrchestrator
import com.pdfimagetools.app.ui.common.ProcessedOutput
import com.pdfimagetools.app.ui.common.ToolPhase
import com.pdfimagetools.app.ui.common.ToolResult
import com.pdfimagetools.app.util.FileUtils
import com.pdfimagetools.app.util.UriUtils
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class PdfToImageUiState(
    val phase: ToolPhase = ToolPhase.PICK,
    val fileUri: Uri? = null,
    val fileName: String = "",
    val format: ImageFormat = ImageFormat.JPEG,
    val progress: Pair<Int, Int>? = null,
    val result: ToolResult? = null,
    val errorMessage: String? = null
)

class PdfToImageViewModel(
    private val pdfManager: PdfManager,
    private val storageManager: StorageManager,
    private val saveOrchestrator: SaveOrchestrator
) : ViewModel() {

    private val _uiState = MutableStateFlow(PdfToImageUiState())
    val uiState: StateFlow<PdfToImageUiState> = _uiState.asStateFlow()

    fun onPdfPicked(context: Context, uri: Uri) {
        _uiState.value = PdfToImageUiState(
            phase = ToolPhase.CONFIGURE,
            fileUri = uri,
            fileName = UriUtils.queryDisplayName(context, uri) ?: "document.pdf"
        )
    }

    fun setFormat(format: ImageFormat) = _uiState.update { it.copy(format = format) }

    fun process() {
        val state = _uiState.value
        val uri = state.fileUri ?: return
        _uiState.update { it.copy(phase = ToolPhase.PROCESSING, progress = 0 to 1, errorMessage = null) }
        viewModelScope.launch {
            runCatching {
                val baseName = FileUtils.nameWithoutExtension(state.fileName).ifBlank { "PDF" }
                val outputDir = storageManager.newWorkFile("pdf_to_image_batch", "dir").apply { mkdirs() }
                pdfManager.renderPdfToImages(uri, state.format, outputDir, baseName) { done, total ->
                    _uiState.update { it.copy(progress = done to total) }
                }
            }.onSuccess { files ->
                val outputs = files.map { file ->
                    ProcessedOutput(file, file.name, state.format.mimeType)
                }
                _uiState.update {
                    it.copy(phase = ToolPhase.RESULT, result = ToolResult(outputs = outputs))
                }
            }.onFailure { throwable ->
                _uiState.update {
                    it.copy(phase = ToolPhase.CONFIGURE, errorMessage = throwable.message ?: "Failed to render PDF pages")
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
                saveOrchestrator.saveOutputs(activity, currentResult.outputs, SaveTarget.PICTURES, "PDF to image")
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
