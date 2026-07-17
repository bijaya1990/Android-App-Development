package com.pdfimagetools.app.core.pdf

import android.net.Uri
import com.pdfimagetools.app.core.image.ImageFormat
import java.io.File

interface PdfManager {

    suspend fun getPageCount(pdfUri: Uri): Int

    /** Combines one or more images (in the given order) into a single PDF, one image per page. */
    suspend fun imagesToPdf(
        imageUris: List<Uri>,
        outputFile: File,
        onProgress: (done: Int, total: Int) -> Unit = { _, _ -> }
    ): File

    /** Concatenates PDFs, in the given order, into a single output PDF. */
    suspend fun mergePdfs(
        pdfUris: List<Uri>,
        outputFile: File
    ): File

    /** Extracts a contiguous, 1-indexed inclusive page range into a new PDF. */
    suspend fun splitPdfByRange(
        pdfUri: Uri,
        startPage: Int,
        endPage: Int,
        outputFile: File
    ): File

    /** Splits a PDF into consecutive chunks of [pagesPerFile] pages each. */
    suspend fun splitPdfEveryNPages(
        pdfUri: Uri,
        pagesPerFile: Int,
        outputDir: File,
        baseName: String
    ): List<File>

    /** Re-encodes every page as a compressed raster image to shrink overall file size. */
    suspend fun compressPdf(
        pdfUri: Uri,
        level: CompressionLevel,
        outputFile: File
    ): File

    /** Renders every page of a PDF to a standalone raster image file. */
    suspend fun renderPdfToImages(
        pdfUri: Uri,
        format: ImageFormat,
        outputDir: File,
        baseName: String,
        onProgress: (done: Int, total: Int) -> Unit = { _, _ -> }
    ): List<File>
}
