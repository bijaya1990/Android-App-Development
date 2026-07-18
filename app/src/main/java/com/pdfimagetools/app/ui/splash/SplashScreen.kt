package com.pdfimagetools.app.ui.splash

import androidx.compose.animation.core.LinearEasing
import androidx.compose.animation.core.RepeatMode
import androidx.compose.animation.core.animateFloat
import androidx.compose.animation.core.infiniteRepeatable
import androidx.compose.animation.core.rememberInfiniteTransition
import androidx.compose.animation.core.tween
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.pdfimagetools.app.ui.theme.Accent
import com.pdfimagetools.app.ui.theme.Background
import com.pdfimagetools.app.ui.theme.TextSecondary
import kotlinx.coroutines.delay

private const val SPLASH_DURATION_MS = 2000L
private const val LOGO_BOX_HEIGHT_DP = 96

@Composable
fun SplashScreen(onFinished: () -> Unit) {
    val infiniteTransition = rememberInfiniteTransition(label = "scan_line")
    val lineProgress by infiniteTransition.animateFloat(
        initialValue = 0f,
        targetValue = 1f,
        animationSpec = infiniteRepeatable(
            animation = tween(durationMillis = 850, easing = LinearEasing),
            repeatMode = RepeatMode.Reverse
        ),
        label = "line_progress"
    )

    androidx.compose.runtime.LaunchedEffect(Unit) {
        delay(SPLASH_DURATION_MS)
        onFinished()
    }

    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(Background),
        contentAlignment = Alignment.Center
    ) {
        Column(horizontalAlignment = Alignment.CenterHorizontally) {
            Box(
                modifier = Modifier
                    .width(220.dp)
                    .height(LOGO_BOX_HEIGHT_DP.dp),
                contentAlignment = Alignment.Center
            ) {
                Text(
                    text = "Z Scanner",
                    fontSize = 34.sp,
                    fontWeight = FontWeight.Bold,
                    color = Accent
                )
                Box(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(top = (LOGO_BOX_HEIGHT_DP * lineProgress).dp)
                        .height(3.dp)
                        .clip(RoundedCornerShape(2.dp))
                        .background(Accent.copy(alpha = 0.85f))
                )
            }
            Text(
                text = "Scan • Edit • Protect • Share",
                style = MaterialTheme.typography.bodyMedium,
                color = TextSecondary,
                modifier = Modifier.padding(top = 12.dp)
            )
        }
    }
}
