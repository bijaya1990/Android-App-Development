package com.questionpapermaker.app.ui.templates

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.questionpapermaker.app.data.model.template.PaperTemplate
import com.questionpapermaker.app.data.repository.PaperRepository
import com.questionpapermaker.app.data.repository.TemplateFavoritesRepository
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch

class TemplatePickerViewModel(
    private val repository: PaperRepository,
    private val favoritesRepository: TemplateFavoritesRepository
) : ViewModel() {

    val favoriteIds: StateFlow<Set<String>> = favoritesRepository.favoriteIds

    fun useTemplate(template: PaperTemplate, onCreated: (String) -> Unit) {
        viewModelScope.launch {
            val paper = repository.createPaperFromTemplate(template)
            onCreated(paper.id)
        }
    }

    fun toggleFavorite(template: PaperTemplate) {
        viewModelScope.launch { favoritesRepository.toggleFavorite(template.id) }
    }
}
