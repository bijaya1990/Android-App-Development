package com.pdfimagetools.app.ui.quickscan

import android.graphics.Bitmap
import androidx.compose.ui.geometry.Offset
import kotlin.math.abs
import kotlin.math.sqrt
import org.opencv.android.Utils
import org.opencv.core.Core
import org.opencv.core.Mat
import org.opencv.core.MatOfPoint
import org.opencv.core.MatOfPoint2f
import org.opencv.core.Point
import org.opencv.core.Scalar
import org.opencv.core.Size
import org.opencv.imgproc.Imgproc

/** The four corners of a (possibly skewed) document outline, in source-bitmap pixel coordinates. */
data class CropQuad(
    val topLeft: Offset,
    val topRight: Offset,
    val bottomRight: Offset,
    val bottomLeft: Offset
)

fun CropQuad.map(transform: (Offset) -> Offset): CropQuad =
    CropQuad(transform(topLeft), transform(topRight), transform(bottomRight), transform(bottomLeft))

/** Splits this quad into left/right halves along the line at [fraction] (0..1) of the way across
 * the top and bottom edges — a perspective-correct split for a skewed quad, since it follows the
 * quad's own (possibly slanted) top and bottom edges rather than a straight vertical line. Used
 * to separate an open book's two pages at the spine. */
fun CropQuad.splitAtFraction(fraction: Float): Pair<CropQuad, CropQuad> {
    fun lerp(a: Offset, b: Offset, t: Float) = Offset(a.x + (b.x - a.x) * t, a.y + (b.y - a.y) * t)
    val topGutter = lerp(topLeft, topRight, fraction)
    val bottomGutter = lerp(bottomLeft, bottomRight, fraction)
    val left = CropQuad(topLeft, topGutter, bottomGutter, bottomLeft)
    val right = CropQuad(topGutter, topRight, bottomRight, bottomGutter)
    return left to right
}

private fun distance(a: Offset, b: Offset): Float {
    val dx = a.x - b.x
    val dy = a.y - b.y
    return sqrt(dx * dx + dy * dy)
}

/**
 * Document edge detection, run once on a still capture (never on a live camera feed), using
 * OpenCV: grayscale + blur + Canny edge detection, then [Imgproc.findContours] to find candidate
 * outlines and [Imgproc.approxPolyDP] to reduce the largest plausible one to a 4-point polygon —
 * the same classical pipeline dedicated scanner apps use to find a document's corners, including
 * skew from an angled shot (this is a true quadrilateral, not an axis-aligned box). If OpenCV
 * fails to load on a device, or no 4-sided contour large enough to plausibly be the document is
 * found, [detect] falls back to a small fixed margin so the capture flow never breaks — either
 * way, the result only pre-fills [CropAdjustScreen] for the user to confirm or drag-correct.
 */
object DocumentEdgeDetector {

    private const val ANALYSIS_WIDTH = 700.0
    private const val MIN_CONTOUR_AREA_FRACTION = 0.15
    private const val MAX_CONTOUR_AREA_FRACTION = 0.94
    private const val APPROX_EPSILON_FRACTION = 0.02
    private const val FALLBACK_MARGIN_FRACTION = 0.03f
    private const val BORDER_SIZE = 8
    private const val GUTTER_ANALYSIS_WIDTH = 400
    private const val GUTTER_SEARCH_BAND_FRACTION = 0.30f
    private const val GUTTER_MIN_PEAK_RATIO = 2.2f

    // Reused across every call instead of allocated per-detection; lives for the process lifetime.
    private val dilateKernel: Mat by lazy { Imgproc.getStructuringElement(Imgproc.MORPH_RECT, Size(3.0, 3.0)) }

    fun detect(bitmap: Bitmap): CropQuad {
        return try {
            detectWithOpenCv(bitmap) ?: fallbackQuad(bitmap.width, bitmap.height)
        } catch (_: Throwable) {
            // OpenCV missing/failed to load on this device/ABI, or a pathological input image —
            // never let a detection failure break the capture flow.
            fallbackQuad(bitmap.width, bitmap.height)
        }
    }

