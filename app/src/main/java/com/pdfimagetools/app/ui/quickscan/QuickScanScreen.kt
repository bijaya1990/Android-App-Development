package com.pdfimagetools.app.ui.quickscan

import android.app.Activity
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.IntentSenderRequest
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.DocumentScanner
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
import com.google.mlkit.vision.documentscanner.GmsDocumentScanner
import com.google.mlkit.vision.documentscanner.GmsDocumentScannerOptions
import com.google.mlkit.vision.documentscanner.GmsDocumentScanning
import com.google.mlkit.vision.documentscanner.GmsDocumentScanningResult
import com.pdfimagetools.app.core.di.ServiceLocator
import com.pdfimagetools.app.core.di.SimpleViewModelFactory
import com.pdfimagetools.app.ui.common.ToolPhase
import com.pdfimagetools.app.ui.components.AppTopBar
import com.pdfimagetools.app.ui.components.BannerAdView
import com.pdfimagetools.app.ui.components.EmptyPickState
import com.pdfimagetools.app.ui.components.ResultSection
import com.pdfimagetools.app.ui.components.rememberToolResultActions
import com.pdfimagetools.app.util.findActivity

private fun buildScanner(): GmsDocumentScanner {
    val options = GmsDocumentScannerOptions.Builder()
        .setGalleryImportAllowed(true)
        .setPageLimit(50)
        .setResultFormats(GmsDocumentScannerOptions.RESULT_FORMAT_PDF)
        .setScannerMode(GmsDocumentScannerOptions.SCANNER_MODE_FULL)
        .build()
    return GmsDocumentScanning.getClient(options)
}

@Composable
fun QuickScanScreen(onBack: () -> Unit) {
    val viewModel: QuickScanViewModel = viewModel(
        factory = SimpleViewModelFactory { QuickScanViewModel(ServiceLocator.storageManager, ServiceLocator.saveOrchestrator) }
    )
    val state by viewModel.uiState.collectAsState()
    val context = LocalContext.current
    val activity = context.findActivity()
    val actions = rememberToolResultActions()

    val scannerLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.StartIntentSenderForResult()
    ) { activityResult ->
        val scanResult = GmsDocumentScanningResult.fromActivityResultIntent(activityResult.data)
        if (activityResult.resultCode == Activity.RESULT_OK && scanResult != null) {
            val pdfUri = scanResult.pdf?.uri
            if (pdfUri != null) {
                viewModel.onScanCompleted(context, pdfUri)
            } else {
                viewModel.onScanFailed("Scan produced no output")
            }
        }
        // RESULT_CANCELED: user backed out of the scanner — stay on the start screen silently.
    }

    fun startScan() {
        val currentActivity = activity ?: return
        buildScanner()
            .getStartScanIntent(currentActivity)
            .addOnSuccessListener { intentSender ->
                scannerLauncher.launch(IntentSenderRequest.Builder(intentSender).build())
            }
            .addOnFailureListener { exception ->
                viewModel.onScanFailed(exception.message ?: "Could not start the scanner")
            }
    }

    Scaffold(
        topBar = { AppTopBar(title = "Quick Scan", onBack = onBack) },
        bottomBar = { BannerAdView() }
    ) { padding ->
        Box(modifier = Modifier.fillMaxSize().padding(padding)) {
            when (state.phase) {
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
                else -> {
                    androidx.compose.foundation.layout.Column(modifier = Modifier.fillMaxSize()) {
                        state.errorMessage?.let { message ->
                            Text(
                                text = message,
                                color = MaterialTheme.colorScheme.error,
                                style = MaterialTheme.typography.bodySmall,
                                modifier = Modifier.padding(horizontal = 24.dp, vertical = 12.dp)
                            )
                        }
                        EmptyPickState(
                            icon = Icons.Filled.DocumentScanner,
                            title = "Scan a document",
                            description = "Point your camera at a document. Edges, cropping, and perspective are detected automatically — capture as many pages as you need in one session.",
                            buttonText = "Start scanning",
                            onClick = ::startScan,
                            modifier = Modifier.weight(1f)
                        )
                    }
                }
            }
        }
    }
}
