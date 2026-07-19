package com.pdfimagetools.app.ui.quickscan

import android.graphics.Bitmap
import androidx.compose.ui.geometry.Offset
import kotlin.math.abs
import kotlin.math.atan2
import kotlin.math.hypot
import kotlin.math.sqrt
import org.opencv.android.Utils
import org.opencv.core.Core
import org.opencv.core.Mat
import org.opencv.core.MatOfPoint
import org.opencv.core.MatOfPoint2f
import org.opencv.core.Point
import org.opencv.core.RotatedRect
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
    private const val ANALYSIS_WIDTH_SECONDARY = 500.0
    private const val MIN_CONTOUR_AREA_FRACTION = 0.15
    private const val MAX_CONTOUR_AREA_FRACTION = 0.94
    private const val APPROX_EPSILON_FRACTION = 0.02
    private const val FALLBACK_MARGIN_FRACTION = 0.03f
    private const val BORDER_SIZE = 8
    private const val GUTTER_ANALYSIS_WIDTH = 400
    private const val GUTTER_SEARCH_BAND_FRACTION = 0.30f
    private const val GUTTER_MIN_PEAK_RATIO = 2.2f
    private const val HOUGH_THRESHOLD = 60
    private const val HOUGH_MIN_LINE_LENGTH_FRACTION = 0.25
    private const val HOUGH_MAX_LINE_GAP = 20.0
    private const val HOUGH_ANGLE_TOLERANCE_DEGREES = 20.0
    private const val MIN_BOUNDING_DIMENSION_FRACTION = 0.25

    // Reused across every call instead of allocated per-detection; lives for the process lifetime.
    private val dilateKernel: Mat by lazy { Imgproc.getStructuringElement(Imgproc.MORPH_RECT, Size(3.0, 3.0)) }
    private val closeKernel: Mat by lazy { Imgproc.getStructuringElement(Imgproc.MORPH_RECT, Size(7.0, 7.0)) }

    private data class ScoredQuad(val quad: CropQuad, val score: Double)

    /** Runs every detection method and keeps the best-scoring result, rather than stopping at the
     * first one that finds anything: a busy background can make the primary scale return a
     * technically-plausible but mediocre quad while the secondary scale or the Hough fallback
     * would have found the real, more rectangular document edge. Score is contour area times
     * rectangularity (how closely the shape matches its own minimum-area bounding rectangle), so
     * a large, clean rectangle always beats a smaller or more irregular candidate. */
    fun detect(bitmap: Bitmap): CropQuad {
        return try {
            val candidates = listOfNotNull(
                detectWithOpenCv(bitmap, ANALYSIS_WIDTH),
                detectWithOpenCv(bitmap, ANALYSIS_WIDTH_SECONDARY),
                detectWithHoughLines(bitmap)
            )
            candidates.maxByOrNull { it.score }?.quad ?: fallbackQuad(bitmap.width, bitmap.height)
        } catch (_: Throwable) {
            // OpenCV missing/failed to load on this device/ABI, or a pathological input image —
            // never let a detection failure break the capture flow.
            fallbackQuad(bitmap.width, bitmap.height)
        }
    }

    /** How closely [corners] matches its own minimum-area rotated bounding rectangle (1.0 =
     * perfect fit). A real document quad, even photographed at a steep angle, hugs its own
     * tightest rotated rectangle closely; a spurious quad from background clutter typically
     * doesn't, which is what makes this a useful tie-breaker beyond raw area. */
    private fun rectangularity(corners: Array<Point>, contourArea: Double): Double {
        val points2f = MatOfPoint2f(*corners)
        val rotatedRect: RotatedRect = Imgproc.minAreaRect(points2f)
        points2f.release()
        val rectArea = rotatedRect.size.width * rotatedRect.size.height
        if (rectArea <= 0.0) return 0.0
        return (contourArea / rectArea).coerceIn(0.0, 1.0)
    }

    /** Primary detection pass at a given analysis scale — trying a second, different scale when
     * the first finds nothing is a cheap way to recover from a contour that only closes cleanly
     * at one resolution (a real risk with JPEG noise/compression artifacts). */
    private fun detectWithOpenCv(bitmap: Bitmap, analysisWidth: Double): ScoredQuad? {
        val source = Mat()
        Utils.bitmapToMat(bitmap, source)

        val scale = analysisWidth / source.cols()
        val resized = Mat()
        Imgproc.resize(source, resized, Size(analysisWidth, source.rows() * scale))
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

        // Morphological closing (dilate-then-erode as one step, over a larger kernel) bridges
        // small gaps left in the edge map — e.g. where the page edge briefly loses contrast
        // against the background — so the contour search sees one closed outline instead of
        // several broken segments.
        Imgproc.morphologyEx(dilated, dilated, Imgproc.MORPH_CLOSE, closeKernel)

        val resizedWidth = resized.cols()
        val resizedHeight = resized.rows()
        val analysisArea = resizedHeight.toDouble() * resizedWidth
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
        var bestScore = 0.0

        for (contour in contours) {
            val area = Imgproc.contourArea(contour)
            if (area >= analysisArea * MIN_CONTOUR_AREA_FRACTION && area <= analysisArea * MAX_CONTOUR_AREA_FRACTION) {
                val contour2f = MatOfPoint2f(*contour.toArray())
                val perimeter = Imgproc.arcLength(contour2f, true)
                val approx = MatOfPoint2f()
                Imgproc.approxPolyDP(contour2f, approx, APPROX_EPSILON_FRACTION * perimeter, true)
                contour2f.release()

                if (approx.total() == 4L) {
                    val candidate = approx.toArray()
                    if (isPlausibleDocumentQuad(candidate, resizedWidth, resizedHeight)) {
                        val score = area * rectangularity(candidate, area)
                        if (score > bestScore) {
                            bestScore = score
                            bestCorners = candidate
                        }
                    }
                }
                approx.release()
            }
            contour.release()
        }

        val corners = bestCorners ?: return null
        val ordered = orderCorners(corners)
        val invScale = 1f / scale.toFloat()
        fun toOriginal(p: Point) = Offset(((p.x - BORDER_SIZE) * invScale).toFloat(), ((p.y - BORDER_SIZE) * invScale).toFloat())
        val quad = CropQuad(
            topLeft = toOriginal(ordered[0]),
            topRight = toOriginal(ordered[1]),
            bottomRight = toOriginal(ordered[2]),
            bottomLeft = toOriginal(ordered[3])
        )
        // Normalize score to a 0..1-ish scale (fraction of analysis frame area) so it's
        // comparable against candidates found at a different analysis scale or via Hough lines.
        return ScoredQuad(quad, bestScore / analysisArea)
    }

    private data class LineSegment(val x1: Double, val y1: Double, val x2: Double, val y2: Double) {
        val angleDegrees: Double get() = Math.toDegrees(atan2(y2 - y1, x2 - x1))
        val length: Double get() = hypot(x2 - x1, y2 - y1)
    }

    /** Last-resort fallback when contour search finds no clean 4-sided outline at either scale:
     * Hough line detection finds straight segments directly, groups them into roughly-horizontal
     * and roughly-vertical, picks the longest candidate near each of the four sides, and
     * intersects adjacent pairs of lines to get the corners. More forgiving than the contour path
     * when the page's edges are individually clear but never form one unbroken outline (e.g. a
     * gap where the edge crosses a similarly-lit background). */
    private fun detectWithHoughLines(bitmap: Bitmap): ScoredQuad? {
        val source = Mat()
        Utils.bitmapToMat(bitmap, source)

        val scale = ANALYSIS_WIDTH / source.cols()
        val resized = Mat()
        Imgproc.resize(source, resized, Size(ANALYSIS_WIDTH, source.rows() * scale))
        source.release()

        val gray = Mat()
        Imgproc.cvtColor(resized, gray, Imgproc.COLOR_RGBA2GRAY)
        val width = resized.cols()
        val height = resized.rows()
        resized.release()

        val blurred = Mat()
        Imgproc.GaussianBlur(gray, blurred, Size(5.0, 5.0), 0.0)
        gray.release()

        val edges = Mat()
        Imgproc.Canny(blurred, edges, 50.0, 150.0)
        blurred.release()

        val lines = Mat()
        Imgproc.HoughLinesP(edges, lines, 1.0, Math.PI / 180, HOUGH_THRESHOLD, width * HOUGH_MIN_LINE_LENGTH_FRACTION, HOUGH_MAX_LINE_GAP)
        edges.release()

        val segments = mutableListOf<LineSegment>()
        for (i in 0 until lines.rows()) {
            val row = lines.get(i, 0)
            segments += LineSegment(row[0], row[1], row[2], row[3])
        }
        lines.release()
        if (segments.isEmpty()) return null

        fun isHorizontal(s: LineSegment) = abs(s.angleDegrees).let { it < HOUGH_ANGLE_TOLERANCE_DEGREES || it > 180 - HOUGH_ANGLE_TOLERANCE_DEGREES }
        fun isVertical(s: LineSegment) = abs(abs(s.angleDegrees) - 90) < HOUGH_ANGLE_TOLERANCE_DEGREES
        fun midY(s: LineSegment) = (s.y1 + s.y2) / 2
        fun midX(s: LineSegment) = (s.x1 + s.x2) / 2

        val horizontals = segments.filter(::isHorizontal)
        val verticals = segments.filter(::isVertical)

        val top = horizontals.filter { midY(it) < height * 0.5 }.maxByOrNull { it.length } ?: return null
        val bottom = horizontals.filter { midY(it) >= height * 0.5 }.maxByOrNull { it.length } ?: return null
        val left = verticals.filter { midX(it) < width * 0.5 }.maxByOrNull { it.length } ?: return null
        val right = verticals.filter { midX(it) >= width * 0.5 }.maxByOrNull { it.length } ?: return null

        fun intersect(a: LineSegment, b: LineSegment): Point? {
            val denom = (a.x1 - a.x2) * (b.y1 - b.y2) - (a.y1 - a.y2) * (b.x1 - b.x2)
            if (abs(denom) < 1e-6) return null
            val aCross = a.x1 * a.y2 - a.y1 * a.x2
            val bCross = b.x1 * b.y2 - b.y1 * b.x2
            val px = (aCross * (b.x1 - b.x2) - (a.x1 - a.x2) * bCross) / denom
            val py = (aCross * (b.y1 - b.y2) - (a.y1 - a.y2) * bCross) / denom
            return Point(px, py)
        }

        val topLeft = intersect(top, left) ?: return null
        val topRight = intersect(top, right) ?: return null
        val bottomLeft = intersect(bottom, left) ?: return null
        val bottomRight = intersect(bottom, right) ?: return null

        val quadPoints = arrayOf(topLeft, topRight, bottomRight, bottomLeft)
        if (!isPlausibleDocumentQuad(quadPoints, width, height)) return null

        val area = polygonArea(quadPoints)
        val score = area * rectangularity(quadPoints, area) / (width.toDouble() * height)

        val invScale = 1f / scale.toFloat()
        fun toOriginal(p: Point) = Offset(
            (p.x * invScale).toFloat().coerceIn(0f, bitmap.width.toFloat()),
            (p.y * invScale).toFloat().coerceIn(0f, bitmap.height.toFloat())
        )

        val quad = CropQuad(
            topLeft = toOriginal(topLeft),
            topRight = toOriginal(topRight),
            bottomRight = toOriginal(bottomRight),
            bottomLeft = toOriginal(bottomLeft)
        )
        return ScoredQuad(quad, score)
    }

    /** Shoelace-formula polygon area, for the Hough-fallback quad which has no OpenCV contour to
     * call [Imgproc.contourArea] on. */
    private fun polygonArea(points: Array<Point>): Double {
        var sum = 0.0
        for (i in points.indices) {
            val p1 = points[i]
            val p2 = points[(i + 1) % points.size]
            sum += p1.x * p2.y - p2.x * p1.y
        }
        return abs(sum) / 2.0
    }

    /**
     * Rejects degenerate "detections" before they're ever compared by area: a busy, high-contrast
     * background (patterned fabric, tiled floor, printed wallpaper) generates edges everywhere,
     * and Canny+contour search can close a spurious loop into a 4-point polygon that happens to
     * clear the area thresholds despite being a thin, useless sliver nowhere near the real
     * document — exactly what a self-intersecting or squashed-bounding-box quad looks like. This
     * checks the candidate is a simple convex polygon (via [Imgproc.isContourConvex]) and that its
     * bounding box actually spans a sane fraction of the frame in *both* directions, not just one.
     */
    private fun isPlausibleDocumentQuad(corners: Array<Point>, frameWidth: Int, frameHeight: Int): Boolean {
        if (corners.size != 4) return false

        val asMat = MatOfPoint(*corners)
        val convex = Imgproc.isContourConvex(asMat)
        asMat.release()
        if (!convex) return false

        val boundingWidth = corners.maxOf { it.x } - corners.minOf { it.x }
        val boundingHeight = corners.maxOf { it.y } - corners.minOf { it.y }
        return boundingWidth >= frameWidth * MIN_BOUNDING_DIMENSION_FRACTION &&
            boundingHeight >= frameHeight * MIN_BOUNDING_DIMENSION_FRACTION
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
