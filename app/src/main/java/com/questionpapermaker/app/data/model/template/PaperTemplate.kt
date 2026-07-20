package com.questionpapermaker.app.data.model.template

import com.questionpapermaker.app.data.model.FooterStyle
import com.questionpapermaker.app.data.model.HeaderStyle
import com.questionpapermaker.app.data.model.NumberingStyle
import com.questionpapermaker.app.data.model.QuestionType

enum class TemplateCategory(val displayName: String) {
    SCHOOL("School"),
    COLLEGE("College"),
    COACHING("Coaching"),
    GENERAL("General")
}

/** Blueprint for one section a template pre-configures -- no questions, just structure. */
data class TemplateSectionBlueprint(
    val title: String,
    val instruction: String? = null,
    val numberingStyle: NumberingStyle = NumberingStyle.ARABIC,
    val subNumberingStyle: NumberingStyle = NumberingStyle.PARENTHESIZED_LOWER_ALPHA,
    val defaultQuestionType: QuestionType = QuestionType.DEFAULT,
    val defaultMarksPerQuestion: Double? = null
)

/**
 * A ready-made paper layout: header/footer style plus a set of section blueprints. Selecting a
 * template creates a new draft paper pre-configured with these settings -- the teacher still
 * fills in exam name, subject, questions etc, exactly like starting from scratch, just with the
 * structural boilerplate already in place.
 */
data class PaperTemplate(
    val id: String,
    val name: String,
    val category: TemplateCategory,
    val description: String,
    val examType: String? = null,
    val headerStyle: HeaderStyle = HeaderStyle.PROFESSIONAL,
    val footerStyle: FooterStyle = FooterStyle.SIMPLE,
    val showSignatureArea: Boolean = false,
    val generalInstructions: String? = null,
    val sections: List<TemplateSectionBlueprint> = emptyList()
)
