package com.pdfimagetools.app.ui.quickscan

import android.Manifest
import android.content.pm.PackageManager
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.camera.core.CameraSelector
import androidx.camera.core.ImageCapture
import androidx.camera.core.ImageCaptureException
import androidx.camera.core.Preview
import androidx.camera.lifecycle.ProcessCameraProvider
import androidx.camera.view.PreviewView
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.statusBarsPadding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CameraAlt
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Description
import androidx.compose.material.icons.filled.FlashAuto
import androidx.compose.material.icons.filled.FlashOff
import androidx.compose.material.icons.filled.FlashOn
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberUpdatedState
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalLifecycleOwner
import androidx.compose.ui.unit.dp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.core.content.ContextCompat
import androidx.lifecycle.viewmodel.compose.viewModel
import coil.compose.AsyncImage
import com.pdfimagetools.app.core.di.ServiceLocator
import com.pdfimagetools.app.core.di.SimpleViewModelFactory
import com.pdfimagetools.app.ui.common.ToolPhase
import com.pdfimagetools.app.ui.components.BannerAdView
import com.pdfimagetools.app.ui.components.EmptyPickState
import com.pdfimagetools.app.ui.components.LoadingOverlay
import com.pdfimagetools.app.ui.components.Pressable3DIconButton
import com.pdfimagetools.app.ui.components.ResultSection
import com.pdfimagetools.app.ui.components.rememberToolResultActions
import com.pdfimagetools.app.util.findActivity
import java.util.concurrent.Executor

