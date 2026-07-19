package com.pdfimagetools.app.ui.mergepdf

import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowDownward
import androidx.compose.material.icons.filled.ArrowUpward
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.MergeType
import androidx.compose.material.icons.filled.NoteAdd
import androidx.compose.material.icons.filled.PictureAsPdf
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel
import com.pdfimagetools.app.core.di.ServiceLocator
import com.pdfimagetools.app.core.di.SimpleViewModelFactory
import com.pdfimagetools.app.ui.common.ToolPhase
import com.pdfimagetools.app.ui.components.AppTopBar
import com.pdfimagetools.app.ui.components.BannerAdView
import com.pdfimagetools.app.ui.components.EmptyPickState
import com.pdfimagetools.app.ui.components.LoadingOverlay
import com.pdfimagetools.app.ui.components.ResultSection
import com.pdfimagetools.app.ui.components.rememberToolResultActions
import com.pdfimagetools.app.ui.theme.TextSecondary
import com.pdfimagetools.app.util.FileUtils
import com.pdfimagetools.app.util.findActivity

@Composable
fun MergePdfScreen(onBack: () -> Unit) {
    val viewModel: MergePdfViewModel = viewModel(
        factory = SimpleViewModelFactory {
            MergePdfViewModel(ServiceLocator.pdfManager, ServiceLocator.storageManager, ServiceLocator.saveOrchestrator)
        }
    )
    val state by viewModel.uiState.collectAsState()
    val context = LocalContext.current
    val activity = context.findActivity()
    val actions = rememberToolResultActions()

    val pickPdfsLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.OpenMultipleDocuments()
    ) { uris -> viewModel.onPdfsPicked(context, uris) }

    Scaffold(
        topBar = { AppTopBar(title = "Merge PDF", onBack = onBack) },
        bottomBar = { BannerAdView(modifier = Modifier.navigationBarsPadding()) }
    ) { padding ->
        Box(modifier = Modifier.fillMaxSize().padding(padding)) {
            when (state.phase) {
                ToolPhase.PICK -> EmptyPickState(
                    icon = Icons.Filled.MergeType,
                    title = "Select PDFs",
                    description = "Choose two or more PDFs to combine into one document.",
                    buttonText = "Select PDFs",
                    onClick = { pickPdfsLauncher.launch(arrayOf("application/pdf")) }
                )

                ToolPhase.CONFIGURE -> Column(modifier = Modifier.fillMaxSize()) {
                    state.errorMessage?.let {
                        Text(it, color = MaterialTheme.colorScheme.error, modifier = Modifier.padding(16.dp))
                    }
                    LazyColumn(
                        modifier = Modifier.weight(1f).fillMaxWidth(),
                        contentPadding = PaddingValues(16.dp),
                        verticalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        items(items = state.files.withIndex().toList(), key = { it.index }) { (index, file) ->
                            PdfFileRow(
                                index = index,
                                name = file.displayName,
                                sizeText = FileUtils.readableSize(file.sizeBytes),
                                canMoveUp = index > 0,
                                canMoveDown = index < state.files.size - 1,
                                onMoveUp = { viewModel.moveFile(index, index - 1) },
                                onMoveDown = { viewModel.moveFile(index, index + 1) },
                                onRemove = { viewModel.removeFile(index) }
                            )
                        }
                    }
                    Row(
                        modifier = Modifier.fillMaxWidth().padding(16.dp),
                        horizontalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        OutlinedButton(
                            onClick = { pickPdfsLauncher.launch(arrayOf("application/pdf")) },
                            modifier = Modifier.weight(1f)
                        ) {
                            Icon(Icons.Filled.NoteAdd, contentDescription = null, modifier = Modifier.size(18.dp))
                            Text("  Add more", modifier = Modifier.padding(start = 4.dp))
                        }
                        Button(onClick = viewModel::process, modifier = Modifier.weight(1f)) {
                            Text("Merge")
                        }
                    }
                }

                ToolPhase.PROCESSING -> LoadingOverlay(message = "Merging PDFs…")

                ToolPhase.RESULT -> state.result?.let { result ->
                    Box(modifier = Modifier.fillMaxSize().padding(16.dp)) {
                        ResultSection(
                            result = result,
                            onSaveAll = { activity?.let(viewModel::save) },
                            onShareAll = { actions.share(result.outputs, result.savedFiles, "application/pdf") },
                            onOpenOutput = { output -> actions.open(output, result.savedFiles[output.file.absolutePath]) },
                            onShareOutput = { output -> actions.share(listOf(output), result.savedFiles, "application/pdf") }
                        )
                    }
                }
            }
        }
    }
}

@Composable
private fun PdfFileRow(
    index: Int,
    name: String,
    sizeText: String,
    canMoveUp: Boolean,
    canMoveDown: Boolean,
    onMoveUp: () -> Unit,
    onMoveDown: () -> Unit,
    onRemove: () -> Unit
) {
    Card(shape = RoundedCornerShape(12.dp)) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(12.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Icon(Icons.Filled.PictureAsPdf, contentDescription = null)
            Column(modifier = Modifier.weight(1f).padding(start = 12.dp)) {
                Text("${index + 1}. $name", maxLines = 1, overflow = TextOverflow.Ellipsis)
                Text(sizeText, style = MaterialTheme.typography.bodySmall, color = TextSecondary)
            }
            IconButton(onClick = onMoveUp, enabled = canMoveUp) {
                Icon(Icons.Filled.ArrowUpward, contentDescription = "Move up")
            }
            IconButton(onClick = onMoveDown, enabled = canMoveDown) {
                Icon(Icons.Filled.ArrowDownward, contentDescription = "Move down")
            }
            IconButton(onClick = onRemove) {
                Icon(Icons.Filled.Close, contentDescription = "Remove")
            }
        }
    }
}
