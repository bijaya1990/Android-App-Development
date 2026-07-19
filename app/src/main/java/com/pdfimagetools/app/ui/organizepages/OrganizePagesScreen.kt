package com.pdfimagetools.app.ui.organizepages

import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.aspectRatio
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.ContentCut
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
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
import com.pdfimagetools.app.ui.theme.Accent
import com.pdfimagetools.app.ui.theme.TextSecondary
import com.pdfimagetools.app.util.findActivity

@Composable
fun OrganizePagesScreen(onBack: () -> Unit) {
    val viewModel: OrganizePagesViewModel = viewModel(
        factory = SimpleViewModelFactory {
            OrganizePagesViewModel(ServiceLocator.pdfManager, ServiceLocator.storageManager, ServiceLocator.saveOrchestrator)
        }
    )
    val state by viewModel.uiState.collectAsState()
    val context = LocalContext.current
    val activity = context.findActivity()
    val actions = rememberToolResultActions()

    val pickPdfLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.OpenDocument()
    ) { uri -> uri?.let { viewModel.onPdfPicked(context, it) } }

    Scaffold(
        topBar = { AppTopBar(title = "Organize pages", onBack = onBack) },
        bottomBar = { BannerAdView(modifier = Modifier.navigationBarsPadding()) }
    ) { padding ->
        Box(modifier = Modifier.fillMaxSize().padding(padding)) {
            when (state.phase) {
                ToolPhase.PICK -> EmptyPickState(
                    icon = Icons.Filled.ContentCut,
                    title = "Select a PDF",
                    description = "Choose a PDF to delete or extract specific pages.",
                    buttonText = "Select PDF",
                    onClick = { pickPdfLauncher.launch(arrayOf("application/pdf")) }
                )

                ToolPhase.CONFIGURE -> Column(modifier = Modifier.fillMaxSize()) {
                    Text(
                        text = "${state.fileName} · ${state.selectedIndices.size} selected",
                        style = MaterialTheme.typography.titleSmall,
                        maxLines = 1,
                        modifier = Modifier.padding(16.dp)
                    )
                    state.errorMessage?.let {
                        Text(it, color = MaterialTheme.colorScheme.error, modifier = Modifier.padding(horizontal = 16.dp))
                    }

                    if (state.isLoadingThumbnails) {
                        Box(modifier = Modifier.weight(1f).fillMaxWidth(), contentAlignment = Alignment.Center) {
                            CircularProgressIndicator()
                        }
                    } else {
                        LazyVerticalGrid(
                            columns = GridCells.Fixed(3),
                            modifier = Modifier.weight(1f).fillMaxWidth(),
                            contentPadding = PaddingValues(16.dp),
                            horizontalArrangement = Arrangement.spacedBy(8.dp),
                            verticalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            items(items = state.thumbnails, key = { it.index }) { thumbnail ->
                                val isSelected = thumbnail.index in state.selectedIndices
                                Box(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .aspectRatio(0.72f)
                                        .clip(RoundedCornerShape(8.dp))
                                        .border(
                                            width = if (isSelected) 3.dp else 1.dp,
                                            color = if (isSelected) Accent else MaterialTheme.colorScheme.outline,
                                            shape = RoundedCornerShape(8.dp)
                                        )
                                        .clickable { viewModel.togglePage(thumbnail.index) }
                                ) {
                                    Image(
                                        bitmap = thumbnail.bitmap.asImageBitmap(),
                                        contentDescription = "Page ${thumbnail.index + 1}",
                                        contentScale = ContentScale.Crop,
                                        modifier = Modifier.fillMaxSize()
                                    )
                                    Text(
                                        text = "${thumbnail.index + 1}",
                                        color = Color.White,
                                        style = MaterialTheme.typography.labelLarge,
                                        modifier = Modifier
                                            .padding(4.dp)
                                            .background(Color.Black.copy(alpha = 0.55f), RoundedCornerShape(4.dp))
                                            .padding(horizontal = 4.dp)
                                    )
                                    if (isSelected) {
                                        Icon(
                                            Icons.Filled.CheckCircle,
                                            contentDescription = null,
                                            tint = Accent,
                                            modifier = Modifier.align(Alignment.TopEnd).padding(4.dp)
                                        )
                                    }
                                }
                            }
                        }
                    }

                    Row(
                        modifier = Modifier.fillMaxWidth().padding(16.dp),
                        horizontalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        OutlinedButton(onClick = viewModel::extractSelected, modifier = Modifier.weight(1f)) {
                            Text("Extract selected")
                        }
                        Button(onClick = viewModel::deleteSelected, modifier = Modifier.weight(1f)) {
                            Icon(Icons.Filled.Delete, contentDescription = null, modifier = Modifier.padding(end = 4.dp))
                            Text("Delete selected")
                        }
                    }
                    Text(
                        text = "Tip: tap pages to select them, then choose an action above.",
                        style = MaterialTheme.typography.bodySmall,
                        color = TextSecondary,
                        modifier = Modifier.padding(start = 16.dp, end = 16.dp, bottom = 12.dp)
                    )
                }

                ToolPhase.PROCESSING -> LoadingOverlay(message = "Saving pages…")

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
