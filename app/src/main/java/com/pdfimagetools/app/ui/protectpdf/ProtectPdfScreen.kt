package com.pdfimagetools.app.ui.protectpdf

import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material3.Button
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.input.PasswordVisualTransformation
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
import com.pdfimagetools.app.util.findActivity

@Composable
fun ProtectPdfScreen(onBack: () -> Unit) {
    val viewModel: ProtectPdfViewModel = viewModel(
        factory = SimpleViewModelFactory {
            ProtectPdfViewModel(ServiceLocator.pdfManager, ServiceLocator.storageManager, ServiceLocator.saveOrchestrator)
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
        topBar = { AppTopBar(title = "Password protect", onBack = onBack) },
        bottomBar = { BannerAdView() }
    ) { padding ->
        Box(modifier = Modifier.fillMaxSize().padding(padding)) {
            when (state.phase) {
                ToolPhase.PICK -> EmptyPickState(
                    icon = Icons.Filled.Lock,
                    title = "Select a PDF",
                    description = "Choose a PDF to encrypt with AES-256 and a password.",
                    buttonText = "Select PDF",
                    onClick = { pickPdfLauncher.launch(arrayOf("application/pdf")) }
                )

                ToolPhase.CONFIGURE -> Column(
                    modifier = Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(16.dp)
                ) {
                    Text(state.fileName, style = MaterialTheme.typography.titleMedium, maxLines = 1)
                    state.errorMessage?.let {
                        Text(it, color = MaterialTheme.colorScheme.error, modifier = Modifier.padding(top = 8.dp))
                    }

                    OutlinedTextField(
                        value = state.userPassword,
                        onValueChange = viewModel::setUserPassword,
                        label = { Text("Password to open (required)") },
                        visualTransformation = PasswordVisualTransformation(),
                        modifier = Modifier.fillMaxWidth().padding(top = 20.dp)
                    )
                    OutlinedTextField(
                        value = state.ownerPassword,
                        onValueChange = viewModel::setOwnerPassword,
                        label = { Text("Owner password (optional)") },
                        visualTransformation = PasswordVisualTransformation(),
                        modifier = Modifier.fillMaxWidth().padding(top = 12.dp)
                    )
                    Text(
                        "Leave the owner password blank to use the same password for both.",
                        style = MaterialTheme.typography.bodySmall,
                        color = TextSecondary,
                        modifier = Modifier.padding(top = 4.dp)
                    )

                    Row(
                        modifier = Modifier.fillMaxWidth().padding(top = 24.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("Allow printing", modifier = Modifier.weight(1f))
                        Switch(checked = state.allowPrinting, onCheckedChange = viewModel::setAllowPrinting)
                    }
                    Row(
                        modifier = Modifier.fillMaxWidth().padding(top = 8.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("Allow copying text", modifier = Modifier.weight(1f))
                        Switch(checked = state.allowCopy, onCheckedChange = viewModel::setAllowCopy)
                    }

                    Button(onClick = viewModel::process, modifier = Modifier.fillMaxWidth().padding(top = 28.dp)) {
                        Text("Protect PDF")
                    }
                }

                ToolPhase.PROCESSING -> LoadingOverlay(message = "Encrypting PDF…")

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
