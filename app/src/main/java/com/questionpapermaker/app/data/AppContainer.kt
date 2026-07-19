package com.questionpapermaker.app.data

import android.content.Context
import com.questionpapermaker.app.data.database.AppDatabase
import com.questionpapermaker.app.data.repository.DefaultsRepository
import com.questionpapermaker.app.data.repository.PaperRepository
import com.questionpapermaker.app.pdf.PdfExporter

/**
 * Hand-rolled dependency container. The app is small enough that a DI framework would be
 * pure ceremony; every screen reaches its dependencies through this single object.
 */
class AppContainer(context: Context) {
    val appContext: Context = context.applicationContext

    val database: AppDatabase by lazy { AppDatabase.getInstance(appContext) }
    val defaultsRepository: DefaultsRepository by lazy { DefaultsRepository(appContext) }
    val paperRepository: PaperRepository by lazy { PaperRepository(database, defaultsRepository) }
    val pdfExporter: PdfExporter by lazy { PdfExporter(appContext) }
}
