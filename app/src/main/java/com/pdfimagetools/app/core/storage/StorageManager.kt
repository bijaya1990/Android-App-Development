package com.pdfimagetools.app.core.storage

import android.content.Intent
import android.net.Uri
import java.io.File

/**
 * Handles all filesystem / MediaStore interaction so the rest of the app never touches
 * scoped-storage APIs directly. Saves land in the public Downloads or Pictures collection
 * via MediaStore on API 29+, and via direct File IO + media scan on older API levels.
 */
interface StorageManager {

    /** Directory for scratch files created while a tool is processing input. Safe to clear anytime. */
    fun newWorkFile(prefix: String, extension: String): File

    suspend fun saveBytesToPublicStorage(
        bytes: ByteArray,
        displayName: String,
        mimeType: String,
        target: SaveTarget
    ): SavedFile

    suspend fun saveFileToPublicStorage(
        source: File,
        displayName: String,
        mimeType: String,
        target: SaveTarget
    ): SavedFile

    /** Wraps a cache/work file in a shareable FileProvider content Uri (used before a Save). */
    fun uriForWorkFile(file: File): Uri

    fun openFileIntent(uri: Uri, mimeType: String): Intent

    fun shareFilesIntent(uris: List<Uri>, mimeType: String): Intent

    suspend fun deleteSavedFile(uri: Uri): Boolean

    fun clearWorkCache(): Long

    fun cacheSizeBytes(): Long
}
