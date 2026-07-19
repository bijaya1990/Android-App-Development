package com.pdfimagetools.app.ui.quickscan

import android.graphics.Bitmap
import org.opencv.android.Utils
import org.opencv.core.Core
import org.opencv.core.CvType
import org.opencv.core.Mat
import org.opencv.core.MatOfDouble
import org.opencv.core.Point
import org.opencv.core.Scalar
import org.opencv.core.Size
import org.opencv.imgproc.Imgproc
import org.opencv.photo.Photo

/**
 * Real OpenCV post-processing for a straightened page. Each function is a standard, well-known
 * classical technique (not a trained model) — the two riskiest ones ([flattenPageCurve],
 * [removeFingersAtEdges]) are opt-in in the UI rather than applied by default, since their
 * boundary/skin-tone heuristics can misfire on a page that doesn't match their assumptions and
 * there is no way to test them against real photos in this environment.
 */
object ImageEnhancer {

    /** Auto color/contrast enhancement via CLAHE (contrast-limited adaptive histogram
     * equalization) applied only to the lightness channel in LAB space, so contrast improves
     * without the color casts a naive RGB contrast stretch would introduce. */
    fun claheEnhance(bitmap: Bitmap): Bitmap {
        val source = Mat()
        Utils.bitmapToMat(bitmap, source)
        val bgr = Mat()
        Imgproc.cvtColor(source, bgr, Imgproc.COLOR_RGBA2BGR)
        source.release()

        val lab = Mat()
        Imgproc.cvtColor(bgr, lab, Imgproc.COLOR_BGR2Lab)
        bgr.release()

        val channels = mutableListOf<Mat>()
        Core.split(lab, channels)
        val clahe = Imgproc.createCLAHE(CLAHE_CLIP_LIMIT, Size(CLAHE_TILE_SIZE, CLAHE_TILE_SIZE))
        clahe.apply(channels[0], channels[0])
        Core.merge(channels, lab)
        channels.forEach { it.release() }

        val enhancedBgr = Mat()
        Imgproc.cvtColor(lab, enhancedBgr, Imgproc.COLOR_Lab2BGR)
        lab.release()

        val resultRgba = Mat()
        Imgproc.cvtColor(enhancedBgr, resultRgba, Imgproc.COLOR_BGR2RGBA)
        enhancedBgr.release()

        val result = Bitmap.createBitmap(bitmap.width, bitmap.height, Bitmap.Config.ARGB_8888)
        Utils.matToBitmap(resultRgba, result)
        resultRgba.release()
        return result
    }

    /** A genuine "scanned document" look: estimate the background illumination (a large-kernel
     * median blur, since text/lines are thin compared to the kernel and get smoothed away, leaving
     * just the lighting gradient — including shadows), divide the original by it to normalize
     * lighting evenly across the page, then binarize with an adaptive (locally-thresholded, not
     * global) threshold so it stays crisp even where lighting still varies a little. */
    fun adaptiveThresholdDocument(bitmap: Bitmap): Bitmap {
        val source = Mat()
        Utils.bitmapToMat(bitmap, source)
        val gray = Mat()
        Imgproc.cvtColor(source, gray, Imgproc.COLOR_RGBA2GRAY)
        source.release()

        val background = Mat()
        Imgproc.medianBlur(gray, background, SHADOW_KERNEL_SIZE)
        val normalized = Mat()
        Core.divide(gray, background, normalized, 255.0)
        gray.release()
        background.release()

        val thresholded = Mat()
        Imgproc.adaptiveThreshold(
            normalized, thresholded, 255.0,
            Imgproc.ADAPTIVE_THRESH_GAUSSIAN_C, Imgproc.THRESH_BINARY,
            ADAPTIVE_THRESHOLD_BLOCK_SIZE, ADAPTIVE_THRESHOLD_C
        )
        normalized.release()

        val resultRgba = Mat()
        Imgproc.cvtColor(thresholded, resultRgba, Imgproc.COLOR_GRAY2RGBA)
        thresholded.release()

        val result = Bitmap.createBitmap(bitmap.width, bitmap.height, Bitmap.Config.ARGB_8888)
        Utils.matToBitmap(resultRgba, result)
        resultRgba.release()
        return result
    }

