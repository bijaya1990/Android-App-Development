package com.pdfimagetools.app.ui.compresspdf

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
import androidx.compose.material.icons.filled.FolderZip
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.RadioButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel
import com.pdfimagetools.app.core.di.ServiceLocator
import com.pdfimagetools.app.core.di.SimpleViewModelFactory
import com.pdfimagetools.app.core.pdf.CompressionLevel
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

private data class LevelOption(val level: CompressionLevel, val title: String, val description: String)

private val levelOptions = listOf(
    LevelOption(CompressionLevel.LOW, "Low compression", "Best quality, smaller size reduction"),
    LevelOption(CompressionLevel.MEDIUM, "Medium compression", "Balanced quality and file size"),
    LevelOption(CompressionLevel.HIGH, "High compression", "Smallest file, lower quality")
)

@Composable
fun CompressPdfScreen(onBack: () -> Unit) {
    val viewModel: CompressPdfViewModel = viewModel(
        factory = SimpleViewModelFactory {
            CompressPdfViewModel(ServiceLocator.pdfManager, ServiceLocator.storageManager, ServiceLocator.saveOrchestrator)
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
        topBar = { AppTopBar(title = "Compress PDF", onBack = onBack) },
        bottomBar = { BannerAdView(modifier = Modifier.navigationBarsPadding()) }
    ) { padding ->
        Box(modifier = Modifier.fillMaxSize().padding(padding)) {
            when (state.phase) {
                ToolPhase.PICK -> EmptyPickState(
                    icon = Icons.Filled.FolderZip,
                    title = "Select a PDF",
                    description = "Choose a PDF to shrink its file size.",
                    buttonText = "Select PDF",
                    onClick = { pickPdfLauncher.launch(arrayOf("application/pdf")) }
                )

                ToolPhase.CONFIGURE -> Column(modifier = Modifier.fillMaxSize().padding(16.dp)) {
                    Text(state.fileName, style = MaterialTheme.typography.titleMedium, maxLines = 1)
                    Text(
                        FileUtils.readableSize(state.originalSizeBytes),
                        style = MaterialTheme.typography.bodySmall,
                        color = TextSecondary,
                        modifier = Modifier.padding(top = 4.dp)
                    )
                    state.errorMessage?.let {
                        Text(it, color = MaterialTheme.colorScheme.error, modifier = Modifier.padding(top = 12.dp))
                    }
                    Column(
                        modifier = Modifier.padding(top = 20.dp),
                        verticalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        levelOptions.forEach { option ->
                            Card(
                                onClick = { viewModel.setLevel(option.level) },
                                colors = CardDefaults.cardColors(
                                    containerColor = if (state.level == option.level) MaterialTheme.colorScheme.primaryContainer else MaterialTheme.colorScheme.surface
                                )
                            ) {
                                Row(
                                    modifier = Modifier.fillMaxWidth().padding(12.dp),
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    RadioButton(selected = state.level == option.level, onClick = { viewModel.setLevel(option.level) })
                                    Column(modifier = Modifier.padding(start = 8.dp)) {
                                        Text(option.title, style = MaterialTheme.typography.titleSmall)
                                        Text(option.description, style = MaterialTheme.typography.bodySmall, color = TextSecondary)
                                    }
                                }
                            }
                        }
                    }
                    Button(onClick = viewModel::process, modifier = Modifier.fillMaxWidth().padding(top = 24.dp)) {
                        Text("Compress")
                    }
                }

                ToolPhase.PROCESSING -> LoadingOverlay(message = "Compressing PDF…")

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
