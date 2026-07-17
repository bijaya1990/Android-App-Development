package com.pdfimagetools.app.ui.components

import android.content.Context
import android.content.Intent
import android.widget.Toast
import androidx.compose.runtime.Composable
import androidx.compose.runtime.remember
import androidx.compose.ui.platform.LocalContext
import com.pdfimagetools.app.core.di.ServiceLocator
import com.pdfimagetools.app.core.storage.SavedFile
import com.pdfimagetools.app.ui.common.ProcessedOutput

/** Share/open actions shared by every tool's result screen; resolves the right Uri whether or
 * not the output has been saved to public storage yet. */
class ToolResultActions(private val context: Context) {

    private val storageManager get() = ServiceLocator.storageManager

    fun share(outputs: List<ProcessedOutput>, savedFiles: Map<String, SavedFile>, mimeType: String) {
        if (outputs.isEmpty()) return
        val uris = outputs.map { output ->
            savedFiles[output.file.absolutePath]?.uri ?: storageManager.uriForWorkFile(output.file)
        }
        val intent = storageManager.shareFilesIntent(uris, mimeType)
        runCatching { context.startActivity(Intent.createChooser(intent, "Share")) }
    }

    fun open(output: ProcessedOutput, savedFile: SavedFile?) {
        val uri = savedFile?.uri ?: storageManager.uriForWorkFile(output.file)
        val intent = storageManager.openFileIntent(uri, output.mimeType)
        runCatching { context.startActivity(intent) }
            .onFailure { Toast.makeText(context, "No app found to open this file", Toast.LENGTH_SHORT).show() }
    }
}

@Composable
fun rememberToolResultActions(): ToolResultActions {
    val context = LocalContext.current
    return remember(context) { ToolResultActions(context) }
}
