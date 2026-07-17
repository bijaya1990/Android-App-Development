package com.pdfimagetools.app.ui.components

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.FileDownload
import androidx.compose.material.icons.filled.OpenInNew
import androidx.compose.material.icons.filled.Share
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.unit.dp
import com.pdfimagetools.app.ui.common.ProcessedOutput
import com.pdfimagetools.app.ui.common.ToolResult
import com.pdfimagetools.app.ui.theme.Accent
import com.pdfimagetools.app.ui.theme.Success
import com.pdfimagetools.app.ui.theme.TextSecondary
import com.pdfimagetools.app.util.FileUtils

@Composable
fun ResultSection(
    result: ToolResult,
    onSaveAll: () -> Unit,
    onShareAll: () -> Unit,
    onOpenOutput: (ProcessedOutput) -> Unit,
    onShareOutput: (ProcessedOutput) -> Unit,
    modifier: Modifier = Modifier
) {
    Column(modifier = modifier.fillMaxWidth()) {
        Box(
            modifier = Modifier
                .size(56.dp)
                .background(Success.copy(alpha = 0.12f), CircleShape),
            contentAlignment = Alignment.Center
        ) {
            Icon(Icons.Filled.CheckCircle, contentDescription = null, tint = Success)
        }

        Text(
            text = if (result.outputs.size == 1) "File ready" else "${result.outputs.size} files ready",
            style = MaterialTheme.typography.titleLarge,
            modifier = Modifier.padding(top = 16.dp)
        )

        val sizeSummary = if (result.originalTotalSizeBytes != null && result.originalTotalSizeBytes > 0) {
            val before = FileUtils.readableSize(result.originalTotalSizeBytes)
            val after = FileUtils.readableSize(result.totalOutputSizeBytes)
            val savedPercent = ((1.0 - result.totalOutputSizeBytes.toDouble() / result.originalTotalSizeBytes) * 100)
                .coerceIn(0.0, 100.0)
            "$before -> $after (%.0f%% smaller)".format(savedPercent)
        } else {
            FileUtils.readableSize(result.totalOutputSizeBytes)
        }
        Text(
            text = sizeSummary,
            style = MaterialTheme.typography.bodyMedium,
            color = TextSecondary,
            modifier = Modifier.padding(top = 4.dp)
        )

        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(top = 20.dp),
            horizontalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Button(
                onClick = onSaveAll,
                enabled = !result.isSaving,
                modifier = Modifier.weight(1f)
            ) {
                if (result.isSaving) {
                    CircularProgressIndicator(modifier = Modifier.size(18.dp), color = Color.White, strokeWidth = 2.dp)
                } else {
                    Icon(
                        imageVector = if (result.allSaved) Icons.Filled.CheckCircle else Icons.Filled.FileDownload,
                        contentDescription = null,
                        modifier = Modifier.size(18.dp)
                    )
                    Text(text = if (result.allSaved) "  Saved" else "  Save", modifier = Modifier.padding(start = 4.dp))
                }
            }
            OutlinedButton(onClick = onShareAll, modifier = Modifier.weight(1f)) {
                Icon(Icons.Filled.Share, contentDescription = null, modifier = Modifier.size(18.dp))
                Text("  Share", modifier = Modifier.padding(start = 4.dp))
            }
            if (result.outputs.size == 1) {
                OutlinedButton(onClick = { onOpenOutput(result.outputs.first()) }, modifier = Modifier.weight(1f)) {
                    Icon(Icons.Filled.OpenInNew, contentDescription = null, modifier = Modifier.size(18.dp))
                    Text("  Open", modifier = Modifier.padding(start = 4.dp))
                }
            }
        }

        result.saveError?.let { error ->
            Text(
                text = error,
                color = MaterialTheme.colorScheme.error,
                style = MaterialTheme.typography.bodySmall,
                modifier = Modifier.padding(top = 8.dp)
            )
        }

        if (result.outputs.size > 1) {
            LazyColumn(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 20.dp),
                verticalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                items(items = result.outputs, key = { it.file.absolutePath }) { output ->
                    OutputRow(
                        output = output,
                        isSaved = result.savedFiles.containsKey(output.file.absolutePath),
                        onOpen = { onOpenOutput(output) },
                        onShare = { onShareOutput(output) }
                    )
                }
            }
        }
    }
}

@Composable
private fun OutputRow(
    output: ProcessedOutput,
    isSaved: Boolean,
    onOpen: () -> Unit,
    onShare: () -> Unit
) {
    Card(
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 12.dp, vertical = 8.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Column(modifier = Modifier.weight(1f)) {
                Text(output.displayName, style = MaterialTheme.typography.bodyMedium, maxLines = 1)
                Text(
                    text = FileUtils.readableSize(output.sizeBytes) + if (isSaved) " · Saved" else "",
                    style = MaterialTheme.typography.bodySmall,
                    color = if (isSaved) Success else TextSecondary
                )
            }
            IconButton(onClick = onOpen) {
                Icon(Icons.Filled.OpenInNew, contentDescription = "Open", tint = Accent)
            }
            IconButton(onClick = onShare) {
                Icon(Icons.Filled.Share, contentDescription = "Share", tint = Accent)
            }
        }
    }
}
