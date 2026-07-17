package com.pdfimagetools.app.core.pdf

/** Coarse compression presets exposed to the user for the Compress PDF tool. */
enum class CompressionLevel(val renderScale: Float, val jpegQuality: Float) {
    LOW(2.0f, 0.82f),
    MEDIUM(1.4f, 0.6f),
    HIGH(0.9f, 0.35f)
}

sealed class SplitMode {
    data class PageRange(val startPage: Int, val endPage: Int) : SplitMode()
    data class EveryNPages(val pagesPerFile: Int) : SplitMode()
}
