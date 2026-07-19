package com.questionpapermaker.app.ui.theme

import androidx.compose.ui.graphics.Color

/**
 * "Academic Blue" palette -- ported from the Stitch design system (academic_utility_system).
 * Corporate Modernism / document-centric utility: white "paper canvas" surfaces on a soft
 * gray background, restrained borders instead of heavy shadows.
 */

// Light theme
val LightPrimary = Color(0xFF1A73E8)
val LightOnPrimary = Color(0xFFFFFFFF)
val LightPrimaryContainer = Color(0xFFD2E3FC)
val LightOnPrimaryContainer = Color(0xFF0842A0)
val LightSecondary = Color(0xFF005AC1)
val LightOnSecondaryContainer = Color(0xFF00285C)
val LightBackground = Color(0xFFF8F9FA)
val LightSurface = Color(0xFFFFFFFF)
val LightSurfaceVariant = Color(0xFFF1F3F4)
val LightOnSurface = Color(0xFF202124)
val LightOnSurfaceVariant = Color(0xFF5F6368)
val LightOutline = Color(0xFFDADCE0)
val LightOutlineVariant = Color(0xFFE8EAED)
val LightError = Color(0xFFBA1A1A)
val LightErrorContainer = Color(0xFFFFDAD6)
val LightOnErrorContainer = Color(0xFF93000A)
val LightTertiaryContainer = Color(0xFFD2E3FC)
val LightOnTertiaryContainer = Color(0xFF0842A0)

// Dark theme (derived to keep the same relationships; the source design is light-only)
val DarkPrimary = Color(0xFFA8C7FA)
val DarkOnPrimary = Color(0xFF00315C)
val DarkPrimaryContainer = Color(0xFF0842A0)
val DarkOnPrimaryContainer = Color(0xFFD2E3FC)
val DarkSecondary = Color(0xFF9DC5FF)
val DarkBackground = Color(0xFF131314)
val DarkSurface = Color(0xFF1E1F20)
val DarkSurfaceVariant = Color(0xFF2A2C2E)
val DarkOnSurface = Color(0xFFE3E2E6)
val DarkOnSurfaceVariant = Color(0xFFC4C6C8)
val DarkOutline = Color(0xFF444649)
val DarkOutlineVariant = Color(0xFF303133)
val DarkError = Color(0xFFFFB4AB)
val DarkErrorContainer = Color(0xFF93000A)
val DarkOnErrorContainer = Color(0xFFFFDAD6)
val DarkTertiaryContainer = Color(0xFF0842A0)
val DarkOnTertiaryContainer = Color(0xFFD2E3FC)

/** Rotating accent colors for Recent Paper cards' left edge + subject chip, keyed by subject text. */
val SubjectAccentColors = listOf(
    Color(0xFF1A73E8), // blue
    Color(0xFF9334E6), // purple
    Color(0xFFE8710A), // orange
    Color(0xFF188038), // green
    Color(0xFFD01884), // pink
    Color(0xFFBA1A1A) // red
)

fun subjectAccentColor(seed: String): Color {
    if (seed.isBlank()) return SubjectAccentColors.first()
    val index = (seed.trim().lowercase().hashCode().let { if (it < 0) -it else it }) % SubjectAccentColors.size
    return SubjectAccentColors[index]
}
