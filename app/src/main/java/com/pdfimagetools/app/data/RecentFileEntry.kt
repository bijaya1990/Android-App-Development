package com.pdfimagetools.app.data

data class RecentFileEntry(
    val id: String,
    val displayName: String,
    val uriString: String,
    val mimeType: String,
    val sizeBytes: Long,
    val createdAtMillis: Long,
    val toolName: String
)
