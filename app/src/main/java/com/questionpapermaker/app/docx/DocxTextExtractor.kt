package com.questionpapermaker.app.docx

import org.xmlpull.v1.XmlPullParser
import org.xmlpull.v1.XmlPullParserFactory
import java.io.InputStream
import java.util.zip.ZipInputStream

/**
 * Extracts plain paragraph text from a real .docx file -- a ZIP package containing
 * WordprocessingML parts -- using only [java.util.zip] and Android's bundled XML pull parser,
 * no external library. The extracted text is fed into [com.questionpapermaker.app.engine.SmartPasteParser]
 * exactly like pasted text, so numbering/sections/options/marks are still auto-detected.
 */
object DocxTextExtractor {

    fun extractText(inputStream: InputStream): String {
        ZipInputStream(inputStream).use { zip ->
            var entry = zip.nextEntry
            while (entry != null) {
                if (entry.name == "word/document.xml") {
                    return parseDocumentXml(zip)
                }
                entry = zip.nextEntry
            }
        }
        return ""
    }

    private fun parseDocumentXml(stream: InputStream): String {
        val parser = XmlPullParserFactory.newInstance().newPullParser()
        parser.setInput(stream, "UTF-8")

        val result = StringBuilder()
        val paragraph = StringBuilder()
        var insideText = false
        var eventType = parser.eventType

        while (eventType != XmlPullParser.END_DOCUMENT) {
            when (eventType) {
                XmlPullParser.START_TAG -> when (parser.name) {
                    "w:t" -> insideText = true
                    "w:tab" -> paragraph.append('\t')
                    "w:br" -> paragraph.append('\n')
                }
                XmlPullParser.TEXT -> if (insideText) paragraph.append(parser.text)
                XmlPullParser.END_TAG -> when (parser.name) {
                    "w:t" -> insideText = false
                    "w:p" -> {
                        result.append(paragraph).append('\n')
                        paragraph.setLength(0)
                    }
                }
            }
            eventType = parser.next()
        }
        return result.toString()
    }
}
