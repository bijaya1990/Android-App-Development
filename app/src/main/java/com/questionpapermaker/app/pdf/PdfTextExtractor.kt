package com.questionpapermaker.app.pdf

import android.content.Context
import com.tom_roush.pdfbox.android.PDFBoxResourceLoader
import com.tom_roush.pdfbox.pdmodel.PDDocument
import com.tom_roush.pdfbox.text.PDFTextStripper
import java.io.InputStream

/**
 * Extracts plain text from a real PDF file via pdfbox-android -- the standard Android port of
 * Apache PDFBox -- so real-world compressed/encoded PDFs (not just trivial uncompressed ones)
 * are supported. The extracted text is fed into
 * [com.questionpapermaker.app.engine.SmartPasteParser] exactly like pasted text.
 */
class PdfTextExtractor(context: Context) {

    private val appContext = context.applicationContext
    private var resourcesLoaded = false

    fun extractText(inputStream: InputStream): String {
        if (!resourcesLoaded) {
            PDFBoxResourceLoader.init(appContext)
            resourcesLoaded = true
        }
        return PDDocument.load(inputStream).use { document ->
            PDFTextStripper().getText(document)
        }
    }
}
