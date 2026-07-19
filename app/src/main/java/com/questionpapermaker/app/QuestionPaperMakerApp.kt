package com.questionpapermaker.app

import android.app.Application
import com.questionpapermaker.app.data.AppContainer

class QuestionPaperMakerApp : Application() {
    lateinit var container: AppContainer
        private set

    override fun onCreate() {
        super.onCreate()
        container = AppContainer(this)
    }
}
