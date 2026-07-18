package com.pdfimagetools.app.ui.components

import androidx.compose.animation.core.animateDpAsState
import androidx.compose.animation.core.animateFloatAsState
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.interaction.collectIsPressedAsState
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Surface
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.Shape
import androidx.compose.ui.graphics.graphicsLayer
import androidx.compose.ui.unit.dp
import com.pdfimagetools.app.ui.theme.Accent

/**
 * A filled surface that scales down and drops its shadow on press, giving a tactile "3D" feel
 * instead of Material's flat ripple-only feedback.
 */
@Composable
fun Pressable3DButton(
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    shape: Shape = RoundedCornerShape(20.dp),
    containerColor: Color = Accent,
    contentColor: Color = Color.White,
    horizontalPadding: androidx.compose.ui.unit.Dp = 20.dp,
    verticalPadding: androidx.compose.ui.unit.Dp = 16.dp,
    content: @Composable () -> Unit
) {
    val interactionSource = remember { MutableInteractionSource() }
    val isPressed by interactionSource.collectIsPressedAsState()
    val scale by animateFloatAsState(targetValue = if (isPressed) 0.95f else 1f, label = "press_scale")
    val shadow by animateDpAsState(targetValue = if (isPressed) 2.dp else 10.dp, label = "press_shadow")

    Surface(
        onClick = onClick,
        modifier = modifier.graphicsLayer {
            scaleX = scale
            scaleY = scale
        },
        shape = shape,
        color = containerColor,
        contentColor = contentColor,
        shadowElevation = shadow,
        tonalElevation = shadow,
        interactionSource = interactionSource
    ) {
        Row(
            modifier = Modifier.padding(horizontal = horizontalPadding, vertical = verticalPadding),
            horizontalArrangement = Arrangement.Center,
            verticalAlignment = Alignment.CenterVertically
        ) {
            content()
        }
    }
}

/** Round variant for icon-first actions like the scanner's shutter button. */
@Composable
fun Pressable3DIconButton(
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    containerColor: Color = Accent,
    contentColor: Color = Color.White,
    content: @Composable () -> Unit
) {
    val interactionSource = remember { MutableInteractionSource() }
    val isPressed by interactionSource.collectIsPressedAsState()
    val scale by animateFloatAsState(targetValue = if (isPressed) 0.90f else 1f, label = "press_scale")
    val shadow by animateDpAsState(targetValue = if (isPressed) 2.dp else 12.dp, label = "press_shadow")

    Surface(
        onClick = onClick,
        modifier = modifier.graphicsLayer {
            scaleX = scale
            scaleY = scale
        },
        shape = CircleShape,
        color = containerColor,
        contentColor = contentColor,
        shadowElevation = shadow,
        tonalElevation = shadow,
        interactionSource = interactionSource
    ) {
        Row(
            horizontalArrangement = Arrangement.Center,
            verticalAlignment = Alignment.CenterVertically
        ) {
            content()
        }
    }
}
