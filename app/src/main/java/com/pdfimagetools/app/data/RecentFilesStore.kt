package com.pdfimagetools.app.data

import android.content.Context
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import org.json.JSONArray
import org.json.JSONObject
import java.util.UUID

/** Lightweight JSON-backed history of files produced by the tools, newest first. */
class RecentFilesStore(context: Context) {

    private val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
    private val _entries = MutableStateFlow(loadAll())
    val entries: StateFlow<List<RecentFileEntry>> = _entries.asStateFlow()

    fun add(
        displayName: String,
        uriString: String,
        mimeType: String,
        sizeBytes: Long,
        toolName: String
    ) {
        val entry = RecentFileEntry(
            id = UUID.randomUUID().toString(),
            displayName = displayName,
            uriString = uriString,
            mimeType = mimeType,
            sizeBytes = sizeBytes,
            createdAtMillis = System.currentTimeMillis(),
            toolName = toolName
        )
        val updated = (listOf(entry) + _entries.value).take(MAX_ENTRIES)
        _entries.value = updated
        persist(updated)
    }

    fun remove(id: String) {
        val updated = _entries.value.filterNot { it.id == id }
        _entries.value = updated
        persist(updated)
    }

    private fun loadAll(): List<RecentFileEntry> {
        val raw = prefs.getString(KEY_ENTRIES, null) ?: return emptyList()
        return runCatching {
            val array = JSONArray(raw)
            (0 until array.length()).map { index ->
                val obj = array.getJSONObject(index)
                RecentFileEntry(
                    id = obj.getString("id"),
                    displayName = obj.getString("displayName"),
                    uriString = obj.getString("uriString"),
                    mimeType = obj.getString("mimeType"),
                    sizeBytes = obj.getLong("sizeBytes"),
                    createdAtMillis = obj.getLong("createdAtMillis"),
                    toolName = obj.getString("toolName")
                )
            }
        }.getOrDefault(emptyList())
    }

    private fun persist(entries: List<RecentFileEntry>) {
        val array = JSONArray()
        entries.forEach { entry ->
            array.put(
                JSONObject().apply {
                    put("id", entry.id)
                    put("displayName", entry.displayName)
                    put("uriString", entry.uriString)
                    put("mimeType", entry.mimeType)
                    put("sizeBytes", entry.sizeBytes)
                    put("createdAtMillis", entry.createdAtMillis)
                    put("toolName", entry.toolName)
                }
            )
        }
        prefs.edit().putString(KEY_ENTRIES, array.toString()).apply()
    }

    companion object {
        private const val PREFS_NAME = "recent_files"
        private const val KEY_ENTRIES = "entries"
        private const val MAX_ENTRIES = 200
    }
}
