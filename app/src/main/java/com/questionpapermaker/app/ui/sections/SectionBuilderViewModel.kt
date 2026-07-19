package com.questionpapermaker.app.ui.sections

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.questionpapermaker.app.data.database.entity.QuestionEntity
import com.questionpapermaker.app.data.database.entity.SectionEntity
import com.questionpapermaker.app.data.model.PaperWithContent
import com.questionpapermaker.app.data.repository.PaperRepository
import com.questionpapermaker.app.engine.NumberedSection
import com.questionpapermaker.app.engine.NumberingEngine
import com.questionpapermaker.app.engine.ValidationEngine
import com.questionpapermaker.app.engine.ValidationIssue
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch

class SectionBuilderViewModel(
    private val paperId: String,
    private val repository: PaperRepository
) : ViewModel() {

    val content: StateFlow<PaperWithContent?> = repository.observePaperWithContent(paperId)
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), null)

    val numberedSections: StateFlow<List<NumberedSection>> = content
        .map { c -> c?.let { NumberingEngine.number(it.sections, it.paper.continuousNumbering) }.orEmpty() }
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), emptyList())

    val validationIssues: StateFlow<List<ValidationIssue>> = content
        .map { c -> c?.let { ValidationEngine.validate(it) }.orEmpty() }
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), emptyList())

    fun addSection() {
        viewModelScope.launch { repository.addSection(paperId) }
    }

    fun updateSection(section: SectionEntity) {
        viewModelScope.launch { repository.updateSection(section) }
    }

    fun deleteSection(section: SectionEntity) {
        viewModelScope.launch { repository.deleteSection(section) }
    }

    fun moveSection(section: SectionEntity, delta: Int) {
        val sections = content.value?.sections?.map { it.section } ?: return
        val index = sections.indexOfFirst { it.id == section.id }
        val target = index + delta
        if (index < 0 || target < 0 || target >= sections.size) return
        val reordered = sections.toMutableList().apply { add(target, removeAt(index)) }
        viewModelScope.launch { repository.reorderSections(paperId, reordered.map { it.id }) }
    }

    fun addQuestion(section: SectionEntity) {
        viewModelScope.launch { repository.addQuestion(section) }
    }

    fun updateQuestion(question: QuestionEntity) {
        viewModelScope.launch { repository.updateQuestion(question, paperId) }
    }

    fun deleteQuestion(question: QuestionEntity) {
        viewModelScope.launch { repository.deleteQuestion(question, paperId) }
    }

    fun duplicateQuestion(question: QuestionEntity) {
        viewModelScope.launch { repository.duplicateQuestion(question, paperId) }
    }

    fun moveQuestion(question: QuestionEntity, delta: Int) {
        val sectionQuestions = content.value?.sections
            ?.firstOrNull { it.section.id == question.sectionId }
            ?.questions ?: return
        val index = sectionQuestions.indexOfFirst { it.id == question.id }
        val target = index + delta
        if (index < 0 || target < 0 || target >= sectionQuestions.size) return
        val reordered = sectionQuestions.toMutableList().apply { add(target, removeAt(index)) }
        viewModelScope.launch { repository.reorderQuestions(question.sectionId, reordered.map { it.id }, paperId) }
    }
}
