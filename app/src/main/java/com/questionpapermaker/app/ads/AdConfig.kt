package com.questionpapermaker.app.ads

/**
 * Ad unit IDs. These are Google's official public *test* ad units -- they always fill with
 * clearly-labelled test creatives and are safe to ship in a debug build, but they earn no
 * revenue and must never be used in a production release.
 *
 * TODO(ads): replace both constants below with your real AdMob ad unit IDs (from
 * https://apps.admob.com -> your app -> Ad units) before publishing, and replace
 * `admob_app_id` in res/values/strings.xml with your real App ID at the same time.
 */
object AdConfig {
    const val BANNER_AD_UNIT_ID = "ca-app-pub-3940256099942544/6300978111"
    const val INTERSTITIAL_AD_UNIT_ID = "ca-app-pub-3940256099942544/1033173712"
}
