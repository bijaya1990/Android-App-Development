package com.pdfimagetools.app.ui.compresspdf

import android.app.Activity
import android.content.Context
import android.net.Uri
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.pdfimagetools.app.core.pdf.CompressionLevel
import com.pdfimagetools.app.core.pdf.PdfManager
import com.pdfimagetools.app.core.storage.SaveTarget
import com.pdfimagetools.app.core.storage.StorageManager
import com.pdfimagetools.app.domain.SaveOrchestrator
import com.pdfimagetools.app.ui.common.ProcessedOutput
import com.pdfimagetools.app.ui.common.ToolPhase
import com.pdfimagetools.app.ui.common.ToolResult
import com.pdfimagetools.app.util.FileUtils
import com.pdfimagetools.app.util.MimeUtils
import com.pdfimagetools.app.util.UriUtils
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class CompressPdfUiState(
    val phase: ToolPhase = ToolPhase.PICK,
    val fileUri: Uri? = null,
    val fileName: String = "",
    val originalSizeBytes: Long = 0,
    val level: CompressionLevel = CompressionLevel.MEDIUM,
    val result: ToolResult? = null,
    val errorMessage: String? = null
)

class CompressPdfViewModel(
    private val pdfManager: PdfManager,
    private val storageManager: StorageManager,
    private val saveOrchestrator: SaveOrchestrator
) : ViewModel() {

    private val _uiState = MutableStateFlow(CompressPdfUiState())
    val uiState: StateFlow<CompressPdfUiState> = _uiState.asStateFlow()

    fun onPdfPicked(context: Context, uri: Uri) {
        _uiState.value = CompressPdfUiState(
            phase = ToolPhase.CONFIGURE,
            fileUri = uri,
            fileName = UriUtils.queryDisplayName(context, uri) ?: "document.pdf",
            originalSizeBytes = UriUtils.querySize(context, uri)
        )
    }

    fun setLevel(level: CompressionLevel) = _uiState.update { it.copy(level = level) }

    fun process() {
        val state = _uiState.value
        val uri = state.fileUri ?: return
        _uiState.update { it.copy(phase = ToolPhase.PROCESSING, errorMessage = null) }
        viewModelScope.launch {
            runCatching {
                val outputFile = storageManager.newWorkFile("compress_pdf", "pdf")
                pdfManager.compressPdf(uri, state.level, outputFile)
            }.onSuccess { outputFile ->
                val baseName = FileUtils.nameWithoutExtension(state.fileName)
                val output = ProcessedOutput(outputFile, "${baseName}_compressed.pdf", MimeUtils.PDF)
                _uiState.update {
                    it.copy(
                        phase = ToolPhase.RESULT,
                        result = ToolResult(outputs = listOf(output), originalTotalSizeBytes = state.originalSizeBytes)
                    )
                }
            }.onFailure { throwable ->
                _uiState.update {
                    it.copy(phase = ToolPhase.CONFIGURE, errorMessage = throwable.message ?: "Failed to compress PDF")
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
                saveOrchestrator.saveOutputs(activity, currentResult.outputs, SaveTarget.DOWNLOADS, "Compress PDF")
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
