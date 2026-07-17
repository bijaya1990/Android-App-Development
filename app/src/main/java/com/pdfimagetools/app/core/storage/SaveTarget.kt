package com.pdfimagetools.app.core.storage

/** Public folder a processed file should be exported into. */
enum class SaveTarget {
    DOWNLOADS,
    PICTURES
}

/** Result of a completed save operation. */
data class SavedFile(
    val uri: android.net.Uri,
    val displayName: String,
    val mimeType: String,
    val sizeBytes: Long,
    val target: SaveTarget
)