@Composable
fun QuickScanScreen(onBack: () -> Unit) {
    val viewModel: QuickScanViewModel = viewModel(
        factory = SimpleViewModelFactory {
            QuickScanViewModel(ServiceLocator.pdfManager, ServiceLocator.storageManager, ServiceLocator.saveOrchestrator)
        }
    )
    val state by viewModel.uiState.collectAsState()
    val context = LocalContext.current
    val activity = context.findActivity()
    val actions = rememberToolResultActions()

    var hasCameraPermission by remember {
        mutableStateOf(
            ContextCompat.checkSelfPermission(context, Manifest.permission.CAMERA) == PackageManager.PERMISSION_GRANTED
        )
    }
    val permissionLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.RequestPermission()
    ) { granted -> hasCameraPermission = granted }

    Scaffold(
        bottomBar = { if (state.phase != ToolPhase.PICK) BannerAdView(modifier = Modifier.navigationBarsPadding()) }
    ) { padding ->
        Box(modifier = Modifier.fillMaxSize().padding(if (state.phase == ToolPhase.PICK) androidx.compose.foundation.layout.PaddingValues(0.dp) else padding)) {
            when (state.phase) {
                ToolPhase.PROCESSING -> LoadingOverlay(message = "Building PDF from ${state.pages.size} page(s)…")

                ToolPhase.RESULT -> state.result?.let { result ->
                    Column(modifier = Modifier.fillMaxSize()) {
                        Box(modifier = Modifier.weight(1f).padding(16.dp)) {
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

                else -> {
                    if (!hasCameraPermission) {
                        EmptyPickState(
                            icon = Icons.Filled.CameraAlt,
                            title = "Camera access needed",
                            description = "Z Scanner needs the camera to scan documents. Grant access to continue.",
                            buttonText = "Allow camera",
                            onClick = { permissionLauncher.launch(Manifest.permission.CAMERA) }
                        )
                    } else {
                        val review = state.reviewingCapture
                        if (review != null) {
                            CropAdjustScreen(
                                bitmap = review.bitmap,
                                initialQuad = review.quad,
                                onConfirm = viewModel::confirmCrop,
                                onRetake = viewModel::retakeCapture
                            )
                        } else {
                            CameraCaptureContent(
                                state = state,
                                onBack = onBack,
                                onCaptured = viewModel::onPhotoCaptured,
                                onCaptureError = viewModel::onCaptureError,
                                onRemovePage = viewModel::removePage,
                                onFinish = viewModel::finishScanning
                            )
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun CameraCaptureContent(
    state: QuickScanUiState,
    onBack: () -> Unit,
    onCaptured: (java.io.File) -> Unit,
    onCaptureError: (String) -> Unit,
    onRemovePage: (String) -> Unit,
    onFinish: () -> Unit
) {
    val context = LocalContext.current
    val lifecycleOwner = LocalLifecycleOwner.current
    val mainExecutor: Executor = remember { ContextCompat.getMainExecutor(context) }

    val previewView = remember { PreviewView(context) }
    val imageCapture = remember { ImageCapture.Builder().build() }
    var flashMode by remember { mutableIntStateOf(ImageCapture.FLASH_MODE_OFF) }
    val onCapturedState = rememberUpdatedState(onCaptured)
    val onCaptureErrorState = rememberUpdatedState(onCaptureError)

    androidx.compose.runtime.LaunchedEffect(previewView) {
        val cameraProviderFuture = ProcessCameraProvider.getInstance(context)
        cameraProviderFuture.addListener({
            val cameraProvider = cameraProviderFuture.get()
            val preview = Preview.Builder().build().apply {
                surfaceProvider = previewView.surfaceProvider
            }
            runCatching {
                cameraProvider.unbindAll()
                cameraProvider.bindToLifecycle(
                    lifecycleOwner,
                    CameraSelector.DEFAULT_BACK_CAMERA,
                    preview,
                    imageCapture
                )
            }.onFailure { onCaptureErrorState.value("Couldn't start the camera") }
        }, mainExecutor)
    }

    Box(modifier = Modifier.fillMaxSize()) {
        AndroidView(factory = { previewView }, modifier = Modifier.fillMaxSize())

        // Top bar overlay
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .background(Color.Black.copy(alpha = 0.35f))
                .statusBarsPadding()
                .padding(horizontal = 8.dp, vertical = 8.dp),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            IconButton(onClick = onBack) {
                Icon(Icons.Filled.Close, contentDescription = "Close", tint = Color.White)
            }
            Text(
                text = if (state.pages.isEmpty()) "Scan a document" else "${state.pages.size} page(s) captured",
                color = Color.White,
                style = MaterialTheme.typography.titleSmall
            )
            IconButton(onClick = {
                imageCapture.flashMode = when (flashMode) {
                    ImageCapture.FLASH_MODE_OFF -> ImageCapture.FLASH_MODE_AUTO
                    ImageCapture.FLASH_MODE_AUTO -> ImageCapture.FLASH_MODE_ON
                    else -> ImageCapture.FLASH_MODE_OFF
                }
                flashMode = imageCapture.flashMode
            }) {
                Icon(
                    imageVector = when (flashMode) {
                        ImageCapture.FLASH_MODE_AUTO -> Icons.Filled.FlashAuto
                        ImageCapture.FLASH_MODE_ON -> Icons.Filled.FlashOn
                        else -> Icons.Filled.FlashOff
                    },
                    contentDescription = "Flash",
                    tint = Color.White
                )
            }
        }

        state.errorMessage?.let { message ->
            Text(
                text = message,
                color = Color.White,
                style = MaterialTheme.typography.bodySmall,
                modifier = Modifier
                    .align(Alignment.TopCenter)
                    .padding(top = 64.dp)
                    .background(MaterialTheme.colorScheme.error, RoundedCornerShape(8.dp))
                    .padding(horizontal = 12.dp, vertical = 6.dp)
            )
        }

        // Bottom overlay: thumbnail strip, shutter, and finish button
        Column(
            modifier = Modifier
                .align(Alignment.BottomCenter)
                .fillMaxWidth()
                .background(Color.Black.copy(alpha = 0.35f))
                .navigationBarsPadding()
                .padding(bottom = 24.dp, top = 12.dp)
        ) {
            if (state.pages.isNotEmpty()) {
                LazyRow(
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                    modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp, vertical = 8.dp)
                ) {
                    items(items = state.pages, key = { it.id }) { page ->
                        Box(modifier = Modifier.size(56.dp)) {
                            AsyncImage(
                                model = page.file,
                                contentDescription = null,
                                contentScale = ContentScale.Crop,
                                modifier = Modifier
                                    .fillMaxSize()
                                    .clip(RoundedCornerShape(8.dp))
                            )
                            IconButton(
                                onClick = { onRemovePage(page.id) },
                                modifier = Modifier.size(20.dp).align(Alignment.TopEnd)
                            ) {
                                Icon(
                                    Icons.Filled.Close,
                                    contentDescription = "Remove page",
                                    tint = Color.White,
                                    modifier = Modifier
                                        .background(Color.Black.copy(alpha = 0.6f), CircleShape)
                                        .padding(2.dp)
                                )
                            }
                        }
                    }
                }
            }

            Row(
                modifier = Modifier.fillMaxWidth().padding(horizontal = 24.dp, vertical = 8.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Box(modifier = Modifier.width(96.dp)) {
                    if (state.pages.isNotEmpty()) {
                        Button(onClick = onFinish) {
                            Icon(Icons.Filled.Description, contentDescription = null, modifier = Modifier.size(18.dp))
                            Text("  Done", modifier = Modifier.padding(start = 4.dp))
                        }
                    }
                }

                Pressable3DIconButton(
                    onClick = {
                        val outputFile = ServiceLocator.storageManager.newWorkFile("scan_capture", "jpg")
                        val outputOptions = ImageCapture.OutputFileOptions.Builder(outputFile).build()
                        imageCapture.takePicture(
                            outputOptions,
                            mainExecutor,
                            object : ImageCapture.OnImageSavedCallback {
                                override fun onImageSaved(output: ImageCapture.OutputFileResults) {
                                    onCapturedState.value(outputFile)
                                }

                                override fun onError(exception: ImageCaptureException) {
                                    onCaptureErrorState.value(exception.message ?: "Capture failed")
                                }
                            }
                        )
                    },
                    modifier = Modifier.size(72.dp)
                ) {
                    if (state.isProcessingCapture) {
                        CircularProgressIndicator(color = Color.White, modifier = Modifier.size(28.dp), strokeWidth = 3.dp)
                    } else {
                        Icon(Icons.Filled.CameraAlt, contentDescription = "Scan now", modifier = Modifier.size(30.dp))
                    }
                }

                Box(modifier = Modifier.width(96.dp))
            }
        }
    }
}
