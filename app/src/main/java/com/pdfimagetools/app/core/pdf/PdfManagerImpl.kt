package com.pdfimagetools.app.core.pdf

import android.content.Context
import android.graphics.Bitmap
import android.net.Uri
import com.pdfimagetools.app.core.image.BitmapUtils
import com.pdfimagetools.app.core.image.ImageFormat
import com.tom_roush.pdfbox.io.MemoryUsageSetting
import com.tom_roush.pdfbox.multipdf.PDFMergerUtility
import com.tom_roush.pdfbox.multipdf.Splitter
import com.tom_roush.pdfbox.pdmodel.PDDocument
import com.tom_roush.pdfbox.pdmodel.PDPage
import com.tom_roush.pdfbox.pdmodel.PDPageContentStream
import com.tom_roush.pdfbox.pdmodel.common.PDRectangle
import com.tom_roush.pdfbox.pdmodel.graphics.image.JPEGFactory
import com.tom_roush.pdfbox.pdmodel.graphics.image.PDImageXObject
import com.tom_roush.pdfbox.rendering.ImageType
import com.tom_roush.pdfbox.rendering.PDFRenderer
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import java.io.File
import java.io.FileOutputStream
import java.io.InputStream

class PdfManagerImpl(private val context: Context) : PdfManager {

    private fun openInput(uri: Uri): InputStream =
        context.contentResolver.openInputStream(uri) ?: error("Unable to open $uri")

    override suspend fun getPageCount(pdfUri: Uri): Int = withContext(Dispatchers.IO) {
        openInput(pdfUri).use { input ->
            PDDocument.load(input).use { document -> document.numberOfPages }
        }
    }

    override suspend fun imagesToPdf(
        imageUris: List<Uri>,
        outputFile: File,
        onProgress: (done: Int, total: Int) -> Unit
    ): File = withContext(Dispatchers.IO) {
        require(imageUris.isNotEmpty()) { "No images selected" }
        val document = PDDocument()
        try {
            imageUris.forEachIndexed { index, uri ->
                val bitmap = BitmapUtils.decodeSampledBitmap(context, uri, MAX_IMAGE_DIMENSION)
                try {
                    addImagePage(document, bitmap)
                } finally {
                    bitmap.recycle()
                }
                onProgress(index + 1, imageUris.size)
            }
            document.save(outputFile)
        } finally {
            document.close()
        }
        outputFile
    }

    private fun addImagePage(document: PDDocument, bitmap: Bitmap) {
        val pageRect = PDRectangle.A4
        val margin = 24f
        val maxWidth = pageRect.width - margin * 2
        val maxHeight = pageRect.height - margin * 2

        val imageAspect = bitmap.width.toFloat() / bitmap.height.toFloat()
        var drawWidth = maxWidth
        var drawHeight = drawWidth / imageAspect
        if (drawHeight > maxHeight) {
            drawHeight = maxHeight
            drawWidth = drawHeight * imageAspect
        }
        val x = (pageRect.width - drawWidth) / 2f
        val y = (pageRect.height - drawHeight) / 2f

        val page = PDPage(pageRect)
        document.addPage(page)
        val image: PDImageXObject = JPEGFactory.createFromImage(document, bitmap, 0.88f)
        val contentStream = PDPageContentStream(document, page)
        try {
            contentStream.drawImage(image, x, y, drawWidth, drawHeight)
        } finally {
            contentStream.close()
        }
    }

    override suspend fun mergePdfs(pdfUris: List<Uri>, outputFile: File): File = withContext(Dispatchers.IO) {
        require(pdfUris.size >= 2) { "Select at least two PDFs to merge" }
        val merger = PDFMergerUtility()
        val inputStreams = pdfUris.map { openInput(it) }
        try {
            inputStreams.forEach { merger.addSource(it) }
            FileOutputStream(outputFile).use { out ->
                merger.destinationStream = out
                merger.mergeDocuments(MemoryUsageSetting.setupMainMemoryOnly())
            }
        } finally {
            inputStreams.forEach { runCatching { it.close() } }
        }
        outputFile
    }

