package com.pdfimagetools.app.ui.recent

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.History
import androidx.compose.material.icons.filled.Image
import androidx.compose.material.icons.filled.OpenInNew
import androidx.compose.material.icons.filled.PictureAsPdf
import androidx.compose.material.icons.filled.Share
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Card
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel
import com.pdfimagetools.app.core.di.ServiceLocator
import com.pdfimagetools.app.core.di.SimpleViewModelFactory
import com.pdfimagetools.app.data.RecentFileEntry
import com.pdfimagetools.app.ui.components.EmptyPickState
import com.pdfimagetools.app.ui.theme.TextSecondary
import com.pdfimagetools.app.util.FileUtils

/** Body for the "Files" tab — no Scaffold/top bar of its own; MainScaffold supplies those. */
@Composable
fun RecentFilesContent(modifier: Modifier = Modifier) {
    val viewModel: RecentFilesViewModel = viewModel(
        factory = SimpleViewModelFactory { RecentFilesViewModel(ServiceLocator.recentFilesStore, ServiceLocator.storageManager) }
    )
    val entries by viewModel.entries.collectAsState()
    val context = LocalContext.current
    var pendingDelete by remember { mutableStateOf<RecentFileEntry?>(null) }

    Box(modifier = modifier.fillMaxSize()) {
        if (entries.isEmpty()) {
            EmptyPickState(
                icon = Icons.Filled.History,
                title = "No files yet",
                description = "Files you create or convert will show up here.",
                buttonText = "Start a scan",
                onClick = {}
            )
        } else {
            LazyColumn(
                contentPadding = PaddingValues(16.dp),
                modifier = Modifier.fillMaxSize()
            ) {
                items(items = entries, key = { it.id }) { entry ->
                    RecentFileRow(
                        entry = entry,
                        onOpen = {
                            val uri = Uri.parse(entry.uriString)
                            val intent = ServiceLocator.storageManager.openFileIntent(uri, entry.mimeType)
                            runCatching { context.startActivity(intent) }
                        },
                        onShare = {
                            val uri = Uri.parse(entry.uriString)
                            val intent = ServiceLocator.storageManager.shareFilesIntent(listOf(uri), entry.mimeType)
                            runCatching { context.startActivity(Intent.createChooser(intent, "Share")) }
                        },
                        onDelete = { pendingDelete = entry },
                        modifier = Modifier.padding(bottom = 8.dp)
                    )
                }
            }
        }
    }

    pendingDelete?.let { entry ->
        AlertDialog(
            onDismissRequest = { pendingDelete = null },
            title = { Text("Delete file?") },
            text = { Text("\"${entry.displayName}\" will be permanently deleted.") },
            confirmButton = {
                TextButton(onClick = {
                    viewModel.delete(entry)
                    pendingDelete = null
                }) { Text("Delete") }
            },
            dismissButton = {
                TextButton(onClick = { pendingDelete = null }) { Text("Cancel") }
            }
        )
    }
}

@Composable
private fun RecentFileRow(
    entry: RecentFileEntry,
    onOpen: () -> Unit,
    onShare: () -> Unit,
    onDelete: () -> Unit,
    modifier: Modifier = Modifier
) {
    Card(shape = RoundedCornerShape(12.dp), modifier = modifier.fillMaxWidth()) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(12.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Icon(
                imageVector = if (entry.mimeType == "application/pdf") Icons.Filled.PictureAsPdf else Icons.Filled.Image,
                contentDescription = null
            )
            Column(modifier = Modifier.weight(1f).padding(start = 12.dp)) {
                Text(entry.displayName, maxLines = 1, overflow = TextOverflow.Ellipsis, style = MaterialTheme.typography.bodyMedium)
                Text(
                    "${entry.toolName} · ${FileUtils.readableSize(entry.sizeBytes)} · ${FileUtils.relativeTime(entry.createdAtMillis)}",
                    style = MaterialTheme.typography.bodySmall,
                    color = TextSecondary,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
            }
            IconButton(onClick = onOpen) { Icon(Icons.Filled.OpenInNew, contentDescription = "Open") }
            IconButton(onClick = onShare) { Icon(Icons.Filled.Share, contentDescription = "Share") }
            IconButton(onClick = onDelete) { Icon(Icons.Filled.Delete, contentDescription = "Delete", tint = MaterialTheme.colorScheme.error) }
        }
    }
}
