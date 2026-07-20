package com.questionpapermaker.app.ui.templates

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.questionpapermaker.app.data.model.template.PaperTemplate
import com.questionpapermaker.app.data.repository.PaperRepository
import kotlinx.coroutines.launch

class TemplatePickerViewModel(private val repository: PaperRepository) : ViewModel() {

    fun useTemplate(template: PaperTemplate, onCreated: (String) -> Unit) {
        viewModelScope.launch {
            val paper = repository.createPaperFromTemplate(template)
            onCreated(paper.id)
        }
    }
}
