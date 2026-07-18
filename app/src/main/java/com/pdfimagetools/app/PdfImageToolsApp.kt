package com.pdfimagetools.app

import android.app.Application
import com.pdfimagetools.app.core.di.ServiceLocator
import com.tom_roush.pdfbox.android.PDFBoxResourceLoader

class PdfImageToolsApp : Application() {

    override fun onCreate() {
        super.onCreate()
        ServiceLocator.init(this)
        PDFBoxResourceLoader.init(applicationContext)
        ServiceLocator.adsManager.initialize()
        ServiceLocator.notificationHelper.ensureChannel()
    }
}
