package com.questionpapermaker.app.data.model

import kotlinx.serialization.Serializable

/**
 * Reusable defaults a teacher saves once and gets applied to every new paper
 * (institution branding, department, footer, signature) so they never retype them.
 */
@Serializable
data class TeacherDefaults(
    val institutionName: String? = null,
    val institutionLogoUri: String? = null,
    val department: String? = null,
    val programme: String? = null,
    val footerText: String? = null,
    val signatureLabel: String? = null
)
