package com.pdfimagetools.app.ui.theme

import androidx.compose.ui.graphics.Color

// "Precision Scan" design system — see DESIGN.md.
val Background = Color(0xFFF9F9F9)
val Surface = Color(0xFFF9F9F9)
val SurfaceContainerLowest = Color(0xFFFFFFFF)
val SurfaceContainerLow = Color(0xFFF3F3F3)
val SurfaceContainer = Color(0xFFEEEEEE)
val SurfaceContainerHigh = Color(0xFFE8E8E8)
val SurfaceContainerHighest = Color(0xFFE2E2E2)

val OnBackground = Color(0xFF1A1C1C)
val OnSurface = Color(0xFF1A1C1C)
val OnSurfaceVariant = Color(0xFF3C494C)

val Outline = Color(0xFF6C797C)
val OutlineVariant = Color(0xFFBBC9CC)

val Primary = Color(0xFF006876)
val OnPrimary = Color(0xFFFFFFFF)
val PrimaryContainer = Color(0xFF00BCD4)
val OnPrimaryContainer = Color(0xFF004650)

val Secondary = Color(0xFF5E5E5E)
val OnSecondary = Color(0xFFFFFFFF)
val SecondaryContainer = Color(0xFFE3E2E2)
val OnSecondaryContainer = Color(0xFF646464)

val Tertiary = Color(0xFF5F5E5E)
val TertiaryContainer = Color(0xFFADABAB)

val Error = Color(0xFFBA1A1A)
val OnError = Color(0xFFFFFFFF)
val ErrorContainer = Color(0xFFFFDAD6)
val OnErrorContainer = Color(0xFF93000A)

/** Deep neutral used only for the camera viewfinder background, per DESIGN.md. */
val ViewfinderBackground = Color(0xFF212121)

// Legacy aliases kept so existing call sites (Accent, TextPrimary, ...) keep compiling.
val Accent = Primary
val AccentDark = OnPrimaryContainer
val AccentContainer = PrimaryContainer
val TextPrimary = OnSurface
val TextSecondary = OnSurfaceVariant
val Divider = OutlineVariant
val Success = Color(0xFF16A34A)
val Danger = Error
