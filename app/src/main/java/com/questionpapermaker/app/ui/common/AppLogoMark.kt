package com.questionpapermaker.app.ui.common

import androidx.compose.foundation.Canvas
import androidx.compose.material3.MaterialTheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.geometry.CornerRadius
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.geometry.Size
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.Path
import androidx.compose.ui.graphics.StrokeCap
import androidx.compose.ui.graphics.StrokeJoin
import androidx.compose.ui.graphics.drawscope.Stroke

/**
 * The app's mark: a graduation cap over a checked document, matching the brand logo from the
 * design system. Drawn as vector shapes so it stays crisp at any size (launcher, splash, app bar).
 */
@Composable
fun AppLogoMark(modifier: Modifier = Modifier, tint: Color = MaterialTheme.colorScheme.primary) {
    Canvas(modifier = modifier) {
        val w = size.width
        val h = size.height

        val docLeft = w * 0.26f
        val docRight = w * 0.80f
        val docTop = h * 0.44f
        val docBottom = h * 0.92f
        val docHeight = docBottom - docTop
        val strokeWidth = w * 0.045f

        drawRoundRect(
            color = tint,
            topLeft = Offset(docLeft, docTop),
            size = Size(docRight - docLeft, docHeight),
            cornerRadius = CornerRadius(w * 0.05f, w * 0.05f),
            style = Stroke(width = strokeWidth)
        )

        val lineStrokeWidth = w * 0.035f
        drawLine(
            color = tint,
            start = Offset(docLeft + w * 0.09f, docTop + docHeight * 0.30f),
            end = Offset(docRight - w * 0.09f, docTop + docHeight * 0.30f),
            strokeWidth = lineStrokeWidth,
            cap = StrokeCap.Round
        )
        drawLine(
            color = tint,
            start = Offset(docLeft + w * 0.09f, docTop + docHeight * 0.48f),
            end = Offset(docRight - w * 0.32f, docTop + docHeight * 0.48f),
            strokeWidth = lineStrokeWidth,
            cap = StrokeCap.Round
        )

        val checkPath = Path().apply {
            moveTo(docLeft + w * 0.11f, docTop + docHeight * 0.70f)
            lineTo(docLeft + w * 0.24f, docTop + docHeight * 0.83f)
            lineTo(docLeft + w * 0.46f, docTop + docHeight * 0.60f)
        }
        drawPath(
            path = checkPath,
            color = tint,
            style = Stroke(width = lineStrokeWidth * 1.1f, cap = StrokeCap.Round, join = StrokeJoin.Round)
        )

        val capCenterX = (docLeft + docRight) / 2f
        val capTopY = h * 0.06f
        val capWidth = w * 0.66f
        val capHeight = h * 0.22f

        val capPath = Path().apply {
            moveTo(capCenterX, capTopY)
            lineTo(capCenterX + capWidth / 2f, capTopY + capHeight / 2f)
            lineTo(capCenterX, capTopY + capHeight)
            lineTo(capCenterX - capWidth / 2f, capTopY + capHeight / 2f)
            close()
        }
        drawPath(path = capPath, color = tint)

        drawRoundRect(
            color = tint,
            topLeft = Offset(capCenterX - capWidth * 0.26f, capTopY + capHeight * 0.5f),
            size = Size(capWidth * 0.52f, capHeight * 0.5f),
            cornerRadius = CornerRadius(w * 0.015f, w * 0.015f)
        )

        val tasselX = capCenterX + capWidth * 0.32f
        drawLine(
            color = tint,
            start = Offset(tasselX, capTopY + capHeight * 0.5f),
            end = Offset(tasselX, capTopY + capHeight * 1.05f),
            strokeWidth = w * 0.02f,
            cap = StrokeCap.Round
        )
        drawCircle(color = tint, radius = w * 0.028f, center = Offset(tasselX, capTopY + capHeight * 1.1f))
    }
}
