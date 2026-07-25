package com.naukripatra.emicalculator.util;

import com.google.android.gms.ads.AdRequest;
import com.google.android.gms.ads.AdView;

/**
 * AdMob identifiers for this app. The Application ID itself lives in AndroidManifest.xml
 * (required to be declared there by the SDK); ad unit IDs used at runtime are kept here.
 */
public final class AdConfig {

    public static final String BANNER_AD_UNIT_ID = "ca-app-pub-5327551236542725/8738151826";
    public static final String INTERSTITIAL_AD_UNIT_ID = "ca-app-pub-5327551236542725/1102104301";

    private AdConfig() {
    }

    /**
     * Loads the shared banner ad unit into a {@code layout_ad_banner.xml} AdView. The ad size
     * and ad unit ID are already declared in that layout's XML (required so the SDK knows the
     * banner's size before its first layout pass), so this only needs to trigger the load.
     */
    public static void loadBanner(AdView adView) {
        adView.loadAd(new AdRequest.Builder().build());
    }
}
