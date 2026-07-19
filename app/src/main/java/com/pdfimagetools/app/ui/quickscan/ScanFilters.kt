package com.pdfimagetools.app.ui.quickscan

import android.graphics.Bitmap
import android.graphics.Canvas
import android.graphics.ColorMatrixColorFilter
import android.graphics.Paint

/**
 * The look applied to a scanned page, matching the Original/Color/Grayscale/B&W modes common to
 * dedicated scanner apps. Every mode is expressed as one contrast+saturation pair so the live
 * preview in [CropAdjustScreen] and the bitmap actually saved always match exactly — both read
 * the same [colorMatrixValues].
 */
enum class ScanFilter(val label: String, val contrast: Float, val saturation: Float) {
    ORIGINAL("Original", contrast = 1f, saturation = 1f),
    COLOR("Color", contrast = 1.12f, saturation = 1.15f),
    GRAYSCALE("Grayscale", contrast = 1.08f, saturation = 0f),
    BLACK_AND_WHITE("B&W", contrast = 6f, saturation = 0f)
}

/**
 * Builds a standard 4x5 contrast+saturation color matrix (Android's row-major
 * android.graphics.ColorMatrix layout, which androidx.compose.ui.graphics.ColorMatrix also
 * accepts as-is) for this filter. Saturation uses the usual luminance-preserving weights; contrast
 * is a linear scale+offset applied after it, which is why [BLACK_AND_WHITE] can reuse the same
 * formula with a very steep contrast to approximate a hard black/white threshold.
 */
fun ScanFilter.colorMatrixValues(): FloatArray {
    val translate = (-0.5f * contrast + 0.5f) * 255f
    val invSat = 1f - saturation
    val lumR = 0.213f * invSat
    val lumG = 0.715f * invSat
    val lumB = 0.072f * invSat
    return floatArrayOf(
        contrast * (lumR + saturation), contrast * lumG, contrast * lumB, 0f, translate,
        contrast * lumR, contrast * (lumG + saturation), contrast * lumB, 0f, translate,
        contrast * lumR, contrast * lumG, contrast * (lumB + saturation), 0f, translate,
        0f, 0f, 0f, 1f, 0f
    )
}

/**
 * Applies [filter] to a full-resolution page bitmap and recycles [source]. COLOR and
 * BLACK_AND_WHITE route through [ImageEnhancer]'s real OpenCV pipeline (CLAHE, and background
 * illumination correction + adaptive threshold, respectively) for a genuinely enhanced result;
 * ORIGINAL/GRAYSCALE stay on the cheap [colorMatrixValues] path since there's nothing more to
 * compute for them. The live preview in [CropAdjustScreen] always uses the fast ColorMatrix
 * approximation for every filter — including COLOR/B&W — so it stays responsive; only the
 * final saved page pays for the heavier OpenCV processing.
 */
fun applyScanFilter(source: Bitmap, filter: ScanFilter): Bitmap {
    return when (filter) {
        ScanFilter.COLOR -> {
            val result = ImageEnhancer.claheEnhance(source)
            source.recycle()
            result
        }
        ScanFilter.BLACK_AND_WHITE -> {
            val result = ImageEnhancer.adaptiveThresholdDocument(source)
            source.recycle()
            result
        }
        ScanFilter.ORIGINAL, ScanFilter.GRAYSCALE -> {
            val output = Bitmap.createBitmap(source.width, source.height, Bitmap.Config.ARGB_8888)
            val canvas = Canvas(output)
            val paint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
                colorFilter = ColorMatrixColorFilter(filter.colorMatrixValues())
            }
            canvas.drawBitmap(source, 0f, 0f, paint)
            source.recycle()
            output
        }
    }
}
