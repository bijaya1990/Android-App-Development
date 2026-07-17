package com.pdfimagetools.app.core.image

import android.content.Context
import android.graphics.Bitmap
import android.graphics.Canvas
import android.graphics.Color
import android.net.Uri
import android.provider.OpenableColumns
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import java.io.File
import java.io.FileOutputStream

class ImageManagerImpl(private val context: Context) : ImageManager {

    override suspend fun originalSizeBytes(uri: Uri): Long = withContext(Dispatchers.IO) {
        var size = -1L
        context.contentResolver.query(uri, arrayOf(OpenableColumns.SIZE), null, null, null)?.use { cursor ->
            val sizeIndex = cursor.getColumnIndex(OpenableColumns.SIZE)
            if (sizeIndex >= 0 && cursor.moveToFirst() && !cursor.isNull(sizeIndex)) {
                size = cursor.getLong(sizeIndex)
            }
        }
        if (size < 0) {
            size = runCatching {
                context.contentResolver.openAssetFileDescriptor(uri, "r")?.use { it.length }
            }.getOrNull() ?: 0L
        }
        size
    }

    override suspend fun compressImage(uri: Uri, qualityPercent: Int, outputFile: File): File =
        withContext(Dispatchers.IO) {
            val bitmap = BitmapUtils.decodeSampledBitmap(context, uri, MAX_DIMENSION)
            try {
                val flattened = flattenOntoWhite(bitmap)
                try {
                    FileOutputStream(outputFile).use { out ->
                        flattened.compress(Bitmap.CompressFormat.JPEG, qualityPercent.coerceIn(1, 100), out)
                    }
                } finally {
                    if (flattened !== bitmap) flattened.recycle()
                }
            } finally {
                bitmap.recycle()
            }
            outputFile
        }

    override suspend fun convertFormat(uri: Uri, targetFormat: ImageFormat, outputFile: File): File =
        withContext(Dispatchers.IO) {
            val bitmap = BitmapUtils.decodeSampledBitmap(context, uri, MAX_DIMENSION)
            try {
                val toEncode = if (targetFormat == ImageFormat.JPEG) flattenOntoWhite(bitmap) else bitmap
                try {
                    FileOutputStream(outputFile).use { out ->
                        toEncode.compress(targetFormat.compressFormat, 95, out)
                    }
                } finally {
                    if (toEncode !== bitmap) toEncode.recycle()
                }
            } finally {
                bitmap.recycle()
            }
            outputFile
        }

    /** JPEG has no alpha channel; compositing onto white avoids transparent areas turning black. */
    private fun flattenOntoWhite(bitmap: Bitmap): Bitmap {
        if (!bitmap.hasAlpha()) return bitmap
        val flattened = Bitmap.createBitmap(bitmap.width, bitmap.height, Bitmap.Config.ARGB_8888)
        val canvas = Canvas(flattened)
        canvas.drawColor(Color.WHITE)
        canvas.drawBitmap(bitmap, 0f, 0f, null)
        return flattened
    }

    companion object {
        private const val MAX_DIMENSION = 4096
    }
}
