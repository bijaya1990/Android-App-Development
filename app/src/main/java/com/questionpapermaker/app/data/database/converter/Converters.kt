package com.questionpapermaker.app.data.database.converter

import androidx.room.TypeConverter
import com.questionpapermaker.app.data.model.FooterStyle
import com.questionpapermaker.app.data.model.HeaderStyle
import com.questionpapermaker.app.data.model.MarginPreset
import com.questionpapermaker.app.data.model.NumberingStyle
import com.questionpapermaker.app.data.model.PageNumberPosition
import com.questionpapermaker.app.data.model.PageOrientation
import com.questionpapermaker.app.data.model.PageSize
import com.questionpapermaker.app.data.model.QuestionOption
import com.questionpapermaker.app.data.model.QuestionType
import com.questionpapermaker.app.data.model.SubQuestion
import kotlinx.serialization.encodeToString
import kotlinx.serialization.json.Json

private val json = Json { ignoreUnknownKeys = true; encodeDefaults = true }

class Converters {

    @TypeConverter
    fun fromPageSize(value: PageSize): String = value.name

    @TypeConverter
    fun toPageSize(value: String): PageSize = enumValueOrDefault(value, PageSize.A4)

    @TypeConverter
    fun fromOrientation(value: PageOrientation): String = value.name

    @TypeConverter
    fun toOrientation(value: String): PageOrientation = enumValueOrDefault(value, PageOrientation.PORTRAIT)

    @TypeConverter
    fun fromMarginPreset(value: MarginPreset): String = value.name

    @TypeConverter
    fun toMarginPreset(value: String): MarginPreset = enumValueOrDefault(value, MarginPreset.MEDIUM)

    @TypeConverter
    fun fromHeaderStyle(value: HeaderStyle): String = value.name

    @TypeConverter
    fun toHeaderStyle(value: String): HeaderStyle = enumValueOrDefault(value, HeaderStyle.PROFESSIONAL)

    @TypeConverter
    fun fromFooterStyle(value: FooterStyle): String = value.name

    @TypeConverter
    fun toFooterStyle(value: String): FooterStyle = enumValueOrDefault(value, FooterStyle.SIMPLE)

    @TypeConverter
    fun fromPageNumberPosition(value: PageNumberPosition): String = value.name

    @TypeConverter
    fun toPageNumberPosition(value: String): PageNumberPosition =
        enumValueOrDefault(value, PageNumberPosition.BOTTOM_CENTER)

    @TypeConverter
    fun fromNumberingStyle(value: NumberingStyle): String = value.name

    @TypeConverter
    fun toNumberingStyle(value: String): NumberingStyle = enumValueOrDefault(value, NumberingStyle.ARABIC)

    @TypeConverter
    fun fromQuestionType(value: QuestionType): String = value.name

    @TypeConverter
    fun toQuestionType(value: String): QuestionType = enumValueOrDefault(value, QuestionType.DEFAULT)

    @TypeConverter
    fun fromSubQuestionList(value: List<SubQuestion>): String = json.encodeToString(value)

    @TypeConverter
    fun toSubQuestionList(value: String): List<SubQuestion> =
        if (value.isBlank()) emptyList() else json.decodeFromString(value)

    @TypeConverter
    fun fromQuestionOptionList(value: List<QuestionOption>): String = json.encodeToString(value)

    @TypeConverter
    fun toQuestionOptionList(value: String): List<QuestionOption> =
        if (value.isBlank()) emptyList() else json.decodeFromString(value)

    private inline fun <reified T : Enum<T>> enumValueOrDefault(value: String, default: T): T =
        try {
            enumValueOf<T>(value)
        } catch (e: IllegalArgumentException) {
            default
        }
}
