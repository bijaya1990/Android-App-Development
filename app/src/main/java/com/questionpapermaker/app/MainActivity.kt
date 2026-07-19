package com.questionpapermaker.app

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.Surface
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import com.questionpapermaker.app.data.AppContainer
import com.questionpapermaker.app.navigation.QuestionPaperMakerNavHost
import com.questionpapermaker.app.ui.theme.QuestionPaperMakerTheme

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()

        val container = (application as QuestionPaperMakerApp).container

        setContent {
            AppRoot(container)
        }
    }
}

@Composable
private fun AppRoot(container: AppContainer) {
    QuestionPaperMakerTheme {
        Surface(modifier = Modifier.fillMaxSize()) {
            QuestionPaperMakerNavHost(container)
        }
    }
}
