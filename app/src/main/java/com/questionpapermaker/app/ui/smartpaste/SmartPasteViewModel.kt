package com.questionpapermaker.app.ui.smartpaste

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.questionpapermaker.app.data.repository.PaperRepository
import com.questionpapermaker.app.engine.SmartPasteParser
import com.questionpapermaker.app.engine.SmartPasteResult
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

enum class SmartPasteStep { INPUT, PREVIEW }

/**
 * Drives the Smart Paste -> Import Preview flow for one paper. Parsing is instant and
 * client-side (see [SmartPasteParser]); nothing is written to the paper until [confirmImport]
 * is called, and even then only the sections/questions the teacher left checked are imported.
 */
class SmartPasteViewModel(
    private val paperId: String,
    private val repository: PaperRepository
) : ViewModel() {

    private val _step = MutableStateFlow(SmartPasteStep.INPUT)
    val step: StateFlow<SmartPasteStep> = _step.asStateFlow()

    private val _rawText = MutableStateFlow("")
    val rawText: StateFlow<String> = _rawText.asStateFlow()

    private val _result = MutableStateFlow<SmartPasteResult?>(null)
    val result: StateFlow<SmartPasteResult?> = _result.asStateFlow()

    private val _excludedSections = MutableStateFlow<Set<Int>>(emptySet())
    val excludedSections: StateFlow<Set<Int>> = _excludedSections.asStateFlow()

    private val _excludedQuestions = MutableStateFlow<Set<Pair<Int, Int>>>(emptySet())
    val excludedQuestions: StateFlow<Set<Pair<Int, Int>>> = _excludedQuestions.asStateFlow()

    private val _importing = MutableStateFlow(false)
    val importing: StateFlow<Boolean> = _importing.asStateFlow()

    fun onTextChange(text: String) {
        _rawText.value = text
    }

    fun parse() {
        if (_rawText.value.isBlank()) return
        _result.value = SmartPasteParser.parse(_rawText.value)
        _excludedSections.value = emptySet()
        _excludedQuestions.value = emptySet()
        _step.value = SmartPasteStep.PREVIEW
    }

    fun backToInput() {
        _step.value = SmartPasteStep.INPUT
    }

    fun toggleSection(sectionIndex: Int) {
        _excludedSections.value = _excludedSections.value.let {
            if (sectionIndex in it) it - sectionIndex else it + sectionIndex
        }
    }

    fun toggleQuestion(sectionIndex: Int, questionIndex: Int) {
        val key = sectionIndex to questionIndex
        _excludedQuestions.value = _excludedQuestions.value.let {
            if (key in it) it - key else it + key
        }
    }

    fun selectedQuestionCount(): Int {
        val res = _result.value ?: return 0
        var count = 0
        res.sections.forEachIndexed { sectionIndex, section ->
            if (sectionIndex in _excludedSections.value) return@forEachIndexed
            section.questions.forEachIndexed { questionIndex, _ ->
                if ((sectionIndex to questionIndex) !in _excludedQuestions.value) count++
            }
        }
        return count
    }

    fun confirmImport(onDone: () -> Unit) {
        val res = _result.value ?: return
        val excludedSections = _excludedSections.value
        val excludedQuestions = _excludedQuestions.value

        val filteredSections = res.sections.mapIndexedNotNull { sectionIndex, section ->
            if (sectionIndex in excludedSections) return@mapIndexedNotNull null
            val filteredQuestions = section.questions.filterIndexed { questionIndex, _ ->
                (sectionIndex to questionIndex) !in excludedQuestions
            }
            if (filteredQuestions.isEmpty()) null else section.copy(questions = filteredQuestions)
        }
        if (filteredSections.isEmpty()) return

        _importing.value = true
        viewModelScope.launch {
            repository.importSmartPasteResult(paperId, SmartPasteResult(filteredSections, emptyList()))
            _importing.value = false
            onDone()
        }
    }
}
