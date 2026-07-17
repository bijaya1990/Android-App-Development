package com.pdfimagetools.app.ui.home

import androidx.activity.compose.BackHandler
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.AddPhotoAlternate
import androidx.compose.material.icons.filled.CallSplit
import androidx.compose.material.icons.filled.FolderZip
import androidx.compose.material.icons.filled.History
import androidx.compose.material.icons.filled.Image
import androidx.compose.material.icons.filled.MergeType
import androidx.compose.material.icons.filled.PhotoSizeSelectSmall
import androidx.compose.material.icons.filled.PictureAsPdf
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material.icons.filled.SwapHoriz
import androidx.compose.material.icons.filled.Transform
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.CenterAlignedTopAppBar
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.unit.dp
import androidx.navigation.NavHostController
import com.pdfimagetools.app.ui.components.BannerAdView
import com.pdfimagetools.app.ui.components.NativeAdCard
import com.pdfimagetools.app.ui.components.SectionHeader
import com.pdfimagetools.app.ui.components.ToolCard
import com.pdfimagetools.app.navigation.Screen

private data class ToolInfo(
    val title: String,
    val description: String,
    val icon: ImageVector,
    val route: String
)

private val createPdfTools = listOf(
    ToolInfo("Image to PDF", "Combine photos into a single PDF", Icons.Filled.AddPhotoAlternate, Screen.ImageToPdf.route),
    ToolInfo("Merge PDF", "Combine multiple PDFs into one", Icons.Filled.MergeType, Screen.MergePdf.route),
    ToolInfo("Split PDF", "Extract pages into separate files", Icons.Filled.CallSplit, Screen.SplitPdf.route)
)

private val reduceSizeTools = listOf(
    ToolInfo("Compress PDF", "Shrink a PDF file size", Icons.Filled.FolderZip, Screen.CompressPdf.route),
    ToolInfo("Compress image", "Reduce photo file size", Icons.Filled.PhotoSizeSelectSmall, Screen.CompressImage.route)
)

private val convertTools = listOf(
    ToolInfo("PDF to image", "Save PDF pages as pictures", Icons.Filled.Image, Screen.PdfToImage.route),
    ToolInfo("JPG to PNG", "Convert JPG photos to PNG", Icons.Filled.Transform, Screen.ConvertFormat.routeFor(Screen.ConvertFormat.TARGET_PNG)),
    ToolInfo("PNG to JPG", "Convert PNG photos to JPG", Icons.Filled.SwapHoriz, Screen.ConvertFormat.routeFor(Screen.ConvertFormat.TARGET_JPG))
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun HomeScreen(navController: NavHostController, onExitApp: () -> Unit) {
    var showExitDialog by remember { mutableStateOf(false) }
    BackHandler(enabled = true) { showExitDialog = true }

    Scaffold(
        topBar = {
            CenterAlignedTopAppBar(
                title = { Text("PDF & Image Tools") },
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
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .padding(horizontal = 16.dp),
            contentPadding = androidx.compose.foundation.layout.PaddingValues(vertical = 16.dp),
            verticalArrangement = Arrangement.spacedBy(24.dp)
        ) {
            item { ToolSection(title = "Create PDF", tools = createPdfTools, navController = navController) }
            item { NativeAdCard() }
            item { ToolSection(title = "Reduce size", tools = reduceSizeTools, navController = navController) }
            item { ToolSection(title = "Convert", tools = convertTools, navController = navController) }
        }
    }

    if (showExitDialog) {
        AlertDialog(
            onDismissRequest = { showExitDialog = false },
            title = { Text("Exit app?") },
            text = { Text("Are you sure you want to close PDF & Image Tools?") },
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
private fun ToolSection(title: String, tools: List<ToolInfo>, navController: NavHostController) {
    Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
        SectionHeader(title)
        tools.chunked(2).forEach { rowTools ->
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                rowTools.forEach { tool ->
                    ToolCard(
                        title = tool.title,
                        description = tool.description,
                        icon = tool.icon,
                        onClick = { navController.navigate(tool.route) },
                        modifier = Modifier.weight(1f)
                    )
                }
                if (rowTools.size == 1) {
                    androidx.compose.foundation.layout.Spacer(modifier = Modifier.weight(1f))
                }
            }
        }
    }
}
