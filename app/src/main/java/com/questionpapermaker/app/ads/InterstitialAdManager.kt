package com.questionpapermaker.app.ads

import android.app.Activity
import android.content.Context
import android.util.Log
import com.google.android.gms.ads.AdError
import com.google.android.gms.ads.AdRequest
import com.google.android.gms.ads.FullScreenContentCallback
import com.google.android.gms.ads.LoadAdError
import com.google.android.gms.ads.interstitial.InterstitialAd
import com.google.android.gms.ads.interstitial.InterstitialAdLoadCallback

/**
 * Loads one interstitial ahead of time and shows it before a download completes. The user's
 * actual task (getting their PDF) is never blocked on the ad: if nothing is loaded yet or the
 * ad fails for any reason, [showThenRun] just runs [onComplete] immediately.
 */
class InterstitialAdManager(private val appContext: Context) {

    private var interstitialAd: InterstitialAd? = null
    private var isLoading = false

    fun preload() {
        if (interstitialAd != null || isLoading) return
        isLoading = true
        InterstitialAd.load(
            appContext,
            AdConfig.INTERSTITIAL_AD_UNIT_ID,
            AdRequest.Builder().build(),
            object : InterstitialAdLoadCallback() {
                override fun onAdLoaded(ad: InterstitialAd) {
                    isLoading = false
                    interstitialAd = ad
                }

                override fun onAdFailedToLoad(error: LoadAdError) {
                    isLoading = false
                    interstitialAd = null
                    Log.d(TAG, "Interstitial failed to load: ${error.message}")
                }
            }
        )
    }

    /** Shows the preloaded interstitial (if any) on [activity], then always calls [onComplete] exactly once. */
    fun showThenRun(activity: Activity, onComplete: () -> Unit) {
        val ad = interstitialAd
        if (ad == null) {
            preload()
            onComplete()
            return
        }

        ad.fullScreenContentCallback = object : FullScreenContentCallback() {
            override fun onAdDismissedFullScreenContent() {
                interstitialAd = null
                preload()
                onComplete()
            }

            override fun onAdFailedToShowFullScreenContent(adError: AdError) {
                interstitialAd = null
                preload()
                onComplete()
            }
        }
        ad.show(activity)
    }

    private companion object {
        const val TAG = "InterstitialAdManager"
    }
}
