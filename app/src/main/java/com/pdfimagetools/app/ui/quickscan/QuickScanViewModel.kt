package com.pdfimagetools.app.ui.quickscan

import android.app.Activity
import android.content.Context
import android.net.Uri
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.pdfimagetools.app.core.storage.SaveTarget
import com.pdfimagetools.app.core.storage.StorageManager
import com.pdfimagetools.app.domain.SaveOrchestrator
import com.pdfimagetools.app.ui.common.ProcessedOutput
import com.pdfimagetools.app.ui.common.ToolPhase
import com.pdfimagetools.app.ui.common.ToolResult
import com.pdfimagetools.app.util.FileUtils
import com.pdfimagetools.app.util.MimeUtils
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

data class QuickScanUiState(
    val phase: ToolPhase = ToolPhase.PICK,
    val result: ToolResult? = null,
    val errorMessage: String? = null
)

/**
 * Wraps Google's ML Kit Document Scanner (Play services), which owns the entire capture
 * experience: live edge detection, auto-crop, perspective correction, filters, and
 * multi-page batch capture. This view model only takes over once the scanner hands back a
 * finished PDF, feeding it through the same save/share pipeline every other tool uses.
 */
class QuickScanViewModel(
    private val storageManager: StorageManager,
    private val saveOrchestrator: SaveOrchestrator
) : ViewModel() {

    private val _uiState = MutableStateFlow(QuickScanUiState())
    val uiState: StateFlow<QuickScanUiState> = _uiState.asStateFlow()

    fun onScanCompleted(context: Context, scannedPdfUri: Uri) {
        _uiState.update { it.copy(errorMessage = null) }
        viewModelScope.launch {
            runCatching {
                val outputFile = storageManager.newWorkFile("scan", "pdf")
                withContext(Dispatchers.IO) {
                    context.contentResolver.openInputStream(scannedPdfUri)?.use { input ->
                        outputFile.outputStream().use { output -> input.copyTo(output) }
                    } ?: error("Unable to read scanned document")
                }
                outputFile
            }.onSuccess { outputFile ->
                val output = ProcessedOutput(outputFile, "Scan_${FileUtils.timestamp()}.pdf", MimeUtils.PDF)
                _uiState.update {
                    it.copy(phase = ToolPhase.RESULT, result = ToolResult(outputs = listOf(output)))
                }
            }.onFailure { throwable ->
                _uiState.update { it.copy(errorMessage = throwable.message ?: "Failed to import scan") }
            }
        }
    }

    fun onScanFailed(message: String) {
        _uiState.update { it.copy(errorMessage = message) }
    }

    fun scanAnother() {
        _uiState.value = QuickScanUiState()
    }

    fun save(activity: Activity) {
        val currentResult = _uiState.value.result ?: return
        if (currentResult.isSaving) return
        _uiState.update { it.copy(result = currentResult.copy(isSaving = true, saveError = null)) }
        viewModelScope.launch {
            runCatching {
                saveOrchestrator.saveOutputs(activity, currentResult.outputs, SaveTarget.DOWNLOADS, "Quick Scan")
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
