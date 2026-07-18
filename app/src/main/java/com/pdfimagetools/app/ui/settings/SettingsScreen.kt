package com.pdfimagetools.app.ui.settings

import android.content.ActivityNotFoundException
import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CleaningServices
import androidx.compose.material.icons.filled.PrivacyTip
import androidx.compose.material.icons.filled.StarRate
import androidx.compose.material3.Card
import androidx.compose.material3.FilterChip
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Slider
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
import com.pdfimagetools.app.core.storage.SaveTarget
import com.pdfimagetools.app.ui.components.AppTopBar
import com.pdfimagetools.app.ui.components.BannerAdView
import com.pdfimagetools.app.ui.components.SectionHeader
import com.pdfimagetools.app.ui.theme.TextSecondary
import com.pdfimagetools.app.util.FileUtils

// TODO: replace with the app's real, hosted privacy policy URL before release.
private const val PRIVACY_POLICY_URL = "https://www.pdfimagetools.app/privacy-policy"

@Composable
fun SettingsScreen(onBack: () -> Unit) {
    val viewModel: SettingsViewModel = viewModel(
        factory = SimpleViewModelFactory { SettingsViewModel(ServiceLocator.settingsStore, ServiceLocator.storageManager) }
    )
    val settings by viewModel.settings.collectAsState()
    val cacheSize by viewModel.cacheSizeBytes.collectAsState()
    val context = LocalContext.current

    Scaffold(
        topBar = { AppTopBar(title = "Settings", onBack = onBack) },
        bottomBar = { BannerAdView() }
    ) { padding ->
        LazyColumn(
            modifier = Modifier.fillMaxSize().padding(padding),
            contentPadding = PaddingValues(16.dp),
            verticalArrangement = Arrangement.spacedBy(24.dp)
        ) {
            item {
                Column {
                    SectionHeader("Default save location")
                    Text(
                        "Where compressed and converted photos are saved. PDFs always save to Downloads.",
                        style = MaterialTheme.typography.bodySmall,
                        color = TextSecondary,
                        modifier = Modifier.padding(bottom = 12.dp)
                    )
                    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        FilterChip(
                            selected = settings.defaultImageSaveTarget == SaveTarget.PICTURES,
                            onClick = { viewModel.setDefaultImageSaveTarget(SaveTarget.PICTURES) },
                            label = { Text("Pictures") }
                        )
                        FilterChip(
                            selected = settings.defaultImageSaveTarget == SaveTarget.DOWNLOADS,
                            onClick = { viewModel.setDefaultImageSaveTarget(SaveTarget.DOWNLOADS) },
                            label = { Text("Downloads") }
                        )
                    }
                }
            }

            item {
                Column {
                    SectionHeader("Default output quality")
                    Text(
                        "${settings.defaultQualityPercent}% — used as the starting quality for Compress image.",
                        style = MaterialTheme.typography.bodySmall,
                        color = TextSecondary
                    )
                    Slider(
                        value = settings.defaultQualityPercent.toFloat(),
                        onValueChange = { viewModel.setDefaultQuality(it.toInt()) },
                        valueRange = 10f..100f,
                        steps = 8
                    )
                }
            }

            item {
                Column {
                    SectionHeader("Storage")
                    SettingsRow(
                        icon = Icons.Filled.CleaningServices,
                        title = "Clear cache",
                        subtitle = "Frees ${FileUtils.readableSize(cacheSize)} of temporary work files",
                        onClick = { viewModel.clearCache() }
                    )
                }
            }

            item {
                Column {
                    SectionHeader("About")
                    SettingsRow(
                        icon = Icons.Filled.StarRate,
                        title = "Rate this app",
                        subtitle = "Enjoying Z Scanner? Leave a review",
                        onClick = {
                            val uri = Uri.parse("market://details?id=${context.packageName}")
                            try {
                                context.startActivity(Intent(Intent.ACTION_VIEW, uri))
                            } catch (e: ActivityNotFoundException) {
                                val webUri = Uri.parse("https://play.google.com/store/apps/details?id=${context.packageName}")
                                context.startActivity(Intent(Intent.ACTION_VIEW, webUri))
                            }
                        }
                    )
                    SettingsRow(
                        icon = Icons.Filled.PrivacyTip,
                        title = "Privacy policy",
                        subtitle = PRIVACY_POLICY_URL,
                        onClick = {
                            runCatching {
                                context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(PRIVACY_POLICY_URL)))
                            }
                        }
                    )
                }
            }
        }
    }
}

@Composable
private fun SettingsRow(
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    title: String,
    subtitle: String,
    onClick: () -> Unit
) {
    Card(
        shape = RoundedCornerShape(12.dp),
        modifier = Modifier.fillMaxWidth().padding(top = 8.dp).clickable(onClick = onClick)
    ) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(12.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Icon(icon, contentDescription = null, tint = MaterialTheme.colorScheme.primary)
            Column(modifier = Modifier.padding(start = 12.dp)) {
                Text(title, style = MaterialTheme.typography.bodyMedium)
                Text(subtitle, style = MaterialTheme.typography.bodySmall, color = TextSecondary)
            }
        }
    }
}
