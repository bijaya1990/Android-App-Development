package com.pdfimagetools.app.ui.pdftoimage

import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Image
import androidx.compose.material3.Button
import androidx.compose.material3.FilterChip
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel
import com.pdfimagetools.app.core.di.ServiceLocator
import com.pdfimagetools.app.core.di.SimpleViewModelFactory
import com.pdfimagetools.app.core.image.ImageFormat
import com.pdfimagetools.app.ui.common.ToolPhase
import com.pdfimagetools.app.ui.components.AppTopBar
import com.pdfimagetools.app.ui.components.BannerAdView
import com.pdfimagetools.app.ui.components.EmptyPickState
import com.pdfimagetools.app.ui.components.LoadingOverlay
import com.pdfimagetools.app.ui.components.ResultSection
import com.pdfimagetools.app.ui.components.rememberToolResultActions
import com.pdfimagetools.app.util.findActivity

@Composable
fun PdfToImageScreen(onBack: () -> Unit) {
    val viewModel: PdfToImageViewModel = viewModel(
        factory = SimpleViewModelFactory {
            PdfToImageViewModel(ServiceLocator.pdfManager, ServiceLocator.storageManager, ServiceLocator.saveOrchestrator)
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
        topBar = { AppTopBar(title = "PDF to image", onBack = onBack) },
        bottomBar = { BannerAdView(modifier = Modifier.navigationBarsPadding()) }
    ) { padding ->
        Box(modifier = Modifier.fillMaxSize().padding(padding)) {
            when (state.phase) {
                ToolPhase.PICK -> EmptyPickState(
                    icon = Icons.Filled.Image,
                    title = "Select a PDF",
                    description = "Choose a PDF to save each page as a picture.",
                    buttonText = "Select PDF",
                    onClick = { pickPdfLauncher.launch(arrayOf("application/pdf")) }
                )

                ToolPhase.CONFIGURE -> Column(modifier = Modifier.fillMaxSize().padding(16.dp)) {
                    Text(state.fileName, style = MaterialTheme.typography.titleMedium, maxLines = 1)
                    state.errorMessage?.let {
                        Text(it, color = MaterialTheme.colorScheme.error, modifier = Modifier.padding(top = 12.dp))
                    }
                    Text("Output format", style = MaterialTheme.typography.titleSmall, modifier = Modifier.padding(top = 20.dp))
                    Row(
                        modifier = Modifier.fillMaxWidth().padding(top = 8.dp),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        FilterChip(
                            selected = state.format == ImageFormat.JPEG,
                            onClick = { viewModel.setFormat(ImageFormat.JPEG) },
                            label = { Text("JPG") }
                        )
                        FilterChip(
                            selected = state.format == ImageFormat.PNG,
                            onClick = { viewModel.setFormat(ImageFormat.PNG) },
                            label = { Text("PNG") }
                        )
                    }
                    Button(onClick = viewModel::process, modifier = Modifier.fillMaxWidth().padding(top = 24.dp)) {
                        Text("Convert to images")
                    }
                }

                ToolPhase.PROCESSING -> {
                    val (done, total) = state.progress ?: (0 to 1)
                    LoadingOverlay(
                        message = "Rendering pages ($done/$total)…",
                        progressFraction = if (total > 0) done.toFloat() / total else null
                    )
                }

                ToolPhase.RESULT -> state.result?.let { result ->
                    Box(modifier = Modifier.fillMaxSize().padding(16.dp)) {
                        ResultSection(
                            result = result,
                            onSaveAll = { activity?.let(viewModel::save) },
                            onShareAll = { actions.share(result.outputs, result.savedFiles, state.format.mimeType) },
                            onOpenOutput = { output -> actions.open(output, result.savedFiles[output.file.absolutePath]) },
                            onShareOutput = { output -> actions.share(listOf(output), result.savedFiles, state.format.mimeType) }
                        )
                    }
                }
            }
        }
    }
}
