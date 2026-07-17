package com.pdfimagetools.app.ui.components

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import com.pdfimagetools.app.ui.theme.TextSecondary

@Composable
fun LoadingOverlay(
    message: String,
    progressFraction: Float? = null,
    modifier: Modifier = Modifier
) {
    Column(
        modifier = modifier.fillMaxSize().padding(32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        if (progressFraction != null) {
            CircularProgressIndicator(progress = { progressFraction })
        } else {
            CircularProgressIndicator()
        }
        Text(
            text = message,
            style = MaterialTheme.typography.bodyMedium,
            color = TextSecondary,
            modifier = Modifier.padding(top = 20.dp)
        )
        if (progressFraction != null) {
            LinearProgressIndicator(
                progress = { progressFraction },
                modifier = Modifier.padding(top = 12.dp)
            )
        }
    }
}
