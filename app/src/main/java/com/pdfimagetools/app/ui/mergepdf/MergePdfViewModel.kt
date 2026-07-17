package com.pdfimagetools.app.ui.mergepdf

import android.app.Activity
import android.content.Context
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
import com.pdfimagetools.app.util.UriUtils
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class PickedPdf(val uri: Uri, val displayName: String, val sizeBytes: Long)

data class MergePdfUiState(
    val phase: ToolPhase = ToolPhase.PICK,
    val files: List<PickedPdf> = emptyList(),
    val result: ToolResult? = null,
    val errorMessage: String? = null
)

class MergePdfViewModel(
    private val pdfManager: PdfManager,
    private val storageManager: StorageManager,
    private val saveOrchestrator: SaveOrchestrator
) : ViewModel() {

    private val _uiState = MutableStateFlow(MergePdfUiState())
    val uiState: StateFlow<MergePdfUiState> = _uiState.asStateFlow()

    fun onPdfsPicked(context: Context, uris: List<Uri>) {
        if (uris.isEmpty()) return
        val picked = uris.map { uri ->
            PickedPdf(uri, UriUtils.queryDisplayName(context, uri) ?: "document.pdf", UriUtils.querySize(context, uri))
        }
        _uiState.update { it.copy(files = it.files + picked, phase = ToolPhase.CONFIGURE) }
    }

    fun moveFile(fromIndex: Int, toIndex: Int) {
        _uiState.update { state ->
            val mutable = state.files.toMutableList()
            if (toIndex !in mutable.indices || fromIndex !in mutable.indices) return@update state
            mutable.add(toIndex, mutable.removeAt(fromIndex))
            state.copy(files = mutable)
        }
    }

    fun removeFile(index: Int) {
        _uiState.update { state ->
            val mutable = state.files.toMutableList()
            if (index in mutable.indices) mutable.removeAt(index)
            state.copy(files = mutable, phase = if (mutable.isEmpty()) ToolPhase.PICK else state.phase)
        }
    }

    fun process() {
        val files = _uiState.value.files
        if (files.size < 2) {
            _uiState.update { it.copy(errorMessage = "Select at least two PDFs to merge") }
            return
        }
        _uiState.update { it.copy(phase = ToolPhase.PROCESSING, errorMessage = null) }
        viewModelScope.launch {
            val originalTotal = files.sumOf { it.sizeBytes }
            runCatching {
                val outputFile = storageManager.newWorkFile("merge_pdf", "pdf")
                pdfManager.mergePdfs(files.map { it.uri }, outputFile)
                outputFile
            }.onSuccess { outputFile ->
                val displayName = "Merged_${FileUtils.timestamp()}.pdf"
                val output = ProcessedOutput(outputFile, displayName, MimeUtils.PDF)
                _uiState.update {
                    it.copy(
                        phase = ToolPhase.RESULT,
                        result = ToolResult(outputs = listOf(output), originalTotalSizeBytes = originalTotal)
                    )
                }
            }.onFailure { throwable ->
                _uiState.update {
                    it.copy(phase = ToolPhase.CONFIGURE, errorMessage = throwable.message ?: "Failed to merge PDFs")
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
                saveOrchestrator.saveOutputs(activity, currentResult.outputs, SaveTarget.DOWNLOADS, "Merge PDF")
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
