package com.pdfimagetools.app.ui.recent

import android.net.Uri
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.pdfimagetools.app.core.storage.StorageManager
import com.pdfimagetools.app.data.RecentFileEntry
import com.pdfimagetools.app.data.RecentFilesStore
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch

class RecentFilesViewModel(
    private val recentFilesStore: RecentFilesStore,
    private val storageManager: StorageManager
) : ViewModel() {

    val entries: StateFlow<List<RecentFileEntry>> = recentFilesStore.entries

    fun delete(entry: RecentFileEntry) {
        viewModelScope.launch {
            runCatching { storageManager.deleteSavedFile(Uri.parse(entry.uriString)) }
            recentFilesStore.remove(entry.id)
        }
    }
}
