package com.pdfimagetools.app.ui.components

import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.viewinterop.AndroidView
import com.google.android.gms.ads.AdRequest
import com.google.android.gms.ads.AdSize
import com.google.android.gms.ads.AdView
import com.pdfimagetools.app.core.ads.AdUnitIds

/** Fixed banner shown at the bottom of a screen. Loads a real ad request; the test device id
 * configured in [com.pdfimagetools.app.core.ads.AdsManagerImpl] makes this device always render
 * Google's test creative instead of a live ad. */
@Composable
fun BannerAdView(modifier: Modifier = Modifier) {
    AndroidView(
        modifier = modifier.fillMaxWidth(),
        factory = { context ->
            AdView(context).apply {
                adUnitId = AdUnitIds.BANNER
                setAdSize(AdSize.BANNER)
                loadAd(AdRequest.Builder().build())
            }
        }
    )
}
