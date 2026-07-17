package com.pdfimagetools.app.core.di

import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider

/** Small reusable [ViewModelProvider.Factory] for ViewModels built from [ServiceLocator] dependencies. */
class SimpleViewModelFactory<VM : ViewModel>(private val creator: () -> VM) : ViewModelProvider.Factory {
    @Suppress("UNCHECKED_CAST")
    override fun <T : ViewModel> create(modelClass: Class<T>): T = creator() as T
}
