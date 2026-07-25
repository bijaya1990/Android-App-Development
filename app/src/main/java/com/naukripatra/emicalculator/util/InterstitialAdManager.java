package com.naukripatra.emicalculator.util;

import android.app.Activity;
import android.content.Context;
import android.content.SharedPreferences;

import androidx.annotation.NonNull;

import com.google.android.gms.ads.AdError;
import com.google.android.gms.ads.AdRequest;
import com.google.android.gms.ads.FullScreenContentCallback;
import com.google.android.gms.ads.LoadAdError;
import com.google.android.gms.ads.interstitial.InterstitialAd;
import com.google.android.gms.ads.interstitial.InterstitialAdLoadCallback;

/**
 * Shows a full-screen interstitial the 1st time the user taps Calculate, then again every
 * 3rd time after (taps 1, 3, 6, 9, ...). The ad is preloaded ahead of time so most taps don't
 * wait on a network call, and {@code onComplete} always fires — whether or not an ad was shown —
 * so callers can safely navigate on afterward without ever blocking the user.
 */
public final class InterstitialAdManager {

    private static final String PREFS_NAME = "ad_prefs";
    private static final String KEY_CLICK_COUNT = "calculate_click_count";

    private static InterstitialAd interstitialAd;
    private static boolean loading;

    private InterstitialAdManager() {
    }

    public interface OnCompleteListener {
        void onComplete();
    }

    public static void preload(Context context) {
        if (interstitialAd != null || loading) {
            return;
        }
        loading = true;
        InterstitialAd.load(context.getApplicationContext(), AdConfig.INTERSTITIAL_AD_UNIT_ID,
                new AdRequest.Builder().build(), new InterstitialAdLoadCallback() {
                    @Override
                    public void onAdLoaded(@NonNull InterstitialAd ad) {
                        interstitialAd = ad;
                        loading = false;
                    }

                    @Override
                    public void onAdFailedToLoad(@NonNull LoadAdError loadAdError) {
                        interstitialAd = null;
                        loading = false;
                    }
                });
    }

    public static void showIfEligible(Activity activity, OnCompleteListener listener) {
        SharedPreferences prefs = activity.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE);
        int count = prefs.getInt(KEY_CLICK_COUNT, 0) + 1;
        prefs.edit().putInt(KEY_CLICK_COUNT, count).apply();

        boolean eligible = count == 1 || count % 3 == 0;

        if (!eligible || interstitialAd == null) {
            listener.onComplete();
            preload(activity);
            return;
        }

        InterstitialAd adToShow = interstitialAd;
        interstitialAd = null;
        adToShow.setFullScreenContentCallback(new FullScreenContentCallback() {
            @Override
            public void onAdDismissedFullScreenContent() {
                preload(activity);
                listener.onComplete();
            }

            @Override
            public void onAdFailedToShowFullScreenContent(@NonNull AdError adError) {
                preload(activity);
                listener.onComplete();
            }
        });
        adToShow.show(activity);
    }
}
