package com.naukripatra.emicalculator.util;

import com.google.android.gms.ads.AdRequest;
import com.google.android.gms.ads.AdView;
import com.naukripatra.emicalculator.BuildConfig;

/**
 * AdMob identifiers for this app. The Application ID itself lives in AndroidManifest.xml
 * (required to be declared there by the SDK); ad unit IDs used at runtime are kept here.
 *
 * Debug builds always use Google's official test ad unit IDs, which serve a guaranteed
 * house/test ad regardless of AdMob account review status. A brand-new AdMob account or a
 * freshly created ad unit routinely serves zero fill for real IDs until Google finishes
 * reviewing it — switching to the test IDs while developing is the standard way to confirm
 * the integration itself works instead of chasing a fill-rate problem.
 *
 * The banner's ad unit ID and size are both declared declaratively in layout_ad_banner.xml
 * (app:adUnitId / app:adSize) rather than set programmatically — AdMob's AdView only allows
 * each to be set once, ever, and setting them in Java after XML inflation proved unreliable
 * to sequence correctly, so this sticks to Google's simplest documented pattern.
 */
public final class AdConfig {

    private static final String REAL_INTERSTITIAL_AD_UNIT_ID = "ca-app-pub-5327551236542725/1102104301";
    private static final String TEST_INTERSTITIAL_AD_UNIT_ID = "ca-app-pub-3940256099942544/1033173712";

    private AdConfig() {
    }

    public static String interstitialAdUnitId() {
        return BuildConfig.DEBUG ? TEST_INTERSTITIAL_AD_UNIT_ID : REAL_INTERSTITIAL_AD_UNIT_ID;
    }

    /** Triggers the load for the banner AdView declared in {@code layout_ad_banner.xml}. */
    public static void loadBanner(AdView adView) {
        adView.loadAd(new AdRequest.Builder().build());
    }
}
