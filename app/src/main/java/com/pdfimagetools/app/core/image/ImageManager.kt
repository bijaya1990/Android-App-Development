package com.pdfimagetools.app.core.image

import android.net.Uri
import java.io.File

interface ImageManager {

    suspend fun originalSizeBytes(uri: Uri): Long

    /** Re-encodes the image as JPEG at [qualityPercent] (1-100), always shrinking file size. */
    suspend fun compressImage(uri: Uri, qualityPercent: Int, outputFile: File): File

    /** Converts an image to [targetFormat], flattening transparency onto white when needed. */
    suspend fun convertFormat(uri: Uri, targetFormat: ImageFormat, outputFile: File): File
}
