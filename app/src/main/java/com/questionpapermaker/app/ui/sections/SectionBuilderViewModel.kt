package com.questionpapermaker.app.ui.sections

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.questionpapermaker.app.data.database.entity.QuestionEntity
import com.questionpapermaker.app.data.database.entity.SectionEntity
import com.questionpapermaker.app.data.model.PaperWithContent
import com.questionpapermaker.app.data.model.SectionWithQuestions
import com.questionpapermaker.app.data.repository.PaperRepository
import com.questionpapermaker.app.engine.NumberedSection
import com.questionpapermaker.app.engine.NumberingEngine
import com.questionpapermaker.app.engine.ValidationEngine
import com.questionpapermaker.app.engine.ValidationIssue
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch

/** Cap on how many structural edits can be undone -- effectively unlimited for real usage, just bounded to avoid unbounded memory growth. */
private const val MAX_UNDO_HISTORY = 200

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

    // Undo/redo covers structural edits -- add/delete/duplicate/reorder a section or question --
    // captured as full before/after snapshots of the paper's sections+questions (both entity
    // types are immutable data classes, so the List reference itself is already a valid
    // point-in-time snapshot; no manual deep-copy needed). Plain field edits (typing in a title,
    // question text, marks, etc.) are intentionally NOT pushed here: those fire on every
    // keystroke, and undoing one character at a time would make the stack useless. Those edits
    // are still never lost -- they're autosaved immediately like everything else -- they just
    // aren't individually undoable, only preserved as part of whichever structural step
    // surrounds them.
    private val undoStack = ArrayDeque<List<SectionWithQuestions>>()
    private val redoStack = ArrayDeque<List<SectionWithQuestions>>()

    private val _canUndo = MutableStateFlow(false)
    val canUndo: StateFlow<Boolean> = _canUndo.asStateFlow()

    private val _canRedo = MutableStateFlow(false)
    val canRedo: StateFlow<Boolean> = _canRedo.asStateFlow()

    private fun pushUndoSnapshot() {
        val current = content.value?.sections ?: return
        undoStack.addLast(current)
        while (undoStack.size > MAX_UNDO_HISTORY) undoStack.removeFirst()
        redoStack.clear()
        _canUndo.value = true
        _canRedo.value = false
    }

    fun undo() {
        val previous = undoStack.removeLastOrNull() ?: return
        val current = content.value?.sections ?: return
        redoStack.addLast(current)
        _canUndo.value = undoStack.isNotEmpty()
        _canRedo.value = true
        viewModelScope.launch { repository.replaceContent(paperId, previous) }
    }

    fun redo() {
        val next = redoStack.removeLastOrNull() ?: return
        val current = content.value?.sections ?: return
        undoStack.addLast(current)
        _canUndo.value = true
        _canRedo.value = redoStack.isNotEmpty()
        viewModelScope.launch { repository.replaceContent(paperId, next) }
    }

    fun addSection() {
        pushUndoSnapshot()
        viewModelScope.launch { repository.addSection(paperId) }
    }

    fun updateSection(section: SectionEntity) {
        viewModelScope.launch { repository.updateSection(section) }
    }

    fun deleteSection(section: SectionEntity) {
        pushUndoSnapshot()
        viewModelScope.launch { repository.deleteSection(section) }
    }

    fun duplicateSection(section: SectionEntity) {
        pushUndoSnapshot()
        viewModelScope.launch { repository.duplicateSection(section) }
    }

    fun moveSection(section: SectionEntity, delta: Int) {
        val sections = content.value?.sections?.map { it.section } ?: return
        val index = sections.indexOfFirst { it.id == section.id }
        val target = index + delta
        if (index < 0 || target < 0 || target >= sections.size) return
        pushUndoSnapshot()
        val reordered = sections.toMutableList().apply { add(target, removeAt(index)) }
        viewModelScope.launch { repository.reorderSections(paperId, reordered.map { it.id }) }
    }

    fun addQuestion(section: SectionEntity, onCreated: (String) -> Unit = {}) {
        pushUndoSnapshot()
        viewModelScope.launch { onCreated(repository.addQuestion(section).id) }
    }

    fun updateQuestion(question: QuestionEntity) {
        viewModelScope.launch { repository.updateQuestion(question, paperId) }
    }

    fun deleteQuestion(question: QuestionEntity) {
        pushUndoSnapshot()
        viewModelScope.launch { repository.deleteQuestion(question, paperId) }
    }

    fun duplicateQuestion(question: QuestionEntity) {
        pushUndoSnapshot()
        viewModelScope.launch { repository.duplicateQuestion(question, paperId) }
    }

    fun moveQuestion(question: QuestionEntity, delta: Int) {
        val sectionQuestions = content.value?.sections
            ?.firstOrNull { it.section.id == question.sectionId }
            ?.questions ?: return
        val index = sectionQuestions.indexOfFirst { it.id == question.id }
        val target = index + delta
        if (index < 0 || target < 0 || target >= sectionQuestions.size) return
        pushUndoSnapshot()
        val reordered = sectionQuestions.toMutableList().apply { add(target, removeAt(index)) }
        viewModelScope.launch { repository.reorderQuestions(question.sectionId, reordered.map { it.id }, paperId) }
    }

    /** Commits a drag-and-drop reorder: [orderedQuestionIds] is the section's full, final question order. */
    fun reorderQuestions(sectionId: String, orderedQuestionIds: List<String>) {
        pushUndoSnapshot()
        viewModelScope.launch { repository.reorderQuestions(sectionId, orderedQuestionIds, paperId) }
    }
}
