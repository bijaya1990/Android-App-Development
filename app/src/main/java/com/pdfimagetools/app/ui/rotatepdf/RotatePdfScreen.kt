package com.pdfimagetools.app.ui.rotatepdf

import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.animation.core.animateFloatAsState
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.PictureAsPdf
import androidx.compose.material.icons.filled.RotateLeft
import androidx.compose.material.icons.filled.RotateRight
import androidx.compose.material3.Button
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.rotate
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
import com.pdfimagetools.app.ui.theme.AccentContainer
import com.pdfimagetools.app.util.findActivity

@Composable
fun RotatePdfScreen(onBack: () -> Unit) {
    val viewModel: RotatePdfViewModel = viewModel(
        factory = SimpleViewModelFactory {
            RotatePdfViewModel(ServiceLocator.pdfManager, ServiceLocator.storageManager, ServiceLocator.saveOrchestrator)
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
        topBar = { AppTopBar(title = "Rotate PDF", onBack = onBack) },
        bottomBar = { BannerAdView(modifier = Modifier.navigationBarsPadding()) }
    ) { padding ->
        Box(modifier = Modifier.fillMaxSize().padding(padding)) {
            when (state.phase) {
                ToolPhase.PICK -> EmptyPickState(
                    icon = Icons.Filled.RotateRight,
                    title = "Select a PDF",
                    description = "Choose a PDF to rotate every page.",
                    buttonText = "Select PDF",
                    onClick = { pickPdfLauncher.launch(arrayOf("application/pdf")) }
                )

                ToolPhase.CONFIGURE -> Column(
                    modifier = Modifier.fillMaxSize().padding(16.dp),
                    horizontalAlignment = Alignment.CenterHorizontally
                ) {
                    Text(state.fileName, style = MaterialTheme.typography.titleMedium, maxLines = 1)
                    state.errorMessage?.let {
                        Text(it, color = MaterialTheme.colorScheme.error, modifier = Modifier.padding(top = 8.dp))
                    }

                    val animatedRotation by animateFloatAsState(targetValue = state.rotationDegrees.toFloat(), label = "rotation")
                    Box(
                        modifier = Modifier
                            .padding(top = 32.dp)
                            .size(140.dp, 180.dp)
                            .rotate(animatedRotation)
                            .background(AccentContainer, RoundedCornerShape(12.dp)),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(Icons.Filled.PictureAsPdf, contentDescription = null, tint = Accent, modifier = Modifier.size(48.dp))
                    }

                    Row(
                        modifier = Modifier.padding(top = 32.dp),
                        horizontalArrangement = Arrangement.spacedBy(24.dp)
                    ) {
                        IconButton(onClick = viewModel::rotateLeft) {
                            Icon(Icons.Filled.RotateLeft, contentDescription = "Rotate left")
                        }
                        Text("${state.rotationDegrees}°", style = MaterialTheme.typography.titleMedium)
                        IconButton(onClick = viewModel::rotateRight) {
                            Icon(Icons.Filled.RotateRight, contentDescription = "Rotate right")
                        }
                    }

                    Button(onClick = viewModel::process, modifier = Modifier.fillMaxWidth().padding(top = 40.dp)) {
                        Text("Apply rotation")
                    }
                }

                ToolPhase.PROCESSING -> LoadingOverlay(message = "Rotating PDF…")

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
