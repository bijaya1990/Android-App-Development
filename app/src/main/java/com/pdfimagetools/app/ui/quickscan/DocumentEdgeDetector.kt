package com.pdfimagetools.app.ui.quickscan

import android.graphics.Bitmap
import androidx.compose.ui.geometry.Offset
import kotlin.math.abs
import kotlin.math.sqrt

/** The four corners of a (possibly skewed) document outline, in source-bitmap pixel coordinates. */
data class CropQuad(
    val topLeft: Offset,
    val topRight: Offset,
    val bottomRight: Offset,
    val bottomLeft: Offset
)

fun CropQuad.map(transform: (Offset) -> Offset): CropQuad =
    CropQuad(transform(topLeft), transform(topRight), transform(bottomRight), transform(bottomLeft))

private fun distance(a: Offset, b: Offset): Float {
    val dx = a.x - b.x
    val dy = a.y - b.y
    return sqrt(dx * dx + dy * dy)
}

/**
 * Best-effort document edge detection, run once on a still capture (never on a live camera
 * feed). For each of the four sides, scans several parallel lines inward from that edge and
 * looks for the first sizeable luminance jump — the median crossing point across those lines is
 * used as that side's inset. This is a classical, deterministic technique (no ML model, no
 * native CV library), so it won't perfectly find corners on every background, but it gives a
 * real content-aware starting rectangle rather than a fixed margin — the user can drag any
 * corner afterward to correct it exactly, including introducing skew for photos taken at an
 * angle.
 */
object DocumentEdgeDetector {

    fun detect(bitmap: Bitmap): CropQuad {
        val analysisWidth = 220
        val scale = analysisWidth.toFloat() / bitmap.width
        val analysisHeight = (bitmap.height * scale).toInt().coerceAtLeast(1)

        val small = Bitmap.createScaledBitmap(bitmap, analysisWidth, analysisHeight, true)
        val pixels = IntArray(analysisWidth * analysisHeight)
        small.getPixels(pixels, 0, analysisWidth, 0, 0, analysisWidth, analysisHeight)
        small.recycle()

        val luminance = IntArray(pixels.size)
        for (i in pixels.indices) {
            val p = pixels[i]
            val r = (p shr 16) and 0xFF
            val g = (p shr 8) and 0xFF
            val b = p and 0xFF
            luminance[i] = (r * 299 + g * 587 + b * 114) / 1000
        }

        fun at(x: Int, y: Int): Int {
            val cx = x.coerceIn(0, analysisWidth - 1)
            val cy = y.coerceIn(0, analysisHeight - 1)
            return luminance[cy * analysisWidth + cx]
        }

        val threshold = 16
        val fallbackFraction = 0.06f

        fun medianInset(maxSteps: Int, sample: (step: Int, line: Int) -> Int): Int {
            val fallback = (maxSteps * fallbackFraction).toInt().coerceAtLeast(2)
            val found = (0 until 5).map { line ->
                var prev = sample(0, line)
                var result = fallback
                for (step in 1 until maxSteps) {
                    val cur = sample(step, line)
                    if (abs(cur - prev) > threshold) {
                        result = step
                        break
                    }
                    prev = cur
                }
                result
            }.sorted()
            return found[found.size / 2]
        }

        val maxVerticalScan = analysisHeight / 2
        val maxHorizontalScan = analysisWidth / 2

        val topInset = medianInset(maxVerticalScan) { step, line ->
            at(((line + 1) * analysisWidth) / 6, step)
        }
        val bottomInset = medianInset(maxVerticalScan) { step, line ->
            at(((line + 1) * analysisWidth) / 6, analysisHeight - 1 - step)
        }
        val leftInset = medianInset(maxHorizontalScan) { step, line ->
            at(step, ((line + 1) * analysisHeight) / 6)
        }
        val rightInset = medianInset(maxHorizontalScan) { step, line ->
            at(analysisWidth - 1 - step, ((line + 1) * analysisHeight) / 6)
        }

        val invScale = 1f / scale
        val maxInsetX = bitmap.width / 3f
        val maxInsetY = bitmap.height / 3f
        val top = (topInset * invScale).coerceIn(0f, maxInsetY)
        val bottom = (bottomInset * invScale).coerceIn(0f, maxInsetY)
        val left = (leftInset * invScale).coerceIn(0f, maxInsetX)
        val right = (rightInset * invScale).coerceIn(0f, maxInsetX)

        return CropQuad(
            topLeft = Offset(left, top),
            topRight = Offset(bitmap.width - right, top),
            bottomRight = Offset(bitmap.width - right, bitmap.height - bottom),
            bottomLeft = Offset(left, bitmap.height - bottom)
        )
    }

    /** Straightens the quad region of [source] into its own upright bitmap via a projective warp. */
    fun perspectiveWarp(source: Bitmap, quad: CropQuad): Bitmap {
        val topWidth = distance(quad.topLeft, quad.topRight)
        val bottomWidth = distance(quad.bottomLeft, quad.bottomRight)
        val leftHeight = distance(quad.topLeft, quad.bottomLeft)
        val rightHeight = distance(quad.topRight, quad.bottomRight)

        val outWidth = ((topWidth + bottomWidth) / 2f).toInt().coerceAtLeast(1)
        val outHeight = ((leftHeight + rightHeight) / 2f).toInt().coerceAtLeast(1)

        val src = floatArrayOf(
            quad.topLeft.x, quad.topLeft.y,
            quad.topRight.x, quad.topRight.y,
            quad.bottomRight.x, quad.bottomRight.y,
            quad.bottomLeft.x, quad.bottomLeft.y
        )
        val dst = floatArrayOf(
            0f, 0f,
            outWidth.toFloat(), 0f,
            outWidth.toFloat(), outHeight.toFloat(),
            0f, outHeight.toFloat()
        )
        val matrix = android.graphics.Matrix()
        matrix.setPolyToPoly(src, 0, dst, 0, 4)

        val output = Bitmap.createBitmap(outWidth, outHeight, Bitmap.Config.ARGB_8888)
        val canvas = android.graphics.Canvas(output)
        val paint = android.graphics.Paint(android.graphics.Paint.ANTI_ALIAS_FLAG or android.graphics.Paint.FILTER_BITMAP_FLAG)
        canvas.drawBitmap(source, matrix, paint)
        return output
    }
}
