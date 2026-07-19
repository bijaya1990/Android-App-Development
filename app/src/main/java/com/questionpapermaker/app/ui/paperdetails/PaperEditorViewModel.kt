package com.questionpapermaker.app.ui.paperdetails

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.questionpapermaker.app.data.database.entity.PaperEntity
import com.questionpapermaker.app.data.model.TeacherDefaults
import com.questionpapermaker.app.data.repository.DefaultsRepository
import com.questionpapermaker.app.data.repository.PaperRepository
import kotlinx.coroutines.FlowPreview
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.debounce
import kotlinx.coroutines.flow.filterNotNull
import kotlinx.coroutines.flow.launchIn
import kotlinx.coroutines.flow.onEach
import kotlinx.coroutines.launch

/**
 * Edits a single [PaperEntity] end to end -- both the Paper Details screen (institution, exam
 * metadata) and the Paper Layout screen (page size, margins, header/footer style) share this
 * ViewModel since they mutate the same document and both autosave the same way.
 *
 * Every field is optional except examName, subject and maxMarks (enforced in the UI, not the
 * data layer, so the draft can always be saved mid-edit).
 */
class PaperEditorViewModel(
    private val paperId: String,
    private val repository: PaperRepository,
    private val defaultsRepository: DefaultsRepository
) : ViewModel() {

    private val _paper = MutableStateFlow<PaperEntity?>(null)
    val paper: StateFlow<PaperEntity?> = _paper

    init {
        viewModelScope.launch { _paper.value = repository.getPaper(paperId) }

        @OptIn(FlowPreview::class)
        _paper.filterNotNull()
            .debounce(400)
            .onEach { repository.savePaper(it) }
            .launchIn(viewModelScope)
    }

    fun update(transform: (PaperEntity) -> PaperEntity) {
        _paper.value = _paper.value?.let(transform)
    }

    fun saveAsDefaults() {
        val current = _paper.value ?: return
        viewModelScope.launch {
            defaultsRepository.saveDefaults(
                TeacherDefaults(
                    institutionName = current.institutionName,
                    institutionLogoUri = current.institutionLogoUri,
                    department = current.department,
                    programme = current.programme,
                    footerText = null,
                    signatureLabel = current.signatureLabel
                )
            )
        }
    }

    /** Saves immediately (bypassing the autosave debounce) so navigation never loses the last edit. */
    fun saveNow(onSaved: () -> Unit) {
        val current = _paper.value ?: return
        viewModelScope.launch {
            repository.savePaper(current)
            onSaved()
        }
    }
}
