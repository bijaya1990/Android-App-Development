package com.pdfimagetools.app.ui.protectpdf

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

data class ProtectPdfUiState(
    val phase: ToolPhase = ToolPhase.PICK,
    val fileUri: Uri? = null,
    val fileName: String = "",
    val userPassword: String = "",
    val ownerPassword: String = "",
    val allowPrinting: Boolean = true,
    val allowCopy: Boolean = true,
    val result: ToolResult? = null,
    val errorMessage: String? = null
)

class ProtectPdfViewModel(
    private val pdfManager: PdfManager,
    private val storageManager: StorageManager,
    private val saveOrchestrator: SaveOrchestrator
) : ViewModel() {

    private val _uiState = MutableStateFlow(ProtectPdfUiState())
    val uiState: StateFlow<ProtectPdfUiState> = _uiState.asStateFlow()

    fun onPdfPicked(context: Context, uri: Uri) {
        _uiState.value = ProtectPdfUiState(
            phase = ToolPhase.CONFIGURE,
            fileUri = uri,
            fileName = UriUtils.queryDisplayName(context, uri) ?: "document.pdf"
        )
    }

    fun setUserPassword(value: String) = _uiState.update { it.copy(userPassword = value) }
    fun setOwnerPassword(value: String) = _uiState.update { it.copy(ownerPassword = value) }
    fun setAllowPrinting(value: Boolean) = _uiState.update { it.copy(allowPrinting = value) }
    fun setAllowCopy(value: Boolean) = _uiState.update { it.copy(allowCopy = value) }

    fun process() {
        val state = _uiState.value
        val uri = state.fileUri ?: return
        if (state.userPassword.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Enter a password to open the PDF") }
            return
        }
        val ownerPassword = state.ownerPassword.ifBlank { state.userPassword }
        _uiState.update { it.copy(phase = ToolPhase.PROCESSING, errorMessage = null) }
        viewModelScope.launch {
            runCatching {
                val outputFile = storageManager.newWorkFile("protect_pdf", "pdf")
                pdfManager.protectPdf(
                    pdfUri = uri,
                    ownerPassword = ownerPassword,
                    userPassword = state.userPassword,
                    allowPrinting = state.allowPrinting,
                    allowCopy = state.allowCopy,
                    outputFile = outputFile
                )
            }.onSuccess { outputFile ->
                val baseName = FileUtils.nameWithoutExtension(state.fileName)
                val output = ProcessedOutput(outputFile, "${baseName}_protected.pdf", MimeUtils.PDF)
                _uiState.update {
                    it.copy(phase = ToolPhase.RESULT, result = ToolResult(outputs = listOf(output)))
                }
            }.onFailure { throwable ->
                _uiState.update {
                    it.copy(phase = ToolPhase.CONFIGURE, errorMessage = throwable.message ?: "Failed to protect PDF")
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
                saveOrchestrator.saveOutputs(activity, currentResult.outputs, SaveTarget.DOWNLOADS, "Password protect")
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
