package com.pdfimagetools.app.ui.quickscan

import android.graphics.Bitmap
import androidx.compose.foundation.Canvas
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.gestures.detectDragGestures
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.BoxWithConstraints
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.offset
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.statusBarsPadding
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material3.Button
import androidx.compose.material3.FilterChip
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.ColorFilter
import androidx.compose.ui.graphics.ColorMatrix
import androidx.compose.ui.graphics.Path
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.input.pointer.pointerInput
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalDensity
import androidx.compose.ui.unit.IntOffset
import androidx.compose.ui.unit.dp
import com.pdfimagetools.app.ui.theme.Primary
import kotlin.math.roundToInt

private enum class PageSelection { FULL_SPREAD, LEFT_PAGE, RIGHT_PAGE }

/**
 * Shows the just-captured photo with the detected document outline overlaid as a draggable
 * quadrilateral. The user can drag any of the four corners to correct the recommendation before
 * confirming — there is no live tracking here, only a static image the user has full control
 * over, which is what makes this reliable regardless of how good the initial detection was.
 *
 * When [gutterFraction] is non-null (the capture looked like an open book spread), the screen
 * defaults to just one page — auto-selected — with chips to switch to the other page or keep
 * both; otherwise it behaves exactly like a single-page crop.
 */
@Composable
fun CropAdjustScreen(
    bitmap: Bitmap,
    initialQuad: CropQuad,
    gutterFraction: Float?,
    onConfirm: (CropQuad, ScanFilter) -> Unit,
    onRetake: () -> Unit
) {
    val imageBitmap = remember(bitmap) { bitmap.asImageBitmap() }
    val density = LocalDensity.current
    var selectedFilter by remember { mutableStateOf(ScanFilter.BLACK_AND_WHITE) }
    val previewColorFilter = remember(selectedFilter) {
        ColorFilter.colorMatrix(ColorMatrix(selectedFilter.colorMatrixValues()))
    }
    val hasSpread = gutterFraction != null
    var pageSelection by remember(gutterFraction) {
        mutableStateOf(if (hasSpread) PageSelection.RIGHT_PAGE else PageSelection.FULL_SPREAD)
    }
    val activeQuad = remember(initialQuad, gutterFraction, pageSelection) {
        if (gutterFraction != null) {
            val (left, right) = initialQuad.splitAtFraction(gutterFraction)
            when (pageSelection) {
                PageSelection.LEFT_PAGE -> left
                PageSelection.RIGHT_PAGE -> right
                PageSelection.FULL_SPREAD -> initialQuad
            }
        } else {
            initialQuad
        }
    }

    Column(modifier = Modifier.fillMaxSize().background(Color.Black).statusBarsPadding()) {
        Text(
            text = if (hasSpread) {
                "Looks like an open book — pick a page, or keep both"
            } else {
                "Drag the corners to match the document edges"
            },
            color = Color.White,
            style = MaterialTheme.typography.bodyMedium,
            modifier = Modifier.fillMaxWidth().padding(16.dp)
        )

        BoxWithConstraints(modifier = Modifier.weight(1f).fillMaxWidth()) {
            val containerWidthPx = with(density) { maxWidth.toPx() }
            val containerHeightPx = with(density) { maxHeight.toPx() }
            val fitScale = remember(containerWidthPx, containerHeightPx, bitmap) {
                minOf(containerWidthPx / bitmap.width, containerHeightPx / bitmap.height)
            }
            val displayWidthPx = bitmap.width * fitScale
            val displayHeightPx = bitmap.height * fitScale
            val offsetXPx = (containerWidthPx - displayWidthPx) / 2f
            val offsetYPx = (containerHeightPx - displayHeightPx) / 2f

            fun bitmapToDisplay(p: Offset) = Offset(p.x * fitScale + offsetXPx, p.y * fitScale + offsetYPx)
            fun displayToBitmap(p: Offset) = Offset((p.x - offsetXPx) / fitScale, (p.y - offsetYPx) / fitScale)

            var quad by remember(activeQuad) { mutableStateOf(activeQuad.map(::bitmapToDisplay)) }

            Image(
                bitmap = imageBitmap,
                contentDescription = null,
                contentScale = ContentScale.Fit,
                colorFilter = previewColorFilter,
                modifier = Modifier.fillMaxSize()
            )

            Canvas(modifier = Modifier.fillMaxSize()) {
                val path = Path().apply {
                    moveTo(quad.topLeft.x, quad.topLeft.y)
                    lineTo(quad.topRight.x, quad.topRight.y)
                    lineTo(quad.bottomRight.x, quad.bottomRight.y)
                    lineTo(quad.bottomLeft.x, quad.bottomLeft.y)
                    close()
                }
                drawPath(path, color = Primary, style = Stroke(width = 3.dp.toPx()))
            }

            fun clamped(p: Offset) = Offset(
                p.x.coerceIn(offsetXPx, offsetXPx + displayWidthPx),
                p.y.coerceIn(offsetYPx, offsetYPx + displayHeightPx)
            )

            CornerHandle(point = quad.topLeft) { delta -> quad = quad.copy(topLeft = clamped(quad.topLeft + delta)) }
            CornerHandle(point = quad.topRight) { delta -> quad = quad.copy(topRight = clamped(quad.topRight + delta)) }
            CornerHandle(point = quad.bottomRight) { delta -> quad = quad.copy(bottomRight = clamped(quad.bottomRight + delta)) }
            CornerHandle(point = quad.bottomLeft) { delta -> quad = quad.copy(bottomLeft = clamped(quad.bottomLeft + delta)) }

            Column(
                modifier = Modifier
                    .align(Alignment.BottomCenter)
                    .fillMaxWidth()
                    .background(Color.Black.copy(alpha = 0.55f))
                    .navigationBarsPadding()
                    .padding(16.dp)
            ) {
                if (hasSpread) {
                    Row(
                        horizontalArrangement = Arrangement.spacedBy(8.dp),
                        modifier = Modifier.fillMaxWidth().padding(bottom = 12.dp)
                    ) {
                        FilterChip(
                            selected = pageSelection == PageSelection.LEFT_PAGE,
                            onClick = { pageSelection = PageSelection.LEFT_PAGE },
                            label = { Text("Left page") }
                        )
                        FilterChip(
                            selected = pageSelection == PageSelection.RIGHT_PAGE,
                            onClick = { pageSelection = PageSelection.RIGHT_PAGE },
                            label = { Text("Right page") }
                        )
                        FilterChip(
                            selected = pageSelection == PageSelection.FULL_SPREAD,
                            onClick = { pageSelection = PageSelection.FULL_SPREAD },
                            label = { Text("Both pages") }
                        )
                    }
                }
                Row(
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                    modifier = Modifier.fillMaxWidth().padding(bottom = 12.dp)
                ) {
                    ScanFilter.entries.forEach { filter ->
                        FilterChip(
                            selected = filter == selectedFilter,
                            onClick = { selectedFilter = filter },
                            label = { Text(filter.label) }
                        )
                    }
                }
                Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                    OutlinedButton(onClick = onRetake, modifier = Modifier.weight(1f)) {
                        Text("Retake")
                    }
                    Button(
                        onClick = { onConfirm(quad.map(::displayToBitmap), selectedFilter) },
                        modifier = Modifier.weight(1f)
                    ) {
                        Text("Use this crop")
                    }
                }
            }
        }
    }
}

@Composable
private fun CornerHandle(point: Offset, onDrag: (Offset) -> Unit) {
    val handleSize = 28.dp
    val density = LocalDensity.current
    val halfSizePx = with(density) { (handleSize / 2).toPx() }

    Box(
        modifier = Modifier
            .offset { IntOffset((point.x - halfSizePx).roundToInt(), (point.y - halfSizePx).roundToInt()) }
            .size(handleSize)
            .background(Color.White, CircleShape)
            .border(2.dp, Primary, CircleShape)
            .pointerInput(Unit) {
                detectDragGestures { change, dragAmount ->
                    change.consume()
                    onDrag(dragAmount)
                }
            }
    )
}
