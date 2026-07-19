package com.questionpapermaker.app.ui.layout

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
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
import androidx.compose.ui.unit.dp
import com.questionpapermaker.app.data.model.FooterStyle
import com.questionpapermaker.app.data.model.HeaderStyle
import com.questionpapermaker.app.data.model.MarginPreset
import com.questionpapermaker.app.data.model.PageNumberPosition
import com.questionpapermaker.app.data.model.PageOrientation
import com.questionpapermaker.app.data.model.PageSize
import com.questionpapermaker.app.ui.common.EnumDropdown
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
                title = { Text("Paper Layout") },
                navigationIcon = {
                    IconButton(onClick = onBack) { Icon(Icons.Default.ArrowBack, contentDescription = "Back") }
                },
                actions = {
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
                OutlinedButton(onClick = onBack) { Text("Back") }
                Button(onClick = { viewModel.saveNow(onNext) }) { Text("Next: Build Sections") }
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
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            SectionLabel("Page Setup")
            EnumDropdown(
                label = "Paper Size",
                value = current.pageSize,
                options = PageSize.entries,
                displayName = { it.displayName },
                onSelect = { size -> viewModel.update { it.copy(pageSize = size) } },
                modifier = Modifier.fillMaxWidth()
            )
            EnumDropdown(
                label = "Orientation",
                value = current.orientation,
                options = PageOrientation.entries,
                displayName = { if (it == PageOrientation.PORTRAIT) "Portrait" else "Landscape" },
                onSelect = { o -> viewModel.update { it.copy(orientation = o) } },
                modifier = Modifier.fillMaxWidth()
            )
            EnumDropdown(
                label = "Margins",
                value = current.marginPreset,
                options = MarginPreset.entries,
                displayName = { it.displayName },
                onSelect = { m -> viewModel.update { it.copy(marginPreset = m) } },
                modifier = Modifier.fillMaxWidth()
            )

            HorizontalDivider()
            SectionLabel("Header & Footer")
            EnumDropdown(
                label = "Header Style",
                value = current.headerStyle,
                options = HeaderStyle.entries,
                displayName = { it.displayName },
                onSelect = { h -> viewModel.update { it.copy(headerStyle = h) } },
                modifier = Modifier.fillMaxWidth()
            )
            EnumDropdown(
                label = "Footer Style",
                value = current.footerStyle,
                options = FooterStyle.entries,
                displayName = { it.displayName },
                onSelect = { f -> viewModel.update { it.copy(footerStyle = f) } },
                modifier = Modifier.fillMaxWidth()
            )
            EnumDropdown(
                label = "Page Number Position",
                value = current.pageNumberPosition,
                options = PageNumberPosition.entries,
                displayName = { it.displayName },
                onSelect = { p -> viewModel.update { it.copy(pageNumberPosition = p) } },
                modifier = Modifier.fillMaxWidth()
            )
            ToggleRow(
                label = "Show Institution Logo",
                checked = current.showInstitutionLogo,
                onCheckedChange = { v -> viewModel.update { it.copy(showInstitutionLogo = v) } }
            )

            HorizontalDivider()
            SectionLabel("Extras")
            ToggleRow(
                label = "Continuous Numbering Across Sections",
                checked = current.continuousNumbering,
                onCheckedChange = { v -> viewModel.update { it.copy(continuousNumbering = v) } }
            )
            ToggleRow(
                label = "Signature Area",
                checked = current.showSignatureArea,
                onCheckedChange = { v -> viewModel.update { it.copy(showSignatureArea = v) } }
            )
            if (current.showSignatureArea) {
                OutlinedTextField(
                    value = current.signatureLabel.orEmpty(),
                    onValueChange = { v -> viewModel.update { it.copy(signatureLabel = v) } },
                    label = { Text("Signature Label (e.g. Head of Department)") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )
            }
            OutlinedTextField(
                value = current.watermarkText.orEmpty(),
                onValueChange = { v -> viewModel.update { it.copy(watermarkText = v.ifBlank { null }) } },
                label = { Text("Watermark (e.g. CONFIDENTIAL, DRAFT)") },
                singleLine = true,
                modifier = Modifier.fillMaxWidth()
            )

            HorizontalDivider(modifier = Modifier.padding(vertical = 24.dp))
        }
    }
}

@Composable
private fun SectionLabel(title: String) {
    Text(text = title, style = MaterialTheme.typography.titleMedium, color = MaterialTheme.colorScheme.primary)
}

@Composable
private fun ToggleRow(label: String, checked: Boolean, onCheckedChange: (Boolean) -> Unit) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.SpaceBetween
    ) {
        Text(label, modifier = Modifier.padding(top = 12.dp))
        Switch(checked = checked, onCheckedChange = onCheckedChange)
    }
}
