package com.questionpapermaker.app.ui.home

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.questionpapermaker.app.data.database.entity.PaperEntity
import com.questionpapermaker.app.data.repository.PaperRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.debounce
import kotlinx.coroutines.flow.flatMapLatest
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch

class HomeViewModel(private val repository: PaperRepository) : ViewModel() {

    private val searchQuery = MutableStateFlow("")
    val query: StateFlow<String> = searchQuery

    @OptIn(kotlinx.coroutines.FlowPreview::class, kotlinx.coroutines.ExperimentalCoroutinesApi::class)
    val papers: StateFlow<List<PaperEntity>> = searchQuery
        .debounce(150)
        .flatMapLatest { repository.searchPapers(it) }
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), emptyList())

    fun onSearchQueryChange(newQuery: String) {
        searchQuery.value = newQuery
    }

    fun createNewPaper(onCreated: (String) -> Unit) {
        viewModelScope.launch {
            val paper = repository.createDraftPaper()
            onCreated(paper.id)
        }
    }

    fun duplicatePaper(paperId: String) {
        viewModelScope.launch { repository.duplicatePaper(paperId) }
    }

    fun moveToTrash(paperId: String) {
        viewModelScope.launch { repository.moveToTrash(paperId) }
    }
}
