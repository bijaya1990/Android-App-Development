package com.pdfimagetools.app.navigation

sealed class Screen(val route: String) {
    data object Home : Screen("home")
    data object ImageToPdf : Screen("image_to_pdf")
    data object MergePdf : Screen("merge_pdf")
    data object SplitPdf : Screen("split_pdf")
    data object CompressPdf : Screen("compress_pdf")
    data object CompressImage : Screen("compress_image")
    data object PdfToImage : Screen("pdf_to_image")
    data object RecentFiles : Screen("recent_files")
    data object Settings : Screen("settings")

    data object ConvertFormat : Screen("convert_format/{target}") {
        const val ARG_TARGET = "target"
        const val TARGET_PNG = "png"
        const val TARGET_JPG = "jpg"
        fun routeFor(target: String) = "convert_format/$target"
    }
}
