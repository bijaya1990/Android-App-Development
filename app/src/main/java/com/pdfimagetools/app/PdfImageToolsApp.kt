package com.pdfimagetools.app

import android.app.Application
import android.util.Log
import com.pdfimagetools.app.core.di.ServiceLocator
import com.tom_roush.pdfbox.android.PDFBoxResourceLoader
import org.opencv.android.OpenCVLoader

class PdfImageToolsApp : Application() {

    override fun onCreate() {
        super.onCreate()
        ServiceLocator.init(this)
        PDFBoxResourceLoader.init(applicationContext)
        ServiceLocator.adsManager.initialize()
        ServiceLocator.notificationHelper.ensureChannel()

        if (!OpenCVLoader.initLocal()) {
            Log.e("PdfImageToolsApp", "OpenCV native library failed to load; document edge detection will fall back to a fixed margin")
        }
    }
}
