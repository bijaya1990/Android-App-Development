package com.pdfimagetools.app.ui.splitpdf

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

enum class SplitModeUi { RANGE, EVERY_N }

data class SplitPdfUiState(
    val phase: ToolPhase = ToolPhase.PICK,
    val fileUri: Uri? = null,
    val fileName: String = "",
    val totalPages: Int = 0,
    val isLoadingPageCount: Boolean = false,
    val mode: SplitModeUi = SplitModeUi.RANGE,
    val startPage: Int = 1,
    val endPage: Int = 1,
    val everyN: Int = 1,
    val result: ToolResult? = null,
    val errorMessage: String? = null
)

class SplitPdfViewModel(
    private val pdfManager: PdfManager,
    private val storageManager: StorageManager,
    private val saveOrchestrator: SaveOrchestrator
) : ViewModel() {

    private val _uiState = MutableStateFlow(SplitPdfUiState())
    val uiState: StateFlow<SplitPdfUiState> = _uiState.asStateFlow()

    fun onPdfPicked(context: Context, uri: Uri) {
        val name = UriUtils.queryDisplayName(context, uri) ?: "document.pdf"
        _uiState.value = SplitPdfUiState(
            phase = ToolPhase.CONFIGURE,
            fileUri = uri,
            fileName = name,
            isLoadingPageCount = true
        )
        viewModelScope.launch {
            val pages = runCatching { pdfManager.getPageCount(uri) }.getOrDefault(0)
            _uiState.update {
                it.copy(isLoadingPageCount = false, totalPages = pages, endPage = pages.coerceAtLeast(1))
            }
        }
    }

    fun setMode(mode: SplitModeUi) = _uiState.update { it.copy(mode = mode) }

    fun setStartPage(value: Int) = _uiState.update {
        val clamped = value.coerceIn(1, it.totalPages.coerceAtLeast(1))
        it.copy(startPage = clamped, endPage = it.endPage.coerceAtLeast(clamped))
    }

    fun setEndPage(value: Int) = _uiState.update {
        val clamped = value.coerceIn(it.startPage, it.totalPages.coerceAtLeast(1))
        it.copy(endPage = clamped)
    }

    fun setEveryN(value: Int) = _uiState.update {
        it.copy(everyN = value.coerceIn(1, it.totalPages.coerceAtLeast(1)))
    }

    fun process() {
        val state = _uiState.value
        val uri = state.fileUri ?: return
        _uiState.update { it.copy(phase = ToolPhase.PROCESSING, errorMessage = null) }
        viewModelScope.launch {
            runCatching {
                when (state.mode) {
                    SplitModeUi.RANGE -> {
                        val outputFile = storageManager.newWorkFile("split_pdf", "pdf")
                        listOf(pdfManager.splitPdfByRange(uri, state.startPage, state.endPage, outputFile))
                    }
                    SplitModeUi.EVERY_N -> {
                        val outputDir = storageManager.newWorkFile("split_pdf_batch", "dir").apply { mkdirs() }
                        pdfManager.splitPdfEveryNPages(uri, state.everyN, outputDir, "Split_${FileUtils.timestamp()}")
                    }
                }
            }.onSuccess { files ->
                val baseName = FileUtils.nameWithoutExtension(state.fileName)
                val outputs = files.mapIndexed { index, file ->
                    val displayName = if (files.size == 1) {
                        "${baseName}_p${state.startPage}-${state.endPage}.pdf"
                    } else {
                        "${baseName}_part${index + 1}.pdf"
                    }
                    ProcessedOutput(file, displayName, MimeUtils.PDF)
                }
                _uiState.update {
                    it.copy(phase = ToolPhase.RESULT, result = ToolResult(outputs = outputs))
                }
            }.onFailure { throwable ->
                _uiState.update {
                    it.copy(phase = ToolPhase.CONFIGURE, errorMessage = throwable.message ?: "Failed to split PDF")
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
                saveOrchestrator.saveOutputs(activity, currentResult.outputs, SaveTarget.DOWNLOADS, "Split PDF")
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
