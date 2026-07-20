package com.questionpapermaker.app.ui.settings

enum class LegalDocumentType(val routeValue: String, val title: String) {
    PRIVACY_POLICY("privacy_policy", "Privacy Policy"),
    TERMS_OF_USE("terms_of_use", "Terms of Use"),
    CONTACT_US("contact_us", "Contact Us");

    companion object {
        fun fromRouteValue(value: String?): LegalDocumentType =
            entries.firstOrNull { it.routeValue == value } ?: PRIVACY_POLICY
    }
}
