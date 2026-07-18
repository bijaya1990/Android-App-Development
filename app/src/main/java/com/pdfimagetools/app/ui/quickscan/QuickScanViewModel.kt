package com.pdfimagetools.app.ui.quickscan

import android.app.Activity
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.graphics.Canvas
import android.graphics.ColorMatrix
import android.graphics.ColorMatrixColorFilter
import android.graphics.Paint
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
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import java.io.File
import java.io.FileOutputStream
import java.util.UUID

data class ScannedPage(val id: String, val file: File)

data class QuickScanUiState(
    val phase: ToolPhase = ToolPhase.PICK,
    val pages: List<ScannedPage> = emptyList(),
    val isProcessingCapture: Boolean = false,
    val result: ToolResult? = null,
    val errorMessage: String? = null
)

/**
 * Fully custom capture flow (CameraX), replacing the earlier Google-scanner-UI integration.
 * Deliberately has no live edge-tracking overlay: each shot is captured, then deterministically
 * margin-cropped and contrast-enhanced, which is far more predictable/stable than a hand-rolled
 * real-time detector that can't be verified without a physical device. The camera reopens
 * automatically after every capture so the user can keep scanning pages back-to-back.
 */
class QuickScanViewModel(
    private val pdfManager: PdfManager,
    private val storageManager: StorageManager,
    private val saveOrchestrator: SaveOrchestrator
) : ViewModel() {

    private val _uiState = MutableStateFlow(QuickScanUiState())
    val uiState: StateFlow<QuickScanUiState> = _uiState.asStateFlow()

    fun onPhotoCaptured(rawFile: File) {
        _uiState.update { it.copy(isProcessingCapture = true, errorMessage = null) }
        viewModelScope.launch {
            runCatching {
                withContext(Dispatchers.Default) {
                    val original = decodeSampledBitmap(rawFile, MAX_CAPTURE_DIMENSION)
                        ?: error("Couldn't read the captured photo")
                    val processed = enhanceAndCrop(original)
                    val outputFile = storageManager.newWorkFile("scan_page", "jpg")
                    FileOutputStream(outputFile).use { out ->
                        processed.compress(Bitmap.CompressFormat.JPEG, 90, out)
                    }
                    processed.recycle()
                    outputFile
                }
            }.onSuccess { processedFile ->
                rawFile.delete()
                _uiState.update { state ->
                    state.copy(
                        isProcessingCapture = false,
                        pages = state.pages + ScannedPage(UUID.randomUUID().toString(), processedFile)
                    )
                }
            }.onFailure { throwable ->
                rawFile.delete()
                _uiState.update { it.copy(isProcessingCapture = false, errorMessage = throwable.message ?: "Couldn't process that page") }
            }
        }
    }

    fun onCaptureError(message: String) {
        _uiState.update { it.copy(isProcessingCapture = false, errorMessage = message) }
    }

    fun removePage(id: String) {
        _uiState.update { state -> state.copy(pages = state.pages.filterNot { it.id == id }) }
    }

    fun finishScanning() {
        val pages = _uiState.value.pages
        if (pages.isEmpty()) return
        _uiState.update { it.copy(phase = ToolPhase.PROCESSING, errorMessage = null) }
        viewModelScope.launch {
            runCatching {
                val pageUris = pages.map { storageManager.uriForWorkFile(it.file) }
                val outputFile = storageManager.newWorkFile("scan", "pdf")
                pdfManager.imagesToPdf(pageUris, outputFile)
            }.onSuccess { outputFile ->
                val output = ProcessedOutput(outputFile, "Scan_${FileUtils.timestamp()}.pdf", MimeUtils.PDF)
                _uiState.update {
                    it.copy(phase = ToolPhase.RESULT, result = ToolResult(outputs = listOf(output)))
                }
            }.onFailure { throwable ->
                _uiState.update {
                    it.copy(phase = ToolPhase.PICK, errorMessage = throwable.message ?: "Failed to build the PDF")
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

    /** Modern camera sensors can produce 12-48MP stills; decoding those at full resolution (and
     * then allocating two more full-size bitmaps during crop/enhance) risks OutOfMemoryError on
     * many devices. A scanned page never needs more than a couple thousand pixels on its long
     * edge, so downsample at decode time the same way the rest of the app already does. */
    private fun decodeSampledBitmap(file: File, maxDimension: Int): Bitmap? {
        val boundsOptions = BitmapFactory.Options().apply { inJustDecodeBounds = true }
        BitmapFactory.decodeFile(file.absolutePath, boundsOptions)

        var sampleSize = 1
        val width = boundsOptions.outWidth
        val height = boundsOptions.outHeight
        if (width > 0 && height > 0 && (width > maxDimension || height > maxDimension)) {
            var halfWidth = width / 2
            var halfHeight = height / 2
            while ((halfWidth / sampleSize) >= maxDimension || (halfHeight / sampleSize) >= maxDimension) {
                sampleSize *= 2
            }
        }

        val decodeOptions = BitmapFactory.Options().apply { inSampleSize = sampleSize }
        return BitmapFactory.decodeFile(file.absolutePath, decodeOptions)
    }

    /** Trims a small margin (typical background/hand around a held document) and lifts
     * contrast/brightness for a cleaner "scanned" look — deterministic, no detection guesswork. */
    private fun enhanceAndCrop(source: Bitmap): Bitmap {
        val marginX = (source.width * 0.03f).toInt()
        val marginY = (source.height * 0.03f).toInt()
        val width = (source.width - 2 * marginX).coerceAtLeast(1)
        val height = (source.height - 2 * marginY).coerceAtLeast(1)
        val cropped = Bitmap.createBitmap(source, marginX, marginY, width, height)

        val enhanced = Bitmap.createBitmap(cropped.width, cropped.height, Bitmap.Config.ARGB_8888)
        val canvas = Canvas(enhanced)
        val contrast = 1.12f
        val translate = (-0.5f * contrast + 0.5f) * 255f
        val colorMatrix = ColorMatrix(
            floatArrayOf(
                contrast, 0f, 0f, 0f, translate,
                0f, contrast, 0f, 0f, translate,
                0f, 0f, contrast, 0f, translate,
                0f, 0f, 0f, 1f, 0f
            )
        )
        val paint = Paint(Paint.ANTI_ALIAS_FLAG).apply { colorFilter = ColorMatrixColorFilter(colorMatrix) }
        canvas.drawBitmap(cropped, 0f, 0f, paint)

        if (cropped !== source) cropped.recycle()
        source.recycle()
        return enhanced
    }

    override fun onCleared() {
        _uiState.value.pages.forEach { it.file.delete() }
        super.onCleared()
    }

    companion object {
        private const val MAX_CAPTURE_DIMENSION = 2400
    }
}
