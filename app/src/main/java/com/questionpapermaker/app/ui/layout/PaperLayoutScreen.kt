package com.questionpapermaker.app.ui.layout

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.ArrowForward
import androidx.compose.material.icons.filled.Article
import androidx.compose.material.icons.filled.CropLandscape
import androidx.compose.material.icons.filled.CropPortrait
import androidx.compose.material.icons.filled.Description
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.material3.Button
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.questionpapermaker.app.data.model.FooterStyle
import com.questionpapermaker.app.data.model.HeaderStyle
import com.questionpapermaker.app.data.model.MarginPreset
import com.questionpapermaker.app.data.model.PageNumberPosition
import com.questionpapermaker.app.data.model.PageOrientation
import com.questionpapermaker.app.data.model.PageSize
import com.questionpapermaker.app.ui.common.DraftSavedIndicator
import com.questionpapermaker.app.ui.common.EnumDropdown
import com.questionpapermaker.app.ui.common.ExpandableSection
import com.questionpapermaker.app.ui.common.FormCard
import com.questionpapermaker.app.ui.common.SelectableOptionCard
import com.questionpapermaker.app.ui.paperdetails.PaperEditorViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PaperLayoutScreen(
    viewModel: PaperEditorViewModel,
    onBack: () -> Unit,
    onNext: () -> Unit,
    onPreview: () -> Unit
) {
    val paper by viewModel.paper.collectAsState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Question Paper Maker") },
                navigationIcon = {
                    IconButton(onClick = onBack) { Icon(Icons.Default.ArrowBack, contentDescription = "Back") }
                },
                actions = {
                    DraftSavedIndicator(modifier = Modifier.padding(end = 8.dp))
                    IconButton(onClick = onPreview) {
                        Icon(Icons.Default.Visibility, contentDescription = "Preview")
                    }
                }
            )
        },
        bottomBar = {
            Row(
                modifier = Modifier.fillMaxWidth().padding(16.dp),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                OutlinedButton(onClick = onBack, shape = MaterialTheme.shapes.small) {
                    Icon(Icons.Default.ArrowBack, contentDescription = null, modifier = Modifier.height(18.dp))
                    Text("  Back")
                }
                Button(onClick = { viewModel.saveNow(onNext) }, shape = MaterialTheme.shapes.small) {
                    Text("Next Step  ")
                    Icon(Icons.Default.ArrowForward, contentDescription = null, modifier = Modifier.height(18.dp))
                }
            }
        }
    ) { padding ->
        val current = paper ?: return@Scaffold

        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
                .padding(horizontal = 16.dp)
                .verticalScroll(rememberScrollState()),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            Column(modifier = Modifier.padding(top = 12.dp)) {
                Text("Paper Layout", style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.Bold)
                Text(
                    "Configure the physical dimensions and visual structure of your document.",
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    modifier = Modifier.padding(top = 4.dp)
                )
            }

            FormCard(modifier = Modifier.fillMaxWidth()) {
                Text("Page Size", style = MaterialTheme.typography.labelLarge)
                Row(
                    modifier = Modifier.fillMaxWidth().padding(top = 8.dp, bottom = 16.dp),
                    horizontalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    SelectableOptionCard(
                        label = "A4",
                        icon = Icons.Default.Description,
                        selected = current.pageSize == PageSize.A4,
                        onClick = { viewModel.update { it.copy(pageSize = PageSize.A4) } },
                        modifier = Modifier.weight(1f)
                    )
                    SelectableOptionCard(
                        label = "Letter",
                        icon = Icons.Default.Article,
                        selected = current.pageSize == PageSize.LETTER,
                        onClick = { viewModel.update { it.copy(pageSize = PageSize.LETTER) } },
                        modifier = Modifier.weight(1f)
                    )
                }

                Text("Orientation", style = MaterialTheme.typography.labelLarge)
                Row(
                    modifier = Modifier.fillMaxWidth().padding(top = 8.dp, bottom = 16.dp),
                    horizontalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    SelectableOptionCard(
                        label = "Portrait",
                        icon = Icons.Default.CropPortrait,
                        selected = current.orientation == PageOrientation.PORTRAIT,
                        onClick = { viewModel.update { it.copy(orientation = PageOrientation.PORTRAIT) } },
                        modifier = Modifier.weight(1f)
                    )
                    SelectableOptionCard(
                        label = "Landscape",
                        icon = Icons.Default.CropLandscape,
                        selected = current.orientation == PageOrientation.LANDSCAPE,
                        onClick = { viewModel.update { it.copy(orientation = PageOrientation.LANDSCAPE) } },
                        modifier = Modifier.weight(1f)
                    )
                }

                EnumDropdown(
                    label = "Margins",
                    value = current.marginPreset,
                    options = MarginPreset.entries,
                    displayName = { it.displayName },
                    onSelect = { m -> viewModel.update { it.copy(marginPreset = m) } },
                    modifier = Modifier.fillMaxWidth()
                )

                HorizontalDivider(modifier = Modifier.padding(vertical = 16.dp))

                Text("Visual Elements", style = MaterialTheme.typography.labelLarge)
                ToggleRow(
                    label = "Institution Logo",
                    checked = current.showInstitutionLogo,
                    onCheckedChange = { v -> viewModel.update { it.copy(showInstitutionLogo = v) } }
                )
                ToggleRow(
                    label = "Signature Area",
                    checked = current.showSignatureArea,
                    onCheckedChange = { v -> viewModel.update { it.copy(showSignatureArea = v) } }
                )
            }

            FormCard(modifier = Modifier.fillMaxWidth()) {
                ExpandableSection(label = "More Options") {
                    EnumDropdown(
                        label = "Header Style",
                        value = current.headerStyle,
                        options = HeaderStyle.entries,
                        displayName = { it.displayName },
                        onSelect = { h -> viewModel.update { it.copy(headerStyle = h) } },
                        modifier = Modifier.fillMaxWidth().padding(top = 8.dp)
                    )
                    EnumDropdown(
                        label = "Footer Style",
                        value = current.footerStyle,
                        options = FooterStyle.entries,
                        displayName = { it.displayName },
                        onSelect = { f -> viewModel.update { it.copy(footerStyle = f) } },
                        modifier = Modifier.fillMaxWidth().padding(top = 8.dp)
                    )
                    EnumDropdown(
                        label = "Page Number Position",
                        value = current.pageNumberPosition,
                        options = PageNumberPosition.entries,
                        displayName = { it.displayName },
                        onSelect = { p -> viewModel.update { it.copy(pageNumberPosition = p) } },
                        modifier = Modifier.fillMaxWidth().padding(top = 8.dp)
                    )
                    ToggleRow(
                        label = "Continuous Numbering Across Sections",
                        checked = current.continuousNumbering,
                        onCheckedChange = { v -> viewModel.update { it.copy(continuousNumbering = v) } }
                    )
                    if (current.showSignatureArea) {
                        OutlinedTextField(
                            value = current.signatureLabel.orEmpty(),
                            onValueChange = { v -> viewModel.update { it.copy(signatureLabel = v) } },
                            label = { Text("Signature Label (e.g. Head of Department)") },
                            singleLine = true,
                            shape = MaterialTheme.shapes.small,
                            modifier = Modifier.fillMaxWidth().padding(top = 8.dp)
                        )
                    }
                    OutlinedTextField(
                        value = current.watermarkText.orEmpty(),
                        onValueChange = { v -> viewModel.update { it.copy(watermarkText = v.ifBlank { null }) } },
                        label = { Text("Watermark (e.g. CONFIDENTIAL, DRAFT)") },
                        singleLine = true,
                        shape = MaterialTheme.shapes.small,
                        modifier = Modifier.fillMaxWidth().padding(top = 8.dp)
                    )
                }
            }
        }
    }
}

@Composable
private fun ToggleRow(label: String, checked: Boolean, onCheckedChange: (Boolean) -> Unit) {
    Row(
        modifier = Modifier.fillMaxWidth().padding(vertical = 6.dp),
        horizontalArrangement = Arrangement.SpaceBetween
    ) {
        Text(label, modifier = Modifier.padding(top = 12.dp))
        Switch(checked = checked, onCheckedChange = onCheckedChange)
    }
}
