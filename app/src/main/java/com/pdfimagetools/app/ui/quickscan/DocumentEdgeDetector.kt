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
 * feed). Uses a projection-profile method: for each row, sum the vertical-luminance-gradient
 * magnitude across the *entire* row width into one "horizontal edge strength" score, and
 * symmetrically sum the horizontal-gradient magnitude down each column into a "vertical edge
 * strength" score. A document's straight edge shows up as a sharp peak in that profile — this is
 * effectively a simplified Hough-line search restricted to axis-aligned lines, and because every
 * pixel in the row/column votes, it is far more robust to a few noisy pixels or a busy background
 * than sampling a handful of individual scan lines. It's still a classical, deterministic
 * technique (no ML model, no native CV library) so a highly textured background can still fool
 * it, which is exactly why the result only pre-fills [CropAdjustScreen] rather than being applied
 * silently — the user can drag any corner to correct it, including introducing skew.
 */
object DocumentEdgeDetector {

    private const val ANALYSIS_WIDTH = 360
    private const val SEARCH_BAND_FRACTION = 0.45f
    private const val MIN_PEAK_RATIO = 1.5f
    private const val FALLBACK_MARGIN_FRACTION = 0.03f

    fun detect(bitmap: Bitmap): CropQuad {
        val scale = ANALYSIS_WIDTH.toFloat() / bitmap.width
        val width = ANALYSIS_WIDTH
        val height = (bitmap.height * scale).toInt().coerceAtLeast(1)

        val small = Bitmap.createScaledBitmap(bitmap, width, height, true)
        val pixels = IntArray(width * height)
        small.getPixels(pixels, 0, width, 0, 0, width, height)
        small.recycle()

        val luminance = FloatArray(pixels.size)
        for (i in pixels.indices) {
            val p = pixels[i]
            val r = (p shr 16) and 0xFF
            val g = (p shr 8) and 0xFF
            val b = p and 0xFF
            luminance[i] = (r * 299 + g * 587 + b * 114) / 1000f
        }

        fun at(x: Int, y: Int): Float {
            val cx = x.coerceIn(0, width - 1)
            val cy = y.coerceIn(0, height - 1)
            return luminance[cy * width + cx]
        }

        val rowEnergy = FloatArray(height)
        for (y in 0 until height) {
            var sum = 0f
            for (x in 0 until width) {
                sum += abs(at(x, y + 1) - at(x, y - 1))
            }
            rowEnergy[y] = sum
        }
        val colEnergy = FloatArray(width)
        for (x in 0 until width) {
            var sum = 0f
            for (y in 0 until height) {
                sum += abs(at(x + 1, y) - at(x - 1, y))
            }
            colEnergy[x] = sum
        }

        fun strongestPeak(energy: FloatArray, range: IntRange, fallback: Int): Int {
            var bestIndex = -1
            var bestValue = 0f
            var total = 0f
            for (i in range) {
                total += energy[i]
                if (energy[i] > bestValue) {
                    bestValue = energy[i]
                    bestIndex = i
                }
            }
            val average = total / range.count().coerceAtLeast(1)
            return if (bestIndex >= 0 && bestValue > average * MIN_PEAK_RATIO) bestIndex else fallback
        }

        val topBandEnd = (height * SEARCH_BAND_FRACTION).toInt().coerceAtLeast(3)
        val bottomBandStart = (height * (1f - SEARCH_BAND_FRACTION)).toInt().coerceAtMost(height - 3)
        val leftBandEnd = (width * SEARCH_BAND_FRACTION).toInt().coerceAtLeast(3)
        val rightBandStart = (width * (1f - SEARCH_BAND_FRACTION)).toInt().coerceAtMost(width - 3)

        val fallbackTop = (height * FALLBACK_MARGIN_FRACTION).toInt()
        val fallbackBottom = height - 1 - (height * FALLBACK_MARGIN_FRACTION).toInt()
        val fallbackLeft = (width * FALLBACK_MARGIN_FRACTION).toInt()
        val fallbackRight = width - 1 - (width * FALLBACK_MARGIN_FRACTION).toInt()

        val topY = strongestPeak(rowEnergy, 2 until topBandEnd, fallbackTop)
        val bottomY = strongestPeak(rowEnergy, bottomBandStart until (height - 2), fallbackBottom)
        val leftX = strongestPeak(colEnergy, 2 until leftBandEnd, fallbackLeft)
        val rightX = strongestPeak(colEnergy, rightBandStart until (width - 2), fallbackRight)

        val invScale = 1f / scale
        return CropQuad(
            topLeft = Offset(leftX * invScale, topY * invScale),
            topRight = Offset(rightX * invScale, topY * invScale),
            bottomRight = Offset(rightX * invScale, bottomY * invScale),
            bottomLeft = Offset(leftX * invScale, bottomY * invScale)
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
