package com.pdfimagetools.app.ui.common

import com.pdfimagetools.app.core.storage.SavedFile
import java.io.File

/** A tool's output sitting in the app's private work cache, before the user taps Save. */
data class ProcessedOutput(
    val file: File,
    val displayName: String,
    val mimeType: String
) {
    val sizeBytes: Long get() = file.length()
}

enum class ToolPhase { PICK, CONFIGURE, PROCESSING, RESULT }

data class ToolResult(
    val outputs: List<ProcessedOutput>,
    val originalTotalSizeBytes: Long? = null,
    val savedFiles: Map<String, SavedFile> = emptyMap(),
    val isSaving: Boolean = false,
    val saveError: String? = null
) {
    val totalOutputSizeBytes: Long get() = outputs.sumOf { it.sizeBytes }
    val allSaved: Boolean get() = outputs.isNotEmpty() && outputs.all { savedFiles.containsKey(it.file.absolutePath) }
}
