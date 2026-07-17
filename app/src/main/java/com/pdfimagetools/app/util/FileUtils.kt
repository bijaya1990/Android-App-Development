package com.pdfimagetools.app.util

import android.text.format.DateUtils
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import kotlin.math.log10
import kotlin.math.pow

object FileUtils {

    private val timestampFormat = SimpleDateFormat("yyyyMMdd_HHmmss", Locale.US)

    fun timestamp(): String = timestampFormat.format(Date())

    fun relativeTime(epochMillis: Long): String = DateUtils.getRelativeTimeSpanString(
        epochMillis,
        System.currentTimeMillis(),
        DateUtils.MINUTE_IN_MILLIS
    ).toString()

    fun readableSize(bytes: Long): String {
        if (bytes <= 0) return "0 B"
        val units = arrayOf("B", "KB", "MB", "GB")
        val digitGroups = (log10(bytes.toDouble()) / log10(1024.0)).toInt().coerceIn(0, units.size - 1)
        val value = bytes / 1024.0.pow(digitGroups.toDouble())
        return if (digitGroups == 0) {
            "$bytes B"
        } else {
            String.format(Locale.US, "%.1f %s", value, units[digitGroups])
        }
    }

    fun nameWithoutExtension(displayName: String): String {
        val dot = displayName.lastIndexOf('.')
        return if (dot > 0) displayName.substring(0, dot) else displayName
    }

    fun extensionOf(displayName: String): String {
        val dot = displayName.lastIndexOf('.')
        return if (dot >= 0 && dot < displayName.length - 1) displayName.substring(dot + 1) else ""
    }

    fun sanitizeFileName(name: String): String {
        val cleaned = name.trim().replace(Regex("[\\\\/:*?\"<>|]"), "_")
        return cleaned.ifBlank { "file" }
    }
}
