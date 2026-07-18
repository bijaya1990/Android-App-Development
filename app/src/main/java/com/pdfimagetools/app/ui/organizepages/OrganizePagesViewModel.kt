package com.pdfimagetools.app.ui.organizepages

import android.app.Activity
import android.content.Context
import android.graphics.Bitmap
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

data class PageThumbnail(val index: Int, val bitmap: Bitmap)

data class OrganizePagesUiState(
    val phase: ToolPhase = ToolPhase.PICK,
    val fileUri: Uri? = null,
    val fileName: String = "",
    val thumbnails: List<PageThumbnail> = emptyList(),
    val isLoadingThumbnails: Boolean = false,
    val selectedIndices: Set<Int> = emptySet(),
    val result: ToolResult? = null,
    val errorMessage: String? = null
)

/** Backs both the "Delete pages" and "Extract pages" quick actions — same picker + thumbnail
 * grid, two different destructive/non-destructive actions on the selection. */
class OrganizePagesViewModel(
    private val pdfManager: PdfManager,
    private val storageManager: StorageManager,
    private val saveOrchestrator: SaveOrchestrator
) : ViewModel() {

    private val _uiState = MutableStateFlow(OrganizePagesUiState())
    val uiState: StateFlow<OrganizePagesUiState> = _uiState.asStateFlow()

    fun onPdfPicked(context: Context, uri: Uri) {
        recycleThumbnails()
        _uiState.value = OrganizePagesUiState(
            phase = ToolPhase.CONFIGURE,
            fileUri = uri,
            fileName = UriUtils.queryDisplayName(context, uri) ?: "document.pdf",
            isLoadingThumbnails = true
        )
        viewModelScope.launch {
            runCatching { pdfManager.renderPageThumbnails(uri, THUMBNAIL_MAX_DIMENSION) }
                .onSuccess { bitmaps ->
                    _uiState.update {
                        it.copy(
                            isLoadingThumbnails = false,
                            thumbnails = bitmaps.mapIndexed { index, bitmap -> PageThumbnail(index, bitmap) }
                        )
                    }
                }
                .onFailure { throwable ->
                    _uiState.update {
                        it.copy(isLoadingThumbnails = false, errorMessage = throwable.message ?: "Failed to load pages")
                    }
                }
        }
    }

    fun togglePage(index: Int) = _uiState.update { state ->
        val updated = if (index in state.selectedIndices) state.selectedIndices - index else state.selectedIndices + index
        state.copy(selectedIndices = updated)
    }

    fun clearSelection() = _uiState.update { it.copy(selectedIndices = emptySet()) }

    fun deleteSelected() {
        val state = _uiState.value
        if (state.selectedIndices.isEmpty()) {
            _uiState.update { it.copy(errorMessage = "Select at least one page to delete") }
            return
        }
        val keep = state.thumbnails.map { it.index }.filterNot { it in state.selectedIndices }
        if (keep.isEmpty()) {
            _uiState.update { it.copy(errorMessage = "Keep at least one page") }
            return
        }
        runExtraction(keep, "_edited", "Delete pages")
    }

    fun extractSelected() {
        val state = _uiState.value
        if (state.selectedIndices.isEmpty()) {
            _uiState.update { it.copy(errorMessage = "Select at least one page to extract") }
            return
        }
        runExtraction(state.selectedIndices.sorted(), "_extracted", "Extract pages")
    }

    private fun runExtraction(keepIndices: List<Int>, suffix: String, toolName: String) {
        val state = _uiState.value
        val uri = state.fileUri ?: return
        _uiState.update { it.copy(phase = ToolPhase.PROCESSING, errorMessage = null) }
        viewModelScope.launch {
            runCatching {
                val outputFile = storageManager.newWorkFile("organize_pages", "pdf")
                pdfManager.extractPages(uri, keepIndices, outputFile)
            }.onSuccess { outputFile ->
                val baseName = FileUtils.nameWithoutExtension(state.fileName)
                val output = ProcessedOutput(outputFile, "$baseName$suffix.pdf", MimeUtils.PDF)
                _uiState.update {
                    it.copy(phase = ToolPhase.RESULT, result = ToolResult(outputs = listOf(output)))
                }
                lastToolName = toolName
            }.onFailure { throwable ->
                _uiState.update {
                    it.copy(phase = ToolPhase.CONFIGURE, errorMessage = throwable.message ?: "Failed to save pages")
                }
            }
        }
    }

    private var lastToolName: String = "Organize pages"

    fun save(activity: Activity) {
        val currentResult = _uiState.value.result ?: return
        if (currentResult.isSaving) return
        _uiState.update { it.copy(result = currentResult.copy(isSaving = true, saveError = null)) }
        viewModelScope.launch {
            runCatching {
                saveOrchestrator.saveOutputs(activity, currentResult.outputs, SaveTarget.DOWNLOADS, lastToolName)
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

    private fun recycleThumbnails() {
        _uiState.value.thumbnails.forEach { it.bitmap.recycle() }
    }

    override fun onCleared() {
        recycleThumbnails()
        super.onCleared()
    }

    companion object {
        private const val THUMBNAIL_MAX_DIMENSION = 320
    }
}