    private fun detectWithOpenCv(bitmap: Bitmap): CropQuad? {
        val source = Mat()
        Utils.bitmapToMat(bitmap, source)

        val scale = ANALYSIS_WIDTH / source.cols()
        val resized = Mat()
        Imgproc.resize(source, resized, Size(ANALYSIS_WIDTH, source.rows() * scale))
        source.release()

        val gray = Mat()
        Imgproc.cvtColor(resized, gray, Imgproc.COLOR_RGBA2GRAY)

        val blurred = Mat()
        Imgproc.GaussianBlur(gray, blurred, Size(5.0, 5.0), 0.0)
        gray.release()

        val edges = Mat()
        Imgproc.Canny(blurred, edges, 50.0, 150.0)
        blurred.release()

        val dilated = Mat()
        Imgproc.dilate(edges, dilated, dilateKernel)
        edges.release()

        val analysisArea = resized.rows().toDouble() * resized.cols()
        resized.release()

        // A resized photo's own border is a perfectly straight, perfectly closed rectangle —
        // exactly what a contour search is looking for. Without this padding, findContours can
        // (and often does) latch onto the raw frame edge itself instead of the document inside
        // it, which is precisely the "detects the whole camera area" failure this padding fixes:
        // it puts a black margin around the real content so no genuine edge touches pixel (0,0),
        // forcing every contour to close using only real detected edges.
        Core.copyMakeBorder(dilated, dilated, BORDER_SIZE, BORDER_SIZE, BORDER_SIZE, BORDER_SIZE, Core.BORDER_CONSTANT, Scalar(0.0))

        val contours = mutableListOf<MatOfPoint>()
        val hierarchy = Mat()
        Imgproc.findContours(dilated, contours, hierarchy, Imgproc.RETR_EXTERNAL, Imgproc.CHAIN_APPROX_SIMPLE)
        dilated.release()
        hierarchy.release()

        var bestCorners: Array<Point>? = null
        var bestArea = 0.0

        for (contour in contours) {
            val area = Imgproc.contourArea(contour)
            if (area >= analysisArea * MIN_CONTOUR_AREA_FRACTION && area <= analysisArea * MAX_CONTOUR_AREA_FRACTION) {
                val contour2f = MatOfPoint2f(*contour.toArray())
                val perimeter = Imgproc.arcLength(contour2f, true)
                val approx = MatOfPoint2f()
                Imgproc.approxPolyDP(contour2f, approx, APPROX_EPSILON_FRACTION * perimeter, true)
                contour2f.release()

                if (approx.total() == 4L && area > bestArea) {
                    bestArea = area
                    bestCorners = approx.toArray()
                }
                approx.release()
            }
            contour.release()
        }

        val corners = bestCorners ?: return null
        val ordered = orderCorners(corners)
        val invScale = 1f / scale.toFloat()
        fun toOriginal(p: Point) = Offset(((p.x - BORDER_SIZE) * invScale).toFloat(), ((p.y - BORDER_SIZE) * invScale).toFloat())
        return CropQuad(
            topLeft = toOriginal(ordered[0]),
            topRight = toOriginal(ordered[1]),
            bottomRight = toOriginal(ordered[2]),
            bottomLeft = toOriginal(ordered[3])
        )
    }

    /** Sorts 4 unordered polygon corners into [topLeft, topRight, bottomRight, bottomLeft] by
     * the standard sum/difference trick: top-left has the smallest x+y, bottom-right the largest;
     * of the remaining two, top-right has the largest x-y and bottom-left the smallest. */
    private fun orderCorners(points: Array<Point>): List<Point> {
        val indices = points.indices.toList()
        val topLeftIdx = indices.minBy { points[it].x + points[it].y }
        val bottomRightIdx = indices.maxBy { points[it].x + points[it].y }
        val remainingIdx = indices.filter { it != topLeftIdx && it != bottomRightIdx }
        val topRightIdx = remainingIdx.maxBy { points[it].x - points[it].y }
        val bottomLeftIdx = remainingIdx.minBy { points[it].x - points[it].y }
        return listOf(points[topLeftIdx], points[topRightIdx], points[bottomRightIdx], points[bottomLeftIdx])
    }

    /**
     * If [straightenedPage] (already perspective-corrected to the outer document quad) looks like
     * an open book spread — two pages joined at a visible vertical spine/gutter near the middle —
     * returns the x-fraction (0..1) of that gutter. Returns null if no confident central line is
     * found, meaning this looks like a single page rather than a spread. This is a plain
     * luminance-gradient projection restricted to the central band (same technique the original
     * axis-aligned edge search used): every pixel in a column votes into that column's score, and
     * the strongest peak — if it clears the surrounding average by a wide margin — is taken as the
     * spine. No OpenCV needed here since the page is already straightened, so "vertical" really is
     * vertical in image space.
     */
    fun findBookGutterFraction(straightenedPage: Bitmap): Float? {
        val width = GUTTER_ANALYSIS_WIDTH
        val scale = width.toFloat() / straightenedPage.width
        val height = (straightenedPage.height * scale).toInt().coerceAtLeast(1)

        val small = Bitmap.createScaledBitmap(straightenedPage, width, height, true)
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

        val bandStart = (width * GUTTER_SEARCH_BAND_FRACTION).toInt()
        val bandEnd = (width * (1f - GUTTER_SEARCH_BAND_FRACTION)).toInt()
        if (bandEnd <= bandStart) return null

        var bestX = -1
        var bestValue = 0f
        var total = 0f
        for (x in bandStart until bandEnd) {
            var sum = 0f
            for (y in 0 until height) {
                sum += abs(at(x + 1, y) - at(x - 1, y))
            }
            total += sum
            if (sum > bestValue) {
                bestValue = sum
                bestX = x
            }
        }

        val average = total / (bandEnd - bandStart)
        return if (bestX >= 0 && bestValue > average * GUTTER_MIN_PEAK_RATIO) bestX.toFloat() / width else null
    }

    private fun fallbackQuad(width: Int, height: Int): CropQuad {
        val marginX = width * FALLBACK_MARGIN_FRACTION
        val marginY = height * FALLBACK_MARGIN_FRACTION
        return CropQuad(
            topLeft = Offset(marginX, marginY),
            topRight = Offset(width - marginX, marginY),
            bottomRight = Offset(width - marginX, height - marginY),
            bottomLeft = Offset(marginX, height - marginY)
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
