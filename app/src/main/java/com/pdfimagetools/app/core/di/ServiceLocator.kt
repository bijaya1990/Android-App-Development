package com.pdfimagetools.app.core.di

import android.content.Context
import com.pdfimagetools.app.core.ads.AdsManager
import com.pdfimagetools.app.core.ads.AdsManagerImpl
import com.pdfimagetools.app.core.image.ImageManager
import com.pdfimagetools.app.core.image.ImageManagerImpl
import com.pdfimagetools.app.core.notifications.NotificationHelper
import com.pdfimagetools.app.core.pdf.PdfManager
import com.pdfimagetools.app.core.pdf.PdfManagerImpl
import com.pdfimagetools.app.core.storage.StorageManager
import com.pdfimagetools.app.core.storage.StorageManagerImpl
import com.pdfimagetools.app.data.RecentFilesStore
import com.pdfimagetools.app.data.SettingsStore
import com.pdfimagetools.app.domain.SaveOrchestrator

/** Minimal hand-rolled service locator; keeps the dependency graph explicit without a DI framework. */
object ServiceLocator {

    private lateinit var appContext: Context

    val storageManager: StorageManager by lazy { StorageManagerImpl(appContext) }
    val pdfManager: PdfManager by lazy { PdfManagerImpl(appContext) }
    val imageManager: ImageManager by lazy { ImageManagerImpl(appContext) }
    val adsManager: AdsManager by lazy { AdsManagerImpl(appContext) }
    val notificationHelper: NotificationHelper by lazy { NotificationHelper(appContext, storageManager) }
    val recentFilesStore: RecentFilesStore by lazy { RecentFilesStore(appContext) }
    val settingsStore: SettingsStore by lazy { SettingsStore(appContext) }
    val saveOrchestrator: SaveOrchestrator by lazy {
        SaveOrchestrator(storageManager, adsManager, notificationHelper, recentFilesStore)
    }

    fun init(context: Context) {
        appContext = context.applicationContext
    }
}
