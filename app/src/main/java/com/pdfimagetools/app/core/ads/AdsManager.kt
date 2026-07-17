package com.pdfimagetools.app.core.ads

import android.app.Activity
import com.google.android.gms.ads.nativead.NativeAd

object AdUnitIds {
    const val APPLICATION_ID = "ca-app-pub-5327551236542725~6041492259"
    const val REWARDED_INTERSTITIAL = "ca-app-pub-5327551236542725/6715711453"
    const val BANNER = "ca-app-pub-5327551236542725/4289426889"
    const val NATIVE = "ca-app-pub-5327551236542725/5291517366"
    const val TEST_DEVICE_ID = "4aaf3294-0b68-4ac6-a3ee-d615f82465cf"
}

interface AdsManager {

    fun initialize()

    /** Starts loading a rewarded interstitial ahead of time so it is ready when needed. */
    fun preloadRewardedInterstitial()

    /**
     * Shows the rewarded interstitial if one is ready. [onComplete] is always invoked exactly
     * once — whether the ad was shown, failed to load, or failed to display — so the caller can
     * proceed with saving without ever being blocked by ads.
     */
    fun showRewardedInterstitialThenProceed(activity: Activity, onComplete: () -> Unit)

    /** Suspends until the rewarded interstitial has been shown/skipped/failed; never throws. */
    suspend fun awaitRewardedInterstitial(activity: Activity)

    fun loadNativeAd(onLoaded: (NativeAd) -> Unit, onFailed: () -> Unit)
}