    /** Laplacian-variance blur score (Pech-Pacheco et al.) — a sharp image has a wide spread of
     * second-derivative response, a blurry one doesn't, so variance of the Laplacian is a simple,
     * well-established sharpness proxy. Computed on a fixed-size grayscale downsample so the score
     * is comparable across different capture resolutions; [isBlurry] applies a conservative cutoff
     * meant to only flag clearly-bad shots rather than second-guess every capture. */
    fun blurVariance(bitmap: Bitmap): Double {
        val analysisWidth = BLUR_ANALYSIS_WIDTH
        val scale = analysisWidth.toFloat() / bitmap.width
        val analysisHeight = (bitmap.height * scale).toInt().coerceAtLeast(1)
        val small = Bitmap.createScaledBitmap(bitmap, analysisWidth, analysisHeight, true)

        val source = Mat()
        Utils.bitmapToMat(small, source)
        small.recycle()
        val gray = Mat()
        Imgproc.cvtColor(source, gray, Imgproc.COLOR_RGBA2GRAY)
        source.release()

        val laplacian = Mat()
        Imgproc.Laplacian(gray, laplacian, CvType.CV_64F)
        gray.release()

        val mean = MatOfDouble()
        val stddev = MatOfDouble()
        Core.meanStdDev(laplacian, mean, stddev)
        laplacian.release()
        val stdValue = stddev.toArray().getOrElse(0) { 0.0 }
        mean.release()
        stddev.release()
        return stdValue * stdValue
    }

    fun isBlurry(bitmap: Bitmap): Boolean = blurVariance(bitmap) < BLUR_VARIANCE_THRESHOLD

    /**
     * Best-effort correction for a page photographed slightly bowed (common near a book's gutter):
     * for each row, finds where the light background gives way to the darker page content near
     * the left/right margins, smooths those two boundary curves to cut noise, then horizontally
     * resamples each row so the boundaries land on a single straight vertical line instead of a
     * curve. This only corrects left/right bowing captured this way — it is not a general 3D
     * surface dewarp — and the per-row shift is clamped so a bad boundary read on any one row
     * can't introduce a wild distortion. Opt-in: on an already-flat page this heuristic has
     * nothing to correct and, in the worst case, adds a very slight resample softness.
     */
    fun flattenPageCurve(bitmap: Bitmap): Bitmap {
        val width = bitmap.width
        val height = bitmap.height

        val gray = Mat()
        run {
            val source = Mat()
            Utils.bitmapToMat(bitmap, source)
            Imgproc.cvtColor(source, gray, Imgproc.COLOR_RGBA2GRAY)
            source.release()
        }
        val grayData = ByteArray(width * height)
        gray.get(0, 0, grayData)
        gray.release()

        fun luminance(x: Int, y: Int): Int {
            val v = grayData[y * width + x].toInt()
            return if (v < 0) v + 256 else v
        }

        val marginBand = (width * CURVE_MARGIN_BAND_FRACTION).toInt().coerceAtLeast(4)
        val leftEdge = DoubleArray(height)
        val rightEdge = DoubleArray(height)
        for (y in 0 until height) {
            var l = 0
            while (l < marginBand && luminance(l, y) > CURVE_BACKGROUND_BRIGHTNESS) l++
            var r = width - 1
            while (r > width - marginBand && luminance(r, y) > CURVE_BACKGROUND_BRIGHTNESS) r--
            leftEdge[y] = l.toDouble()
            rightEdge[y] = r.toDouble()
        }

        fun movingAverage(values: DoubleArray, window: Int): DoubleArray {
            val result = DoubleArray(values.size)
            for (i in values.indices) {
                var sum = 0.0
                var count = 0
                for (j in (i - window)..(i + window)) {
                    if (j in values.indices) {
                        sum += values[j]
                        count++
                    }
                }
                result[i] = sum / count
            }
            return result
        }
        val smoothLeft = movingAverage(leftEdge, CURVE_SMOOTHING_WINDOW)
        val smoothRight = movingAverage(rightEdge, CURVE_SMOOTHING_WINDOW)
        val targetLeft = smoothLeft.average()
        val targetRight = smoothRight.average().coerceAtLeast(targetLeft + 1.0)
        val maxShift = width * CURVE_MAX_SHIFT_FRACTION

        val mapX = Mat(height, width, CvType.CV_32FC1)
        val mapY = Mat(height, width, CvType.CV_32FC1)
        val mapXData = FloatArray(width * height)
        val mapYData = FloatArray(width * height)
        for (y in 0 until height) {
            val srcLeft = smoothLeft[y]
            val srcRight = smoothRight[y].coerceAtLeast(srcLeft + 1.0)
            val scale = (srcRight - srcLeft) / (targetRight - targetLeft)
            for (x in 0 until width) {
                var sourceX = srcLeft + (x - targetLeft) * scale
                sourceX = sourceX.coerceIn(x - maxShift, x + maxShift)
                sourceX = sourceX.coerceIn(0.0, (width - 1).toDouble())
                val index = y * width + x
                mapXData[index] = sourceX.toFloat()
                mapYData[index] = y.toFloat()
            }
        }
        mapX.put(0, 0, mapXData)
        mapY.put(0, 0, mapYData)

        val source = Mat()
        Utils.bitmapToMat(bitmap, source)
        val output = Mat()
        Imgproc.remap(source, output, mapX, mapY, Imgproc.INTER_LINEAR, Core.BORDER_REPLICATE, Scalar(255.0, 255.0, 255.0, 255.0))
        source.release()
        mapX.release()
        mapY.release()

        val result = Bitmap.createBitmap(width, height, Bitmap.Config.ARGB_8888)
        Utils.matToBitmap(output, result)
        output.release()
        return result
    }

