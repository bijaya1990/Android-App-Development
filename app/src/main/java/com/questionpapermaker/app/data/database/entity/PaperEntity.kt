package com.questionpapermaker.app.data.database.entity

import androidx.room.Entity
import androidx.room.PrimaryKey
import com.questionpapermaker.app.data.model.FooterStyle
import com.questionpapermaker.app.data.model.HeaderStyle
import com.questionpapermaker.app.data.model.MarginPreset
import com.questionpapermaker.app.data.model.PageNumberPosition
import com.questionpapermaker.app.data.model.PageOrientation
import com.questionpapermaker.app.data.model.PageSize

/**
 * A question paper. Every field except [examName], [subject] and [maxMarks] is optional
 * by design so the app stays usable across countries and institution types.
 */
@Entity(tableName = "papers")
data class PaperEntity(
    @PrimaryKey val id: String,

    // Paper Details
    val institutionName: String? = null,
    val institutionLogoUri: String? = null,
    val examName: String = "",
    val examType: String? = null,
    val subject: String = "",
    val courseName: String? = null,
    val courseCode: String? = null,
    val programme: String? = null,
    val department: String? = null,
    val faculty: String? = null,
    val className: String? = null,
    val semester: String? = null,
    val academicSession: String? = null,
    val academicYear: String? = null,
    val examDate: String? = null,
    val duration: String? = null,
    val maxMarks: String = "",
    val generalInstructions: String? = null,

    // Paper Layout
    val pageSize: PageSize = PageSize.A4,
    val orientation: PageOrientation = PageOrientation.PORTRAIT,
    val marginPreset: MarginPreset = MarginPreset.MEDIUM,
    val customMarginTopPt: Float? = null,
    val customMarginBottomPt: Float? = null,
    val customMarginLeftPt: Float? = null,
    val customMarginRightPt: Float? = null,
    val headerStyle: HeaderStyle = HeaderStyle.PROFESSIONAL,
    val footerStyle: FooterStyle = FooterStyle.SIMPLE,
    val pageNumberPosition: PageNumberPosition = PageNumberPosition.BOTTOM_CENTER,
    val showInstitutionLogo: Boolean = true,
    val showSignatureArea: Boolean = false,
    val signatureLabel: String? = null,
    val watermarkText: String? = null,
    val continuousNumbering: Boolean = true,
    /** Compact print: two flowing columns per page instead of one, for worksheets/notes. */
    val compactTwoColumnPrint: Boolean = false,

    // Bookkeeping
    val createdAt: Long = System.currentTimeMillis(),
    val updatedAt: Long = System.currentTimeMillis(),
    val isTrashed: Boolean = false,
    val trashedAt: Long? = null,
    val lastExportedAt: Long? = null,
    val lastExportedPdfUri: String? = null
) {
    /** Human friendly title used in Recent Papers / Home. Never blank. */
    val displayTitle: String
        get() = listOfNotNull(examName.ifBlank { null }, subject.ifBlank { null })
            .joinToString(" – ")
            .ifBlank { "Untitled Paper" }
}
