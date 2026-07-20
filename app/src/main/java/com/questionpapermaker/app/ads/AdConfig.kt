package com.questionpapermaker.app.ads

/**
 * Real AdMob ad unit IDs (publisher ca-app-pub-5327551236542725).
 *
 * TODO(ads): the App ID in res/values/strings.xml (admob_app_id) is still Google's public
 * *test* App ID -- replace it with the real one from AdMob (App -> Settings -> App ID) before
 * publishing. Ads will not serve correctly with a mismatched App ID / ad unit ID pairing.
 */
object AdConfig {
    /** "Home Banner" ad unit. */
    const val BANNER_AD_UNIT_ID = "ca-app-pub-5327551236542725/5339155808"

    /** Rewarded Interstitial ad unit shown before a PDF download completes. */
    const val INTERSTITIAL_AD_UNIT_ID = "ca-app-pub-5327551236542725/1870684081"
}