    /**
     * Best-effort removal of a finger holding the page down near an edge: masks broadly
     * skin-toned pixels restricted to the outer border band (so a genuinely skin-colored photo
     * printed on the page itself, away from the edges, is left alone) and inpaints over them.
     * Skin-tone detection is inherently unreliable across lighting and skin tones — this is why
     * it's an opt-in toggle rather than automatic.
     */
    fun removeFingersAtEdges(bitmap: Bitmap): Bitmap {
        val source = Mat()
        Utils.bitmapToMat(bitmap, source)
        val bgr = Mat()
        Imgproc.cvtColor(source, bgr, Imgproc.COLOR_RGBA2BGR)
        source.release()

        val hsv = Mat()
        Imgproc.cvtColor(bgr, hsv, Imgproc.COLOR_BGR2HSV)
        val skinMask = Mat()
        Core.inRange(hsv, Scalar(0.0, 30.0, 60.0), Scalar(25.0, 150.0, 255.0), skinMask)
        hsv.release()

        val width = bgr.cols()
        val height = bgr.rows()
        val borderBand = (minOf(width, height) * FINGER_BORDER_BAND_FRACTION).toInt().coerceAtLeast(8)
        val edgeMask = Mat.zeros(skinMask.size(), skinMask.type())
        Imgproc.rectangle(edgeMask, Point(0.0, 0.0), Point(width.toDouble(), height.toDouble()), Scalar(255.0), -1)
        Imgproc.rectangle(
            edgeMask,
            Point(borderBand.toDouble(), borderBand.toDouble()),
            Point((width - borderBand).toDouble(), (height - borderBand).toDouble()),
            Scalar(0.0),
            -1
        )
        val fingerMask = Mat()
        Core.bitwise_and(skinMask, edgeMask, fingerMask)
        skinMask.release()
        edgeMask.release()
        Imgproc.dilate(fingerMask, fingerMask, Imgproc.getStructuringElement(Imgproc.MORPH_ELLIPSE, Size(9.0, 9.0)))

        val inpainted = Mat()
        Photo.inpaint(bgr, fingerMask, inpainted, 7.0, Photo.INPAINT_TELEA)
        bgr.release()
        fingerMask.release()

        val resultRgba = Mat()
        Imgproc.cvtColor(inpainted, resultRgba, Imgproc.COLOR_BGR2RGBA)
        inpainted.release()

        val result = Bitmap.createBitmap(width, height, Bitmap.Config.ARGB_8888)
        Utils.matToBitmap(resultRgba, result)
        resultRgba.release()
        return result
    }

    private const val CLAHE_CLIP_LIMIT = 2.0
    private const val CLAHE_TILE_SIZE = 8.0
    private const val SHADOW_KERNEL_SIZE = 41
    private const val ADAPTIVE_THRESHOLD_BLOCK_SIZE = 25
    private const val ADAPTIVE_THRESHOLD_C = 10.0
    private const val BLUR_ANALYSIS_WIDTH = 700
    private const val BLUR_VARIANCE_THRESHOLD = 15.0
    private const val CURVE_MARGIN_BAND_FRACTION = 0.15
    private const val CURVE_BACKGROUND_BRIGHTNESS = 200
    private const val CURVE_SMOOTHING_WINDOW = 15
    private const val CURVE_MAX_SHIFT_FRACTION = 0.08
    private const val FINGER_BORDER_BAND_FRACTION = 0.10
}
