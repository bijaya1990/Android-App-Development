package com.pdfimagetools.app.util

object MimeUtils {
    const val PDF = "application/pdf"
    const val JPEG = "image/jpeg"
    const val PNG = "image/png"

    fun forExtension(extension: String): String = when (extension.lowercase()) {
        "pdf" -> PDF
        "png" -> PNG
        "jpg", "jpeg" -> JPEG
        else -> "application/octet-stream"
    }
}
