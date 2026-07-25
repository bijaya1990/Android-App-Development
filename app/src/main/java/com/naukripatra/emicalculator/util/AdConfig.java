package com.naukripatra.emicalculator.util;

import android.app.Activity;
import android.util.DisplayMetrics;

import com.google.android.gms.ads.AdRequest;
import com.google.android.gms.ads.AdSize;
import com.google.android.gms.ads.AdView;
import com.naukripatra.emicalculator.BuildConfig;
import com.naukripatra.emicalculator.R;

/**
 * AdMob identifiers for this app. The Application ID itself lives in AndroidManifest.xml
 * (required to be declared there by the SDK); ad unit IDs used at runtime are kept here.
 *
 * Debug builds always use Google's official test ad unit IDs, which serve a guaranteed
 * house/test ad regardless of AdMob account review status. A brand-new AdMob account or a
 * freshly created ad unit routinely serves zero fill for real IDs until Google finishes
 * reviewing it — switching to the test IDs while developing is the standard way to confirm
 * the integration itself works instead of chasing a fill-rate problem.
 */
public final class AdConfig {

    private static final String REAL_INTERSTITIAL_AD_UNIT_ID = "ca-app-pub-5327551236542725/1102104301";
    private static final String TEST_INTERSTITIAL_AD_UNIT_ID = "ca-app-pub-3940256099942544/1033173712";

    private AdConfig() {
    }

    public static String interstitialAdUnitId() {
        return BuildConfig.DEBUG ? TEST_INTERSTITIAL_AD_UNIT_ID : REAL_INTERSTITIAL_AD_UNIT_ID;
    }

    /**
     * Loads the shared banner ad unit into a {@code layout_ad_banner.xml} AdView. Both the ad
     * unit ID and the ad size are set here in Java — not via XML attributes — and in this exact
     * order, since AdMob's AdView allows each to be set only once, ever, and requires both to be
     * set before {@code loadAd()} is called. The ad unit ID resolves from
     * {@code R.string.admob_banner_ad_unit_id}, which the debug build type overrides with
     * Google's test banner ID — see {@code src/debug/res/values/strings.xml}.
     */
    public static void loadBanner(Activity activity, AdView adView) {
        adView.setAdUnitId(activity.getString(R.string.admob_banner_ad_unit_id));
        adView.setAdSize(adaptiveBannerSize(activity));
        adView.loadAd(new AdRequest.Builder().build());
    }

    private static AdSize adaptiveBannerSize(Activity activity) {
        DisplayMetrics outMetrics = activity.getResources().getDisplayMetrics();
        float density = outMetrics.density;
        int adWidthPixels = outMetrics.widthPixels;
        int adWidthDp = (int) (adWidthPixels / density);
        return AdSize.getCurrentOrientationAnchoredAdaptiveBannerAdSize(activity, adWidthDp);
    }
}
