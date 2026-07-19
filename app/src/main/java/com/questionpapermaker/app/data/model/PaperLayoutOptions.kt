package com.questionpapermaker.app.data.model

enum class PageSize(val displayName: String, val widthPt: Float, val heightPt: Float) {
    A4("A4", 595.28f, 841.89f),
    LETTER("Letter", 612f, 792f),
    LEGAL("Legal", 612f, 1008f),
    A5("A5", 419.53f, 595.28f)
}

enum class PageOrientation { PORTRAIT, LANDSCAPE }

enum class MarginPreset(val displayName: String, val pointsPerSide: Float) {
    SMALL("Small", 36f),
    MEDIUM("Medium", 54f),
    LARGE("Large", 72f),
    EXTRA_LARGE("Extra Large", 90f),
    CUSTOM("Custom", 54f)
}

enum class HeaderStyle(val displayName: String) {
    SIMPLE("Simple"),
    PROFESSIONAL("Professional"),
    UNIVERSITY("University Style"),
    MINIMAL("Minimal"),
    MODERN("Modern")
}

enum class FooterStyle(val displayName: String) {
    SIMPLE("Simple"),
    PROFESSIONAL("Professional"),
    UNIVERSITY("University"),
    CUSTOM("Custom")
}

enum class PageNumberPosition(val displayName: String) {
    TOP_LEFT("Top Left"),
    TOP_CENTER("Top Center"),
    TOP_RIGHT("Top Right"),
    BOTTOM_LEFT("Bottom Left"),
    BOTTOM_CENTER("Bottom Center"),
    BOTTOM_RIGHT("Bottom Right"),
    HIDDEN("Hide")
}
