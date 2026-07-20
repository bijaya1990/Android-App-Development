package com.questionpapermaker.app.ui.preview

import android.content.Context
import android.graphics.Bitmap
import android.graphics.Color
import android.graphics.pdf.PdfRenderer
import android.os.ParcelFileDescriptor
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.questionpapermaker.app.data.model.PaperWithContent
import com.questionpapermaker.app.data.repository.PaperRepository
import com.questionpapermaker.app.docx.DocxExporter
import com.questionpapermaker.app.engine.ValidationEngine
import com.questionpapermaker.app.engine.ValidationIssue
import com.questionpapermaker.app.pdf.PdfExporter
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.FlowPreview
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.collectLatest
import kotlinx.coroutines.flow.debounce
import kotlinx.coroutines.flow.filterNotNull
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import java.io.File

/**
 * Renders the paper to an actual PDF on every change and rasterizes that same PDF for on-screen
 * preview, so what the teacher sees can never drift from what gets printed or exported.
 */
class PreviewViewModel(
    private val paperId: String,
    private val repository: PaperRepository,
    private val pdfExporter: PdfExporter,
    private val docxExporter: DocxExporter,
    private val appContext: Context
) : ViewModel() {

    val content: StateFlow<PaperWithContent?> = repository.observePaperWithContent(paperId)
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), null)

    val validationIssues: StateFlow<List<ValidationIssue>> = content
        .map { c -> c?.let { ValidationEngine.validate(it) }.orEmpty() }
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), emptyList())

    private val _pdfFile = MutableStateFlow<File?>(null)
    val pdfFile: StateFlow<File?> = _pdfFile

    private val _pageBitmaps = MutableStateFlow<List<Bitmap>>(emptyList())
    val pageBitmaps: StateFlow<List<Bitmap>> = _pageBitmaps

    private val _isRendering = MutableStateFlow(false)
    val isRendering: StateFlow<Boolean> = _isRendering

    init {
        @OptIn(FlowPreview::class, ExperimentalCoroutinesApi::class)
        viewModelScope.launch {
            content.filterNotNull().debounce(400).collectLatest { current ->
                _isRendering.value = true
                val previewDir = File(appContext.cacheDir, "preview").apply { mkdirs() }
                val file = File(previewDir, "$paperId.pdf")
                pdfExporter.export(current, file)
                _pdfFile.value = file
                _pageBitmaps.value = renderPages(file)
                _isRendering.value = false
            }
        }
    }

    /** Copies the freshly exported PDF into permanent app storage (for sharing/printing/export history). */
    fun exportToFiles(onReady: (File) -> Unit) {
        val current = content.value ?: return
        viewModelScope.launch {
            val exportsDir = File(appContext.filesDir, "exports").apply { mkdirs() }
            val safeName = current.paper.displayTitle.replace(Regex("[^A-Za-z0-9 _-]"), "").ifBlank { "Question Paper" }
            val outFile = File(exportsDir, "$safeName.pdf")
            pdfExporter.export(current, outFile)
            repository.savePaper(
                current.paper.copy(
                    lastExportedAt = System.currentTimeMillis(),
                    lastExportedPdfUri = outFile.toURI().toString()
                )
            )
            onReady(outFile)
        }
    }

    /** Exports the paper as a real, editable Word (.docx) document into permanent app storage. */
    fun exportDocx(onReady: (File) -> Unit) {
        val current = content.value ?: return
        viewModelScope.launch {
            val exportsDir = File(appContext.filesDir, "exports").apply { mkdirs() }
            val safeName = current.paper.displayTitle.replace(Regex("[^A-Za-z0-9 _-]"), "").ifBlank { "Question Paper" }
            val outFile = File(exportsDir, "$safeName.docx")
            docxExporter.export(current, outFile)
            onReady(outFile)
        }
    }

    private suspend fun renderPages(file: File): List<Bitmap> = withContext(Dispatchers.IO) {
        val scale = 2f
        // PdfRenderer takes ownership of the descriptor and closes it itself -- do not also
        // wrap it in its own `use {}` or the second close() throws.
        val descriptor = ParcelFileDescriptor.open(file, ParcelFileDescriptor.MODE_READ_ONLY)
        PdfRenderer(descriptor).use { renderer ->
            (0 until renderer.pageCount).map { index ->
                renderer.openPage(index).use { page ->
                    val bitmap = Bitmap.createBitmap(
                        (page.width * scale).toInt().coerceAtLeast(1),
                        (page.height * scale).toInt().coerceAtLeast(1),
                        Bitmap.Config.ARGB_8888
                    )
                    bitmap.eraseColor(Color.WHITE)
                    page.render(bitmap, null, null, PdfRenderer.Page.RENDER_MODE_FOR_DISPLAY)
                    bitmap
                }
            }
        }
    }
}
