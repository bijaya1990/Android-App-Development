package com.pdfimagetools.app.ui.home

import android.net.Uri
import androidx.activity.compose.BackHandler
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ExperimentalLayoutApi
import androidx.compose.foundation.layout.FlowRow
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.AddPhotoAlternate
import androidx.compose.material.icons.filled.CallSplit
import androidx.compose.material.icons.filled.ContentCut
import androidx.compose.material.icons.filled.DocumentScanner
import androidx.compose.material.icons.filled.FolderOpen
import androidx.compose.material.icons.filled.FolderZip
import androidx.compose.material.icons.filled.History
import androidx.compose.material.icons.filled.Image
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.MergeType
import androidx.compose.material.icons.filled.PhotoSizeSelectSmall
import androidx.compose.material.icons.filled.PictureAsPdf
import androidx.compose.material.icons.filled.RotateRight
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material.icons.filled.SwapHoriz
import androidx.compose.material.icons.filled.Transform
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CenterAlignedTopAppBar
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.navigation.NavHostController
import com.pdfimagetools.app.core.di.ServiceLocator
import com.pdfimagetools.app.data.RecentFileEntry
import com.pdfimagetools.app.navigation.Screen
import com.pdfimagetools.app.ui.components.BannerAdView
import com.pdfimagetools.app.ui.components.NativeAdCard
import com.pdfimagetools.app.ui.components.Pressable3DButton
import com.pdfimagetools.app.ui.components.QuickActionChip
import com.pdfimagetools.app.ui.components.SectionHeader
import com.pdfimagetools.app.ui.theme.Accent
import com.pdfimagetools.app.ui.theme.TextSecondary
import com.pdfimagetools.app.util.FileUtils

private data class QuickAction(val title: String, val icon: ImageVector, val route: String)

private val quickActions = listOf(
    QuickAction("Merge PDF", Icons.Filled.MergeType, Screen.MergePdf.route),
    QuickAction("Split PDF", Icons.Filled.CallSplit, Screen.SplitPdf.route),
    QuickAction("Compress PDF", Icons.Filled.FolderZip, Screen.CompressPdf.route),
    QuickAction("Compress image", Icons.Filled.PhotoSizeSelectSmall, Screen.CompressImage.route),
    QuickAction("Image to PDF", Icons.Filled.AddPhotoAlternate, Screen.ImageToPdf.route),
    QuickAction("PDF to image", Icons.Filled.Image, Screen.PdfToImage.route),
    QuickAction("Rotate", Icons.Filled.RotateRight, Screen.RotatePdf.route),
    QuickAction("Delete pages", Icons.Filled.ContentCut, Screen.OrganizePages.route),
    QuickAction("Extract pages", Icons.Filled.ContentCut, Screen.OrganizePages.route),
    QuickAction("Password", Icons.Filled.Lock, Screen.ProtectPdf.route),
    QuickAction("JPG to PNG", Icons.Filled.Transform, Screen.ConvertFormat.routeFor(Screen.ConvertFormat.TARGET_PNG)),
    QuickAction("PNG to JPG", Icons.Filled.SwapHoriz, Screen.ConvertFormat.routeFor(Screen.ConvertFormat.TARGET_JPG)),
    QuickAction("History", Icons.Filled.History, Screen.RecentFiles.route),
    QuickAction("Settings", Icons.Filled.Settings, Screen.Settings.route)
)

