package com.pdfimagetools.app.core.image

import android.graphics.Bitmap

enum class ImageFormat(val extension: String, val mimeType: String, val compressFormat: Bitmap.CompressFormat) {
    JPEG("jpg", "image/jpeg", Bitmap.CompressFormat.JPEG),
    PNG("png", "image/png", Bitmap.CompressFormat.PNG)
}
