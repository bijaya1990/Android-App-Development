package com.pdfimagetools.app.ui.quickscan

import android.graphics.Bitmap
import androidx.compose.ui.geometry.Offset
import kotlin.math.sqrt
import org.opencv.android.Utils
import org.opencv.core.Mat
import org.opencv.core.MatOfPoint
import org.opencv.core.MatOfPoint2f
import org.opencv.core.Point
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
    private const val APPROX_EPSILON_FRACTION = 0.02
    private const val FALLBACK_MARGIN_FRACTION = 0.03f

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

        val contours = mutableListOf<MatOfPoint>()
        val hierarchy = Mat()
        Imgproc.findContours(dilated, contours, hierarchy, Imgproc.RETR_LIST, Imgproc.CHAIN_APPROX_SIMPLE)
        dilated.release()
        hierarchy.release()

        val analysisArea = resized.rows().toDouble() * resized.cols()
        resized.release()

        var bestCorners: Array<Point>? = null
        var bestArea = 0.0

        for (contour in contours) {
            val area = Imgproc.contourArea(contour)
            if (area >= analysisArea * MIN_CONTOUR_AREA_FRACTION) {
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
        return CropQuad(
            topLeft = Offset((ordered[0].x * invScale).toFloat(), (ordered[0].y * invScale).toFloat()),
            topRight = Offset((ordered[1].x * invScale).toFloat(), (ordered[1].y * invScale).toFloat()),
            bottomRight = Offset((ordered[2].x * invScale).toFloat(), (ordered[2].y * invScale).toFloat()),
            bottomLeft = Offset((ordered[3].x * invScale).toFloat(), (ordered[3].y * invScale).toFloat())
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
