package com.questionpapermaker.app.ui.preview

import android.content.Intent
import android.print.PrintAttributes
import android.print.PrintManager
import androidx.compose.foundation.Image
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.MoreVert
import androidx.compose.material.icons.filled.PictureAsPdf
import androidx.compose.material.icons.filled.Print
import androidx.compose.material.icons.filled.Share
import androidx.compose.material.icons.filled.WarningAmber
import androidx.compose.material.icons.filled.ZoomIn
import androidx.compose.material.icons.filled.ZoomOut
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableFloatStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalDensity
import androidx.compose.ui.unit.dp
import androidx.core.content.FileProvider
import com.questionpapermaker.app.engine.ValidationSeverity
import com.questionpapermaker.app.pdf.PdfPrintAdapter
import java.io.File

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PreviewScreen(
    viewModel: PreviewViewModel,
    onBack: () -> Unit,
    onExported: () -> Unit
) {
    val pageBitmaps by viewModel.pageBitmaps.collectAsState()
    val isRendering by viewModel.isRendering.collectAsState()
    val issues by viewModel.validationIssues.collectAsState()
    val context = LocalContext.current
    val density = LocalDensity.current

    var zoomPercent by remember { mutableFloatStateOf(100f) }
    var showBlockingIssuesDialog by remember { mutableStateOf(false) }
    var moreMenuExpanded by remember { mutableStateOf(false) }

    val hasBlockingErrors = issues.any { it.severity == ValidationSeverity.ERROR }

    fun withExportedFile(action: (File) -> Unit) {
        if (hasBlockingErrors) {
            showBlockingIssuesDialog = true
        } else {
            viewModel.exportToFiles(action)
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Question Paper Maker") },
                navigationIcon = {
                    IconButton(onClick = onBack) { Icon(Icons.Default.ArrowBack, contentDescription = "Back") }
                },
                actions = {
                    com.questionpapermaker.app.ui.common.DraftSavedIndicator(modifier = Modifier.padding(end = 12.dp))
                }
            )
        },
        bottomBar = {
            Row(
                modifier = Modifier.fillMaxWidth().padding(16.dp),
                horizontalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                Button(
                    onClick = { withExportedFile { onExported() } },
                    shape = MaterialTheme.shapes.small,
                    modifier = Modifier.weight(1f)
                ) {
                    Icon(Icons.Default.PictureAsPdf, contentDescription = null, modifier = Modifier.height(18.dp))
                    Text("  PDF")
                }
                OutlinedButton(
                    onClick = {
                        withExportedFile { file ->
                            val printManager = context.getSystemService(android.content.Context.PRINT_SERVICE) as PrintManager
                            printManager.print(
                                file.nameWithoutExtension,
                                PdfPrintAdapter(file, file.nameWithoutExtension),
                                PrintAttributes.Builder().build()
                            )
                        }
                    },
                    shape = MaterialTheme.shapes.small,
                    modifier = Modifier.weight(1f)
                ) {
                    Icon(Icons.Default.Print, contentDescription = null, modifier = Modifier.height(18.dp))
                    Text("  Print")
                }
                Box {
                    OutlinedButton(onClick = { moreMenuExpanded = true }, shape = MaterialTheme.shapes.small) {
                        Icon(Icons.Default.MoreVert, contentDescription = "More options")
                    }
                    DropdownMenu(expanded = moreMenuExpanded, onDismissRequest = { moreMenuExpanded = false }) {
                        DropdownMenuItem(
                            text = { Text("Share PDF") },
                            leadingIcon = { Icon(Icons.Default.Share, contentDescription = null) },
                            onClick = {
                                moreMenuExpanded = false
                                withExportedFile { file ->
                                    val uri = FileProvider.getUriForFile(context, "${context.packageName}.fileprovider", file)
                                    val intent = Intent(Intent.ACTION_SEND).apply {
                                        type = "application/pdf"
                                        putExtra(Intent.EXTRA_STREAM, uri)
                                        addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
                                    }
                                    context.startActivity(Intent.createChooser(intent, "Share Question Paper"))
                                }
                            }
                        )
                        DropdownMenuItem(
                            text = { Text("Zoom In") },
                            leadingIcon = { Icon(Icons.Default.ZoomIn, contentDescription = null) },
                            onClick = { moreMenuExpanded = false; zoomPercent = (zoomPercent + 25f).coerceAtMost(200f) }
                        )
                        DropdownMenuItem(
                            text = { Text("Zoom Out") },
                            leadingIcon = { Icon(Icons.Default.ZoomOut, contentDescription = null) },
                            onClick = { moreMenuExpanded = false; zoomPercent = (zoomPercent - 25f).coerceAtLeast(25f) }
                        )
                    }
                }
            }
        }
    ) { padding ->
        Column(modifier = Modifier.fillMaxSize().padding(padding)) {
            if (issues.isNotEmpty()) {
                IssuesBar(issueCount = issues.size, hasErrors = hasBlockingErrors)
            }

            if (isRendering && pageBitmaps.isEmpty()) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
            } else {
                LazyColumn(
                    modifier = Modifier.fillMaxSize(),
                    contentPadding = PaddingValues(16.dp),
                    verticalArrangement = Arrangement.spacedBy(20.dp)
                ) {
                    items(pageBitmaps.size) { index ->
                        val bitmap = pageBitmaps[index]
                        val widthDp = with(density) { (bitmap.width * (zoomPercent / 100f)).toDp() }
                        Box(
                            modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                            contentAlignment = Alignment.Center
                        ) {
                            Image(
                                bitmap = bitmap.asImageBitmap(),
                                contentDescription = "Page ${index + 1}",
                                modifier = Modifier.width(widthDp)
                            )
                        }
                    }
                }
            }
        }
    }

    if (showBlockingIssuesDialog) {
        AlertDialog(
            onDismissRequest = { showBlockingIssuesDialog = false },
            title = { Text("Fix these before exporting") },
            text = {
                Column {
                    issues.filter { it.severity == ValidationSeverity.ERROR }.forEach { issue ->
                        Text("• ${issue.message}")
                    }
                }
            },
            confirmButton = {
                TextButton(onClick = { showBlockingIssuesDialog = false }) { Text("OK") }
            }
        )
    }
}

@Composable
private fun IssuesBar(issueCount: Int, hasErrors: Boolean) {
    val color = if (hasErrors) MaterialTheme.colorScheme.error else MaterialTheme.colorScheme.tertiary
    Row(
        modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp, vertical = 8.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Icon(Icons.Default.WarningAmber, contentDescription = null, tint = color)
        Text(
            "  $issueCount item${if (issueCount == 1) "" else "s"} to review",
            style = MaterialTheme.typography.bodyMedium,
            color = color
        )
    }
}
