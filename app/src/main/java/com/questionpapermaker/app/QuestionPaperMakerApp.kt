package com.questionpapermaker.app

import android.app.Application
import com.google.android.gms.ads.MobileAds
import com.questionpapermaker.app.data.AppContainer

class QuestionPaperMakerApp : Application() {
    lateinit var container: AppContainer
        private set

    override fun onCreate() {
        super.onCreate()
        container = AppContainer(this)

        MobileAds.initialize(this) {
            container.interstitialAdManager.preload()
        }
    }
}
