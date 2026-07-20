package com.questionpapermaker.app.ads

import android.app.Activity
import android.content.Context
import android.util.Log
import com.google.android.gms.ads.AdError
import com.google.android.gms.ads.AdRequest
import com.google.android.gms.ads.FullScreenContentCallback
import com.google.android.gms.ads.LoadAdError
import com.google.android.gms.ads.OnUserEarnedRewardListener
import com.google.android.gms.ads.rewardedinterstitial.RewardedInterstitialAd
import com.google.android.gms.ads.rewardedinterstitial.RewardedInterstitialAdLoadCallback

/**
 * Loads one rewarded-interstitial ad ahead of time and shows it before a download completes.
 * The ad unit is configured as "Rewarded Interstitial" in AdMob rather than a plain
 * "Interstitial" -- functionally it's shown full-screen and dismissed the same way, we just
 * ignore the earned-reward callback since there's no in-app reward to grant.
 *
 * The user's actual task (getting their PDF) is never blocked on the ad: if nothing is loaded
 * yet or the ad fails for any reason, [showThenRun] just runs [onComplete] immediately.
 */
class InterstitialAdManager(private val appContext: Context) {

    private var rewardedInterstitialAd: RewardedInterstitialAd? = null
    private var isLoading = false

    fun preload() {
        if (rewardedInterstitialAd != null || isLoading) return
        isLoading = true
        RewardedInterstitialAd.load(
            appContext,
            AdConfig.INTERSTITIAL_AD_UNIT_ID,
            AdRequest.Builder().build(),
            object : RewardedInterstitialAdLoadCallback() {
                override fun onAdLoaded(ad: RewardedInterstitialAd) {
                    isLoading = false
                    rewardedInterstitialAd = ad
                }

                override fun onAdFailedToLoad(error: LoadAdError) {
                    isLoading = false
                    rewardedInterstitialAd = null
                    Log.d(TAG, "Rewarded interstitial failed to load: ${error.message}")
                }
            }
        )
    }

    /** Shows the preloaded ad (if any) on [activity], then always calls [onComplete] exactly once. */
    fun showThenRun(activity: Activity, onComplete: () -> Unit) {
        val ad = rewardedInterstitialAd
        if (ad == null) {
            preload()
            onComplete()
            return
        }

        ad.fullScreenContentCallback = object : FullScreenContentCallback() {
            override fun onAdDismissedFullScreenContent() {
                rewardedInterstitialAd = null
                preload()
                onComplete()
            }

            override fun onAdFailedToShowFullScreenContent(adError: AdError) {
                rewardedInterstitialAd = null
                preload()
                onComplete()
            }
        }
        // We don't grant any in-app reward -- the listener only exists to satisfy the SDK's show() signature.
        ad.show(activity, OnUserEarnedRewardListener { })
    }

    private companion object {
        const val TAG = "InterstitialAdManager"
    }
}
