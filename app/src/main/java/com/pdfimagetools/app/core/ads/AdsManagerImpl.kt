package com.pdfimagetools.app.core.ads

import android.app.Activity
import android.content.Context
import android.util.Log
import com.google.android.gms.ads.AdError
import com.google.android.gms.ads.AdRequest
import com.google.android.gms.ads.FullScreenContentCallback
import com.google.android.gms.ads.LoadAdError
import com.google.android.gms.ads.MobileAds
import com.google.android.gms.ads.RequestConfiguration
import com.google.android.gms.ads.nativead.AdLoader
import com.google.android.gms.ads.nativead.NativeAd
import com.google.android.gms.ads.rewardedinterstitial.RewardedInterstitialAd
import com.google.android.gms.ads.rewardedinterstitial.RewardedInterstitialAdLoadCallback
import kotlinx.coroutines.suspendCancellableCoroutine
import kotlin.coroutines.resume

class AdsManagerImpl(private val context: Context) : AdsManager {

    private var rewardedInterstitialAd: RewardedInterstitialAd? = null
    private var isLoadingRewardedInterstitial = false

    override fun initialize() {
        val configuration = RequestConfiguration.Builder()
            .setTestDeviceIds(listOf(AdUnitIds.TEST_DEVICE_ID))
            .build()
        MobileAds.setRequestConfiguration(configuration)
        MobileAds.initialize(context) {
            preloadRewardedInterstitial()
        }
    }

    override fun preloadRewardedInterstitial() {
        if (rewardedInterstitialAd != null || isLoadingRewardedInterstitial) return
        isLoadingRewardedInterstitial = true
        RewardedInterstitialAd.load(
            context,
            AdUnitIds.REWARDED_INTERSTITIAL,
            AdRequest.Builder().build(),
            object : RewardedInterstitialAdLoadCallback() {
                override fun onAdLoaded(ad: RewardedInterstitialAd) {
                    isLoadingRewardedInterstitial = false
                    rewardedInterstitialAd = ad
                }

                override fun onAdFailedToLoad(adError: LoadAdError) {
                    isLoadingRewardedInterstitial = false
                    rewardedInterstitialAd = null
                    Log.d(TAG, "Rewarded interstitial failed to load: ${adError.message}")
                }
            }
        )
    }

    override fun showRewardedInterstitialThenProceed(activity: Activity, onComplete: () -> Unit) {
        val ad = rewardedInterstitialAd
        if (ad == null) {
            // Never block the user's save/export just because an ad is not ready.
            onComplete()
            preloadRewardedInterstitial()
            return
        }

        var completed = false
        fun completeOnce() {
            if (completed) return
            completed = true
            onComplete()
        }

        ad.fullScreenContentCallback = object : FullScreenContentCallback() {
            override fun onAdDismissedFullScreenContent() {
                rewardedInterstitialAd = null
                preloadRewardedInterstitial()
                completeOnce()
            }

            override fun onAdFailedToShowFullScreenContent(adError: AdError) {
                rewardedInterstitialAd = null
                preloadRewardedInterstitial()
                completeOnce()
            }
        }

        runCatching {
            ad.show(activity) { /* reward earned - no gameplay currency to grant, save proceeds on dismiss */ }
        }.onFailure {
            completeOnce()
        }
    }

    override suspend fun awaitRewardedInterstitial(activity: Activity) {
        suspendCancellableCoroutine { continuation ->
            showRewardedInterstitialThenProceed(activity) {
                if (continuation.isActive) continuation.resume(Unit)
            }
        }
    }

    override fun loadNativeAd(onLoaded: (NativeAd) -> Unit, onFailed: () -> Unit) {
        val adLoader = AdLoader.Builder(context, AdUnitIds.NATIVE)
            .forNativeAd { nativeAd -> onLoaded(nativeAd) }
            .withAdListener(object : com.google.android.gms.ads.AdListener() {
                override fun onAdFailedToLoad(adError: LoadAdError) {
                    onFailed()
                }
            })
            .build()
        adLoader.loadAd(AdRequest.Builder().build())
    }

    companion object {
        private const val TAG = "AdsManager"
    }
}
