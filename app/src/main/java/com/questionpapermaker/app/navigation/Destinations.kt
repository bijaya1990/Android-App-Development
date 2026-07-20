package com.questionpapermaker.app.navigation

/** Every screen the app can navigate to, keyed by a stable route so deep-linking stays simple. */
sealed class Destination(val route: String) {
    data object Splash : Destination("splash")

    data object Home : Destination("home")

    data object PaperDetails : Destination("paper_details/{paperId}") {
        fun createRoute(paperId: String) = "paper_details/$paperId"
    }

    data object PaperLayout : Destination("paper_layout/{paperId}") {
        fun createRoute(paperId: String) = "paper_layout/$paperId"
    }

    data object SectionBuilder : Destination("section_builder/{paperId}") {
        fun createRoute(paperId: String) = "section_builder/$paperId"
    }

    data object Preview : Destination("preview/{paperId}") {
        fun createRoute(paperId: String) = "preview/$paperId"
    }

    data object ExportSuccess : Destination("export_success/{paperId}") {
        fun createRoute(paperId: String) = "export_success/$paperId"
    }

    data object Settings : Destination("settings")

    data object LegalDocument : Destination("legal/{docType}") {
        fun createRoute(docType: String) = "legal/$docType"
    }

    companion object {
        const val ARG_PAPER_ID = "paperId"
        const val ARG_DOC_TYPE = "docType"
    }
}
