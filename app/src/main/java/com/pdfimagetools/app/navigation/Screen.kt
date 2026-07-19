package com.pdfimagetools.app.navigation

sealed class Screen(val route: String) {
    data object Splash : Screen("splash")
    data object Home : Screen("home")
    data object QuickScan : Screen("quick_scan")
    data object ImageToPdf : Screen("image_to_pdf")
    data object MergePdf : Screen("merge_pdf")
    data object SplitPdf : Screen("split_pdf")
    data object CompressPdf : Screen("compress_pdf")
    data object CompressImage : Screen("compress_image")
    data object PdfToImage : Screen("pdf_to_image")
    data object RotatePdf : Screen("rotate_pdf")
    data object OrganizePages : Screen("organize_pages")
    data object ProtectPdf : Screen("protect_pdf")

    data object ConvertFormat : Screen("convert_format/{target}") {
        const val ARG_TARGET = "target"
        const val TARGET_PNG = "png"
        const val TARGET_JPG = "jpg"
        fun routeFor(target: String) = "convert_format/$target"
    }
}
