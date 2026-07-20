package com.questionpapermaker.app.ads

import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalInspectionMode
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.viewinterop.AndroidView
import com.google.android.gms.ads.AdRequest
import com.google.android.gms.ads.AdSize
import com.google.android.gms.ads.AdView

/** A standard 320x50 banner for the Home screen's blank space below the shortcut cards. */
@Composable
fun BannerAdView(modifier: Modifier = Modifier) {
    // AndroidView's factory can't run inside Compose Preview/inspection -- show a placeholder instead.
    if (LocalInspectionMode.current) {
        Text("Ad", modifier = modifier.fillMaxWidth(), textAlign = TextAlign.Center, style = MaterialTheme.typography.labelSmall)
        return
    }

    AndroidView(
        modifier = modifier.fillMaxWidth(),
        factory = { context ->
            AdView(context).apply {
                setAdSize(AdSize.BANNER)
                adUnitId = AdConfig.BANNER_AD_UNIT_ID
                loadAd(AdRequest.Builder().build())
            }
        },
        onRelease = { adView -> adView.destroy() }
    )
}
