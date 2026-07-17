package com.pdfimagetools.app.core.storage

import android.content.ContentValues
import android.content.Context
import android.content.Intent
import android.media.MediaScannerConnection
import android.net.Uri
import android.os.Build
import android.os.Environment
import android.provider.MediaStore
import androidx.core.content.FileProvider
import com.pdfimagetools.app.util.FileUtils
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import java.io.File
import java.io.FileOutputStream

class StorageManagerImpl(private val context: Context) : StorageManager {

    private val authority = "${context.packageName}.fileprovider"
    private val workDir: File by lazy {
        File(context.cacheDir, "work").apply { mkdirs() }
    }

    override fun newWorkFile(prefix: String, extension: String): File {
        val ext = extension.trimStart('.')
        return File(workDir, "${prefix}_${FileUtils.timestamp()}_${System.nanoTime()}.$ext")
    }

    override suspend fun saveBytesToPublicStorage(
        bytes: ByteArray,
        displayName: String,
        mimeType: String,
        target: SaveTarget
    ): SavedFile = withContext(Dispatchers.IO) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            saveViaMediaStore(bytes.size.toLong(), displayName, mimeType, target) { out ->
                out.write(bytes)
            }
        } else {
            saveViaLegacyFile(bytes.size.toLong(), displayName, mimeType, target) { file ->
                FileOutputStream(file).use { it.write(bytes) }
            }
        }
    }

    override suspend fun saveFileToPublicStorage(
        source: File,
        displayName: String,
        mimeType: String,
        target: SaveTarget
    ): SavedFile = withContext(Dispatchers.IO) {
        val size = source.length()
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            saveViaMediaStore(size, displayName, mimeType, target) { out ->
                source.inputStream().use { it.copyTo(out) }
            }
        } else {
            saveViaLegacyFile(size, displayName, mimeType, target) { file ->
                source.inputStream().use { input -> FileOutputStream(file).use { input.copyTo(it) } }
            }
        }
    }

    private fun saveViaMediaStore(
        sizeBytes: Long,
        displayName: String,
        mimeType: String,
        target: SaveTarget,
        writer: (java.io.OutputStream) -> Unit
    ): SavedFile {
        val collection = when (target) {
            SaveTarget.DOWNLOADS -> MediaStore.Downloads.EXTERNAL_CONTENT_URI
            SaveTarget.PICTURES -> MediaStore.Images.Media.EXTERNAL_CONTENT_URI
        }
        val relativePath = when (target) {
            SaveTarget.DOWNLOADS -> Environment.DIRECTORY_DOWNLOADS
            SaveTarget.PICTURES -> Environment.DIRECTORY_PICTURES + "/PDF Image Tools"
        }
        val values = ContentValues().apply {
            put(MediaStore.MediaColumns.DISPLAY_NAME, displayName)
            put(MediaStore.MediaColumns.MIME_TYPE, mimeType)
            put(MediaStore.MediaColumns.RELATIVE_PATH, relativePath)
            put(MediaStore.MediaColumns.IS_PENDING, 1)
        }
        val uri = context.contentResolver.insert(collection, values)
            ?: error("Unable to create entry in $target")
        context.contentResolver.openOutputStream(uri)?.use { out -> writer(out) }
            ?: error("Unable to open output stream for $uri")
        values.clear()
        values.put(MediaStore.MediaColumns.IS_PENDING, 0)
        context.contentResolver.update(uri, values, null, null)
        return SavedFile(uri, displayName, mimeType, sizeBytes, target)
    }

    private fun saveViaLegacyFile(
        sizeBytes: Long,
        displayName: String,
        mimeType: String,
        target: SaveTarget,
        writer: (File) -> Unit
    ): SavedFile {
        val publicDir = when (target) {
            SaveTarget.DOWNLOADS -> Environment.getExternalStoragePublicDirectory(Environment.DIRECTORY_DOWNLOADS)
            SaveTarget.PICTURES -> File(
                Environment.getExternalStoragePublicDirectory(Environment.DIRECTORY_PICTURES),
                "PDF Image Tools"
            )
        }
        publicDir.mkdirs()
        val uniqueFile = uniqueFileFor(publicDir, displayName)
        writer(uniqueFile)
        MediaScannerConnection.scanFile(context, arrayOf(uniqueFile.absolutePath), arrayOf(mimeType), null)
        val uri = FileProvider.getUriForFile(context, authority, uniqueFile)
        return SavedFile(uri, uniqueFile.name, mimeType, uniqueFile.length().takeIf { it > 0 } ?: sizeBytes, target)
    }

    private fun uniqueFileFor(dir: File, displayName: String): File {
        var candidate = File(dir, displayName)
        if (!candidate.exists()) return candidate
        val base = FileUtils.nameWithoutExtension(displayName)
        val ext = FileUtils.extensionOf(displayName)
        var index = 1
        while (candidate.exists()) {
            val name = if (ext.isNotEmpty()) "$base ($index).$ext" else "$base ($index)"
            candidate = File(dir, name)
            index++
        }
        return candidate
    }

    override fun uriForWorkFile(file: File): Uri {
        return FileProvider.getUriForFile(context, authority, file)
    }

    override fun openFileIntent(uri: Uri, mimeType: String): Intent {
        return Intent(Intent.ACTION_VIEW).apply {
            setDataAndType(uri, mimeType)
            addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
            addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
        }
    }

    override fun shareFilesIntent(uris: List<Uri>, mimeType: String): Intent {
        val intent = if (uris.size == 1) {
            Intent(Intent.ACTION_SEND).apply {
                putExtra(Intent.EXTRA_STREAM, uris.first())
            }
        } else {
            Intent(Intent.ACTION_SEND_MULTIPLE).apply {
                putParcelableArrayListExtra(Intent.EXTRA_STREAM, ArrayList(uris))
            }
        }
        return intent.apply {
            type = mimeType
            addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
            addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
        }
    }

    override suspend fun deleteSavedFile(uri: Uri): Boolean = withContext(Dispatchers.IO) {
        runCatching { context.contentResolver.delete(uri, null, null) > 0 }.getOrDefault(false)
    }

    override fun clearWorkCache(): Long {
        var freed = 0L
        workDir.listFiles()?.forEach { file ->
            freed += file.length()
            file.delete()
        }
        return freed
    }

    override fun cacheSizeBytes(): Long {
        return workDir.listFiles()?.sumOf { it.length() } ?: 0L
    }
}
