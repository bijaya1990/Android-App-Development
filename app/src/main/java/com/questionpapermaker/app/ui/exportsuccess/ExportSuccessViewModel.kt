package com.questionpapermaker.app.ui.exportsuccess

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.questionpapermaker.app.data.database.entity.PaperEntity
import com.questionpapermaker.app.data.repository.PaperRepository
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.stateIn

class ExportSuccessViewModel(
    paperId: String,
    repository: PaperRepository
) : ViewModel() {

    val paper: StateFlow<PaperEntity?> = repository.observePaper(paperId)
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), null)
}
