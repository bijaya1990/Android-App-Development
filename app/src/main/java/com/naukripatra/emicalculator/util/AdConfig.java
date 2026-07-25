package com.naukripatra.emicalculator.util;

import com.google.android.gms.ads.AdRequest;
import com.google.android.gms.ads.AdSize;
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

    /** Loads the shared banner ad unit into a {@code layout_ad_banner.xml} AdView. */
    public static void loadBanner(AdView adView) {
        adView.setAdUnitId(BANNER_AD_UNIT_ID);
        adView.setAdSize(AdSize.BANNER);
        adView.loadAd(new AdRequest.Builder().build());
    }
}