    override suspend fun splitPdfByRange(
        pdfUri: Uri,
        startPage: Int,
        endPage: Int,
        outputFile: File
    ): File = withContext(Dispatchers.IO) {
        openInput(pdfUri).use { input ->
            PDDocument.load(input).use { source ->
                val total = source.numberOfPages
                val from = startPage.coerceIn(1, total)
                val to = endPage.coerceIn(from, total)
                val output = PDDocument()
                try {
                    for (pageIndex in (from - 1) until to) {
                        output.importPage(source.getPage(pageIndex))
                    }
                    output.save(outputFile)
                } finally {
                    output.close()
                }
            }
        }
        outputFile
    }

    override suspend fun splitPdfEveryNPages(
        pdfUri: Uri,
        pagesPerFile: Int,
        outputDir: File,
        baseName: String
    ): List<File> = withContext(Dispatchers.IO) {
        require(pagesPerFile >= 1) { "Pages per file must be at least 1" }
        outputDir.mkdirs()
        val results = mutableListOf<File>()
        openInput(pdfUri).use { input ->
            PDDocument.load(input).use { source ->
                val splitter = Splitter()
                splitter.setSplitAtPage(pagesPerFile)
                val parts = splitter.split(source)
                parts.forEachIndexed { index, part ->
                    try {
                        val file = File(outputDir, "${baseName}_part${index + 1}.pdf")
                        part.save(file)
                        results.add(file)
                    } finally {
                        part.close()
                    }
                }
            }
        }
        results
    }

    override suspend fun compressPdf(
        pdfUri: Uri,
        level: CompressionLevel,
        outputFile: File
    ): File = withContext(Dispatchers.IO) {
        openInput(pdfUri).use { input ->
            PDDocument.load(input).use { source ->
                val renderer = PDFRenderer(source)
                val output = PDDocument()
                try {
                    for (pageIndex in 0 until source.numberOfPages) {
                        val sourceBox = source.getPage(pageIndex).mediaBox
                        val bitmap = renderer.renderImage(pageIndex, level.renderScale, ImageType.RGB)
                        try {
                            val page = PDPage(PDRectangle(sourceBox.width, sourceBox.height))
                            output.addPage(page)
                            val image = JPEGFactory.createFromImage(output, bitmap, level.jpegQuality)
                            val contentStream = PDPageContentStream(output, page)
                            try {
                                contentStream.drawImage(image, 0f, 0f, sourceBox.width, sourceBox.height)
                            } finally {
                                contentStream.close()
                            }
                        } finally {
                            bitmap.recycle()
                        }
                    }
                    output.save(outputFile)
                } finally {
                    output.close()
                }
            }
        }
        outputFile
    }

    override suspend fun renderPdfToImages(
        pdfUri: Uri,
        format: ImageFormat,
        outputDir: File,
        baseName: String,
        onProgress: (done: Int, total: Int) -> Unit
    ): List<File> = withContext(Dispatchers.IO) {
        outputDir.mkdirs()
        val results = mutableListOf<File>()
        openInput(pdfUri).use { input ->
            PDDocument.load(input).use { document ->
                val renderer = PDFRenderer(document)
                val total = document.numberOfPages
                for (pageIndex in 0 until total) {
                    val bitmap = renderer.renderImage(pageIndex, RENDER_TO_IMAGE_SCALE, ImageType.RGB)
                    try {
                        val file = File(outputDir, "${baseName}_page${pageIndex + 1}.${format.extension}")
                        FileOutputStream(file).use { out ->
                            bitmap.compress(format.compressFormat, 92, out)
                        }
                        results.add(file)
                    } finally {
                        bitmap.recycle()
                    }
                    onProgress(pageIndex + 1, total)
                }
            }
        }
        results
    }

    companion object {
        private const val MAX_IMAGE_DIMENSION = 2200
        private const val RENDER_TO_IMAGE_SCALE = 2.1f // ~150dpi
    }
}
