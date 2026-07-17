package com.pdfimagetools.app.ui.theme

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable

private val AppColorScheme = lightColorScheme(
    primary = Accent,
    onPrimary = Surface,
    primaryContainer = AccentContainer,
    onPrimaryContainer = Accent,
    secondary = Accent,
    background = Background,
    onBackground = TextPrimary,
    surface = Surface,
    onSurface = TextPrimary,
    surfaceVariant = Background,
    onSurfaceVariant = TextSecondary,
    outline = Divider,
    error = Danger
)

/**
 * The product spec calls for a single clean light "office" theme regardless of system setting,
 * so dark theme is intentionally not wired up here.
 */
@Composable
fun PdfImageToolsTheme(
    darkTheme: Boolean = isSystemInDarkTheme(),
    content: @Composable () -> Unit
) {
    MaterialTheme(
        colorScheme = AppColorScheme,
        typography = AppTypography,
        content = content
    )
}
