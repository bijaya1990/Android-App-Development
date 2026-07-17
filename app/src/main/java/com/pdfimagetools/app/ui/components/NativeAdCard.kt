package com.pdfimagetools.app.ui.components

import android.content.Context
import android.graphics.Color
import android.graphics.Typeface
import android.util.TypedValue
import android.view.Gravity
import android.view.ViewGroup
import android.widget.Button
import android.widget.ImageView
import android.widget.LinearLayout
import android.widget.TextView
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import androidx.compose.ui.viewinterop.AndroidView
import com.google.android.gms.ads.nativead.MediaView
import com.google.android.gms.ads.nativead.NativeAd
import com.google.android.gms.ads.nativead.NativeAdView
import com.pdfimagetools.app.core.di.ServiceLocator

private class NativeAdViews(
    val adView: NativeAdView,
    val media: MediaView,
    val headline: TextView,
    val body: TextView,
    val cta: Button,
    val icon: ImageView
)

private fun buildNativeAdView(context: Context): NativeAdViews {
    val density = context.resources.displayMetrics.density
    fun dp(value: Int) = (value * density).toInt()

    val adView = NativeAdView(context).apply {
        layoutParams = ViewGroup.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.WRAP_CONTENT)
        setBackgroundColor(Color.WHITE)
    }

    val root = LinearLayout(context).apply {
        orientation = LinearLayout.VERTICAL
        setPadding(dp(16), dp(16), dp(16), dp(16))
    }

    val badge = TextView(context).apply {
        text = "Ad"
        setTextColor(Color.parseColor("#6B7280"))
        textSize = 10f
        setTypeface(typeface, Typeface.BOLD)
    }
    root.addView(badge)

    val media = MediaView(context).apply {
        layoutParams = LinearLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, dp(140)).apply {
            topMargin = dp(8)
            bottomMargin = dp(12)
        }
    }
    root.addView(media)

    val row = LinearLayout(context).apply {
        orientation = LinearLayout.HORIZONTAL
        gravity = Gravity.CENTER_VERTICAL
    }

    val icon = ImageView(context).apply {
        layoutParams = LinearLayout.LayoutParams(dp(40), dp(40)).apply { marginEnd = dp(12) }
    }
    row.addView(icon)

    val textColumn = LinearLayout(context).apply {
        orientation = LinearLayout.VERTICAL
        layoutParams = LinearLayout.LayoutParams(0, ViewGroup.LayoutParams.WRAP_CONTENT, 1f)
    }
    val headline = TextView(context).apply {
        setTextColor(Color.parseColor("#1A1D29"))
        setTypeface(typeface, Typeface.BOLD)
        setTextSize(TypedValue.COMPLEX_UNIT_SP, 15f)
        maxLines = 1
    }
    val body = TextView(context).apply {
        setTextColor(Color.parseColor("#6B7280"))
        setTextSize(TypedValue.COMPLEX_UNIT_SP, 13f)
        maxLines = 2
    }
    textColumn.addView(headline)
    textColumn.addView(body)
    row.addView(textColumn)
    root.addView(row)

    val cta = Button(context).apply {
        setTextColor(Color.WHITE)
        setBackgroundColor(Color.parseColor("#2563EB"))
        layoutParams = LinearLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.WRAP_CONTENT).apply {
            topMargin = dp(12)
        }
    }
    root.addView(cta)

    adView.addView(root)
    adView.headlineView = headline
    adView.bodyView = body
    adView.callToActionView = cta
    adView.iconView = icon
    adView.mediaView = media

    return NativeAdViews(adView, media, headline, body, cta, icon)
}

@Composable
fun NativeAdCard(modifier: Modifier = Modifier) {
    var nativeAd by remember { mutableStateOf<NativeAd?>(null) }
    var failed by remember { mutableStateOf(false) }

    LaunchedEffect(Unit) {
        ServiceLocator.adsManager.loadNativeAd(
            onLoaded = { nativeAd = it },
            onFailed = { failed = true }
        )
    }

    DisposableEffect(Unit) {
        onDispose { nativeAd?.destroy() }
    }

    val ad = nativeAd
    if (ad == null || failed) return

    val context = LocalContext.current
    val views = remember { buildNativeAdView(context) }

    AndroidView(
        modifier = modifier.fillMaxWidth(),
        factory = { views.adView },
        update = {
            views.headline.text = ad.headline
            views.body.text = ad.body ?: ""
            views.body.visibility = if (ad.body.isNullOrBlank()) android.view.View.GONE else android.view.View.VISIBLE
            views.cta.text = ad.callToAction ?: "Learn more"
            views.cta.visibility = if (ad.callToAction.isNullOrBlank()) android.view.View.GONE else android.view.View.VISIBLE
            val iconDrawable = ad.icon?.drawable
            if (iconDrawable != null) {
                views.icon.setImageDrawable(iconDrawable)
                views.icon.visibility = android.view.View.VISIBLE
            } else {
                views.icon.visibility = android.view.View.GONE
            }
            views.adView.setNativeAd(ad)
        }
    )
}
