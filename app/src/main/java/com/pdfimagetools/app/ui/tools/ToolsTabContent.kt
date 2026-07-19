package com.pdfimagetools.app.ui.tools

import android.widget.Toast
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.aspectRatio
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.AddPhotoAlternate
import androidx.compose.material.icons.filled.CallSplit
import androidx.compose.material.icons.filled.ContentCut
import androidx.compose.material.icons.filled.Description
import androidx.compose.material.icons.filled.FolderZip
import androidx.compose.material.icons.filled.Image
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.MergeType
import androidx.compose.material.icons.filled.PhotoSizeSelectSmall
import androidx.compose.material.icons.filled.RotateRight
import androidx.compose.material.icons.filled.SwapHoriz
import androidx.compose.material.icons.filled.Transform
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.navigation.NavHostController
import com.pdfimagetools.app.navigation.Screen
import com.pdfimagetools.app.ui.components.NativeAdCard
import com.pdfimagetools.app.ui.theme.Error
import com.pdfimagetools.app.ui.theme.ErrorContainer
import com.pdfimagetools.app.ui.theme.OnPrimaryContainer
import com.pdfimagetools.app.ui.theme.OutlineVariant
import com.pdfimagetools.app.ui.theme.Primary
import com.pdfimagetools.app.ui.theme.PrimaryContainer

private data class ToolItem(
    val title: String,
    val icon: ImageVector,
    val route: String,
    val destructive: Boolean = false
)

private val toolItems = listOf(
    ToolItem("Merge PDF", Icons.Filled.MergeType, Screen.MergePdf.route),
    ToolItem("Split PDF", Icons.Filled.CallSplit, Screen.SplitPdf.route),
    ToolItem("Compress PDF", Icons.Filled.FolderZip, Screen.CompressPdf.route),
    ToolItem("Image to PDF", Icons.Filled.AddPhotoAlternate, Screen.ImageToPdf.route),
    ToolItem("PDF to Image", Icons.Filled.Image, Screen.PdfToImage.route),
    ToolItem("Rotate", Icons.Filled.RotateRight, Screen.RotatePdf.route),
    ToolItem("Delete Pages", Icons.Filled.ContentCut, Screen.OrganizePages.route, destructive = true),
    ToolItem("Extract Pages", Icons.Filled.Description, Screen.OrganizePages.route),
    ToolItem("Password", Icons.Filled.Lock, Screen.ProtectPdf.route),
    ToolItem("Compress Image", Icons.Filled.PhotoSizeSelectSmall, Screen.CompressImage.route),
    ToolItem("JPG to PNG", Icons.Filled.Transform, Screen.ConvertFormat.routeFor(Screen.ConvertFormat.TARGET_PNG)),
    ToolItem("PNG to JPG", Icons.Filled.SwapHoriz, Screen.ConvertFormat.routeFor(Screen.ConvertFormat.TARGET_JPG))
)

@Composable
fun ToolsTabContent(navController: NavHostController) {
    val context = LocalContext.current

    LazyVerticalGrid(
        columns = GridCells.Adaptive(minSize = 150.dp),
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        horizontalArrangement = Arrangement.spacedBy(12.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        item(span = { androidx.compose.foundation.lazy.grid.GridItemSpan(maxLineSpan) }) {
            Column(modifier = Modifier.padding(bottom = 4.dp)) {
                Text("PDF Utilities", style = MaterialTheme.typography.titleLarge)
                Text(
                    "Modify, convert and manage your documents with professional precision.",
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    modifier = Modifier.padding(top = 4.dp)
                )
            }
        }

        items(items = toolItems) { tool ->
            ToolGridCard(tool = tool, onClick = { navController.navigate(tool.route) })
        }

        item(span = { androidx.compose.foundation.lazy.grid.GridItemSpan(maxLineSpan) }) {
            ProUpsellBanner(
                onUpgradeClick = { Toast.makeText(context, "Z Scanner Pro is coming soon", Toast.LENGTH_SHORT).show() }
            )
        }

        item(span = { androidx.compose.foundation.lazy.grid.GridItemSpan(maxLineSpan) }) {
            NativeAdCard()
        }
    }
}

@Composable
private fun ToolGridCard(tool: ToolItem, onClick: () -> Unit) {
    Card(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth().aspectRatio(1f),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        border = androidx.compose.foundation.BorderStroke(1.dp, OutlineVariant),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
    ) {
        Column(
            modifier = Modifier.fillMaxSize().padding(12.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center
        ) {
            Box(
                modifier = Modifier
                    .size(48.dp)
                    .background(
                        if (tool.destructive) ErrorContainer.copy(alpha = 0.5f) else PrimaryContainer.copy(alpha = 0.15f),
                        RoundedCornerShape(10.dp)
                    ),
                contentAlignment = Alignment.Center
            ) {
                Icon(
                    imageVector = tool.icon,
                    contentDescription = null,
                    tint = if (tool.destructive) Error else Primary
                )
            }
            Text(
                text = tool.title,
                style = MaterialTheme.typography.labelLarge,
                color = MaterialTheme.colorScheme.onSurface,
                textAlign = TextAlign.Center,
                maxLines = 2,
                modifier = Modifier.padding(top = 10.dp)
            )
        }
    }
}

@Composable
private fun ProUpsellBanner(onUpgradeClick: () -> Unit) {
    Card(
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = PrimaryContainer)
    ) {
        Column(modifier = Modifier.fillMaxWidth().padding(16.dp)) {
            Text(
                "Z Scanner Pro",
                style = MaterialTheme.typography.titleLarge,
                color = OnPrimaryContainer
            )
            Text(
                "Unlock OCR, batch editing, and unlimited cloud storage.",
                style = MaterialTheme.typography.bodyMedium,
                color = OnPrimaryContainer,
                modifier = Modifier.padding(top = 4.dp, bottom = 12.dp)
            )
            Button(onClick = onUpgradeClick) {
                Text("Upgrade")
            }
        }
    }
}