@OptIn(ExperimentalMaterial3Api::class, ExperimentalLayoutApi::class)
@Composable
fun HomeScreen(navController: NavHostController, onExitApp: () -> Unit) {
    var showExitDialog by remember { mutableStateOf(false) }
    BackHandler(enabled = true) { showExitDialog = true }

    val context = LocalContext.current
    val recentEntries by ServiceLocator.recentFilesStore.entries.collectAsState()

    val openPdfLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.OpenDocument()
    ) { uri: Uri? ->
        uri?.let {
            val intent = ServiceLocator.storageManager.openFileIntent(it, "application/pdf")
            runCatching { context.startActivity(intent) }
        }
    }

    Scaffold(
        topBar = {
            CenterAlignedTopAppBar(
                title = { Text("Z Scanner") },
                actions = {
                    IconButton(onClick = { navController.navigate(Screen.RecentFiles.route) }) {
                        Icon(Icons.Filled.History, contentDescription = "Recent files")
                    }
                    IconButton(onClick = { navController.navigate(Screen.Settings.route) }) {
                        Icon(Icons.Filled.Settings, contentDescription = "Settings")
                    }
                }
            )
        },
        bottomBar = { BannerAdView() }
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .verticalScroll(rememberScrollState())
                .padding(horizontal = 16.dp, vertical = 16.dp),
            verticalArrangement = Arrangement.spacedBy(20.dp)
        ) {
            Pressable3DButton(
                onClick = { navController.navigate(Screen.QuickScan.route) },
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(20.dp)
            ) {
                Icon(Icons.Filled.DocumentScanner, contentDescription = null, modifier = Modifier.size(26.dp))
                Text("  Quick Scan", style = MaterialTheme.typography.titleMedium)
            }

            Row(horizontalArrangement = Arrangement.spacedBy(12.dp), modifier = Modifier.fillMaxWidth()) {
                OutlinedButton(
                    onClick = { openPdfLauncher.launch(arrayOf("application/pdf")) },
                    modifier = Modifier.weight(1f)
                ) {
                    Icon(Icons.Filled.FolderOpen, contentDescription = null, modifier = Modifier.size(18.dp))
                    Text("  Open PDF", modifier = Modifier.padding(start = 4.dp))
                }
                OutlinedButton(
                    onClick = { navController.navigate(Screen.ImageToPdf.route) },
                    modifier = Modifier.weight(1f)
                ) {
                    Icon(Icons.Filled.AddPhotoAlternate, contentDescription = null, modifier = Modifier.size(18.dp))
                    Text("  Import images", modifier = Modifier.padding(start = 4.dp))
                }
            }

            val newestEntry = recentEntries.firstOrNull()
            if (newestEntry != null) {
                Column {
                    SectionHeader("Continue working")
                    RecentEntryCard(entry = newestEntry, context = context)
                }
            }

            if (recentEntries.isNotEmpty()) {
                Column {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        SectionHeader("Recent documents")
                        TextButton(onClick = { navController.navigate(Screen.RecentFiles.route) }) {
                            Text("See all")
                        }
                    }
                    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        recentEntries.take(3).forEach { entry ->
                            RecentEntryCard(entry = entry, context = context)
                        }
                    }
                }
            }

            NativeAdCard()

            Column {
                SectionHeader("Quick actions")
                FlowRow(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(4.dp),
                    verticalArrangement = Arrangement.spacedBy(4.dp)
                ) {
                    quickActions.forEach { action ->
                        QuickActionChip(
                            title = action.title,
                            icon = action.icon,
                            onClick = { navController.navigate(action.route) },
                            modifier = Modifier.width(80.dp)
                        )
                    }
                }
            }
        }
    }

    if (showExitDialog) {
        AlertDialog(
            onDismissRequest = { showExitDialog = false },
            title = { Text("Exit app?") },
            text = { Text("Are you sure you want to close Z Scanner?") },
            confirmButton = {
                TextButton(onClick = onExitApp) { Text("Exit") }
            },
            dismissButton = {
                TextButton(onClick = { showExitDialog = false }) { Text("Continue") }
            }
        )
    }
}

@Composable
private fun RecentEntryCard(entry: RecentFileEntry, context: android.content.Context) {
    Card(
        onClick = {
            val uri = Uri.parse(entry.uriString)
            val intent = ServiceLocator.storageManager.openFileIntent(uri, entry.mimeType)
            runCatching { context.startActivity(intent) }
        },
        shape = RoundedCornerShape(14.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
    ) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(12.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Box(
                modifier = Modifier
                    .size(40.dp)
                    .background(Accent.copy(alpha = 0.12f), CircleShape),
                contentAlignment = Alignment.Center
            ) {
                Icon(
                    imageVector = if (entry.mimeType == "application/pdf") Icons.Filled.PictureAsPdf else Icons.Filled.Image,
                    contentDescription = null,
                    tint = Accent
                )
            }
            Column(modifier = Modifier.weight(1f).padding(start = 12.dp)) {
                Text(entry.displayName, maxLines = 1, overflow = TextOverflow.Ellipsis, style = MaterialTheme.typography.bodyMedium)
                Text(
                    "${entry.toolName} · ${FileUtils.readableSize(entry.sizeBytes)}",
                    style = MaterialTheme.typography.bodySmall,
                    color = TextSecondary,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
            }
        }
    }
}
